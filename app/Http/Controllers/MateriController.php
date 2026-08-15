<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Models\MateriFolder;
use App\Models\MateriRppJournal;
use App\Models\MateriTarget;
use App\Models\ScheduleReminder;
use App\Models\Siswa;
use App\Models\SiswaMateriTargetProgress;
use App\Models\User;
use App\Services\MateriRppPlanner;
use App\Support\MateriFolderTree;
use App\Support\TargetGrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Controller for managing materi (learning materials).
 * 
 * **Feature: website-settings, Requirements 10.1, 10.2, 10.3, 10.4, 10.5**
 */
class MateriController extends Controller
{
    public function __construct()
    {
        $this->middleware('pamong.permission:materi')->only(['index', 'show']);
        $this->middleware('pamong.permission:materi,create')->only(['create', 'store', 'storeFolder']);
        $this->middleware('pamong.permission:materi,edit')->only(['edit', 'update', 'toggleStatus', 'publishRpp', 'updateFolder']);
        $this->middleware('pamong.permission:materi,delete')->only(['destroy']);
    }

    /**
     * Display a listing of materi for admin.
     * 
     * **Validates: Requirements 10.1**
     */
    public function index(Request $request)
    {
        $canCreateMateri = $this->canManageMateri('create');
        $canEditMateri = $this->canManageMateri('edit');
        $canDeleteMateri = $this->canManageMateri('delete');
        $canManageMateri = $canCreateMateri || $canEditMateri || $canDeleteMateri;

        $query = Materi::with(['creator', 'folder.parent']);

        if (! $canManageMateri) {
            $query->where('materi.is_active', true);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('materi.judul', 'like', "%{$search}%")
                  ->orWhere('materi.deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($canManageMateri && $request->filled('status')) {
            $query->where('materi.is_active', $request->status === 'active');
        }

        // Filter by month
        if ($request->filled('bulan')) {
            $query->whereMonth('materi.bulan', date('m', strtotime($request->bulan)))
                  ->whereYear('materi.bulan', date('Y', strtotime($request->bulan)));
        }

        if ($request->filled('folder_id')) {
            $folderIds = app(MateriFolderTree::class)->folderAndDescendantIds(
                $request->integer('folder_id'),
                ! $canManageMateri
            );

            $query->whereIn('materi.materi_folder_id', $folderIds);
        }

        $materi = $query
            ->leftJoin('materi_folders as folders', 'materi.materi_folder_id', '=', 'folders.id')
            ->leftJoin('materi_folders as parent_folders', 'folders.parent_id', '=', 'parent_folders.id')
            ->select('materi.*')
            ->orderByRaw('COALESCE(parent_folders.sort_order, folders.sort_order, 999999)')
            ->orderByRaw('COALESCE(parent_folders.name, folders.name)')
            ->orderByRaw('COALESCE(folders.sort_order, 999999)')
            ->orderBy('folders.name')
            ->orderBy('materi.bulan', 'desc')
            ->paginate(10)
            ->withQueryString();
        $materiFolders = $this->materiFolders();
        $folderCards = $this->folderCards($canManageMateri);
        $folderOptions = $this->folderOptions($canManageMateri);
        $targetAnalytics = $this->buildTargetProgressAnalytics($request, Auth::user());

        return view('materi.index', compact(
            'materi',
            'materiFolders',
            'folderCards',
            'folderOptions',
            'targetAnalytics',
            'canCreateMateri',
            'canEditMateri',
            'canDeleteMateri',
            'canManageMateri'
        ));
    }

    private function buildTargetProgressAnalytics(Request $request, User $user): array
    {
        $gradeOptions = TargetGrade::options();
        $categoryOptions = MateriTarget::categoryOptions();
        $semesterOptions = MateriTarget::semesterOptions();
        $selectedGrade = $request->input('analytics_grade');
        $selectedSemester = (int) $request->input('analytics_semester', MateriTarget::defaultSemester());

        if (! array_key_exists((string) $selectedGrade, $gradeOptions)) {
            $selectedGrade = null;
        }

        if (! array_key_exists($selectedSemester, $semesterOptions)) {
            $selectedSemester = MateriTarget::defaultSemester();
        }

        $studentsInScope = $this->materiAnalyticsStudentQuery($user)
            ->select(['id', 'nis', 'nama', 'tanggal_lahir', 'target_grade_override', 'school_grade', 'status', 'is_active'])
            ->get();

        $canEditSiswa = $user->hasPamongCrudPermission('siswa', 'edit');
        $unleveledStudents = $studentsInScope
            ->filter(fn (Siswa $siswa) => ! $siswa->target_grade)
            ->sortBy('nama')
            ->values()
            ->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'kelas' => $siswa->school_grade_label ?? 'Belum dikonfirmasi',
                'tanggal_lahir' => $siswa->tanggal_lahir?->translatedFormat('d M Y') ?? '-',
                'edit_url' => $canEditSiswa ? route('siswa.edit', $siswa) : null,
            ])
            ->all();

        $eligibleStudents = $studentsInScope
            ->filter(fn (Siswa $siswa) => $siswa->target_grade)
            ->when($selectedGrade, fn (Collection $students) => $students->filter(
                fn (Siswa $siswa) => $siswa->target_grade === $selectedGrade
            ))
            ->values();

        $targets = MateriTarget::active()
            ->forSemester($selectedSemester)
            ->when($selectedGrade, fn ($query) => $query->forGrade($selectedGrade))
            ->get(['id', 'category', 'target_grade', 'semester', 'title']);

        $targetsByGrade = $targets->groupBy('target_grade');
        $targetMap = $targets->keyBy('id');
        $studentGradeMap = $eligibleStudents
            ->mapWithKeys(fn (Siswa $siswa) => [$siswa->id => $siswa->target_grade]);

        $categoryRows = collect($categoryOptions)
            ->map(fn (string $label, string $category) => [
                'category' => $category,
                'label' => $label,
                'expected' => 0,
                'completed' => 0,
                'percentage' => 0,
            ])
            ->all();

        $expectedTotal = 0;

        foreach ($eligibleStudents as $student) {
            $studentTargets = $targetsByGrade->get($student->target_grade, collect());
            $expectedTotal += $studentTargets->count();

            foreach ($studentTargets as $target) {
                if (isset($categoryRows[$target->category])) {
                    $categoryRows[$target->category]['expected']++;
                }
            }
        }

        $completedTotal = 0;
        $validCompletedRows = collect();
        $studentIds = $eligibleStudents->pluck('id');
        $targetIds = $targets->pluck('id');

        if ($studentIds->isNotEmpty() && $targetIds->isNotEmpty()) {
            $completedRows = SiswaMateriTargetProgress::query()
                ->where('is_completed', true)
                ->whereIn('siswa_id', $studentIds)
                ->whereIn('materi_target_id', $targetIds)
                ->get(['siswa_id', 'materi_target_id', 'completed_at']);

            foreach ($completedRows as $progress) {
                $studentGrade = $studentGradeMap->get($progress->siswa_id);
                $target = $targetMap->get($progress->materi_target_id);

                if (! $target || $target->target_grade !== $studentGrade) {
                    continue;
                }

                $completedTotal++;
                $validCompletedRows->push($progress);

                if (isset($categoryRows[$target->category])) {
                    $categoryRows[$target->category]['completed']++;
                }
            }
        }

        foreach ($categoryRows as $category => $row) {
            $categoryRows[$category]['percentage'] = $this->percentage($row['completed'], $row['expected']);
        }

        $categoryRows = collect($categoryRows)
            ->filter(fn (array $row) => $row['expected'] > 0)
            ->values();

        $eligibleStudentMap = $eligibleStudents->keyBy('id');
        $completedStudents = $validCompletedRows
            ->groupBy('siswa_id')
            ->map(function (Collection $rows, int|string $studentId) use ($eligibleStudentMap, $targetMap, $categoryOptions) {
                $student = $eligibleStudentMap->get((int) $studentId);

                if (! $student) {
                    return null;
                }

                $completedTargets = $rows
                    ->map(function (SiswaMateriTargetProgress $progress) use ($targetMap, $categoryOptions) {
                        $target = $targetMap->get($progress->materi_target_id);

                        if (! $target) {
                            return null;
                        }

                        return [
                            'title' => $target->title,
                            'category' => $categoryOptions[$target->category] ?? $target->category,
                            'completed_at' => $progress->completed_at?->translatedFormat('d M Y') ?? '-',
                        ];
                    })
                    ->filter()
                    ->sortBy(fn (array $target) => $target['category'] . '|' . $target['title'])
                    ->values();

                return [
                    'id' => $student->id,
                    'nama' => $student->nama,
                    'nis' => $student->nis,
                    'kelas' => $student->kelas?->nama ?? 'Tanpa kelas',
                    'completed_count' => $completedTargets->count(),
                    'targets' => $completedTargets,
                ];
            })
            ->filter()
            ->sortBy('nama')
            ->values();

        $showCompletedDetails = $request->input('analytics_detail') === 'completed';
        $completedDetailQuery = $request->query();
        $completedDetailQuery['analytics_detail'] = 'completed';
        $completedDetailUrl = route('materi.index', $completedDetailQuery) . '#analytics-completed-details';
        unset($completedDetailQuery['analytics_detail']);
        $completedDetailCloseUrl = route('materi.index', $completedDetailQuery) . '#target-analytics';

        return [
            'grade_options' => $gradeOptions,
            'semester_options' => $semesterOptions,
            'selected_grade' => $selectedGrade,
            'selected_semester' => $selectedSemester,
            'student_count' => $eligibleStudents->count(),
            'scope_student_count' => $studentsInScope->count(),
            'unleveled_count' => count($unleveledStudents),
            'unleveled_students' => $unleveledStudents,
            'target_total' => $expectedTotal,
            'completed_total' => $completedTotal,
            'completed_student_count' => $completedStudents->count(),
            'completed_students' => $completedStudents,
            'show_completed_details' => $showCompletedDetails,
            'completed_detail_url' => $completedDetailUrl,
            'completed_detail_close_url' => $completedDetailCloseUrl,
            'percentage' => $this->percentage($completedTotal, $expectedTotal),
            'categories' => $categoryRows,
            'scope_label' => ($user->isAdmin() || $user->isPengurusPkg() || $user->isPamongExcluded())
                ? 'semua siswa aktif'
                : 'siswa binaan',
        ];
    }

    private function materiAnalyticsStudentQuery(User $user)
    {
        $query = Siswa::query()->active();

        if ($user->isAdmin() || $user->isPengurusPkg() || $user->isPamongExcluded()) {
            return $query;
        }

        return $query->forUser($user);
    }

    private function percentage(int $completed, int $expected): int
    {
        if ($expected < 1) {
            return 0;
        }

        return (int) round(($completed / $expected) * 100);
    }

    /**
     * Show the form for creating a new materi.
     * 
     * **Validates: Requirements 10.2**
     */
    public function create()
    {
        return view('materi.create', [
            'pamongOptions' => $this->rppTeacherOptions(),
            'materiFolders' => $this->materiFolders(),
        ]);
    }

    /**
     * Store a newly created materi.
     * 
     * **Validates: Requirements 10.2**
     */
    public function store(Request $request, MateriRppPlanner $planner)
    {
        $request->validate($this->materiValidationRules());

        $publishRpp = $this->shouldPublishRpp($request);
        $rppPlan = $this->resolveRppPlan($request, $planner, $publishRpp);

        $data = $this->materiPayload($request, $rppPlan, $publishRpp);
        $data['created_by'] = Auth::id();
        $data['is_active'] = true;

        // Handle multiple PDF uploads
        if ($request->hasFile('pdf_files')) {
            $pdfFiles = [];
            foreach ($request->file('pdf_files') as $file) {
                $path = $file->store('materi/pdf', 'public');
                $pdfFiles[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString()
                ];
            }
            $pdfCount = count($pdfFiles);
            $pdfFiles = array_map(function (array $pdf, int $index) use ($data, $pdfCount) {
                $pdf['name'] = Materi::pdfFileNameForTitle($data['judul'], $index, $pdfCount);

                return $pdf;
            }, $pdfFiles, array_keys($pdfFiles));
            $data['pdf_path'] = $pdfFiles;
        }

        $materi = DB::transaction(function () use ($data, $publishRpp, $planner) {
            $materi = Materi::create($data);

            if ($publishRpp) {
                $this->syncRppCalendarEvents($materi, $planner);
            }

            return $materi;
        });

        $message = $publishRpp
            ? 'Materi dan RPP berhasil dipublikasikan ke kalender.'
            : ($request->boolean('rpp_is_enabled')
                ? 'Materi berhasil disimpan sebagai draft RPP.'
                : 'Materi berhasil disimpan tanpa RPP kalender.');

        return redirect()->route('materi.show', $materi)
            ->with('success', $message);
    }

    public function rppPreview(Request $request, MateriRppPlanner $planner): JsonResponse
    {
        if (! ($this->canManageMateri('create') || $this->canManageMateri('edit'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghitung preview RPP.',
            ], 403);
        }

        $request->validate([
            'rpp_total_pages' => 'required|integer|min:1',
            'rpp_start_page' => 'nullable|integer|min:1',
            'rpp_pages_per_session' => 'required|integer|min:1',
            'rpp_start_date' => 'required|date',
            'rpp_start_time' => 'nullable|required_with:rpp_end_time|date_format:H:i',
            'rpp_end_time' => 'nullable|required_with:rpp_start_time|date_format:H:i|after:rpp_start_time',
            'rpp_extra_sessions' => 'nullable|array',
            'rpp_extra_sessions.*.date' => 'nullable|date',
            'rpp_extra_sessions.*.pages' => 'nullable|integer|min:1',
            'rpp_catch_up_ranges' => 'nullable|array',
            'rpp_catch_up_ranges.*.start_date' => 'nullable|date',
            'rpp_catch_up_ranges.*.end_date' => 'nullable|date',
            'rpp_catch_up_ranges.*.pages' => 'nullable|integer|min:1',
            'rpp_teacher_pool' => 'nullable|array',
            'rpp_teacher_pool.*.user_id' => 'nullable|integer|exists:users,id',
            'rpp_teacher_pool.*.name' => 'nullable|string|max:255',
            'judul' => 'nullable|string|max:255',
        ]);

        try {
            $plan = $planner->plan($this->rppPlannerInput($request));
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $previewSessions = collect($plan['sessions']);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_sessions' => $plan['total_sessions'],
                'end_date' => $plan['end_date'],
                'total_pages' => $plan['total_pages'],
                'pages_per_session' => $plan['pages_per_session'],
                'start_time' => $plan['start_time'],
                'end_time' => $plan['end_time'],
            ],
            'sessions' => $previewSessions->take(8)->concat($previewSessions->slice(-3))->unique('number')->values(),
            'share_text' => $this->rppShareText($request->input('judul'), $plan),
        ]);
    }

    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'parent_id' => 'nullable|integer|exists:materi_folders,id',
            'description' => 'nullable|string|max:500',
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $maxOrder = MateriFolder::query()
            ->where('parent_id', $parentId)
            ->max('sort_order') ?? 0;

        MateriFolder::create([
            'name' => $validated['name'],
            'parent_id' => $parentId,
            'description' => $validated['description'] ?? null,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Folder materi berhasil dibuat.');
    }

    public function updateFolder(Request $request, MateriFolder $folder)
    {
        $excludedParentIds = app(MateriFolderTree::class)->folderAndDescendantIds((int) $folder->id, false);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'parent_id' => [
                'nullable',
                'integer',
                'exists:materi_folders,id',
                Rule::notIn($excludedParentIds),
            ],
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'is_active' => 'nullable|boolean',
        ]);

        $folder->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Folder materi berhasil diperbarui.');
    }

    protected function materiValidationRules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'materi_folder_id' => 'nullable|integer|exists:materi_folders,id',
            'deskripsi' => 'required|string',
            'bulan' => 'required|date',
            'calendar_date' => 'nullable|date',
            'pdf_files' => 'nullable|array',
            'pdf_files.*' => 'file|mimes:pdf|max:10240', // 10MB max per file
            'video_url' => 'nullable|url|max:2048',
            'video_links' => 'nullable|array',
            'video_links.*' => 'nullable|url|max:2048',
            'remove_pdfs' => 'nullable|array',
            'rpp_action' => 'nullable|in:draft,publish',
            'publish_rpp' => 'nullable|boolean',
            'rpp_is_enabled' => 'nullable|boolean',
            'rpp_total_pages' => 'nullable|required_if:rpp_is_enabled,1|integer|min:1',
            'rpp_start_page' => 'nullable|integer|min:1',
            'rpp_pages_per_session' => 'nullable|required_if:rpp_is_enabled,1|integer|min:1',
            'rpp_start_date' => 'nullable|required_if:rpp_is_enabled,1|date',
            'rpp_start_time' => 'nullable|required_with:rpp_end_time|date_format:H:i',
            'rpp_end_time' => 'nullable|required_with:rpp_start_time|date_format:H:i|after:rpp_start_time',
            'rpp_extra_sessions' => 'nullable|array',
            'rpp_extra_sessions.*.date' => 'nullable|date',
            'rpp_extra_sessions.*.pages' => 'nullable|integer|min:1',
            'rpp_catch_up_ranges' => 'nullable|array',
            'rpp_catch_up_ranges.*.start_date' => 'nullable|date',
            'rpp_catch_up_ranges.*.end_date' => 'nullable|date',
            'rpp_catch_up_ranges.*.pages' => 'nullable|integer|min:1',
            'rpp_teacher_pool' => 'nullable|array',
            'rpp_teacher_pool.*.user_id' => 'nullable|integer|exists:users,id',
            'rpp_teacher_pool.*.name' => 'nullable|string|max:255',
        ];
    }

    protected function materiPayload(Request $request, ?array $rppPlan, bool $publishRpp): array
    {
        $rppEnabled = $request->boolean('rpp_is_enabled');
        $videoLinks = Materi::normalizeVideoLinksInput(array_merge(
            $request->filled('video_url') ? [$request->input('video_url')] : [],
            $request->input('video_links', [])
        ));

        $payload = [
            'judul' => $request->judul,
            'materi_folder_id' => $request->input('materi_folder_id') ?: null,
            'deskripsi' => $request->deskripsi,
            'bulan' => $request->bulan,
            'video_url' => $videoLinks[0] ?? null,
            'rpp_is_enabled' => $rppEnabled,
            'rpp_status' => $rppEnabled && $publishRpp ? 'published' : 'draft',
            'rpp_total_pages' => $rppEnabled ? $request->integer('rpp_total_pages') : null,
            'rpp_start_page' => $rppEnabled ? max(1, $request->integer('rpp_start_page', 1)) : null,
            'rpp_pages_per_session' => $rppEnabled ? $request->integer('rpp_pages_per_session') : null,
            'rpp_start_date' => $rppEnabled ? $request->input('rpp_start_date') : null,
            'rpp_start_time' => $rppEnabled ? $request->input('rpp_start_time') : null,
            'rpp_end_time' => $rppEnabled ? $request->input('rpp_end_time') : null,
            'rpp_end_date' => $rppEnabled && $rppPlan ? $rppPlan['end_date'] : null,
            'rpp_extra_sessions' => $rppEnabled ? $this->normalizedExtraSessionsForStorage($request, $rppPlan) : null,
            'rpp_catch_up_ranges' => $rppEnabled ? $this->normalizedCatchUpRangesForStorage($request, $rppPlan) : null,
            'rpp_teacher_pool' => $rppEnabled ? $this->normalizedTeacherPoolForStorage($request, $rppPlan) : null,
            'rpp_teacher_overrides' => null,
            'rpp_published_at' => $rppEnabled && $publishRpp ? now() : null,
        ];

        if (Schema::hasColumn('materi', 'video_links')) {
            $payload['video_links'] = $videoLinks ?: null;
        }

        if (Schema::hasColumn('materi', 'calendar_date')) {
            $payload['calendar_date'] = $request->input('calendar_date') ?: null;
        }

        return $payload;
    }

    protected function shouldPublishRpp(Request $request): bool
    {
        if (! $request->boolean('rpp_is_enabled')) {
            return false;
        }

        return $request->input('rpp_action') === 'publish'
            || $request->boolean('publish_rpp');
    }


    /**
     * Display the specified materi.
     * 
     * **Validates: Requirements 10.4**
     */
    public function show(Materi $materi)
    {
        $canEditMateri = $this->canManageMateri('edit');
        $canDeleteMateri = $this->canManageMateri('delete');

        if (! ($canEditMateri || $canDeleteMateri) && ! $materi->is_active) {
            abort(404);
        }

        $materi->loadMissing('folder.parent');
        $rppJournals = MateriRppJournal::query()
            ->with(['creator', 'updater', 'scheduleReminder'])
            ->where('materi_id', $materi->id)
            ->orderByDesc('journal_date')
            ->orderByDesc('updated_at')
            ->get();

        return view('materi.show', compact('materi', 'canEditMateri', 'canDeleteMateri', 'rppJournals'));
    }

    /**
     * Show the form for editing the specified materi.
     * 
     * **Validates: Requirements 10.3**
     */
    public function edit(Materi $materi)
    {
        return view('materi.edit', [
            'materi' => $materi,
            'pamongOptions' => $this->rppTeacherOptions(),
            'materiFolders' => $this->materiFolders(),
        ]);
    }

    /**
     * Update the specified materi.
     * 
     * **Validates: Requirements 10.3**
     */
    public function update(Request $request, Materi $materi, MateriRppPlanner $planner)
    {
        $request->validate($this->materiValidationRules());

        $publishRpp = $this->shouldPublishRpp($request);
        $rppPlan = $this->resolveRppPlan($request, $planner, $publishRpp);
        $data = $this->materiPayload($request, $rppPlan, $publishRpp);

        // Get existing PDF files
        $existingPdfs = $materi->pdf_path ?? [];

        // Handle PDF removal
        if ($request->filled('remove_pdfs')) {
            $removePdfs = $request->remove_pdfs;
            $existingPdfs = array_filter($existingPdfs, function ($pdf, $index) use ($removePdfs) {
                if (in_array($index, $removePdfs)) {
                    // Delete the file
                    Storage::disk('public')->delete($pdf['path']);
                    return false;
                }
                return true;
            }, ARRAY_FILTER_USE_BOTH);
            $existingPdfs = array_values($existingPdfs); // Re-index array
        }

        // Handle new PDF uploads
        if ($request->hasFile('pdf_files')) {
            foreach ($request->file('pdf_files') as $file) {
                $path = $file->store('materi/pdf', 'public');
                $existingPdfs[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString()
                ];
            }
        }

        $pdfCount = count($existingPdfs);
        $existingPdfs = array_map(function (array $pdf, int $index) use ($data, $pdfCount) {
            $pdf['name'] = Materi::pdfFileNameForTitle($data['judul'], $index, $pdfCount);

            return $pdf;
        }, $existingPdfs, array_keys($existingPdfs));

        $data['pdf_path'] = !empty($existingPdfs) ? $existingPdfs : null;

        DB::transaction(function () use ($materi, $data, $publishRpp, $planner) {
            $materi->update($data);

            if ($publishRpp) {
                $this->syncRppCalendarEvents($materi->fresh(), $planner);
            } else {
                $this->deleteRppCalendarEvents($materi);
            }
        });

        $message = $publishRpp
            ? 'Materi dan RPP berhasil dipublikasikan ulang ke kalender.'
            : ($request->boolean('rpp_is_enabled')
                ? 'Materi berhasil diperbarui sebagai draft RPP.'
                : 'Materi berhasil diperbarui tanpa RPP kalender.');

        return redirect()->route('materi.show', $materi)
            ->with('success', $message);
    }

    /**
     * Toggle materi active status.
     */
    public function toggleStatus(Materi $materi, MateriRppPlanner $planner)
    {
        $materi->update(['is_active' => !$materi->is_active]);

        if ($materi->is_active && $materi->isRppPublished()) {
            $this->syncRppCalendarEvents($materi, $planner);
        } else {
            $this->deleteRppCalendarEvents($materi);
        }

        return redirect()->back()
            ->with('success', 'Status materi berhasil diubah.');
    }

    public function publishRpp(Materi $materi, MateriRppPlanner $planner)
    {
        if (! $materi->is_active) {
            return back()->with('error', 'Materi harus aktif sebelum RPP dipublikasikan ke kalender.');
        }

        if (! $materi->hasRpp()) {
            return back()->with('error', 'Lengkapi data RPP sebelum publikasi ke kalender.');
        }

        try {
            $plan = $planner->plan([
                'total_pages' => $materi->rpp_total_pages,
                'start_page' => $materi->rpp_start_page ?: 1,
                'pages_per_session' => $materi->rpp_pages_per_session,
                'start_date' => $materi->rpp_start_date?->toDateString(),
                'start_time' => $materi->rpp_start_time,
                'end_time' => $materi->rpp_end_time,
                'extra_sessions' => $materi->rpp_extra_sessions ?? [],
                'catch_up_ranges' => $materi->rpp_catch_up_ranges ?? [],
                'teacher_pool' => $materi->rpp_teacher_pool ?? [],
            ]);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        DB::transaction(function () use ($materi, $planner, $plan) {
            $materi->update([
                'rpp_status' => 'published',
                'rpp_end_date' => $plan['end_date'],
                'rpp_extra_sessions' => $plan['extra_sessions'],
                'rpp_catch_up_ranges' => $plan['catch_up_ranges'],
                'rpp_teacher_pool' => $plan['teacher_pool'],
                'rpp_teacher_overrides' => null,
                'rpp_published_at' => now(),
            ]);

            $this->syncRppCalendarEvents($materi->fresh(), $planner);
        });

        return back()->with('success', 'RPP berhasil dipublikasikan ke kalender.');
    }

    /**
     * Remove the specified materi.
     */
    public function destroy(Materi $materi)
    {
        ScheduleReminder::query()
            ->where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->delete();

        // Delete all PDF files if exists
        if ($materi->pdf_path && is_array($materi->pdf_path)) {
            foreach ($materi->pdf_path as $pdf) {
                if (isset($pdf['path'])) {
                    Storage::disk('public')->delete($pdf['path']);
                }
            }
        }

        $materi->delete();

        return redirect()->route('materi.index')
            ->with('success', 'Materi berhasil dihapus.');
    }

    /**
     * Display materi for siswa (current month prominently).
     * 
     * **Validates: Requirements 10.5**
     */
    public function siswaIndex(Request $request)
    {
        $materiFolders = $this->activeMateriListing();
        $siswa = Auth::guard('siswa')->user();
        $targetGrade = $siswa?->target_grade;
        $targetGradeLabel = TargetGrade::label($targetGrade);
        $categoryOptions = MateriTarget::categoryOptions();
        $semesterOptions = MateriTarget::semesterOptions();
        $selectedTargetSemester = (int) $request->input('target_semester', MateriTarget::defaultSemester());
        $selectedTargetCategory = $request->input('target_category', MateriTarget::defaultCategory());

        if (! array_key_exists($selectedTargetSemester, $semesterOptions)) {
            $selectedTargetSemester = MateriTarget::defaultSemester();
        }

        if (! array_key_exists($selectedTargetCategory, $categoryOptions)) {
            $selectedTargetCategory = MateriTarget::defaultCategory();
        }

        $materiTargets = collect();
        $targetProgress = collect();

        if ($siswa && $targetGrade) {
            $materiTargets = MateriTarget::active()
                ->forGrade($targetGrade)
                ->forSemester($selectedTargetSemester)
                ->forCategory($selectedTargetCategory)
                ->orderByRaw('semester is null')
                ->orderBy('semester')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();

            $targetProgress = SiswaMateriTargetProgress::query()
                ->where('siswa_id', $siswa->id)
                ->whereIn('materi_target_id', $materiTargets->pluck('id'))
                ->get()
                ->keyBy('materi_target_id');
        }

        return view('siswa.materi.index', compact(
            'materiFolders',
            'targetGrade',
            'targetGradeLabel',
            'categoryOptions',
            'semesterOptions',
            'selectedTargetSemester',
            'selectedTargetCategory',
            'materiTargets',
            'targetProgress'
        ));
    }

    /**
     * Display materi detail for siswa.
     * 
     * **Validates: Requirements 10.4**
     */
    public function siswaShow(Materi $materi)
    {
        if (!$materi->is_active) {
            abort(404);
        }

        $materi->loadMissing('folder.parent');
        return view('siswa.materi.show', compact('materi'));
    }

    public function ortuIndex()
    {
        $materiFolders = $this->activeMateriListing();

        return view('ortu.materi.index', compact('materiFolders'));
    }

    public function ortuShow(Materi $materi)
    {
        if (! $materi->is_active) {
            abort(404);
        }

        $materi->loadMissing('folder.parent');
        return view('ortu.materi.show', compact('materi'));
    }

    protected function resolveRppPlan(Request $request, MateriRppPlanner $planner, bool $publishRpp): ?array
    {
        if (! $request->boolean('rpp_is_enabled')) {
            if ($publishRpp) {
                throw ValidationException::withMessages([
                    'rpp_is_enabled' => 'Aktifkan RPP Kalender sebelum publikasi.',
                ]);
            }

            return null;
        }

        try {
            return $planner->plan($this->rppPlannerInput($request));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'rpp_total_pages' => $exception->getMessage(),
            ]);
        }
    }

    protected function rppPlannerInput(Request $request): array
    {
        return [
            'total_pages' => $request->input('rpp_total_pages'),
            'start_page' => $request->input('rpp_start_page') ?: 1,
            'pages_per_session' => $request->input('rpp_pages_per_session'),
            'start_date' => $request->input('rpp_start_date'),
            'start_time' => $request->input('rpp_start_time'),
            'end_time' => $request->input('rpp_end_time'),
            'extra_sessions' => collect($request->input('rpp_extra_sessions', []))
                ->filter(fn ($session) => is_array($session) && ! empty($session['date']))
                ->map(fn ($session) => [
                    'date' => $session['date'],
                    'pages' => $session['pages'] ?? null,
                ])
                ->values()
                ->all(),
            'catch_up_ranges' => collect($request->input('rpp_catch_up_ranges', []))
                ->filter(fn ($range) => is_array($range) && ! empty($range['start_date']) && ! empty($range['end_date']))
                ->map(fn ($range) => [
                    'start_date' => $range['start_date'],
                    'end_date' => $range['end_date'],
                    'pages' => $range['pages'] ?? null,
                ])
                ->values()
                ->all(),
            'teacher_pool' => $this->normalizedTeacherRows($request->input('rpp_teacher_pool', [])),
        ];
    }

    protected function normalizedExtraSessionsForStorage(Request $request, ?array $rppPlan): ?array
    {
        if ($rppPlan) {
            return $rppPlan['extra_sessions'];
        }

        $sessions = $this->rppPlannerInput($request)['extra_sessions'];

        return $sessions ?: null;
    }

    protected function normalizedCatchUpRangesForStorage(Request $request, ?array $rppPlan): ?array
    {
        if ($rppPlan) {
            return $rppPlan['catch_up_ranges'];
        }

        $ranges = $this->rppPlannerInput($request)['catch_up_ranges'];

        return $ranges ?: null;
    }

    protected function normalizedTeacherPoolForStorage(Request $request, ?array $rppPlan): ?array
    {
        if ($rppPlan) {
            return $rppPlan['teacher_pool'];
        }

        $teachers = $this->rppPlannerInput($request)['teacher_pool'];

        return $teachers ?: null;
    }

    protected function syncRppCalendarEvents(Materi $materi, MateriRppPlanner $planner): void
    {
        if (! $materi->is_active || ! $materi->hasRpp()) {
            $this->deleteRppCalendarEvents($materi);
            return;
        }

        $plan = $planner->plan([
            'total_pages' => $materi->rpp_total_pages,
            'start_page' => $materi->rpp_start_page ?: 1,
            'pages_per_session' => $materi->rpp_pages_per_session,
            'start_date' => $materi->rpp_start_date?->toDateString(),
            'start_time' => $materi->rpp_start_time,
            'end_time' => $materi->rpp_end_time,
            'extra_sessions' => $materi->rpp_extra_sessions ?? [],
            'catch_up_ranges' => $materi->rpp_catch_up_ranges ?? [],
            'teacher_pool' => $materi->rpp_teacher_pool ?? [],
        ]);

        $existingSchedules = ScheduleReminder::query()
            ->with(['rppJournal', 'journalAssignees'])
            ->where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->get();
        $existingBySession = $existingSchedules->keyBy(
            fn (ScheduleReminder $schedule) => (string) data_get($schedule->source_payload, 'number')
        );
        $keptScheduleIds = [];

        foreach ($plan['sessions'] as $session) {
            $teacherDescription = ! empty($session['teacher_name'])
                ? "\nPengajar {$session['teacher_name']}"
                : '';
            $scheduleData = [
                'title' => $materi->judul,
                'description' => "Materi {$materi->judul}\nPertemuan {$session['number']}\n{$session['page_range']}{$teacherDescription}",
                'start_date' => $session['date'],
                'end_date' => null,
                'start_time' => $this->normalizeRppTime($materi->rpp_start_time),
                'end_time' => $this->normalizeRppTime($materi->rpp_end_time),
                'target_audience' => 'all',
                'is_recurring' => false,
                'recurrence_pattern' => null,
                'recurrence_days' => null,
                'location' => 'Kegiatan PKG',
                'color' => '#14B8A6',
                'is_active' => true,
                'created_by' => Auth::id() ?: $materi->created_by,
                'source_type' => ScheduleReminder::SOURCE_MATERI_RPP,
                'source_id' => $materi->id,
                'source_payload' => $session + [
                    'materi_title' => $materi->judul,
                    'total_sessions' => $plan['total_sessions'],
                    'planned_end_date' => $plan['end_date'],
                ],
            ];

            $schedule = $existingBySession->get((string) $session['number']);

            if ($schedule) {
                $schedule->update($scheduleData);

                if (
                    ! $schedule->journal_assignee_type
                    && ! empty($session['teacher_user_id'])
                ) {
                    $schedule->update([
                        'journal_assignee_type' => 'user',
                        'journal_assignee_user_id' => $session['teacher_user_id'],
                        'journal_assignee_siswa_id' => null,
                    ]);
                }
            } else {
                $schedule = ScheduleReminder::create($scheduleData + [
                    'journal_assignee_type' => ! empty($session['teacher_user_id']) ? 'user' : null,
                    'journal_assignee_user_id' => $session['teacher_user_id'] ?? null,
                    'journal_assignee_siswa_id' => null,
                ]);
            }

            if (! empty($session['teacher_user_id'])) {
                $schedule->journalAssignees()->firstOrCreate([
                    'user_id' => $session['teacher_user_id'],
                ], [
                    'assignee_type' => 'user',
                    'siswa_id' => null,
                    'assigned_by' => Auth::id() ?: $materi->created_by,
                ]);
            }

            $keptScheduleIds[] = $schedule->id;
        }

        $existingSchedules
            ->reject(fn (ScheduleReminder $schedule) => in_array($schedule->id, $keptScheduleIds, true))
            ->each(function (ScheduleReminder $schedule) {
                if ($schedule->rppJournal) {
                    $schedule->update(['is_active' => false]);
                } else {
                    $schedule->delete();
                }
            });

        app(\App\Services\MateriRppJournalWorkflowService::class)->touchCache();
    }

    protected function deleteRppCalendarEvents(Materi $materi): void
    {
        ScheduleReminder::query()
            ->with('rppJournal')
            ->where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->get()
            ->each(function (ScheduleReminder $schedule) {
                if ($schedule->rppJournal) {
                    $schedule->update(['is_active' => false]);
                } else {
                    $schedule->delete();
                }
            });

        app(\App\Services\MateriRppJournalWorkflowService::class)->touchCache();
    }

    private function activeMateriListing(): Collection
    {
        return app(MateriFolderTree::class)->folderTree(
            includeInactiveFolders: false,
            includeInactiveMateri: false,
            includeEmptyRoots: true,
            includeUnfiled: true
        );
    }

    private function materiFolders(): Collection
    {
        return app(MateriFolderTree::class)->folderOptions();
    }

    private function folderCards(bool $includeInactiveMateri = false): Collection
    {
        return app(MateriFolderTree::class)->folderTree(
            includeInactiveFolders: $includeInactiveMateri,
            includeInactiveMateri: $includeInactiveMateri,
            includeEmptyRoots: true,
            includeUnfiled: true
        );
    }

    private function folderOptions(bool $includeInactiveFolders = false): Collection
    {
        return app(MateriFolderTree::class)->folderOptions($includeInactiveFolders);
    }

    private function canManageMateri(string $operation): bool
    {
        $user = Auth::user();

        return $user && $user->hasPamongCrudPermission('materi', $operation);
    }

    private function rppTeacherOptions()
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    private function normalizedTeacherRows(mixed $rows, bool $requireDate = false): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $ids = collect($rows)
            ->filter(fn ($row) => is_array($row) && ! empty($row['user_id']))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $namesById = $ids->isEmpty()
            ? collect()
            : User::query()
                ->whereIn('id', $ids)
                ->get(['id', 'name', 'username'])
                ->mapWithKeys(fn (User $user) => [
                    $user->id => $this->rppTeacherDisplayName($user),
                ]);

        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) use ($requireDate, $namesById) {
                $userId = ! empty($row['user_id']) ? (int) $row['user_id'] : null;
                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '' && $userId) {
                    $name = trim((string) ($namesById[$userId] ?? ''));
                }

                if ($name === '') {
                    return null;
                }

                $payload = [
                    'user_id' => $userId,
                    'name' => $name,
                    'is_manual' => ! $userId,
                ];

                if ($requireDate) {
                    if (empty($row['date'])) {
                        return null;
                    }

                    $payload = ['date' => $row['date']] + $payload;
                }

                return $payload;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function rppShareText(?string $judul, array $plan): string
    {
        $title = trim((string) $judul) ?: 'Materi RPP';
        $timeLabel = $this->rppTimeLabel($plan['start_time'] ?? null, $plan['end_time'] ?? null);
        $lines = [
            'RPP Materi PKG',
            'Judul: ' . $title,
            'Total Pertemuan: ' . $plan['total_sessions'],
            'Target Reguler: ' . $plan['pages_per_session'] . ' halaman per pertemuan',
            'Periode: ' . $this->formatRppDate($plan['start_date']) . ' s/d ' . $this->formatRppDate($plan['end_date']),
            $timeLabel ? 'Waktu: ' . $timeLabel : null,
            '',
            'Jadwal:',
        ];
        $lines = array_values(array_filter($lines, fn ($line) => $line !== null));

        foreach ($plan['sessions'] as $session) {
            $dateLabel = ($session['weekday_label'] ?? '-') . ', ' . $this->formatRppDate($session['date']);
            $typeLabel = ($session['type'] ?? null) === 'catch_up'
                ? ' (Kejar target ' . $this->formatRppDate($session['range_start_date'] ?? $session['date']) . ' s/d ' . $this->formatRppDate($session['range_end_date'] ?? $session['date']) . ')'
                : '';

            $lines[] = $session['number'] . '. ' . $dateLabel . $typeLabel;
            $lines[] = '   Materi: ' . ($session['page_range'] ?? '-');
            $lines[] = '   Pengajar: ' . ($session['teacher_name'] ?? '-');
        }

        return implode("\n", $lines);
    }

    private function formatRppDate(?string $date): string
    {
        if (! $date) {
            return '-';
        }

        return date('d-m-Y', strtotime($date));
    }

    private function normalizeRppTime(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function rppTimeLabel(?string $startTime, ?string $endTime): ?string
    {
        $start = $startTime ? substr($startTime, 0, 5) : null;
        $end = $endTime ? substr($endTime, 0, 5) : null;

        if (! $start) {
            return null;
        }

        return $end ? "{$start} - {$end}" : $start;
    }

    private function rppTeacherDisplayName(User $user): string
    {
        $name = trim((string) $user->name);

        if ($name !== '') {
            return $name;
        }

        $username = trim((string) $user->username);

        return $username !== '' ? $username : 'Akun #' . $user->id;
    }
}
