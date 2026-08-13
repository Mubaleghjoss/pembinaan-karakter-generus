<?php

namespace App\Services;

use App\Models\QuranReadingScan;
use App\Models\QuranReadingSheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuranReadingScanService
{
    public function resolveSheet(string $payload): QuranReadingSheet
    {
        [$publicId, $token] = $this->parsePayload($payload);
        $sheet = QuranReadingSheet::query()->where('public_id', $publicId)->first();

        if (! $sheet || ! $sheet->verifyToken($token)) {
            throw ValidationException::withMessages([
                'sheet_payload' => 'QR lembar tidak valid atau lembar sudah dicabut.',
            ]);
        }

        return $sheet;
    }

    public function create(
        QuranReadingSheet $sheet,
        UploadedFile $image,
        ?UploadedFile $processedImage,
        string $actorType,
        ?int $actorId,
        ?string $ocrSuggestion,
    ): QuranReadingScan {
        $originalPath = $this->storeSanitizedImage($image, 'original');
        $processedPath = $processedImage
            ? $this->storeSanitizedImage($processedImage, 'processed')
            : null;

        $decodedOcr = json_decode((string) $ocrSuggestion, true);
        if (! is_array($decodedOcr)) {
            $decodedOcr = [];
        }

        return QuranReadingScan::create([
            'siswa_id' => $sheet->siswa_id,
            'sheet_id' => $sheet->id,
            'uploaded_by_type' => $actorType,
            'uploaded_by_id' => $actorId,
            'original_path' => $originalPath,
            'processed_path' => $processedPath,
            'status' => 'awaiting_confirmation',
            'metadata' => [
                'original_name' => mb_substr($image->getClientOriginalName(), 0, 200),
                'mime' => 'image/jpeg',
                'original_size' => $image->getSize(),
                'ocr_suggestion' => $decodedOcr,
                'scanner_version' => 2,
                'template_version' => (int) ($sheet->template_version ?: 1),
            ],
        ]);
    }

    public function payload(QuranReadingSheet $sheet, string $plainToken): string
    {
        if ((int) $sheet->template_version >= 2) {
            return 'PKGQ:'.strtoupper(str_replace('-', '', (string) $sheet->public_id)).':'.strtoupper($plainToken);
        }

        return 'PKGQURAN:'.$sheet->public_id.':'.$plainToken;
    }

    private function parsePayload(string $payload): array
    {
        if (preg_match('/^PKGQ:([0-9A-F]{32}):([0-9A-F]{32})$/i', trim($payload), $matches)) {
            $hex = strtolower($matches[1]);
            $publicId = sprintf('%s-%s-%s-%s-%s',
                substr($hex, 0, 8),
                substr($hex, 8, 4),
                substr($hex, 12, 4),
                substr($hex, 16, 4),
                substr($hex, 20, 12),
            );

            return [$publicId, strtolower($matches[2])];
        }

        if (preg_match('/^PKGQURAN:([0-9a-f-]{36}):([A-Za-z0-9]+)$/i', trim($payload), $matches)) {
            return [strtolower($matches[1]), $matches[2]];
        }

        throw ValidationException::withMessages([
            'sheet_payload' => 'QR bukan lembar Tracer Bacaan Al-Qur’an PKG.',
        ]);
    }

    private function storeSanitizedImage(UploadedFile $file, string $suffix): string
    {
        $contents = file_get_contents($file->getRealPath());
        $image = $contents !== false && function_exists('imagecreatefromstring')
            ? @imagecreatefromstring($contents)
            : false;

        if (! $image) {
            throw ValidationException::withMessages([
                'scan_image' => 'Foto tidak dapat dibaca. Gunakan JPG, PNG, atau WebP yang valid.',
            ]);
        }

        $temporary = tmpfile();
        if ($temporary === false) {
            imagedestroy($image);
            throw ValidationException::withMessages(['scan_image' => 'Foto tidak dapat diproses.']);
        }

        $meta = stream_get_meta_data($temporary);
        imagejpeg($image, $meta['uri'], 90);
        imagedestroy($image);

        $path = 'quran-reading-scans/'.now()->format('Y/m').'/'.Str::uuid().'-'.$suffix.'.jpg';
        Storage::disk('local')->put($path, file_get_contents($meta['uri']));
        fclose($temporary);

        return $path;
    }
}
