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
    private const DIRECT_SISWA_SESSION = 'generus_registration.direct_siswa_id';
    private const DIRECT_TIME_SESSION = 'generus_registration.direct_at';
    private const ACCESS_TTL_SECONDS = 3600;

    // Template pesan WhatsApp untuk tautan daftar ulang. Placeholder: {nama}, {link}.
    private const WA_TEMPLATE_KEY = 'daftar_ulang_wa_template';
    private const WA_TEMPLATE_DEFAULT = "Assalamu'alaikum. Mohon daftar ulang / lengkapi biodata dan tanda tangan surat pernyataan untuk ananda {nama} melalui tautan berikut:\n{link}\nTerima kasih.";

    // Template pesan WhatsApp INFORMASI AKUN (dikirim setelah surat pernyataan disubmit).
    // Placeholder: {nama}, {nama_ortu}, {nis}, {password}, {username_ortu}, {link_siswa}, {link_ortu}.
    private const ACCOUNT_WA_STUDENT_KEY = 'akun_wa_template_siswa';
    private const ACCOUNT_WA_PARENT_KEY = 'akun_wa_template_ortu';

    private const ACCOUNT_WA_STUDENT_DEFAULT = "Assalamu'alaikum warahmatullahi wabarakatuh 🙏\nKepada {nama} & Orang Tua/Wali,\n\nBerikut informasi akun PKG (mohon disimpan):\n\n1) AKUN GENERUS (anak)\n• Login (NIS): {nis}\n• Password: {password}\n• Halaman masuk: {link_siswa}\n\n2) AKUN ORANG TUA\n• Username: {username_ortu}\n• Password: {password}\n• Halaman masuk: {link_ortu}\n\nCara masuk (sama untuk keduanya):\n1. Buka halaman masuk sesuai akun di atas\n2. Masukkan login/username dan password\n3. Setelah berhasil masuk, mohon ganti password agar lebih aman\n\nDengan akun Orang Tua, Bapak/Ibu dapat melihat Tugas PKG ananda sehingga bisa turut mengingatkan dan membantu melancarkan program PKG.\n\nTerima kasih. Wassalamu'alaikum warahmatullahi wabarakatuh 🤲";

    private const ACCOUNT_WA_PARENT_DEFAULT = "Assalamu'alaikum warahmatullahi wabarakatuh 🙏\nKepada {nama_ortu}, Orang Tua/Wali dari ananda {nama}.\n\nBerikut informasi akun PKG (mohon disimpan):\n\n1) AKUN ORANG TUA\n• Username: {username_ortu}\n• Password: {password}\n• Halaman masuk: {link_ortu}\n\n2) AKUN GENERUS (ananda)\n• Login (NIS): {nis}\n• Password: {password}\n• Halaman masuk: {link_siswa}\n\nCara masuk (sama untuk keduanya):\n1. Buka halaman masuk sesuai akun di atas\n2. Masukkan username/login dan password\n3. Setelah berhasil masuk, mohon ganti password agar lebih aman\n\nDengan akun Orang Tua ini, Bapak/Ibu dapat memantau Tugas PKG ananda sehingga dapat turut mengingatkan dan membantu melancarkan program PKG. Dukungan Bapak/Ibu sangat berarti.\n\nTerima kasih. Wassalamu'alaikum warahmatullahi wabarakatuh 🤲";

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
        $directSiswa = $this->directLinkStudent($request);
        $invite = $directSiswa ? null : $this->requireSessionInvite($request);
        $existingSiswa = $directSiswa ?: $this->resolveVerifiedExistingStudent($request);
        [$registration, $downloadToken, $accountCreated] = $this->registrationService->register(
            $invite,
            $request->validated(),
            $request,
            $existingSiswa
        );
        $this->clearVerifiedStudent($request);
        $this->clearDirectLink($request);
        $request->session()->flash(self::ACCOUNT_CREATED_FLASH, $accountCreated);

        return redirect()->route('public.generus-registration.short.result', [
            'registration' => $registration,
            'downloadToken' => $downloadToken,
        ]);
    }

    /**
     * Tautan langsung khusus per-Generus yang sudah punya akun.
     * Dibuka dari daftar admin/link WhatsApp; membuka form dalam mode "existing"
     * dengan biodata ter-preload, tanpa perlu kode akses undangan.
     */
    public function directExisting(Request $request, string $token): RedirectResponse
    {
        $siswa = $this->studentFromDirectToken($token);

        $request->session()->put([
            self::DIRECT_SISWA_SESSION => $siswa->id,
            self::DIRECT_TIME_SESSION => now()->timestamp,
        ]);
        $this->clearVerifiedStudent($request);

        return redirect()->route('public.generus-registration.direct.form');
    }

    public function directForm(Request $request)
    {
        $siswa = $this->directLinkStudent($request);

        if (! $siswa) {
            return redirect()
                ->route('public.generus-registration.short.index')
                ->withErrors(['access_code' => 'Tautan pendaftaran tidak valid atau sudah berakhir.']);
        }

        $theme = ThemeSetting::current();
        $kelompokOptions = Siswa::kelompokOptions();
        $schoolGradeOptions = TargetGrade::schoolClassOptions();
        $initialStudent = $this->studentFormData($siswa);

        return view('public.generus-registration.direct-form', compact(
            'theme',
            'kelompokOptions',
            'schoolGradeOptions',
            'siswa',
            'initialStudent'
        ));
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
        $accountInfo = $this->buildAccountInfo($registration);

        return view('public.generus-registration.result', compact(
            'theme', 'registration', 'downloadToken', 'isNewAccount', 'accountInfo'
        ));
    }

    /**
     * Rangkuman info akun (login + password NIS) + pesan WA untuk Generus & Orang Tua.
     * Isi pesan memakai template yang bisa diubah admin di halaman Daftar Ulang.
     */
    private function buildAccountInfo(GenerusRegistration $registration): array
    {
        $siswa = $registration->siswa;
        if (! $siswa) {
            return [];
        }

        $nis = (string) $siswa->nis;
        $ortuUsername = $siswa->ortu_username ?: $nis;
        $siswaLoginUrl = route('siswa.login');
        $ortuLoginUrl = route('ortu.login');

        $replacements = [
            '{nama}' => $siswa->nama,
            '{nama_ortu}' => $siswa->nama_wali ? 'Bapak/Ibu ' . $siswa->nama_wali : 'Bapak/Ibu',
            '{nis}' => $nis,
            '{password}' => $nis,
            '{username_ortu}' => $ortuUsername,
            '{link_siswa}' => $siswaLoginUrl,
            '{link_ortu}' => $ortuLoginUrl,
        ];

        $studentTemplate = \App\Models\Setting::get(self::ACCOUNT_WA_STUDENT_KEY, self::ACCOUNT_WA_STUDENT_DEFAULT);
        $parentTemplate = \App\Models\Setting::get(self::ACCOUNT_WA_PARENT_KEY, self::ACCOUNT_WA_PARENT_DEFAULT);

        $studentMsg = str_replace(array_keys($replacements), array_values($replacements), $studentTemplate);
        $parentMsg = str_replace(array_keys($replacements), array_values($replacements), $parentTemplate);

        return [
            'nis' => $nis,
            'student' => [
                'nama' => $siswa->nama,
                'login' => $nis,
                'password' => $nis,
                'login_url' => $siswaLoginUrl,
                'wa' => $this->waLink($siswa->phone),
                'wa_text' => rawurlencode($studentMsg),
            ],
            'parent' => [
                'nama' => $siswa->nama_wali,
                'username' => $ortuUsername,
                'password' => $nis,
                'login_url' => $ortuLoginUrl,
                'wa' => $this->waLink($siswa->phone_wali),
                'wa_text' => rawurlencode($parentMsg),
            ],
        ];
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

    // ================= ADMIN: daftar & tautan daftar-ulang =================

    /**
     * Tabel admin: seluruh Generus aktif + status surat pernyataan,
     * nomor WA (klik langsung), dan tautan daftar-ulang per anak.
     */
    public function adminIndex(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        // Filter status akun: aktif (bukan alumni) / alumni / semua.
        $statusFilter = in_array($request->query('status'), ['aktif', 'alumni', 'semua'], true)
            ? $request->query('status')
            : 'aktif';
        // Filter surat pernyataan: belum / sudah / semua.
        $suratFilter = in_array($request->query('surat'), ['belum', 'sudah', 'semua'], true)
            ? $request->query('surat')
            : 'semua';
        $kelompokFilter = trim((string) $request->query('kelompok', ''));

        $baseQuery = fn () => Siswa::query()
            ->where('is_active', true)
            ->whereIn('status', ['active', 'graduated']);

        // Hitungan global (tanpa filter) untuk kartu statistik.
        $activeStudents = (clone $baseQuery())->where('status', 'active')->get(['id']);
        $activeIds = $activeStudents->pluck('id');
        $activeSignedCount = GenerusRegistration::query()
            ->whereIn('siswa_id', $activeIds)
            ->whereNotNull('statement_accepted_at')
            ->count();
        $stats = [
            'active_total' => $activeIds->count(),
            'active_signed' => $activeSignedCount,
            'active_unsigned' => max(0, $activeIds->count() - $activeSignedCount),
            'alumni_total' => (clone $baseQuery())->where('status', 'graduated')->count(),
        ];

        $students = $baseQuery()
            ->when($statusFilter === 'aktif', fn ($q) => $q->where('status', 'active'))
            ->when($statusFilter === 'alumni', fn ($q) => $q->where('status', 'graduated'))
            ->when($kelompokFilter !== '' && Siswa::hasKelompokColumn(), fn ($q) => $q->where('kelompok', $kelompokFilter))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('nama', 'like', "%{$search}%")
                    ->orWhere('nama_wali', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            }))
            ->orderBy('nama')
            ->get();

        $registrations = GenerusRegistration::query()
            ->whereIn('siswa_id', $students->pluck('id'))
            ->get()
            ->keyBy('siswa_id');

        $rows = $students->map(function (Siswa $siswa) use ($registrations) {
            $registration = $registrations->get($siswa->id);

            return [
                'siswa' => $siswa,
                'registration' => $registration,
                'signed' => (bool) $registration?->statement_accepted_at,
                'direct_url' => route('public.generus-registration.direct', [
                    'token' => self::directTokenFor($siswa),
                ]),
                'student_wa' => $this->waLink($siswa->phone),
                'parent_wa' => $this->waLink($siswa->phone_wali),
                'preview_url' => $registration
                    ? route('admin.generus-registration.preview', ['siswa' => $siswa->id])
                    : null,
                'download_url' => $registration
                    ? route('admin.generus-registration.download', ['siswa' => $siswa->id])
                    : null,
                'mark_shared_url' => route('admin.generus-registration.mark-shared', ['siswa' => $siswa->id]),
                'shared_at' => ($sharedAt = data_get($siswa->metadata, 'daftar_ulang_shared_at'))
                    ? \Illuminate\Support\Carbon::parse($sharedAt)
                    : null,
                'shared_channel' => data_get($siswa->metadata, 'daftar_ulang_shared_channel'),
                'shared_by' => data_get($siswa->metadata, 'daftar_ulang_shared_by'),
            ];
        });

        // Filter surat pernyataan diterapkan setelah status TTD diketahui.
        if ($suratFilter === 'belum') {
            $rows = $rows->where('signed', false)->values();
        } elseif ($suratFilter === 'sudah') {
            $rows = $rows->where('signed', true)->values();
        }

        $theme = ThemeSetting::current();
        $signedCount = $rows->where('signed', true)->count();
        $waTemplate = \App\Models\Setting::get(self::WA_TEMPLATE_KEY, self::WA_TEMPLATE_DEFAULT);
        $accountWaStudentTemplate = \App\Models\Setting::get(self::ACCOUNT_WA_STUDENT_KEY, self::ACCOUNT_WA_STUDENT_DEFAULT);
        $accountWaParentTemplate = \App\Models\Setting::get(self::ACCOUNT_WA_PARENT_KEY, self::ACCOUNT_WA_PARENT_DEFAULT);

        return view('admin.generus-registration.index', [
            'rows' => $rows,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'suratFilter' => $suratFilter,
            'kelompokFilter' => $kelompokFilter,
            'kelompokOptions' => Siswa::kelompokOptions(),
            'stats' => $stats,
            'theme' => $theme,
            'totalCount' => $rows->count(),
            'signedCount' => $signedCount,
            'waTemplate' => $waTemplate,
            'accountWaStudentTemplate' => $accountWaStudentTemplate,
            'accountWaParentTemplate' => $accountWaParentTemplate,
        ]);
    }

    /**
     * Simpan template pesan WhatsApp untuk tautan daftar ulang.
     * Placeholder yang didukung: {nama} = nama Generus, {link} = tautan daftar ulang.
     */
    public function saveWaTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'wa_template' => ['required', 'string', 'max:2000'],
        ], [
            'wa_template.required' => 'Teks pesan tidak boleh kosong.',
            'wa_template.max' => 'Teks pesan terlalu panjang (maks 2000 karakter).',
        ]);

        \App\Models\Setting::set(self::WA_TEMPLATE_KEY, $validated['wa_template'], 'daftar_ulang');

        return redirect()
            ->route('admin.generus-registration.index')
            ->with('success', 'Template pesan WhatsApp berhasil disimpan.');
    }

    /**
     * Simpan template pesan WhatsApp INFORMASI AKUN (untuk anak & orang tua).
     * Placeholder: {nama}, {nama_ortu}, {nis}, {password}, {username_ortu}, {link_siswa}, {link_ortu}.
     */
    public function saveAccountWaTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_wa_student' => ['required', 'string', 'max:4000'],
            'account_wa_parent' => ['required', 'string', 'max:4000'],
        ], [
            'account_wa_student.required' => 'Teks pesan akun untuk anak tidak boleh kosong.',
            'account_wa_parent.required' => 'Teks pesan akun untuk orang tua tidak boleh kosong.',
            'account_wa_student.max' => 'Teks pesan akun anak terlalu panjang (maks 4000 karakter).',
            'account_wa_parent.max' => 'Teks pesan akun orang tua terlalu panjang (maks 4000 karakter).',
        ]);

        \App\Models\Setting::set(self::ACCOUNT_WA_STUDENT_KEY, $validated['account_wa_student'], 'daftar_ulang');
        \App\Models\Setting::set(self::ACCOUNT_WA_PARENT_KEY, $validated['account_wa_parent'], 'daftar_ulang');

        return redirect()
            ->route('admin.generus-registration.index')
            ->with('success', 'Template pesan informasi akun berhasil disimpan.');
    }

    /**
     * Kembalikan template pesan informasi akun ke teks bawaan.
     */
    public function resetAccountWaTemplate(): RedirectResponse
    {
        \App\Models\Setting::set(self::ACCOUNT_WA_STUDENT_KEY, self::ACCOUNT_WA_STUDENT_DEFAULT, 'daftar_ulang');
        \App\Models\Setting::set(self::ACCOUNT_WA_PARENT_KEY, self::ACCOUNT_WA_PARENT_DEFAULT, 'daftar_ulang');

        return redirect()
            ->route('admin.generus-registration.index')
            ->with('success', 'Template pesan informasi akun dikembalikan ke teks bawaan.');
    }

    public function adminPreview(Siswa $siswa): Response
    {
        return $this->documentService->response($this->registrationForSiswa($siswa), false);
    }

    public function adminDownload(Siswa $siswa): Response
    {
        return $this->documentService->response($this->registrationForSiswa($siswa));
    }

    /**
     * Reset daftar ulang seorang Generus: hapus data registrasi + tanda tangan
     * sehingga status kembali "Belum TTD" dan alur daftar ulang bisa diulang.
     * Data biodata siswa TIDAK dihapus.
     */
    public function adminReset(Request $request, Siswa $siswa): RedirectResponse
    {
        $registration = GenerusRegistration::query()->where('siswa_id', $siswa->id)->first();

        if (! $registration) {
            return redirect()
                ->route('admin.generus-registration.index')
                ->with('info', "{$siswa->nama} memang belum pernah mengisi daftar ulang.");
        }

        // Bersihkan berkas tanda tangan / dokumen terkait registrasi ini.
        try {
            if ($registration->public_id) {
                \Illuminate\Support\Facades\Storage::disk('local')
                    ->deleteDirectory('generus-registrations/'.$registration->public_id);
            }
            foreach (array_filter([$registration->parent_signature_path, $registration->student_signature_path]) as $path) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
            }
        } catch (\Throwable $e) {
            // Abaikan kegagalan hapus berkas; yang penting record registrasi dihapus.
        }

        $registration->delete();

        return redirect()
            ->route('admin.generus-registration.index')
            ->with('success', "Daftar ulang {$siswa->nama} berhasil direset. Statusnya kembali \"Belum\" dan tautan daftar ulang bisa dipakai lagi.");
    }

    /**
     * Catat bahwa admin sudah membagikan tautan daftar ulang (via WA atau salin link).
     * Disimpan di metadata siswa agar tak perlu tabel/migrasi baru.
     */
    public function adminMarkShared(Request $request, Siswa $siswa): JsonResponse
    {
        $channel = in_array($request->input('channel'), ['wa', 'link'], true)
            ? $request->input('channel')
            : 'link';

        $metadata = is_array($siswa->metadata) ? $siswa->metadata : [];
        $metadata['daftar_ulang_shared_at'] = now()->toISOString();
        $metadata['daftar_ulang_shared_channel'] = $channel;
        $metadata['daftar_ulang_shared_by'] = Auth::user()?->username ?? Auth::user()?->name;

        $siswa->metadata = $metadata;
        $siswa->save();

        return response()->json([
            'success' => true,
            'shared_at_label' => now()->translatedFormat('d M Y H:i'),
            'channel' => $channel === 'wa' ? 'WA' : 'Salin link',
        ]);
    }

    private function registrationForSiswa(Siswa $siswa): GenerusRegistration
    {
        return GenerusRegistration::query()->where('siswa_id', $siswa->id)->firstOrFail();
    }

    /**
     * Ubah nomor telepon lokal menjadi tautan wa.me (format 62...).
     */
    private function waLink(?string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?: '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }
        if (strlen($digits) < 10) {
            return null;
        }

        return 'https://wa.me/'.$digits;
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

    /**
     * Buat token tautan langsung yang stabil per-Generus (tidak kedaluwarsa),
     * memakai APP_KEY sebagai kunci HMAC sehingga tidak bisa ditebak/diubah.
     */
    public static function directTokenFor(Siswa $siswa): string
    {
        $payload = 'gr-direct:'.$siswa->id;
        $signature = substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 24);

        return $siswa->id.'-'.$signature;
    }

    private function studentFromDirectToken(string $token): Siswa
    {
        if (! preg_match('/^(\d+)-([a-f0-9]{24})$/', $token, $matches)) {
            abort(404);
        }

        $id = (int) $matches[1];
        $expected = substr(hash_hmac('sha256', 'gr-direct:'.$id, (string) config('app.key')), 0, 24);
        abort_unless(hash_equals($expected, $matches[2]), 404);

        return Siswa::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->findOrFail($id);
    }

    private function directLinkStudent(Request $request): ?Siswa
    {
        $openedAt = (int) $request->session()->get(self::DIRECT_TIME_SESSION, 0);

        if (! $openedAt || now()->timestamp - $openedAt > self::ACCESS_TTL_SECONDS) {
            $this->clearDirectLink($request);
            return null;
        }

        return Siswa::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->find($request->session()->get(self::DIRECT_SISWA_SESSION));
    }

    private function clearDirectLink(Request $request): void
    {
        $request->session()->forget([self::DIRECT_SISWA_SESSION, self::DIRECT_TIME_SESSION]);
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
