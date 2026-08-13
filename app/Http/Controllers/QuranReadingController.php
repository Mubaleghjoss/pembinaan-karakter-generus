<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmQuranReadingScanRequest;
use App\Http\Requests\StoreQuranReadingScanRequest;
use App\Models\Kelas;
use App\Models\QuranReadingEntry;
use App\Models\QuranReadingScan;
use App\Models\QuranReadingSheet;
use App\Models\Siswa;
use App\Models\ThemeSetting;
use App\Services\QuranReadingDocumentService;
use App\Services\QuranReadingScanService;
use App\Support\QuranCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuranReadingController extends Controller
{
    public function __construct(
        private readonly QuranReadingDocumentService $documents,
        private readonly QuranReadingScanService $scans,
    ) {
    }

    public function studentIndex(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();

        return view('quran-reading.student-index', $this->studentPayload($request, $siswa));
    }

    public function studentStore(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        $data = $this->validatedEntry($request);

        $siswa->quranReadingEntries()->create($data + [
            'source' => 'manual',
            'submitted_by_type' => 'siswa',
            'submitted_by_id' => $siswa->id,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]);

        return back()->with('success', 'Catatan bacaan dikirim dan menunggu verifikasi Pamong.');
    }

    public function studentUpdate(Request $request, QuranReadingEntry $entry)
    {
        $siswa = Auth::guard('siswa')->user();
        abort_unless((int) $entry->siswa_id === (int) $siswa->id, 403);
        abort_unless($entry->status === QuranReadingEntry::STATUS_PENDING, 409, 'Hanya catatan yang menunggu yang dapat diubah.');

        $entry->update($this->validatedEntry($request));

        return back()->with('success', 'Catatan bacaan diperbarui.');
    }

    public function studentReport(Request $request)
    {
        return $this->reportResponse($request, Auth::guard('siswa')->user());
    }

    public function studentSheet()
    {
        return $this->newSheet(Auth::guard('siswa')->user(), null);
    }

    public function parentIndex(Request $request)
    {
        $siswa = Auth::guard('ortu')->user();
        $entries = $this->verifiedQuery($siswa, $request)->paginate(20)->withQueryString();

        return view('quran-reading.parent-index', compact('siswa', 'entries'));
    }

    public function parentReport(Request $request)
    {
        return $this->reportResponse($request, Auth::guard('ortu')->user());
    }

    public function operationalIndex(Request $request)
    {
        $user = $request->user();
        $siswaQuery = Siswa::active()->with('kelas:id,nama')->orderBy('nama');
        if ($user->isTeacher()) {
            $siswaQuery->whereIn('id', $user->getAssignedSiswaIds());
        }

        if ($request->filled('kelas_id')) {
            $siswaQuery->where('kelas_id', $request->integer('kelas_id'));
        }
        if ($request->filled('kelompok')) {
            $siswaQuery->where('kelompok', $request->string('kelompok'));
        }
        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $siswaQuery->where(fn (Builder $query) => $query
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('nis', 'like', "%{$search}%"));
        }

        $siswaList = $siswaQuery->paginate(20)->withQueryString();
        $pendingQuery = QuranReadingEntry::with(['siswa:id,nis,nama,kelas_id', 'siswa.kelas:id,nama', 'scan:id,siswa_id'])
            ->where('status', QuranReadingEntry::STATUS_PENDING)
            ->latest('reading_date');
        if ($user->isTeacher()) {
            $pendingQuery->whereIn('siswa_id', $user->getAssignedSiswaIds());
        }

        $pendingEntries = $pendingQuery->limit(30)->get();
        $selectedSiswa = $request->filled('siswa_id') ? Siswa::find($request->integer('siswa_id')) : null;
        if ($selectedSiswa) {
            $this->authorizeOperationalStudent($user, $selectedSiswa);
        }

        $recentEntries = $selectedSiswa
            ? $selectedSiswa->quranReadingEntries()->with('verifier:id,name')->latest('reading_date')->limit(30)->get()
            : collect();

        return view('quran-reading.operational-index', [
            'siswaList' => $siswaList,
            'pendingEntries' => $pendingEntries,
            'selectedSiswa' => $selectedSiswa,
            'recentEntries' => $recentEntries,
            'surahOptions' => QuranCatalog::options(),
            'kelasOptions' => Kelas::where('is_active', true)->orderBy('nama')->get(['id', 'nama']),
            'kelompokOptions' => Siswa::kelompokOptions(),
            'capabilities' => [
                'create' => $user->hasPamongCrudPermission('tracer_bacaan_quran', 'create'),
                'edit' => $user->hasPamongCrudPermission('tracer_bacaan_quran', 'edit'),
                'verify' => $user->hasPamongCrudPermission('tracer_bacaan_quran', 'verify'),
                'export' => $user->hasPamongCrudPermission('tracer_bacaan_quran', 'export'),
            ],
        ]);
    }

    public function operationalStore(Request $request)
    {
        $siswaId = $request->validate(['siswa_id' => ['required', 'integer', 'exists:siswa,id']])['siswa_id'];
        $siswa = Siswa::findOrFail($siswaId);
        $this->authorizeOperationalStudent($request->user(), $siswa);
        $data = $this->validatedEntry($request);

        $siswa->quranReadingEntries()->create($data + [
            'source' => 'manual',
            'submitted_by_type' => 'user',
            'submitted_by_id' => $request->user()->id,
            'status' => QuranReadingEntry::STATUS_VERIFIED,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Catatan bacaan tersimpan dan langsung terverifikasi.');
    }

    public function operationalUpdate(Request $request, QuranReadingEntry $entry)
    {
        $this->authorizeOperationalStudent($request->user(), $entry->siswa);
        $entry->update($this->validatedEntry($request));

        return back()->with('success', 'Catatan bacaan berhasil diperbaiki.');
    }

    public function verify(Request $request, QuranReadingEntry $entry)
    {
        $this->authorizeOperationalStudent($request->user(), $entry->siswa);
        abort_unless($entry->status === QuranReadingEntry::STATUS_PENDING, 409, 'Hanya catatan yang menunggu yang dapat diverifikasi.');
        $entry->update([
            'status' => QuranReadingEntry::STATUS_VERIFIED,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'verification_notes' => $request->validate(['verification_notes' => 'nullable|string|max:1000'])['verification_notes'] ?? null,
        ]);

        return back()->with('success', 'Catatan bacaan telah diverifikasi.');
    }

    public function reject(Request $request, QuranReadingEntry $entry)
    {
        $this->authorizeOperationalStudent($request->user(), $entry->siswa);
        abort_unless($entry->status === QuranReadingEntry::STATUS_PENDING, 409, 'Hanya catatan yang menunggu yang dapat ditolak.');
        $data = $request->validate(['verification_notes' => 'required|string|min:3|max:1000']);
        $entry->update([
            'status' => QuranReadingEntry::STATUS_REJECTED,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'verification_notes' => $data['verification_notes'],
        ]);

        return back()->with('success', 'Catatan bacaan ditolak dengan keterangan.');
    }

    public function operationalReport(Request $request, Siswa $siswa)
    {
        $this->authorizeOperationalStudent($request->user(), $siswa);

        return $this->reportResponse($request, $siswa);
    }

    public function operationalSheet(Request $request, Siswa $siswa)
    {
        $this->authorizeOperationalStudent($request->user(), $siswa);

        return $this->newSheet($siswa, $request->user()->id);
    }

    public function scanForm(Request $request, ?Siswa $siswa = null)
    {
        $this->ensureScanEnabled();

        if ($this->isStudentRoute($request)) {
            return redirect()->to(route('siswa.quran.index', ['tab' => 'scan']).'#scan');
        }

        abort_unless($siswa, 404);
        $this->authorizeOperationalStudent($request->user(), $siswa);

        return redirect()->to(route('quran.index', [
            'tab' => 'scan',
            'siswa_id' => $siswa->id,
        ]).'#scan');
    }

    public function scanUpload(StoreQuranReadingScanRequest $request, ?Siswa $siswa = null)
    {
        $this->ensureScanEnabled();
        $data = $request->validated();
        $sheet = $this->scans->resolveSheet($data['sheet_payload']);
        [$actorType, $actorId] = $this->resolveScanActor($request, $siswa, $sheet);

        $scan = $this->scans->create(
            $sheet,
            $request->file('scan_image'),
            $request->file('processed_image'),
            $actorType,
            $actorId,
            $data['ocr_suggestion'] ?? null,
        );

        return redirect()->route(
            $actorType === 'siswa' ? 'siswa.quran.scan.confirm' : 'quran.scan.confirm',
            $actorType === 'siswa' ? $scan : $scan,
        );
    }

    public function publicScanUpload(StoreQuranReadingScanRequest $request)
    {
        $this->ensureScanEnabled();
        $data = $request->validated();
        $sheet = $this->scans->resolveSheet($data['sheet_payload']);
        $scan = $this->scans->create(
            $sheet,
            $request->file('scan_image'),
            $request->file('processed_image'),
            'public',
            null,
            $data['ocr_suggestion'] ?? null,
        );

        $request->session()->put('quran_public_scans.'.$scan->id, true);

        return redirect()->route('public.quran.scan.confirm', $scan);
    }

    public function studentScanConfirmForm(Request $request, QuranReadingScan $scan)
    {
        return $this->renderScanConfirm($request, $scan, true);
    }

    public function scanConfirmForm(Request $request, Siswa $siswa, QuranReadingScan $scan)
    {
        abort_unless((int) $scan->siswa_id === (int) $siswa->id, 404);

        return $this->renderScanConfirm($request, $scan, false, false);
    }

    public function operationalScanConfirmForm(Request $request, QuranReadingScan $scan)
    {
        return $this->renderScanConfirm($request, $scan, false, false);
    }

    public function publicScanConfirmForm(Request $request, QuranReadingScan $scan)
    {
        return $this->renderScanConfirm($request, $scan, false, true);
    }

    private function renderScanConfirm(Request $request, QuranReadingScan $scan, bool $isStudent, bool $isPublic = false)
    {
        $this->ensureScanEnabled();
        $this->authorizeScan($request, $scan, $isStudent, $isPublic);

        return view('quran-reading.scan-confirm', [
            'scan' => $scan->load('siswa.kelas'),
            'surahOptions' => QuranCatalog::options(),
            'isStudent' => $isStudent,
            'isPublic' => $isPublic,
            'theme' => $isPublic ? ThemeSetting::current() : null,
        ]);
    }

    public function studentScanConfirm(ConfirmQuranReadingScanRequest $request, QuranReadingScan $scan)
    {
        return $this->confirmScanResponse($request, $scan, true, false);
    }

    public function scanConfirm(ConfirmQuranReadingScanRequest $request, Siswa $siswa, QuranReadingScan $scan)
    {
        abort_unless((int) $scan->siswa_id === (int) $siswa->id, 404);

        return $this->confirmScanResponse($request, $scan, false, false);
    }

    public function operationalScanConfirm(ConfirmQuranReadingScanRequest $request, QuranReadingScan $scan)
    {
        return $this->confirmScanResponse($request, $scan, false, false);
    }

    public function publicScanConfirm(ConfirmQuranReadingScanRequest $request, QuranReadingScan $scan)
    {
        return $this->confirmScanResponse($request, $scan, false, true);
    }

    private function confirmScanResponse(ConfirmQuranReadingScanRequest $request, QuranReadingScan $scan, bool $isStudent, bool $isPublic)
    {
        $this->ensureScanEnabled();
        $this->authorizeScan($request, $scan, $isStudent, $isPublic);
        abort_if($scan->status === 'confirmed', 409, 'Hasil scan ini sudah dikonfirmasi.');
        $rows = $request->validated()['rows'];

        $actor = $isPublic ? null : ($isStudent ? $request->user('siswa') : $request->user());
        $needsVerification = $isStudent || $isPublic;

        DB::transaction(function () use ($rows, $scan, $needsVerification, $isStudent, $isPublic, $actor) {
            foreach ($rows as $row) {
                $this->validateReadingRange($row);
                $existing = QuranReadingEntry::where('sheet_id', $scan->sheet_id)
                    ->where('sheet_row_number', $row['row_number'])
                    ->first();
                if ($existing?->status === QuranReadingEntry::STATUS_VERIFIED) {
                    continue;
                }

                QuranReadingEntry::updateOrCreate([
                    'sheet_id' => $scan->sheet_id,
                    'sheet_row_number' => $row['row_number'],
                ], collect($row)->except('row_number')->all() + [
                    'siswa_id' => $scan->siswa_id,
                    'source' => 'scan',
                    'scan_id' => $scan->id,
                    'submitted_by_type' => $isPublic ? 'public' : ($isStudent ? 'siswa' : 'user'),
                    'submitted_by_id' => $actor?->id,
                    'status' => $needsVerification ? QuranReadingEntry::STATUS_PENDING : QuranReadingEntry::STATUS_VERIFIED,
                    'verified_by' => $needsVerification ? null : $actor?->id,
                    'verified_at' => $needsVerification ? null : now(),
                ]);
            }

            $scan->update(['status' => 'confirmed', 'extracted_rows' => $rows, 'confirmed_at' => now()]);
        });

        $target = match (true) {
            $isPublic => route('public.scanner', ['mode' => 'quran']).'#quran',
            $isStudent => route('siswa.quran.index', ['tab' => 'rekap']).'#rekap',
            default => route('quran.index', ['tab' => 'rekap', 'siswa_id' => $scan->siswa_id]).'#rekap',
        };

        return redirect()->to($target)
            ->with('success', $needsVerification ? 'Hasil scan dikirim untuk verifikasi Pamong.' : 'Hasil scan disimpan dan terverifikasi.');
    }

    public function studentScanImage(Request $request, QuranReadingScan $scan)
    {
        return $this->scanImageResponse($request, $scan, true);
    }

    public function scanImage(Request $request, Siswa $siswa, QuranReadingScan $scan)
    {
        abort_unless((int) $scan->siswa_id === (int) $siswa->id, 404);

        return $this->scanImageResponse($request, $scan, false, false);
    }

    public function operationalScanImage(Request $request, QuranReadingScan $scan)
    {
        return $this->scanImageResponse($request, $scan, false, false);
    }

    public function publicScanImage(Request $request, QuranReadingScan $scan)
    {
        return $this->scanImageResponse($request, $scan, false, true);
    }

    private function scanImageResponse(Request $request, QuranReadingScan $scan, bool $isStudent, bool $isPublic = false)
    {
        $this->ensureScanEnabled();
        $this->authorizeScan($request, $scan, $isStudent, $isPublic);
        $path = $request->boolean('original') || ! $scan->processed_path
            ? $scan->original_path
            : $scan->processed_path;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function studentPayload(Request $request, Siswa $siswa): array
    {
        $query = $siswa->quranReadingEntries()->with('verifier:id,name')->latest('reading_date');
        $entries = $query->paginate(20)->withQueryString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEntries = $siswa->quranReadingEntries()->where('status', QuranReadingEntry::STATUS_VERIFIED)->whereDate('reading_date', '>=', $monthStart);

        return [
            'siswa' => $siswa,
            'entries' => $entries,
            'surahOptions' => QuranCatalog::options(),
            'lastVerified' => $siswa->quranReadingEntries()->where('status', QuranReadingEntry::STATUS_VERIFIED)->latest('reading_date')->first(),
            'monthPages' => (clone $monthEntries)->selectRaw('COALESCE(SUM(page_end - page_start + 1), 0) as total')->value('total') ?? 0,
            'activeDays' => (clone $monthEntries)->distinct()->count('reading_date'),
            'pendingCount' => $siswa->quranReadingEntries()->where('status', QuranReadingEntry::STATUS_PENDING)->count(),
        ];
    }

    private function validatedEntry(Request $request): array
    {
        $data = $request->validate([
            'reading_date' => ['required', 'date', 'before_or_equal:today'],
            'page_start' => ['required', 'integer', 'between:1,1000'],
            'page_end' => ['required', 'integer', 'between:1,1000'],
            'surah_start' => ['required', 'integer', 'between:1,114'],
            'ayah_start' => ['required', 'integer', 'between:1,286'],
            'surah_end' => ['required', 'integer', 'between:1,114'],
            'ayah_end' => ['required', 'integer', 'between:1,286'],
            'mushaf_label' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->validateReadingRange($data);

        return $data;
    }

    private function validateReadingRange(array $data): void
    {
        $errors = [];
        if ((int) $data['page_end'] < (int) $data['page_start']) {
            $errors['page_end'] = 'Halaman akhir tidak boleh lebih kecil dari halaman awal.';
        }
        if ((int) $data['surah_end'] < (int) $data['surah_start']) {
            $errors['surah_end'] = 'Surat akhir tidak boleh berada sebelum surat awal.';
        }
        foreach (['start', 'end'] as $side) {
            $surah = (int) $data['surah_'.$side];
            $ayah = (int) $data['ayah_'.$side];
            if ($ayah > QuranCatalog::ayahCount($surah)) {
                $errors['ayah_'.$side] = 'Ayat melebihi jumlah ayat '.QuranCatalog::name($surah).'.';
            }
        }
        if ((int) $data['surah_start'] === (int) $data['surah_end'] && (int) $data['ayah_end'] < (int) $data['ayah_start']) {
            $errors['ayah_end'] = 'Ayat akhir tidak boleh lebih kecil dari ayat awal pada surat yang sama.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function verifiedQuery(Siswa $siswa, Request $request)
    {
        return $siswa->quranReadingEntries()
            ->where('status', QuranReadingEntry::STATUS_VERIFIED)
            ->with('verifier:id,name')
            ->when($request->filled('start_date'), fn (Builder $q) => $q->whereDate('reading_date', '>=', $request->date('start_date')))
            ->when($request->filled('end_date'), fn (Builder $q) => $q->whereDate('reading_date', '<=', $request->date('end_date')))
            ->latest('reading_date');
    }

    private function reportResponse(Request $request, Siswa $siswa)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        return $this->documents->report($siswa, $this->verifiedQuery($siswa, $request)->get(), $filters);
    }

    private function newSheet(Siswa $siswa, ?int $generatedBy)
    {
        $latest = $siswa->quranReadingEntries()->where('status', QuranReadingEntry::STATUS_VERIFIED)->latest('reading_date')->first();
        $plainToken = bin2hex(random_bytes(16));
        $sheet = QuranReadingSheet::create([
            'siswa_id' => $siswa->id,
            'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'active',
            'row_count' => 12,
            'template_version' => 2,
            'generated_by' => $generatedBy,
            'last_position' => $latest ? [
                'reading_date' => $latest->reading_date?->toDateString(),
                'page_end' => $latest->page_end,
                'surah_end' => $latest->surah_end,
                'ayah_end' => $latest->ayah_end,
            ] : null,
        ]);

        return $this->documents->sheet($sheet, $plainToken);
    }

    private function authorizeOperationalStudent($user, Siswa $siswa): void
    {
        abort_unless($user, 401);
        if ($user->isTeacher()) {
            abort_unless(in_array($siswa->id, $user->getAssignedSiswaIds(), true), 403);
        }
    }

    private function resolveScanActor(Request $request, ?Siswa $siswa, QuranReadingSheet $sheet): array
    {
        if ($this->isStudentRoute($request)) {
            $student = $request->user('siswa');
            abort_unless((int) $sheet->siswa_id === (int) $student?->id, 403, 'QR lembar bukan milik akun ini.');

            return ['siswa', $student->id, $student];
        }

        $target = $sheet->siswa;
        if ($siswa && (int) $siswa->id !== (int) $target->id) {
            throw ValidationException::withMessages(['sheet_payload' => 'QR lembar bukan milik Generus yang dipilih.']);
        }
        abort_unless($request->user(), 401);
        $this->authorizeOperationalStudent($request->user(), $target);

        return ['user', $request->user()->id, $target];
    }

    private function authorizeScan(Request $request, QuranReadingScan $scan, bool $isStudent, bool $isPublic = false): void
    {
        if ($isPublic) {
            abort_unless($scan->uploaded_by_type === 'public' && $request->session()->has('quran_public_scans.'.$scan->id), 403);

            return;
        }

        if ($isStudent) {
            abort_unless((int) $scan->siswa_id === (int) $request->user('siswa')?->id && $scan->uploaded_by_type === 'siswa', 403);

            return;
        }

        abort_unless(in_array($scan->uploaded_by_type, ['user', 'siswa', 'public'], true), 403);
        $this->authorizeOperationalStudent($request->user(), $scan->siswa);
    }

    private function ensureScanEnabled(): void
    {
        abort_unless((bool) config('quran-reading.scan_enabled'), 404);
    }

    private function isStudentRoute(Request $request): bool
    {
        return str_starts_with((string) $request->route()?->getName(), 'siswa.quran.');
    }
}
