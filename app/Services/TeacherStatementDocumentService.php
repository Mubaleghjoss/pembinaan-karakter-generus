<?php

namespace App\Services;

use App\Models\TeacherProfile;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TeacherStatementDocumentService
{
    public function storeSignature(string $dataUrl): string
    {
        $path = 'teacher-statements/'.Str::uuid().'/tanda-tangan.png';
        $stored = Storage::disk('local')->put($path, $this->decodeSignature($dataUrl));

        if (! $stored) {
            throw ValidationException::withMessages([
                'signature' => 'Tanda tangan tidak dapat disimpan. Silakan coba kembali.',
            ]);
        }

        return $path;
    }

    public function deleteSignature(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    public function response(TeacherProfile $teacher, bool $download = true): Response
    {
        abort_unless(class_exists(Dompdf::class), 503, 'Generator PDF belum tersedia di server.');
        abort_unless(filled($teacher->signature_path), 404);
        abort_unless(Storage::disk('local')->exists($teacher->signature_path), 404);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('public.teacher-availability.pdf', [
            'teacher' => $teacher,
            'signature' => $this->signatureDataUri($teacher->signature_path),
            'participationRole' => $this->participationRoleLabel($teacher->participation_role),
            'rombelLabels' => collect($teacher->rombels ?? [])
                ->map(fn (string $rombel) => TeacherProfile::ROMBELS[$rombel] ?? strtoupper($rombel))
                ->values(),
            'nightLabels' => collect($teacher->available_nights ?? [])
                ->sortBy(fn (string $night) => ($teacher->night_priorities ?? [])[$night] ?? 99)
                ->map(fn (string $night) => TeacherProfile::NIGHTS[$night] ?? $night)
                ->values(),
            'competencyLabels' => collect($teacher->competencies ?? [])
                ->map(fn (string $competency) => $this->competencyLabel($competency))
                ->values(),
            'materialReadiness' => match ($teacher->material_readiness) {
                'ready' => 'Bersedia',
                'needs_support' => 'Perlu pendampingan',
                default => '-',
            },
            'backupPreference' => match ($teacher->backup_contact_preference) {
                'ready' => 'Bersedia',
                'one_day_notice' => 'Bersedia apabila dikabari minimal satu hari sebelumnya',
                'unavailable' => 'Belum memungkinkan',
                default => '-',
            },
        ])->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'surat-kesediaan-guru-'.str($teacher->name)->slug().'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
        ]);
    }

    private function decodeSignature(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'signature' => 'Tanda tangan tidak valid. Silakan hapus lalu tanda tangani kembali.',
            ]);
        }

        $binary = base64_decode($matches[1], true);
        $imageInfo = $binary !== false ? @getimagesizefromstring($binary) : false;

        if ($binary === false || strlen($binary) > 1000000 || ! $imageInfo || ($imageInfo['mime'] ?? null) !== 'image/png') {
            throw ValidationException::withMessages([
                'signature' => 'Tanda tangan tidak valid atau terlalu besar.',
            ]);
        }

        return $binary;
    }

    private function signatureDataUri(string $path): string
    {
        return 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($path));
    }

    private function participationRoleLabel(string $role): string
    {
        return match ($role) {
            TeacherProfile::ROLE_BOTH => 'Siap menjadi pengajar utama dan cadangan',
            TeacherProfile::ROLE_MAIN => 'Siap menjadi pengajar utama',
            TeacherProfile::ROLE_BACKUP => 'Siap menjadi pengajar cadangan',
            TeacherProfile::ROLE_AS_NEEDED => 'Siap membantu sesuai kebutuhan',
            default => 'Saat ini belum memungkinkan',
        };
    }

    private function competencyLabel(string $competency): string
    {
        return match ($competency) {
            'quran' => "Makna Al-Qur'an",
            'hadith' => 'Makna Al-Hadits',
            'memorization' => 'Hafalan',
            'practice' => 'Praktik',
            'class_support' => 'Pendampingan dan pengondisian kelas',
            'all_materials' => 'Bersedia mempelajari seluruh materi',
            default => $competency,
        };
    }
}
