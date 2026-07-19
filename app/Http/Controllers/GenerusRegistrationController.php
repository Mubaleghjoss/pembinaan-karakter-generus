<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenerusRegistrationRequest;
use App\Models\GenerusRegistration;
use App\Models\GenerusRegistrationInvite;
use App\Models\Siswa;
use App\Models\ThemeSetting;
use App\Services\GenerusRegistrationDocumentService;
use App\Services\GenerusRegistrationService;
use App\Support\TargetGrade;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class GenerusRegistrationController extends Controller
{
    private const ACCESS_INVITE_SESSION = 'generus_registration.invite_id';
    private const ACCESS_TIME_SESSION = 'generus_registration.unlocked_at';
    private const VERIFIED_SISWA_SESSION = 'generus_registration.verified_siswa_id';
    private const VERIFIED_TIME_SESSION = 'generus_registration.verified_at';
    private const ACCOUNT_CREATED_FLASH = 'generus_registration.account_created';
    private const ACCESS_TTL_SECONDS = 3600;

    public function __construct(
        private readonly GenerusRegistrationService $registrationService,
        private readonly GenerusRegistrationDocumentService $documentService
    ) {}

    public function index(Request $request)
    {
        $invite = $this->sessionInvite($request);
        $theme = ThemeSetting::current();

        if (! $invite) {
            return view('public.generus-registration.access', compact('theme'));
        }

        return $this->formResponse($request, $invite, route('public.generus-registration.short.store'));
    }

    public function unlock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'access_code' => ['required', 'string', 'min:6', 'max:32'],
        ]);
        $code = $this->normalizeAccessCode($validated['access_code']);
        $invite = GenerusRegistrationInvite::query()
            ->where('token_hash', hash('sha256', $code))
            ->first();

        if (! $invite?->isAvailable()) {
            throw ValidationException::withMessages([
                'access_code' => 'Kode akses tidak valid, kedaluwarsa, atau kuotanya sudah habis.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put([
            self::ACCESS_INVITE_SESSION => $invite->id,
            self::ACCESS_TIME_SESSION => now()->timestamp,
        ]);
        $this->clearVerifiedStudent($request);

        return redirect()->route('public.generus-registration.short.index');
    }

    public function searchStudents(Request $request): JsonResponse
    {
        $this->requireSessionInvite($request);
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\pN\s.\x27-]+$/u'],
        ], [
            'q.regex' => 'Pencarian nama mengandung karakter yang tidak valid.',
        ]);
        $search = trim($validated['q']);

        $students = Siswa::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->where('nama', 'like', "%{$search}%")
            ->orderBy('nama')
            ->limit(10)
            ->get(['id', 'nis', 'nama', 'kelompok']);

        return response()->json(['data' => $students->map(fn (Siswa $siswa) => [
            'selection_token' => Crypt::encryptString((string) $siswa->id),
            'nama' => $siswa->nama,
            'kelompok' => Siswa::kelompokOptions()[$siswa->kelompok] ?? ($siswa->kelompok ?: 'Kelompok belum diisi'),
            'nis_masked' => '***'.substr((string) $siswa->nis, -4),
        ])->values()]);
    }

    public function verifyExisting(Request $request): JsonResponse
    {
        $this->requireSessionInvite($request);
        $validated = $request->validate([
            'selection_token' => ['required', 'string', 'max:2000'],
            'login_type' => ['required', 'in:siswa,ortu'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $siswa = $this->studentFromSelectionToken($validated['selection_token']);
        $valid = $validated['login_type'] === 'siswa'
            ? hash_equals((string) $siswa->nis, (string) $validated['username'])
                && filled($siswa->password)
                && Hash::check($validated['password'], $siswa->password)
            : hash_equals((string) $siswa->ortu_username, (string) $validated['username'])
                && filled($siswa->ortu_password)
                && Hash::check($validated['password'], $siswa->ortu_password);

        if (! $valid) {
            throw ValidationException::withMessages([
                'credentials' => 'Username atau password akun tidak sesuai.',
            ]);
        }

        $request->session()->put([
            self::VERIFIED_SISWA_SESSION => $siswa->id,
            self::VERIFIED_TIME_SESSION => now()->timestamp,
        ]);

        return response()->json([
            'message' => 'Akun berhasil diverifikasi.',
            'student' => $this->studentFormData($siswa),
        ]);
    }

    public function storeShort(StoreGenerusRegistrationRequest $request): RedirectResponse
    {
        $invite = $this->requireSessionInvite($request);
        $existingSiswa = $this->resolveVerifiedExistingStudent($request);
        [$registration, $downloadToken, $accountCreated] = $this->registrationService->register(
            $invite,
            $request->validated(),
            $request,
            $existingSiswa
        );
        $this->clearVerifiedStudent($request);
        $request->session()->flash(self::ACCOUNT_CREATED_FLASH, $accountCreated);

        return redirect()->route('public.generus-registration.short.result', [
            'registration' => $registration,
            'downloadToken' => $downloadToken,
        ]);
    }

    public function show(Request $request, string $token): RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        $request->session()->regenerate();
        $request->session()->put([
            self::ACCESS_INVITE_SESSION => $invite->id,
            self::ACCESS_TIME_SESSION => now()->timestamp,
        ]);

        return redirect()->route('public.generus-registration.short.index');
    }

    public function store(StoreGenerusRegistrationRequest $request, string $token): RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        [$registration, $downloadToken] = $this->registrationService->register(
            $invite,
            $request->validated(),
            $request
        );
        $request->session()->flash(self::ACCOUNT_CREATED_FLASH, true);

        return redirect()->route('public.generus-registration.result', [
            'registration' => $registration,
            'downloadToken' => $downloadToken,
        ]);
    }

    public function result(Request $request, GenerusRegistration $registration, string $downloadToken)
    {
        $this->authorizeDownload($registration, $downloadToken);
        $theme = ThemeSetting::current();
        $isNewAccount = (bool) $request->session()->pull(self::ACCOUNT_CREATED_FLASH, false);

        return view('public.generus-registration.result', compact(
            'theme', 'registration', 'downloadToken', 'isNewAccount'
        ));
    }

    public function pdf(GenerusRegistration $registration, string $downloadToken): Response
    {
        $this->authorizeDownload($registration, $downloadToken);

        return $this->documentService->response($registration);
    }

    public function siswaPreview(): Response
    {
        return $this->documentService->response($this->authenticatedRegistration('siswa'), false);
    }

    public function siswaDownload(): Response
    {
        return $this->documentService->response($this->authenticatedRegistration('siswa'));
    }

    public function ortuPreview(): Response
    {
        return $this->documentService->response($this->authenticatedRegistration('ortu'), false);
    }

    public function ortuDownload(): Response
    {
        return $this->documentService->response($this->authenticatedRegistration('ortu'));
    }

    private function formResponse(Request $request, GenerusRegistrationInvite $invite, string $formAction)
    {
        $theme = ThemeSetting::current();
        $kelompokOptions = Siswa::kelompokOptions();
        $schoolGradeOptions = TargetGrade::schoolClassOptions();
        $verifiedStudent = $this->verifiedStudent($request);
        $initialStudent = $verifiedStudent ? $this->studentFormData($verifiedStudent) : null;
        $initialSelectionToken = $verifiedStudent ? Crypt::encryptString((string) $verifiedStudent->id) : null;

        return view('public.generus-registration.form', compact(
            'theme',
            'invite',
            'kelompokOptions',
            'schoolGradeOptions',
            'formAction',
            'initialStudent',
            'initialSelectionToken'
        ));
    }

    private function studentFormData(Siswa $siswa): array
    {
        return [
            'parent_name' => $siswa->nama_wali ?: '',
            'parent_phone' => $siswa->phone_wali ?: '',
            'student_name' => $siswa->nama,
            'student_phone' => $siswa->phone ?: '',
            'kelompok' => $siswa->kelompok ?: '',
            'birth_place' => $siswa->tempat_lahir ?: '',
            'birth_date' => $siswa->tanggal_lahir?->format('Y-m-d') ?: '',
            'school_grade' => $siswa->target_grade_override ?: '',
        ];
    }

    private function resolveVerifiedExistingStudent(StoreGenerusRegistrationRequest $request): ?Siswa
    {
        if ($request->validated('registration_mode') !== 'existing') {
            return null;
        }

        $selected = $this->studentFromSelectionToken((string) $request->validated('selected_student_token'));
        $verified = $this->verifiedStudent($request);

        if (! $verified || $verified->id !== $selected->id) {
            throw ValidationException::withMessages([
                'credentials' => 'Verifikasi akun sudah berakhir. Silakan verifikasi kembali.',
            ]);
        }

        return $verified;
    }

    private function studentFromSelectionToken(string $token): Siswa
    {
        try {
            $id = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            throw ValidationException::withMessages([
                'selection_token' => 'Pilihan Generus tidak valid. Silakan cari kembali.',
            ]);
        }

        return Siswa::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->findOrFail($id);
    }

    private function verifiedStudent(Request $request): ?Siswa
    {
        $verifiedAt = (int) $request->session()->get(self::VERIFIED_TIME_SESSION, 0);

        if (! $verifiedAt || now()->timestamp - $verifiedAt > self::ACCESS_TTL_SECONDS) {
            $this->clearVerifiedStudent($request);
            return null;
        }

        return Siswa::query()->find($request->session()->get(self::VERIFIED_SISWA_SESSION));
    }

    private function clearVerifiedStudent(Request $request): void
    {
        $request->session()->forget([self::VERIFIED_SISWA_SESSION, self::VERIFIED_TIME_SESSION]);
    }

    private function sessionInvite(Request $request): ?GenerusRegistrationInvite
    {
        $unlockedAt = (int) $request->session()->get(self::ACCESS_TIME_SESSION, 0);

        if (! $unlockedAt || now()->timestamp - $unlockedAt > self::ACCESS_TTL_SECONDS) {
            $request->session()->forget([self::ACCESS_INVITE_SESSION, self::ACCESS_TIME_SESSION]);
            $this->clearVerifiedStudent($request);
            return null;
        }

        $invite = GenerusRegistrationInvite::query()->find(
            $request->session()->get(self::ACCESS_INVITE_SESSION)
        );

        return $invite?->isAvailable() ? $invite : null;
    }

    private function requireSessionInvite(Request $request): GenerusRegistrationInvite
    {
        $invite = $this->sessionInvite($request);

        if (! $invite) {
            if ($request->expectsJson()) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Sesi pendaftaran sudah berakhir. Masukkan kembali kode akses.',
                ], 403));
            }
            throw new HttpResponseException(
                redirect()
                    ->route('public.generus-registration.short.index')
                    ->withErrors(['access_code' => 'Sesi pendaftaran sudah berakhir. Masukkan kembali kode akses.'])
            );
        }

        return $invite;
    }

    private function normalizeAccessCode(string $code): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?: '');
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
        abort_unless(hash_equals(
            $registration->download_token_hash,
            hash('sha256', $downloadToken)
        ), 404);
    }

    private function authenticatedRegistration(string $guard): GenerusRegistration
    {
        $siswa = Auth::guard($guard)->user();
        abort_unless($siswa, 401);

        return GenerusRegistration::query()->where('siswa_id', $siswa->id)->firstOrFail();
    }
}
