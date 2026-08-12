<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\QuranReadingEntry;
use App\Models\QuranReadingScan;
use App\Models\QuranReadingSheet;
use App\Models\Siswa;
use App\Services\QuranReadingDocumentService;
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
    public function __construct(private readonly QuranReadingDocumentService $documents)
    {
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
        $pendingQuery = QuranReadingEntry::with(['siswa:id,nis,nama,kelas_id', 'siswa.kelas:id,nama'])
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

        if (Auth::guard('siswa')->check()) {
            $siswa = Auth::guard('siswa')->user();
            $layout = 'siswa';
        } else {
            abort_unless($siswa, 404);
            $this->authorizeOperationalStudent($request->user(), $siswa);
            $layout = 'operational';
        }

        return view('quran-reading.scan', compact('siswa', 'layout'));
    }

    public function scanUpload(Request $request, ?Siswa $siswa = null)
    {
        $this->ensureScanEnabled();

        [$actorType, $actorId, $siswa] = $this->resolveScanActor($request, $siswa);
        $data = $request->validate([
            'sheet_payload' => ['required', 'string', 'max:500', 'regex:/^PKGQURAN:[0-9a-f-]{36}:[A-Za-z0-9]+$/i'],
            'scan_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'ocr_suggestion' => ['nullable', 'json', 'max:12000'],
        ]);

        [, $publicId, $token] = explode(':', $data['sheet_payload'], 3);
        $sheet = QuranReadingSheet::where('public_id', $publicId)->first();
        if (! $sheet || (int) $sheet->siswa_id !== (int) $siswa->id || ! $sheet->verifyToken($token)) {
            throw ValidationException::withMessages(['sheet_payload' => 'QR lembar tidak valid, sudah dicabut, atau bukan milik siswa ini.']);
        }

        $path = $request->file('scan_image')->store('quran-reading-scans', 'local');
        $scan = QuranReadingScan::create([
            'siswa_id' => $siswa->id,
            'sheet_id' => $sheet->id,
            'uploaded_by_type' => $actorType,
            'uploaded_by_id' => $actorId,
            'original_path' => $path,
            'status' => 'awaiting_confirmation',
            'metadata' => [
                'original_name' => mb_substr($request->file('scan_image')->getClientOriginalName(), 0, 200),
                'mime' => $request->file('scan_image')->getMimeType(),
                'size' => $request->file('scan_image')->getSize(),
                'ocr_suggestion' => $data['ocr_suggestion'] ?? null,
            ],
        ]);

        return redirect()->route($actorType === 'siswa' ? 'siswa.quran.scan.confirm' : 'quran.scan.confirm', $actorType === 'siswa' ? $scan : [$siswa, $scan]);
    }

    public function studentScanConfirmForm(Request $request, QuranReadingScan $scan)
    {
        return $this->renderScanConfirm($request, $scan, null);
    }

    public function scanConfirmForm(Request $request, Siswa $siswa, QuranReadingScan $scan)
    {
        return $this->renderScanConfirm($request, $scan, $siswa);
    }

    private function renderScanConfirm(Request $request, QuranReadingScan $scan, ?Siswa $siswa)
    {
        $this->ensureScanEnabled();
        $this->authorizeScan($request, $scan, $siswa);

        return view('quran-reading.scan-confirm', [
            'scan' => $scan->load('siswa'),
            'surahOptions' => QuranCatalog::options(),
            'isStudent' => Auth::guard('siswa')->check(),
        ]);
    }

    public function studentScanConfirm(Request $request, QuranReadingScan $scan)
    {
        return $this->confirmScanResponse($request, $scan, null);
    }

    public function scanConfirm(Request $request, Siswa $siswa, QuranReadingScan $scan)
    {
        return $this->confirmScanResponse($request, $scan, $siswa);
    }

    private function confirmScanResponse(Request $request, QuranReadingScan $scan, ?Siswa $siswa)
    {
        $this->ensureScanEnabled();
        $this->authorizeScan($request, $scan, $siswa);
        abort_if($scan->status === 'confirmed', 409, 'Hasil scan ini sudah dikonfirmasi.');
        $rows = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:12'],
            'rows.*.row_number' => ['required', 'integer', 'between:1,12'],
            'rows.*.reading_date' => ['required', 'date', 'before_or_equal:today'],
            'rows.*.page_start' => ['required', 'integer', 'between:1,1000'],
            'rows.*.page_end' => ['required', 'integer', 'between:1,1000'],
            'rows.*.surah_start' => ['required', 'integer', 'between:1,114'],
            'rows.*.ayah_start' => ['required', 'integer', 'between:1,286'],
            'rows.*.surah_end' => ['required', 'integer', 'between:1,114'],
            'rows.*.ayah_end' => ['required', 'integer', 'between:1,286'],
            'rows.*.notes' => ['nullable', 'string', 'max:1000'],
        ])['rows'];

        $isStudent = Auth::guard('siswa')->check();
        $actor = $isStudent ? Auth::guard('siswa')->user() : $request->user();

        DB::transaction(function () use ($rows, $scan, $isStudent, $actor) {
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
                    'submitted_by_type' => $isStudent ? 'siswa' : 'user',
                    'submitted_by_id' => $actor->id,
                    'status' => $isStudent ? QuranReadingEntry::STATUS_PENDING : QuranReadingEntry::STATUS_VERIFIED,
                    'verified_by' => $isStudent ? null : $actor->id,
                    'verified_at' => $isStudent ? null : now(),
                ]);
            }

            $scan->update(['status' => 'confirmed', 'extracted_rows' => $rows, 'confirmed_at' => now()]);
        });

        return redirect()->route($isStudent ? 'siswa.quran.index' : 'quran.index', $isStudent ? [] : ['siswa_id' => $scan->siswa_id])
            ->with('success', $isStudent ? 'Hasil scan dikirim untuk verifikasi Pamong.' : 'Hasil scan disimpan dan terverifikasi.');
    }

    public function studentScanImage(Request $request, QuranReadingScan $scan)
    {
        return $this->scanImageResponse($request, $scan, null);
    }

    public function scanImage(Request $request, Siswa $siswa, QuranReadingScan $scan)
    {
        return $this->scanImageResponse($request, $scan, $siswa);
    }

    private function scanImageResponse(Request $request, QuranReadingScan $scan, ?Siswa $siswa)
    {
        $this->ensureScanEnabled();
        $this->authorizeScan($request, $scan, $siswa);
        abort_unless(Storage::disk('local')->exists($scan->original_path), 404);

        return Storage::disk('local')->response($scan->original_path, null, [
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
        $plainToken = Str::random(48);
        $sheet = QuranReadingSheet::create([
            'siswa_id' => $siswa->id,
            'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'active',
            'row_count' => 12,
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

    private function resolveScanActor(Request $request, ?Siswa $siswa): array
    {
        if (Auth::guard('siswa')->check()) {
            $student = Auth::guard('siswa')->user();

            return ['siswa', $student->id, $student];
        }

        abort_unless($siswa, 404);
        $this->authorizeOperationalStudent($request->user(), $siswa);

        return ['user', $request->user()->id, $siswa];
    }

    private function authorizeScan(Request $request, QuranReadingScan $scan, ?Siswa $siswa): void
    {
        if (Auth::guard('siswa')->check()) {
            abort_unless((int) $scan->siswa_id === (int) Auth::guard('siswa')->id() && $scan->uploaded_by_type === 'siswa', 403);

            return;
        }

        $target = $siswa ?: $scan->siswa;
        abort_unless((int) $scan->siswa_id === (int) $target->id, 403);
        $this->authorizeOperationalStudent($request->user(), $target);
    }

    private function ensureScanEnabled(): void
    {
        abort_unless((bool) config('quran-reading.scan_enabled'), 404);
    }
}
