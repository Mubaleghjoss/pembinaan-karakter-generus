<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\LevelRewardTemplate;
use App\Models\Siswa;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Controller for managing reward templates (certificate, pin, nomination, etc.)
 * and allowing students to download their earned rewards.
 */
class CertificateController extends Controller
{
    protected CertificateService $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Admin: Show reward template settings for a specific level.
     */
    public function settings(Level $level)
    {
        $level->load('rewardTemplates');

        // Get all reward types and pair with existing templates
        $rewardTypes = collect(LevelRewardTemplate::REWARD_TYPES)->map(function ($config, $type) use ($level) {
            $template = $level->rewardTemplates->where('reward_type', $type)->first();
            return [
                'type' => $type,
                'label' => $config['label'],
                'icon' => $config['icon'],
                'desc' => $config['desc'],
                'template' => $template,
                'has_template' => $template && $template->hasTemplate(),
            ];
        });

        // For name preview: get longest student name
        $longestName = Siswa::orderByRaw('LENGTH(nama) DESC')->first()?->nama ?? 'NAMA SISWA TERPANJANG';
        $levels = Level::active()->orderBy('level')->get();

        return view('admin.certificate-settings', compact('level', 'rewardTypes', 'longestName', 'levels'));
    }

    /**
     * Admin: Upload/update a reward template.
     */
    public function uploadTemplate(Request $request, Level $level, string $rewardType)
    {
        // Validate reward type
        if (!array_key_exists($rewardType, LevelRewardTemplate::REWARD_TYPES)) {
            return back()->with('error', 'Tipe reward tidak valid.');
        }

        $request->validate([
            'template' => 'required|image|mimes:png,jpg,jpeg|max:5120',
            'name_y' => 'required|integer|min:0|max:100',
            'font_size' => 'required|integer|min:12|max:120',
            'font_color' => 'required|string|max:20',
        ]);

        // Store the template image
        $path = $request->file('template')->store("reward-templates/{$level->id}", 'public');

        // Create or update the reward template
        $template = LevelRewardTemplate::updateOrCreate(
            ['level_id' => $level->id, 'reward_type' => $rewardType],
            [
                'template_path' => $path,
                'name_y' => $request->name_y,
                'font_size' => $request->font_size,
                'font_color' => $request->font_color,
                'is_active' => true,
            ]
        );

        $label = LevelRewardTemplate::REWARD_TYPES[$rewardType]['label'];
        return back()->with('success', "Template {$label} untuk Level {$level->level} berhasil disimpan!");
    }

    /**
     * Admin: Update reward template settings (without re-uploading image).
     */
    public function updateTemplateSettings(Request $request, Level $level, string $rewardType)
    {
        $request->validate([
            'name_y' => 'required|integer|min:0|max:100',
            'font_size' => 'required|integer|min:12|max:120',
            'font_color' => 'required|string|max:20',
        ]);

        $template = LevelRewardTemplate::where('level_id', $level->id)
            ->where('reward_type', $rewardType)
            ->first();

        if (!$template) {
            return back()->with('error', 'Template belum diupload.');
        }

        $template->update([
            'name_y' => $request->name_y,
            'font_size' => $request->font_size,
            'font_color' => $request->font_color,
        ]);

        return back()->with('success', 'Pengaturan template berhasil diupdate!');
    }

    /**
     * Admin: Preview a reward template with sample name.
     */
    public function preview(Request $request, Level $level, string $rewardType)
    {
        $template = LevelRewardTemplate::where('level_id', $level->id)
            ->where('reward_type', $rewardType)
            ->first();

        if (!$template) {
            return response('Template belum diupload untuk tipe ini.', 404);
        }

        if (!$template->hasTemplate()) {
            return response('File template tidak ditemukan di storage.', 404);
        }

        $sampleName = $request->input('name', 'NAMA SISWA CONTOH');

        try {
            $pngData = $this->certificateService->generateFromTemplate($template, strtoupper($sampleName));
        } catch (\Throwable $e) {
            \Log::error('Certificate preview error: ' . $e->getMessage());
            return response('Error: ' . $e->getMessage(), 500);
        }

        if (!$pngData) {
            return response('Gagal generate preview. Cek log untuk detail.', 500);
        }

        return response($pngData, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Siswa/Ortu: Download their reward for a specific level and type.
     */
    public function download(Request $request, Level $level)
    {
        // Get the siswa (could be auth siswa or auth ortu)
        $siswa = null;
        if (Auth::guard('siswa')->check()) {
            $siswa = Auth::guard('siswa')->user();
        } elseif (Auth::guard('ortu')->check()) {
            $siswa = Auth::guard('ortu')->user();
        }

        if (!$siswa) {
            abort(403, 'Unauthorized');
        }

        // Check if student has reached this level
        $siswaPoint = $siswa->siswaPoint;
        if (!$siswaPoint || $siswaPoint->level < $level->level) {
            abort(403, 'Anda belum mencapai level ini.');
        }

        $rewardType = $request->input('type', 'sertifikat');
        $format = $request->input('format', 'png');

        // Find the template
        $template = LevelRewardTemplate::where('level_id', $level->id)
            ->where('reward_type', $rewardType)
            ->where('is_active', true)
            ->first();

        // Fallback to legacy certificate fields for 'sertifikat' type
        if (!$template && $rewardType === 'sertifikat' && $level->hasCertificate()) {
            return $this->downloadLegacyCertificate($request, $level, $siswa);
        }

        if (!$template || !$template->hasTemplate()) {
            abort(404, 'Template reward belum tersedia untuk level ini.');
        }

        $studentName = strtoupper($siswa->nama);
        $label = LevelRewardTemplate::REWARD_TYPES[$rewardType]['label'] ?? 'Reward';
        $filename = "{$label}_{$level->nama}_{$siswa->nama}";
        $isView = $request->boolean('view'); // view=1 means show inline

        if ($format === 'pdf') {
            $pdfData = $this->certificateService->generatePdfFromTemplate($template, $studentName);
            if (!$pdfData) abort(500, 'Gagal generate PDF.');

            if (str_starts_with($pdfData, '<!DOCTYPE') || str_starts_with($pdfData, '<html')) {
                return response($pdfData, 200, ['Content-Type' => 'text/html']);
            }

            $disposition = $isView ? 'inline' : 'attachment';
            return response($pdfData, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$filename}.pdf\"",
            ]);
        }

        // Default: PNG
        $pngData = $this->certificateService->generateFromTemplate($template, $studentName);
        if (!$pngData) abort(500, 'Gagal generate reward.');

        $disposition = $isView ? 'inline' : 'attachment';
        return response($pngData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}.png\"",
        ]);
    }

    /**
     * Legacy: Download certificate using old level fields.
     */
    private function downloadLegacyCertificate(Request $request, Level $level, $siswa)
    {
        $format = $request->input('format', 'png');
        $studentName = strtoupper($siswa->nama);
        $filename = "Sertifikat_{$level->nama}_{$siswa->nama}";

        if ($format === 'pdf') {
            $pdfData = $this->certificateService->generatePdf($level, $studentName);
            if (!$pdfData) abort(500, 'Gagal generate PDF.');
            return response($pdfData, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]);
        }

        $pngData = $this->certificateService->generatePng($level, $studentName);
        if (!$pngData) abort(500, 'Gagal generate sertifikat.');
        return response($pngData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "attachment; filename=\"{$filename}.png\"",
        ]);
    }
}
