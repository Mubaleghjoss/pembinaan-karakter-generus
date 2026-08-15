<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmQuranReadingScanRequest;
use App\Http\Requests\StoreQuranReadingScanRequest;
use App\Models\QuranProgressSubmission;
use App\Models\QuranReadingCycle;
use App\Models\QuranReadingEntry;
use App\Models\QuranReadingScan;
use App\Models\QuranReadingSheet;
use App\Models\QuranSurahProgress;
use App\Models\Siswa;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Services\QuranKhatamService;
use App\Services\QuranBarcodeFlowService;
use App\Services\QuranReadingDocumentService;
use App\Services\QuranReadingScanService;
use App\Support\QuranCatalog;
use App\Support\TargetGrade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class QuranReadingController extends Controller
{
    public function __construct(
        private readonly QuranReadingDocumentService $documents,
        private readonly QuranReadingScanService $scans,
        private readonly QuranKhatamService $khatam,
        private readonly QuranBarcodeFlowService $barcodeFlows,
    ) {}

    public function studentIndex(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();

        return view('quran-reading.student-index', $this->studentPayload($request, $siswa));
    }

    public function studentStore(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        $this->ensureAlumniSubmissionEnabled($siswa);
        $data = $this->validatedEntry($request);

        $siswa->quranReadingEntries()->create($data + [
            'source' => 'manual',
            'submitted_by_type' => 'siswa',
            'submitted_by_id' => $siswa->id,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]);

        return back()->with('success', $siswa->isGraduated()
            ? 'Catatan bacaan dikirim dan menunggu verifikasi Admin.'
            : 'Catatan bacaan dikirim dan menunggu verifikasi Pamong.');
    }

    public function studentUpdate(Request $request, QuranReadingEntry $entry)
    {
        $siswa = Auth::guard('siswa')->user();
        $this->ensureAlumniSubmissionEnabled($siswa);
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

    public function studentKhatamMap()
    {
        return $this->documents->surahReference(Auth::guard('siswa')->user());
    }

    public function studentDuplex()
    {
        return $this->newDuplex(Auth::guard('siswa')->user(), null);
    }

    public function parentIndex(Request $request)
    {
        $siswa = Auth::guard('ortu')->user();
        $entries = $this->verifiedQuery($siswa, $request)->paginate(20)->withQueryString();

        return view('quran-reading.parent-index', compact('siswa', 'entries') + [
            'khatam' => $this->khatam->summaryForStudent($siswa),
            'cycleHistory' => $siswa->quranReadingCycles()->latest('cycle_number')->get(),
        ]);
    }

    public function parentReport(Request $request)
    {
        return $this->reportResponse($request, Auth::guard('ortu')->user());
    }

    public function operationalIndex(Request $request)
    {
        $user = $request->user();
        $siswaList = $this->operationalStudentQuery($request)->paginate(20)->withQueryString();
        $pendingQuery = QuranReadingEntry::with(['siswa:id,nis,nama,school_grade,status,is_active,alumni_reviewer_id', 'siswa.alumniReviewer:id,name', 'scan:id,siswa_id'])
            ->where('status', QuranReadingEntry::STATUS_PENDING)
            ->latest('reading_date');
        if ($user->isTeacher()) {
            $pendingQuery->whereIn('siswa_id', $user->getAssignedSiswaIds());
        }

        $pendingEntries = $pendingQuery->limit(30)->get();
        $pendingProgressQuery = QuranProgressSubmission::with(['siswa:id,nis,nama,school_grade', 'scan:id,siswa_id'])
            ->where('status', QuranProgressSubmission::STATUS_PENDING)
            ->latest();
        if ($user->isTeacher()) {
            $pendingProgressQuery->whereIn('siswa_id', $user->getAssignedSiswaIds());
        }
        $pendingProgressSubmissions = $pendingProgressQuery->limit(30)->get();
        $selectedSiswa = $request->filled('siswa_id')
            ? Siswa::with('pamongAssignments.pamong:id,name')->find($request->integer('siswa_id'))
            : null;
        if ($selectedSiswa) {
            $this->authorizeOperationalStudent($user, $selectedSiswa);
        }

        $recentEntries = $selectedSiswa
            ? $selectedSiswa->quranReadingEntries()->with('verifier:id,name')->latest('reading_date')->limit(30)->get()
            : collect();

        return view('quran-reading.operational-index', [
            'siswaList' => $siswaList,
            'pendingEntries' => $pendingEntries,
            'pendingProgressSubmissions' => $pendingProgressSubmissions,
            'selectedSiswa' => $selectedSiswa,
            'recentEntries' => $recentEntries,
            'surahOptions' => QuranCatalog::options(),
            'khatam' => $selectedSiswa ? $this->khatam->summaryForStudent($selectedSiswa) : null,
            'cycleHistory' => $selectedSiswa ? $selectedSiswa->quranReadingCycles()->latest('cycle_number')->get() : collect(),
            'schoolGradeOptions' => TargetGrade::schoolClassOptions(),
            'pamongOptions' => User::query()->where('status', 'active')->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))->orderByRaw('COALESCE(name, username)')->get(['id', 'name', 'username']),
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
        if ($entry->scan_id) {
            $this->scans->purgeFilesIfComplete($entry->scan()->with('entries:id,scan_id,status')->firstOrFail());
        }

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
        if ($entry->scan_id) {
            $this->scans->purgeFilesIfComplete($entry->scan()->with('entries:id,scan_id,status')->firstOrFail());
        }

        return back()->with('success', 'Catatan bacaan ditolak dengan keterangan.');
    }

    public function verifyProgress(Request $request, QuranProgressSubmission $submission)
    {
        $this->authorizeOperationalStudent($request->user(), $submission->siswa);
        abort_unless($submission->status === QuranProgressSubmission::STATUS_PENDING, 409, 'Hanya progres yang menunggu yang dapat diverifikasi.');
        $notes = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']])['review_notes'] ?? null;
        if ($notes) {
            $submission->update(['review_notes' => $notes]);
        }
        $this->khatam->applySubmission($submission, $request->user()->id);
        if ($submission->scan_id) {
            $this->scans->purgeFilesIfComplete($submission->scan()->with(['entries:id,scan_id,status', 'progressSubmission:id,scan_id,status'])->firstOrFail());
        }

        return back()->with('success', 'Progres Peta Khatam telah diverifikasi.');
    }

    public function rejectProgress(Request $request, QuranProgressSubmission $submission)
    {
        $this->authorizeOperationalStudent($request->user(), $submission->siswa);
        abort_unless($submission->status === QuranProgressSubmission::STATUS_PENDING, 409, 'Hanya progres yang menunggu yang dapat ditolak.');
        $data = $request->validate(['review_notes' => ['required', 'string', 'min:3', 'max:1000']]);
        $submission->update([
            'status' => QuranProgressSubmission::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'],
        ]);
        if ($submission->scan_id) {
            $this->scans->purgeFilesIfComplete($submission->scan()->with(['entries:id,scan_id,status', 'progressSubmission:id,scan_id,status'])->firstOrFail());
        }

        return back()->with('success', 'Pengajuan Peta Khatam ditolak dengan keterangan.');
    }

    public function correctKhatamProgress(Request $request, Siswa $siswa)
    {
        $this->authorizeOperationalStudent($request->user(), $siswa);
        $data = $request->validate([
            'cycle_id' => ['required', 'integer', 'exists:quran_reading_cycles,id'],
            'surah_number' => ['required', 'integer', 'between:1,114'],
            'state' => ['required', 'in:completed,active,reset'],
            'last_ayah' => ['nullable', 'integer', 'min:0', 'max:286'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $cycle = QuranReadingCycle::whereKey($data['cycle_id'])->where('siswa_id', $siswa->id)->firstOrFail();
        $number = (int) $data['surah_number'];
        $maximumAyah = QuranCatalog::ayahCount($number);
        if ($data['state'] === 'active' && ((int) ($data['last_ayah'] ?? 0) < 1 || (int) $data['last_ayah'] > $maximumAyah)) {
            throw ValidationException::withMessages(['last_ayah' => "Ayat harus antara 1 dan {$maximumAyah} untuk surat ini."]);
        }
        if ($data['state'] === 'reset' && QuranReadingCycle::where('siswa_id', $siswa->id)->where('cycle_number', '>', $cycle->cycle_number)->exists()) {
            throw ValidationException::withMessages(['state' => 'Progres siklus lama tidak dapat diturunkan setelah siklus berikutnya dibuat.']);
        }

        DB::transaction(function () use ($data, $cycle, $siswa, $number, $maximumAyah, $request) {
            $progress = QuranSurahProgress::firstOrNew(['cycle_id' => $cycle->id, 'surah_number' => $number]);
            $before = ['last_ayah' => (int) $progress->last_ayah, 'completed' => $progress->completed_at !== null];
            if ($data['state'] === 'completed') {
                $progress->fill(['last_ayah' => $maximumAyah, 'completed_at' => now(), 'source' => 'manual_correction', 'updated_by' => $request->user()->id])->save();
            } elseif ($data['state'] === 'active') {
                $progress->fill(['last_ayah' => (int) $data['last_ayah'], 'completed_at' => null, 'source' => 'manual_correction', 'updated_by' => $request->user()->id])->save();
            } else {
                $progress->delete();
            }
            if ($data['state'] !== 'completed' && $cycle->status === QuranReadingCycle::STATUS_COMPLETED) {
                $cycle->update(['status' => QuranReadingCycle::STATUS_ACTIVE, 'completed_at' => null]);
            } elseif ($data['state'] === 'completed' && $cycle->progress()->whereNotNull('completed_at')->count() === 114) {
                $cycle->update(['status' => QuranReadingCycle::STATUS_COMPLETED, 'completed_at' => now()->toDateString()]);
            }
            QuranProgressSubmission::create([
                'siswa_id' => $siswa->id, 'cycle_id' => $cycle->id, 'marked_on' => now()->toDateString(),
                'completed_surahs' => $data['state'] === 'completed' ? [$number] : [],
                'active_surah' => $data['state'] === 'active' ? $number : null,
                'active_ayah' => $data['state'] === 'active' ? (int) $data['last_ayah'] : null,
                'status' => QuranProgressSubmission::STATUS_VERIFIED, 'submitted_by_type' => 'user',
                'submitted_by_id' => $request->user()->id, 'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(), 'review_notes' => $data['reason'],
                'metadata' => ['manual_correction' => true, 'surah_number' => $number, 'before' => $before, 'state' => $data['state']],
            ]);
        });

        return back()->with('success', 'Koreksi progres tersimpan beserta alasan audit.');
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

    public function operationalKhatamMap(Request $request, Siswa $siswa)
    {
        $this->authorizeOperationalStudent($request->user(), $siswa);

        return $this->documents->surahReference($siswa);
    }

    public function operationalDuplex(Request $request, Siswa $siswa)
    {
        $this->authorizeOperationalStudent($request->user(), $siswa);

        return $this->newDuplex($siswa, $request->user()->id);
    }

    public function blankMonthly()
    {
        return $this->documents->blankMonthly();
    }

    public function blankSurahReference()
    {
        return $this->documents->blankSurahReference();
    }

    public function blankDuplex()
    {
        return $this->documents->blankDuplex();
    }

    public function bulkSheets(Request $request)
    {
        if ($request->input('selection_mode') !== 'selected') {
            $request->merge(['selected_ids' => []]);
        }
        $data = $request->validate([
            'document_type' => ['required', 'in:monthly,surah_reference,duplex,weekly,surah_map'],
            'selection_mode' => ['required', 'in:selected,filtered'],
            'selected_ids' => ['nullable', 'array', 'max:50'],
            'selected_ids.*' => ['integer', 'distinct', 'exists:siswa,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'school_grade' => ['nullable', 'string', \Illuminate\Validation\Rule::in(TargetGrade::values())],
            'pamong_id' => ['nullable', 'integer', 'exists:users,id'],
            'kelompok' => ['nullable', 'string', 'max:80'],
        ]);
        $documentType = match ($data['document_type']) {
            'weekly' => 'monthly',
            'surah_map' => 'surah_reference',
            default => $data['document_type'],
        };

        $query = $this->operationalStudentQuery($request)->orderBy('nama');
        if ($data['selection_mode'] === 'selected') {
            $ids = $data['selected_ids'] ?? [];
            if (! $ids) {
                throw ValidationException::withMessages(['selected_ids' => 'Pilih minimal satu Generus.']);
            }
            $query->whereIn('id', $ids);
        }

        $students = $query->limit(51)->get();
        if ($students->isEmpty()) {
            throw ValidationException::withMessages(['selected_ids' => 'Tidak ada Generus yang sesuai pilihan dan izin akun.']);
        }
        if ($students->count() > 50) {
            throw ValidationException::withMessages(['selected_ids' => 'Maksimal 50 Generus per PDF. Persempit filter terlebih dahulu.']);
        }

        $createdSheets = collect();
        try {
            $pages = [];
            foreach ($students as $siswa) {
                if (in_array($documentType, ['monthly', 'duplex'], true)) {
                    $monthly = $this->createMonthlySheet($siswa, $request->user()->id);
                    $createdSheets->push($monthly['sheet']);
                    $pages[] = $this->documents->monthlyPage($monthly['sheet'], $monthly['token']);
                }
                if (in_array($documentType, ['surah_reference', 'duplex'], true)) {
                    $pages[] = $this->documents->referencePage($siswa);
                }
            }

            $label = match ($documentType) {
                'surah_reference' => 'Peta-Referensi-Khatam',
                'duplex' => 'Paket-Bolak-Balik-Bacaan-Quran',
                default => 'Lembar-Bacaan-Bulanan',
            };
            $filename = $label.'-'.$students->count().'-Generus-'.now()->format('Y-m-d').'.pdf';

            return $this->documents->bulkDocuments($pages, $filename);
        } catch (\Throwable $exception) {
            QuranReadingSheet::whereIn('id', $createdSheets->pluck('id'))->whereDoesntHave('scans')->delete();
            throw $exception;
        }
    }

    public function scanForm(Request $request, ?Siswa $siswa = null)
    {
        $this->ensureScanEnabled();

        if ($this->isStudentRoute($request)) {
            $this->ensureAlumniSubmissionEnabled($request->user('siswa'));
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
        if ($this->isStudentRoute($request)) {
            $this->ensureAlumniSubmissionEnabled($request->user('siswa'));
        }
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

    public function publicBarcodeIdentify(Request $request)
    {
        return $this->identifyBarcode($request, 'public');
    }

    public function studentBarcodeIdentify(Request $request)
    {
        return $this->identifyBarcode($request, 'siswa');
    }

    public function operationalBarcodeIdentify(Request $request)
    {
        return $this->identifyBarcode($request, 'operational');
    }

    public function publicBarcodeStore(Request $request)
    {
        return $this->storeBarcode($request, 'public');
    }

    public function studentBarcodeStore(Request $request)
    {
        return $this->storeBarcode($request, 'siswa');
    }

    public function operationalBarcodeStore(Request $request)
    {
        return $this->storeBarcode($request, 'operational');
    }

    private function identifyBarcode(Request $request, string $context)
    {
        $this->ensureScanEnabled();
        $data = $request->validate([
            'sheet_payload' => ['required', 'string', 'max:500'],
        ], [
            'sheet_payload.required' => 'Barcode belum terbaca. Scan barcode terlebih dahulu.',
        ]);
        $sheet = $this->scans->resolveSheet($data['sheet_payload']);
        $sheet->load('siswa');
        $this->authorizeBarcodeStudent($request, $sheet->siswa, $context);
        $flow = $this->barcodeFlows->create($request, $sheet, $context);
        $siswa = $sheet->siswa;

        return response()->json([
            'flow_id' => $flow['id'],
            'expires_at' => $flow['expires_at'],
            'student' => [
                'name' => $siswa->nama,
                'masked_nis' => $this->maskedNis($siswa->nis),
                'school_grade' => $siswa->school_grade_label ?: 'Belum dikonfirmasi',
                'group' => $siswa->kelompok_label ?: ($siswa->kelompok ?: 'Belum diisi'),
            ],
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function storeBarcode(Request $request, string $context)
    {
        $this->ensureScanEnabled();
        $data = $request->validate([
            'flow_id' => ['required', 'string', 'size:40', 'alpha_num'],
            'surah_start' => ['required', 'integer', 'between:1,114'],
            'ayah_start' => ['required', 'integer', 'between:1,286'],
            'cross_surah' => ['nullable', 'boolean'],
            'surah_end' => ['nullable', 'required_if:cross_surah,1', 'integer', 'between:1,114'],
            'ayah_end' => ['required', 'integer', 'between:1,286'],
            'page_start' => ['nullable', 'required_with:page_end', 'integer', 'between:1,1000'],
            'page_end' => ['nullable', 'required_with:page_start', 'integer', 'between:1,1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $flow = $this->barcodeFlows->get($request, $data['flow_id'], $context);
        $existing = ! empty($flow['completed_entry_id'])
            ? QuranReadingEntry::query()->find($flow['completed_entry_id'])
            : null;
        if ($existing) {
            return $this->barcodeStoredResponse($existing, $context);
        }

        $sheet = QuranReadingSheet::query()->with('siswa')->findOrFail($flow['sheet_id']);
        abort_unless($sheet->status === 'active' && (int) $sheet->siswa_id === (int) $flow['siswa_id'], 404);
        $this->authorizeBarcodeStudent($request, $sheet->siswa, $context);
        $data['surah_end'] = $request->boolean('cross_surah') ? (int) $data['surah_end'] : (int) $data['surah_start'];
        $data['page_start'] = isset($data['page_start']) ? (int) $data['page_start'] : null;
        $data['page_end'] = isset($data['page_end']) ? (int) $data['page_end'] : null;
        $data['reading_date'] = now()->toDateString();
        $this->validateReadingRange($data);

        $entry = DB::transaction(function () use ($request, $sheet, $context, $data) {
            $isOperational = $context === 'operational';
            $actorId = $context === 'siswa' ? $request->user('siswa')->id : ($isOperational ? $request->user()->id : null);

            return $sheet->siswa->quranReadingEntries()->create([
                'reading_date' => $data['reading_date'],
                'page_start' => $data['page_start'],
                'page_end' => $data['page_end'],
                'surah_start' => $data['surah_start'],
                'ayah_start' => $data['ayah_start'],
                'surah_end' => $data['surah_end'],
                'ayah_end' => $data['ayah_end'],
                'notes' => $data['notes'] ?? null,
                'source' => 'barcode_manual',
                'submitted_by_type' => $context === 'siswa' ? 'siswa' : ($isOperational ? 'user' : 'public'),
                'submitted_by_id' => $actorId,
                'status' => $isOperational ? QuranReadingEntry::STATUS_VERIFIED : QuranReadingEntry::STATUS_PENDING,
                'verified_by' => $isOperational ? $actorId : null,
                'verified_at' => $isOperational ? now() : null,
                'sheet_id' => $sheet->id,
            ]);
        });
        $this->barcodeFlows->markCompleted($request, $data['flow_id'], $entry);

        return $this->barcodeStoredResponse($entry, $context);
    }

    private function barcodeStoredResponse(QuranReadingEntry $entry, string $context)
    {
        $redirect = match ($context) {
            'siswa' => route('siswa.quran.index', ['tab' => 'rekap']).'#rekap',
            'operational' => route('quran.index', ['tab' => 'rekap', 'siswa_id' => $entry->siswa_id]).'#rekap',
            default => route('public.scanner', ['mode' => 'quran']).'#quran',
        };

        return response()->json([
            'message' => $context === 'operational'
                ? 'Catatan bacaan tersimpan dan langsung terverifikasi.'
                : 'Catatan bacaan berhasil dikirim untuk verifikasi.',
            'redirect' => $redirect,
            'entry_id' => $entry->id,
        ], 201)->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function authorizeBarcodeStudent(Request $request, Siswa $siswa, string $context): void
    {
        if ($context === 'siswa') {
            if ((int) $request->user('siswa')?->id !== (int) $siswa->id) {
                throw new AccessDeniedHttpException('Barcode bukan milik akun ini.');
            }
        } elseif ($context === 'operational') {
            $user = $request->user();
            if (! $user || ($user->isTeacher() && ! in_array($siswa->id, $user->getAssignedSiswaIds(), true))) {
                throw new AccessDeniedHttpException('Generus berada di luar binaan akun ini.');
            }
        }

        if ($context !== 'operational') {
            $this->ensureAlumniSubmissionEnabled($siswa);
        }
    }

    private function maskedNis(?string $nis): string
    {
        $value = trim((string) $nis);
        if ($value === '') {
            return 'Belum tersedia';
        }

        $visible = mb_substr($value, -3);

        return str_repeat('•', max(2, mb_strlen($value) - 3)).$visible;
    }

    public function publicScanUpload(StoreQuranReadingScanRequest $request)
    {
        $this->ensureScanEnabled();
        $data = $request->validated();
        $sheet = $this->scans->resolveSheet($data['sheet_payload']);
        $this->ensureAlumniSubmissionEnabled($sheet->siswa);
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

    public function openPublicScan(Request $request, string $code)
    {
        $this->ensureScanEnabled();

        try {
            $payload = $this->scans->payloadFromPublicCode($code);
            $this->scans->resolveSheet($payload);
        } catch (ValidationException) {
            abort(404, 'Tautan lembar tidak valid atau sudah tidak aktif.');
        }

        $request->session()->put('quran_scan_prefill', [
            'payload' => $payload,
            'created_at' => now()->timestamp,
        ]);

        return redirect()
            ->to(route('public.scanner', ['mode' => 'quran']).'#quran')
            ->withHeaders([
                'Cache-Control' => 'private, no-store, max-age=0',
                'Referrer-Policy' => 'no-referrer',
            ]);
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
        if ($isStudent || $isPublic) {
            $this->ensureAlumniSubmissionEnabled($scan->siswa);
        }
        abort_if($scan->status === 'expired', 410, 'Foto scan kedaluwarsa dan sudah dibersihkan. Silakan unggah ulang lembar.');

        $scan->load(['siswa.kelas', 'sheet.cycle']);
        if ($scan->sheet?->sheet_type === 'surah_map') {
            return view('quran-reading.khatam-scan-confirm', [
                'scan' => $scan,
                'isStudent' => $isStudent,
                'isPublic' => $isPublic,
                'theme' => $isPublic ? ThemeSetting::current() : null,
                'catalog' => QuranCatalog::class,
                'khatam' => $this->khatam->summary($scan->sheet->cycle),
            ]);
        }

        return view('quran-reading.scan-confirm', [
            'scan' => $scan,
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
        if ($isStudent || $isPublic) {
            $this->ensureAlumniSubmissionEnabled($scan->siswa);
        }
        abort_if($scan->status === 'expired', 410, 'Foto scan kedaluwarsa dan sudah dibersihkan. Silakan unggah ulang lembar.');
        abort_if($scan->status === 'confirmed', 409, 'Hasil scan ini sudah dikonfirmasi.');
        if ($scan->sheet?->sheet_type === 'surah_map') {
            return $this->confirmKhatamScanResponse($request, $scan, $isStudent, $isPublic);
        }
        $rows = $request->validated()['rows'];
        $ocrSuggestion = json_decode((string) ($request->validated()['ocr_suggestion'] ?? ''), true);
        if (! is_array($ocrSuggestion)) {
            $ocrSuggestion = $scan->metadata['ocr_suggestion'] ?? [];
        }
        $maximumRow = max(1, min(31, (int) ($scan->sheet?->row_count ?: 12)));
        if (collect($rows)->contains(fn ($row) => (int) $row['row_number'] > $maximumRow)) {
            throw ValidationException::withMessages([
                'rows' => "Nomor baris tidak boleh melebihi {$maximumRow} untuk lembar ini.",
            ]);
        }

        $actor = $isPublic ? null : ($isStudent ? $request->user('siswa') : $request->user());
        $needsVerification = $isStudent || $isPublic;

        DB::transaction(function () use ($rows, $ocrSuggestion, $scan, $needsVerification, $isStudent, $isPublic, $actor) {
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

            $metadata = $scan->metadata ?? [];
            $metadata['ocr_suggestion'] = $ocrSuggestion;
            $scan->update([
                'status' => 'confirmed',
                'extracted_rows' => $rows,
                'metadata' => $metadata,
                'confirmed_at' => now(),
            ]);
        });

        if (! $needsVerification) {
            $this->scans->purgeFilesIfComplete($scan->fresh('entries:id,scan_id,status'));
        }

        $target = match (true) {
            $isPublic => route('public.scanner', ['mode' => 'quran']).'#quran',
            $isStudent => route('siswa.quran.index', ['tab' => 'rekap']).'#rekap',
            default => route('quran.index', ['tab' => 'rekap', 'siswa_id' => $scan->siswa_id]).'#rekap',
        };

        return redirect()->to($target)
            ->with('success', $needsVerification
                ? ($scan->siswa->isGraduated() ? 'Hasil scan dikirim untuk verifikasi Admin.' : 'Hasil scan dikirim untuk verifikasi Pamong.')
                : 'Hasil scan disimpan dan terverifikasi.');
    }

    private function confirmKhatamScanResponse(ConfirmQuranReadingScanRequest $request, QuranReadingScan $scan, bool $isStudent, bool $isPublic)
    {
        $data = $request->validated();
        $cycle = $scan->sheet?->cycle;
        abort_unless($cycle && (int) $cycle->siswa_id === (int) $scan->siswa_id, 409, 'Siklus Peta Khatam tidak tersedia.');
        if (! empty($data['active_surah']) && (int) ($data['active_ayah'] ?? 0) > QuranCatalog::ayahCount((int) $data['active_surah'])) {
            throw ValidationException::withMessages(['active_ayah' => 'Ayat terakhir melebihi jumlah ayat surat yang dipilih.']);
        }

        $currentCompleted = $cycle->progress()->whereNotNull('completed_at')->pluck('surah_number');
        $completed = collect($data['completed_surahs'] ?? [])->map(fn ($n) => (int) $n)->diff($currentCompleted)->unique()->sort()->values()->all();
        $actor = $isPublic ? null : ($isStudent ? $request->user('siswa') : $request->user());
        $needsVerification = $isStudent || $isPublic;
        $ocrSuggestion = json_decode((string) ($data['ocr_suggestion'] ?? ''), true);
        if (! is_array($ocrSuggestion)) {
            $ocrSuggestion = $scan->metadata['ocr_suggestion'] ?? [];
        }

        $submission = DB::transaction(function () use ($data, $completed, $scan, $cycle, $actor, $isPublic, $isStudent, $ocrSuggestion) {
            $submission = QuranProgressSubmission::create([
                'siswa_id' => $scan->siswa_id,
                'cycle_id' => $cycle->id,
                'sheet_id' => $scan->sheet_id,
                'scan_id' => $scan->id,
                'marked_on' => $data['marked_on'] ?? now()->toDateString(),
                'completed_surahs' => $completed,
                'ambiguous_surahs' => collect($data['ambiguous_surahs'] ?? [])->map(fn ($n) => (int) $n)->unique()->values()->all(),
                'active_surah' => $data['active_surah'] ?? null,
                'active_ayah' => $data['active_ayah'] ?? null,
                'status' => QuranProgressSubmission::STATUS_PENDING,
                'submitted_by_type' => $isPublic ? 'public' : ($isStudent ? 'siswa' : 'user'),
                'submitted_by_id' => $actor?->id,
                'metadata' => ['ocr_suggestion' => $ocrSuggestion, 'baseline' => $scan->sheet->metadata],
            ]);
            $scan->update(['status' => 'confirmed', 'extracted_rows' => $data, 'confirmed_at' => now()]);

            return $submission;
        });

        if (! $needsVerification) {
            $this->khatam->applySubmission($submission, $actor->id);
            $this->scans->purgeFilesIfComplete($scan->fresh(['entries:id,scan_id,status', 'progressSubmission:id,scan_id,status']));
        }

        $target = match (true) {
            $isPublic => route('public.scanner', ['mode' => 'quran']).'#quran',
            $isStudent => route('siswa.quran.index', ['tab' => 'khatam']).'#khatam',
            default => route('quran.index', ['tab' => 'khatam', 'siswa_id' => $scan->siswa_id]).'#khatam',
        };

        return redirect()->to($target)->with('success', $needsVerification
            ? 'Peta Khatam dikirim untuk verifikasi Pamong.'
            : 'Progres Peta Khatam disimpan dan terverifikasi.');
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
        abort_if($scan->files_purged_at || (! $scan->original_path && ! $scan->processed_path), 410, 'Foto scan sudah dibersihkan setelah proses verifikasi selesai.');
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
            'khatam' => $this->khatam->summaryForStudent($siswa),
            'cycleHistory' => $siswa->quranReadingCycles()->latest('cycle_number')->get(),
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
        if ($data['page_start'] !== null && $data['page_end'] !== null && (int) $data['page_end'] < (int) $data['page_start']) {
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
        $page = $this->createMonthlySheet($siswa, $generatedBy);

        return $this->documents->sheet($page['sheet'], $page['token']);
    }

    private function newDuplex(Siswa $siswa, ?int $generatedBy)
    {
        $page = $this->createMonthlySheet($siswa, $generatedBy);

        return $this->documents->duplex($page['sheet'], $page['token']);
    }

    private function createMonthlySheet(Siswa $siswa, ?int $generatedBy): array
    {
        $latest = $siswa->quranReadingEntries()->where('status', QuranReadingEntry::STATUS_VERIFIED)->latest('reading_date')->first();
        $plainToken = bin2hex(random_bytes(16));
        $sheet = QuranReadingSheet::create([
            'siswa_id' => $siswa->id,
            'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'active',
            'row_count' => 31,
            'template_version' => 4,
            'sheet_type' => 'monthly',
            'generated_by' => $generatedBy,
            'last_position' => $latest ? [
                'reading_date' => $latest->reading_date?->toDateString(),
                'page_end' => $latest->page_end,
                'surah_end' => $latest->surah_end,
                'ayah_end' => $latest->ayah_end,
            ] : null,
        ]);

        return ['sheet' => $sheet, 'token' => $plainToken];
    }

    private function operationalStudentQuery(Request $request): Builder
    {
        $query = Siswa::active()
            ->with(['pamongAssignments.pamong:id,name'])
            ->orderBy('nama');
        $user = $request->user();
        if ($user?->isTeacher()) {
            $query->whereIn('id', $user->getAssignedSiswaIds());
        }
        if ($request->filled('school_grade')) $query->where('school_grade', $request->string('school_grade'));
        if ($request->filled('pamong_id')) $query->whereHas('pamongAssignments', fn ($assignment) => $assignment->where('pamong_id', $request->integer('pamong_id')));
        if ($request->filled('kelompok')) {
            $query->where('kelompok', $request->string('kelompok'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(fn (Builder $studentQuery) => $studentQuery
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('nis', 'like', "%{$search}%"));
        }

        return $query;
    }

    private function ensureAlumniSubmissionEnabled(?Siswa $siswa): void
    {
        abort_unless($siswa, 401);
        abort_if(
            $siswa->isGraduated() && ! $siswa->canSubmitAsAlumni(),
            403,
            'Pengiriman bacaan Alumni sedang dinonaktifkan oleh Admin.'
        );
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
