<?php

namespace App\Http\Controllers;

use App\Models\MateriTarget;
use App\Models\Siswa;
use App\Models\SiswaMateriTargetProgress;
use App\Support\TargetGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MateriTargetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['siswaToggle']);
        $this->middleware('auth.siswa')->only(['siswaToggle']);
        $this->middleware('pamong.permission:materi')->only(['index']);
        $this->middleware('pamong.permission:materi,create')->only(['store']);
        $this->middleware('pamong.permission:materi,edit')->only(['update', 'toggleProgress']);
        $this->middleware('pamong.permission:materi,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $gradeOptions = TargetGrade::options();
        $categoryOptions = MateriTarget::categoryOptions();
        $semesterOptions = MateriTarget::semesterOptions();
        $selectedGrade = $request->input('grade', TargetGrade::SMP_7);
        $selectedSemester = (int) $request->input('semester', MateriTarget::defaultSemester());
        $selectedCategory = $request->input('category', MateriTarget::defaultCategory());

        if (! array_key_exists($selectedGrade, $gradeOptions)) {
            $selectedGrade = TargetGrade::SMP_7;
        }

        if (! array_key_exists($selectedSemester, $semesterOptions)) {
            $selectedSemester = MateriTarget::defaultSemester();
        }

        if (! array_key_exists($selectedCategory, $categoryOptions)) {
            $selectedCategory = MateriTarget::defaultCategory();
        }

        $targets = MateriTarget::query()
            ->forGrade($selectedGrade)
            ->forSemester($selectedSemester)
            ->forCategory($selectedCategory)
            ->withCount(['progress as completed_count' => fn ($query) => $query->where('is_completed', true)])
            ->orderByRaw('semester is null')
            ->orderBy('semester')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $activeTargets = $targets->where('is_active', true)->values();
        $students = Siswa::query()
            ->active()
            ->with('kelas:id,nama')
            ->orderBy('kelas_id')
            ->orderBy('nama')
            ->get()
            ->filter(fn (Siswa $siswa) => $siswa->target_grade === $selectedGrade)
            ->values();

        $progressRows = SiswaMateriTargetProgress::query()
            ->whereIn('siswa_id', $students->pluck('id'))
            ->whereIn('materi_target_id', $activeTargets->pluck('id'))
            ->get()
            ->keyBy(fn (SiswaMateriTargetProgress $progress) => $progress->siswa_id . ':' . $progress->materi_target_id);

        return view('materi-targets.index', [
            'gradeOptions' => $gradeOptions,
            'categoryOptions' => $categoryOptions,
            'semesterOptions' => $semesterOptions,
            'selectedGrade' => $selectedGrade,
            'selectedSemester' => $selectedSemester,
            'selectedCategory' => $selectedCategory,
            'targets' => $targets,
            'activeTargets' => $activeTargets,
            'students' => $students,
            'progressRows' => $progressRows,
            'canCreate' => $this->canManageMateri('create'),
            'canEdit' => $this->canManageMateri('edit'),
            'canDelete' => $this->canManageMateri('delete'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedTarget($request);
        $validated['created_by'] = Auth::id();
        $validated['is_active'] = $request->boolean('is_active', true);

        MateriTarget::create($validated);

        return back()->with('success', 'Target materi berhasil dibuat.');
    }

    public function update(Request $request, MateriTarget $target)
    {
        $validated = $this->validatedTarget($request);
        $validated['is_active'] = $request->boolean('is_active');

        $target->update($validated);

        return back()->with('success', 'Target materi berhasil diperbarui.');
    }

    public function destroy(MateriTarget $target)
    {
        $target->delete();

        return back()->with('success', 'Target materi berhasil dihapus.');
    }

    public function toggleProgress(Request $request, Siswa $siswa, MateriTarget $target)
    {
        if ($siswa->target_grade !== $target->target_grade) {
            return back()->with('error', 'Target materi tidak sesuai dengan level kelas siswa.');
        }

        $completed = $request->boolean('completed');
        $this->setProgress($siswa, $target, $completed, 'user', Auth::id());

        return back()->with('success', 'Progress target materi berhasil diperbarui.');
    }

    public function siswaToggle(Request $request, MateriTarget $target)
    {
        $siswa = Auth::guard('siswa')->user();

        abort_if(! $siswa, 403);
        abort_if(! $target->is_active, 404);
        abort_if($siswa->target_grade !== $target->target_grade, 403);

        $completed = $request->boolean('completed');
        $this->setProgress($siswa, $target, $completed, 'siswa', $siswa->id);

        return back()->with('success', $completed ? 'Target materi ditandai selesai.' : 'Target materi ditandai belum selesai.');
    }

    protected function validatedTarget(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', Rule::in(MateriTarget::categoryValues())],
            'target_grade' => ['required', 'string', Rule::in(TargetGrade::values())],
            'semester' => ['nullable', 'integer', Rule::in(array_keys(MateriTarget::semesterOptions()))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    protected function setProgress(Siswa $siswa, MateriTarget $target, bool $completed, string $actorType, ?int $actorId): void
    {
        SiswaMateriTargetProgress::updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'materi_target_id' => $target->id,
            ],
            [
                'is_completed' => $completed,
                'completed_at' => $completed ? now() : null,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
            ]
        );
    }

    private function canManageMateri(string $operation): bool
    {
        $user = Auth::user();

        return $user && $user->hasPamongCrudPermission('materi', $operation);
    }
}
