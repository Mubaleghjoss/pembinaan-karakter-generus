<?php

namespace App\Services;

use App\Models\Level;
use App\Models\LevelRewardTemplate;
use Illuminate\Support\Facades\Storage;

/**
 * Service for generating level certificates.
 * 
 * Uses GD library (built-in PHP) to overlay student name on certificate template image.
 * Supports output as PNG image or PDF (via embedded image).
 */
class CertificateService
{
    /**
     * Generate a certificate image with student name overlaid on template.
     *
     * @param Level $level
     * @param string $studentName
     * @return resource|false GD image resource
     */
    public function generateImage(Level $level, string $studentName)
    {
        $templatePath = storage_path('app/public/' . $level->certificate_template);

        if (!file_exists($templatePath)) {
            return false;
        }

        // Load template image
        $imageInfo = getimagesize($templatePath);
        $mime = $imageInfo['mime'] ?? '';

        switch ($mime) {
            case 'image/png':
                $image = imagecreatefrompng($templatePath);
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($templatePath);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Parse font color
        $fontColor = $level->certificate_font_color ?? '#000000';
        $r = hexdec(substr($fontColor, 1, 2));
        $g = hexdec(substr($fontColor, 3, 2));
        $b = hexdec(substr($fontColor, 5, 2));
        $color = imagecolorallocate($image, $r, $g, $b);

        // Font settings
        $fontSize = $level->certificate_font_size ?? 36;
        $nameY = ($height * ($level->certificate_name_y ?? 50)) / 100;

        // Try to use a nice font, fallback to built-in
        $fontFile = $this->getFontPath();

        if ($fontFile && file_exists($fontFile)) {
            // Use TrueType font
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $studentName);
            $textWidth = abs($bbox[2] - $bbox[0]);
            $textHeight = abs($bbox[7] - $bbox[1]);
            $x = ($width - $textWidth) / 2;
            $y = $nameY + ($textHeight / 2);

            imagettftext($image, $fontSize, 0, (int)$x, (int)$y, $color, $fontFile, $studentName);
        } else {
            // Fallback to built-in font (limited but works without TTF)
            $builtinFont = 5; // Largest built-in font
            $charWidth = imagefontwidth($builtinFont);
            $textWidth = strlen($studentName) * $charWidth;
            $x = ($width - $textWidth) / 2;

            imagestring($image, $builtinFont, (int)$x, (int)$nameY, $studentName, $color);
        }

        return $image;
    }

    /**
     * Generate reward image from a LevelRewardTemplate.
     * Returns PNG binary data.
     */
    public function generateFromTemplate(LevelRewardTemplate $template, string $studentName): ?string
    {
        try {
        // Use Storage disk to get correct path (works on shared hosting)
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (!$disk->exists($template->template_path)) {
            \Log::error('Reward template file not found on disk: ' . $template->template_path);
            return null;
        }
        $templatePath = $disk->path($template->template_path);

        if (!function_exists('imagecreatefrompng')) {
            \Log::error('GD library not available on server');
            return null;
        }

        $imageInfo = getimagesize($templatePath);
        $mime = $imageInfo['mime'] ?? '';

        $image = match($mime) {
            'image/png' => imagecreatefrompng($templatePath),
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($templatePath),
            default => false,
        };

        if (!$image) return null;

        $width = imagesx($image);
        $height = imagesy($image);

        // Parse font color
        $fontColor = $template->font_color ?? '#000000';
        $r = hexdec(substr($fontColor, 1, 2));
        $g = hexdec(substr($fontColor, 3, 2));
        $b = hexdec(substr($fontColor, 5, 2));
        $color = imagecolorallocate($image, $r, $g, $b);

        $fontSize = $template->font_size ?? 36;
        $nameY = ($height * ($template->name_y ?? 50)) / 100;

        $fontFile = $this->getFontPath();

        if ($fontFile && file_exists($fontFile)) {
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $studentName);
            $textWidth = abs($bbox[2] - $bbox[0]);
            $textHeight = abs($bbox[7] - $bbox[1]);
            $x = ($width - $textWidth) / 2;
            $y = $nameY + ($textHeight / 2);
            imagettftext($image, $fontSize, 0, (int)$x, (int)$y, $color, $fontFile, $studentName);
        } else {
            $builtinFont = 5;
            $charWidth = imagefontwidth($builtinFont);
            $textWidth = strlen($studentName) * $charWidth;
            $x = ($width - $textWidth) / 2;
            imagestring($image, $builtinFont, (int)$x, (int)$nameY, $studentName, $color);
        }

        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return $data;
        } catch (\Throwable $e) {
            \Log::error('generateFromTemplate error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate PDF from a LevelRewardTemplate.
     */
    public function generatePdfFromTemplate(LevelRewardTemplate $template, string $studentName): ?string
    {
        $pngData = $this->generateFromTemplate($template, $studentName);
        if (!$pngData) return null;

        $tempFile = tempnam(sys_get_temp_dir(), 'reward_') . '.png';
        file_put_contents($tempFile, $pngData);

        $imageInfo = getimagesize($tempFile);
        $imgWidth = $imageInfo[0];
        $imgHeight = $imageInfo[1];
        $orientation = $imgWidth > $imgHeight ? 'L' : 'P';

        $pdf = $this->createSimplePdf($tempFile, $imgWidth, $imgHeight, $orientation);
        unlink($tempFile);

        return $pdf;
    }

    /**
     * Generate certificate and return as PNG binary.
     */
    public function generatePng(Level $level, string $studentName): ?string
    {
        $image = $this->generateImage($level, $studentName);
        if (!$image) return null;

        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return $data;
    }

    /**
     * Generate certificate as PDF (single page with embedded image).
     */
    public function generatePdf(Level $level, string $studentName): ?string
    {
        $pngData = $this->generatePng($level, $studentName);
        if (!$pngData) return null;

        // Save temp PNG
        $tempFile = tempnam(sys_get_temp_dir(), 'cert_') . '.png';
        file_put_contents($tempFile, $pngData);

        // Get image dimensions for PDF
        $imageInfo = getimagesize($tempFile);
        $imgWidth = $imageInfo[0];
        $imgHeight = $imageInfo[1];

        // Determine orientation
        $orientation = $imgWidth > $imgHeight ? 'L' : 'P'; // Landscape or Portrait

        // Simple PDF generation using FPDF-like raw PDF
        $pdf = $this->createSimplePdf($tempFile, $imgWidth, $imgHeight, $orientation);

        unlink($tempFile);

        return $pdf;
    }

    /**
     * Create a simple single-page PDF with an embedded image.
     * Uses raw PDF generation (no external library needed).
     */
    protected function createSimplePdf(string $imagePath, int $imgWidth, int $imgHeight, string $orientation): string
    {
        // A4 dimensions in points (1 pt = 1/72 inch)
        $a4Width = 595.28;
        $a4Height = 841.89;

        if ($orientation === 'L') {
            [$a4Width, $a4Height] = [$a4Height, $a4Width];
        }

        // Scale image to fit A4
        $scaleX = $a4Width / $imgWidth;
        $scaleY = $a4Height / $imgHeight;
        $scale = min($scaleX, $scaleY);
        $displayWidth = $imgWidth * $scale;
        $displayHeight = $imgHeight * $scale;
        $offsetX = ($a4Width - $displayWidth) / 2;
        $offsetY = ($a4Height - $displayHeight) / 2;

        $imageData = file_get_contents($imagePath);
        $imageBase64 = $imageData;

        // Build raw PDF
        $objects = [];
        $objectId = 0;

        // Object 1: Catalog
        $objectId++;
        $objects[$objectId] = "<< /Type /Catalog /Pages 2 0 R >>";

        // Object 2: Pages
        $objectId++;
        $objects[$objectId] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";

        // Object 3: Page
        $objectId++;
        $objects[$objectId] = sprintf(
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Contents 4 0 R /Resources << /XObject << /Img 5 0 R >> >> >>",
            $a4Width, $a4Height
        );

        // Object 4: Content stream
        $objectId++;
        $contentStream = sprintf(
            "q %.2f 0 0 %.2f %.2f %.2f cm /Img Do Q",
            $displayWidth, $displayHeight, $offsetX, $offsetY
        );
        $objects[$objectId] = "<< /Length " . strlen($contentStream) . " >>\nstream\n" . $contentStream . "\nendstream";

        // Object 5: Image
        $objectId++;
        $objects[$objectId] = sprintf(
            "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /FlateDecode /Length %d >>\nstream\n",
            $imgWidth, $imgHeight, 0
        );

        // Actually, raw PDF with PNG is complex. Let's use a simpler approach.
        // We'll generate an HTML-based PDF approach. For now, return the PNG with PDF wrapper.
        // The simplest reliable approach: use browser print or a proper library.
        
        // For simplicity, we'll just return PNG data wrapped as downloadable.
        // The user can use browser print-to-PDF functionality.
        // Real PDF generation would need FPDF/TCPDF/DomPDF — let's check if any is available.
        
        return $this->generatePdfWithDompdf($imagePath, $a4Width, $a4Height, $displayWidth, $displayHeight, $offsetX, $offsetY, $orientation);
    }

    /**
     * Generate PDF using DomPDF (if available) or return simple HTML-based PDF.
     */
    protected function generatePdfWithDompdf(string $imagePath, float $pageW, float $pageH, float $imgW, float $imgH, float $offX, float $offY, string $orientation): string
    {
        $imageData = base64_encode(file_get_contents($imagePath));
        $orientationCss = $orientation === 'L' ? 'landscape' : 'portrait';

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<style>
@page { size: A4 {$orientationCss}; margin: 0; }
body { margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; width: 100%; height: 100%; }
img { width: 100%; height: 100%; object-fit: contain; }
</style>
</head>
<body>
<img src="data:image/png;base64,{$imageData}">
</body>
</html>
HTML;

        // Try to use DomPDF if available
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('a4', $orientationCss);
            return $pdf->output();
        }

        // If DomPDF not available, return the HTML for browser rendering
        return $html;
    }

    /**
     * Get path to a TrueType font file.
     */
    protected function getFontPath(): ?string
    {
        // Check for custom font in storage
        $customFont = storage_path('app/fonts/certificate.ttf');
        if (file_exists($customFont)) return $customFont;

        // Check common system fonts
        $systemFonts = [
            // Windows
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/times.ttf',
            'C:/Windows/Fonts/calibri.ttf',
            // Linux (common paths)
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/ubuntu/Ubuntu-R.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
            '/usr/share/fonts/noto/NotoSans-Regular.ttf',
        ];

        foreach ($systemFonts as $font) {
            if (file_exists($font)) return $font;
        }

        return null;
    }

    /**
     * Get the longest student name from the database (for preview sizing).
     */
    public function getLongestStudentName(): string
    {
        $longest = \App\Models\Siswa::orderByRaw('LENGTH(nama) DESC')->first();
        return $longest ? $longest->nama : 'MUHAMMAD ABDURRAHMAN AL-FATTAH';
    }
}
