<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenerusRegistrationRequest;
use App\Models\GenerusRegistration;
use App\Models\GenerusRegistrationInvite;
use App\Models\Siswa;
use App\Models\ThemeSetting;
use App\Services\GenerusRegistrationService;
use App\Support\TargetGrade;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class GenerusRegistrationController extends Controller
{
    public function __construct(private readonly GenerusRegistrationService $registrationService) {}

    public function show(string $token)
    {
        $invite = $this->resolveInvite($token);
        $theme = ThemeSetting::current();
        $kelompokOptions = Siswa::kelompokOptions();
        $schoolGradeOptions = TargetGrade::schoolClassOptions();

        return view('public.generus-registration.form', compact(
            'theme',
            'invite',
            'token',
            'kelompokOptions',
            'schoolGradeOptions'
        ));
    }

    public function store(StoreGenerusRegistrationRequest $request, string $token)
    {
        $invite = $this->resolveInvite($token);
        [$registration, $downloadToken] = $this->registrationService->register(
            $invite,
            $request->validated(),
            $request
        );

        return redirect()->route('public.generus-registration.result', [
            'registration' => $registration,
            'downloadToken' => $downloadToken,
        ]);
    }

    public function result(GenerusRegistration $registration, string $downloadToken)
    {
        $this->authorizeDownload($registration, $downloadToken);
        $theme = ThemeSetting::current();

        return view('public.generus-registration.result', compact('theme', 'registration', 'downloadToken'));
    }

    public function pdf(GenerusRegistration $registration, string $downloadToken): Response
    {
        $this->authorizeDownload($registration, $downloadToken);
        abort_unless(class_exists(Dompdf::class), 503, 'Generator PDF belum tersedia di server.');

        $registration->loadMissing('siswa');
        $parentSignature = $this->signatureDataUri($registration->parent_signature_path);
        $studentSignature = $this->signatureDataUri($registration->student_signature_path);
        $html = view('public.generus-registration.pdf', compact(
            'registration',
            'parentSignature',
            'studentSignature'
        ))->render();

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
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function resolveInvite(string $token): GenerusRegistrationInvite
    {
        $invite = GenerusRegistrationInvite::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_unless($invite?->isAvailable(), 404);

        return $invite;
    }

    private function authorizeDownload(GenerusRegistration $registration, string $downloadToken): void
    {
        abort_unless(
            hash_equals($registration->download_token_hash, hash('sha256', $downloadToken)),
            404
        );
    }

    private function signatureDataUri(string $path): string
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
