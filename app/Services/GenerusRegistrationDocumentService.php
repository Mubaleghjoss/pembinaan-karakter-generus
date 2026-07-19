<?php

namespace App\Services;

use App\Models\GenerusRegistration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class GenerusRegistrationDocumentService
{
    public function response(GenerusRegistration $registration, bool $download = true): Response
    {
        abort_unless(class_exists(Dompdf::class), 503, 'Generator PDF belum tersedia di server.');

        $registration->loadMissing('siswa');
        $html = view('public.generus-registration.pdf', [
            'registration' => $registration,
            'parentSignature' => $this->signatureDataUri($registration->parent_signature_path),
            'studentSignature' => $this->signatureDataUri($registration->student_signature_path),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'surat-pernyataan-'.str($registration->student_name)->slug().'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
        ]);
    }

    private function signatureDataUri(string $path): string
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
