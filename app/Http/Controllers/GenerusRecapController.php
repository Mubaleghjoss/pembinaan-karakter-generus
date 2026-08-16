<?php

namespace App\Http\Controllers;

use App\Models\MateriTarget;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Models\SiswaMateriTargetProgress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GenerusRecapController extends Controller
{
    public function index(Request $request)
    {
        $query = array_merge($request->query(), [
            'tab' => 'rekap',
            'panel' => 'rekap-generus',
        ]);

        return redirect(route('presensi.index', $query).'#rekap-generus');
    }

    public function panel(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin() || $user->isPengurusPkg() || $user->hasPamongMenuAccess('presensi'),
            403
        );

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'semester' => ['nullable', 'integer', 'in:1,2'],
            'kelompok' => ['nullable', 'string', 'max:60'],
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : (isset($validated['end_date'])
                ? Carbon::parse($validated['end_date'])->startOfMonth()->startOfDay()
                : now()->startOfMonth()->startOfDay());
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : (isset($validated['start_date'])
                ? Carbon::parse($validated['start_date'])->endOfMonth()->endOfDay()
                : now()->endOfMonth()->endOfDay());
        $semester = (int) ($validated['semester'] ?? MateriTarget::defaultSemester());
        $selectedKelompok = Siswa::normalizeKelompok($validated['kelompok'] ?? null);
        $studentQuery = Siswa::query()
            ->active()
            ->select([
                'id',
                'nis',
                'nama',
                'tanggal_lahir',
                'target_grade_override',
                'kelas_id',
                'alamat',
                'kelompok',
                'status',
                'is_active',
            ]);

        if (! ($user->isAdmin() || $user->isPengurusPkg() || $user->isPamongExcluded())) {
            $studentQuery->forUser($user);
        }

        $students = $studentQuery
            ->orderBy('nama')
            ->get()
            ->filter(fn (Siswa $siswa) => array_key_exists((string) $siswa->kelompok, Siswa::kelompokOptions()))
            ->when($selectedKelompok, fn (Collection $items) => $items->where('kelompok', $selectedKelompok))
            ->values();

        [$rows, $totals] = $this->buildRecap(
            $students,
            $startDate,
            $endDate,
            $semester,
            $selectedKelompok
        );

        return view('presensi.partials.generus-report', [
            'rows' => $rows,
            'totals' => $totals,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'semester' => $semester,
            'semesterOptions' => MateriTarget::semesterOptions(),
            'kelompokOptions' => Siswa::kelompokOptions(),
            'selectedKelompok' => $selectedKelompok,
            'scopeLabel' => ($user->isAdmin() || $user->isPengurusPkg() || $user->isPamongExcluded())
                ? 'semua Generus aktif'
                : 'Generus binaan',
        ]);
    }

    private function buildRecap(
        Collection $students,
        Carbon $startDate,
        Carbon $endDate,
        int $semester,
        ?string $selectedKelompok
    ): array {
        $studentIds = $students->pluck('id');

        $taskByStudent = collect();
        $attendanceByStudent = collect();

        if ($studentIds->isNotEmpty()) {
            $taskByStudent = SiswaKarakterChecklist::query()
                ->whereIn('siswa_id', $studentIds)
                ->whereBetween('checked_at', [$startDate, $endDate])
                ->selectRaw('siswa_id, COUNT(*) as submitted_count, SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
                ->groupBy('siswa_id')
                ->get()
                ->keyBy('siswa_id');

            $attendanceByStudent = Presensi::query()
                ->whereIn('siswa_id', $studentIds)
                ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
                ->selectRaw("siswa_id, COUNT(*) as record_count, SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as present_count")
                ->groupBy('siswa_id')
                ->get()
                ->keyBy('siswa_id');
        }

        $targets = MateriTarget::query()
            ->active()
            ->forSemester($semester)
            ->get(['id', 'target_grade']);
        $targetsByGrade = $targets->groupBy('target_grade');
        $targetMap = $targets->keyBy('id');
        $completedByStudent = collect();

        if ($studentIds->isNotEmpty() && $targets->isNotEmpty()) {
            $completedByStudent = SiswaMateriTargetProgress::query()
                ->where('is_completed', true)
                ->whereIn('siswa_id', $studentIds)
                ->whereIn('materi_target_id', $targets->pluck('id'))
                ->get(['siswa_id', 'materi_target_id'])
                ->groupBy('siswa_id');
        }

        $studentMetrics = $students->mapWithKeys(function (Siswa $siswa) use (
            $taskByStudent,
            $attendanceByStudent,
            $targetsByGrade,
            $targetMap,
            $completedByStudent
        ) {
            $task = $taskByStudent->get($siswa->id);
            $attendance = $attendanceByStudent->get($siswa->id);
            $grade = $siswa->target_grade;
            $expectedTargetIds = $grade
                ? $targetsByGrade->get($grade, collect())->pluck('id')
                : collect();
            $completedTargets = $completedByStudent
                ->get($siswa->id, collect())
                ->filter(function (SiswaMateriTargetProgress $progress) use ($grade, $targetMap) {
                    return $grade && $targetMap->get($progress->materi_target_id)?->target_grade === $grade;
                });

            return [$siswa->id => [
                'task_submitted' => (int) ($task?->submitted_count ?? 0),
                'task_verified' => (int) ($task?->verified_count ?? 0),
                'attendance_records' => (int) ($attendance?->record_count ?? 0),
                'attendance_present' => (int) ($attendance?->present_count ?? 0),
                'rpp_expected' => $expectedTargetIds->count(),
                'rpp_completed' => $completedTargets->count(),
                'has_target_grade' => (bool) $grade,
            ]];
        });

        $groupOptions = collect(Siswa::kelompokOptions())
            ->when($selectedKelompok, fn (Collection $items) => $items->only($selectedKelompok));

        $rows = $groupOptions
            ->map(function (string $label, string $key) use ($students, $studentMetrics) {
                return $this->summarizeGroup(
                    $key,
                    $label,
                    $students->where('kelompok', $key)->values(),
                    $studentMetrics
                );
            })
            ->values();

        $totals = $this->summarizeGroup('all', 'Total Semua Kelompok', $students, $studentMetrics);

        return [$rows, $totals];
    }

    private function summarizeGroup(
        string $key,
        string $label,
        Collection $students,
        Collection $studentMetrics
    ): array {
        $metrics = $students
            ->map(fn (Siswa $siswa) => $studentMetrics->get($siswa->id))
            ->filter();

        $taskSubmitted = (int) $metrics->sum('task_submitted');
        $taskVerified = (int) $metrics->sum('task_verified');
        $attendanceRecords = (int) $metrics->sum('attendance_records');
        $attendancePresent = (int) $metrics->sum('attendance_present');
        $rppExpected = (int) $metrics->sum('rpp_expected');
        $rppCompleted = (int) $metrics->sum('rpp_completed');

        return [
            'key' => $key,
            'label' => $label,
            'total_students' => $students->count(),
            'task' => [
                'submitted' => $taskSubmitted,
                'verified' => $taskVerified,
                'pending' => max(0, $taskSubmitted - $taskVerified),
                'student_count' => $metrics->where('task_verified', '>', 0)->count(),
                'percentage' => $this->percentage($taskVerified, $taskSubmitted),
            ],
            'attendance' => [
                'records' => $attendanceRecords,
                'present' => $attendancePresent,
                'absent' => max(0, $attendanceRecords - $attendancePresent),
                'student_count' => $metrics->where('attendance_present', '>', 0)->count(),
                'percentage' => $this->percentage($attendancePresent, $attendanceRecords),
            ],
            'rpp' => [
                'expected' => $rppExpected,
                'completed' => $rppCompleted,
                'remaining' => max(0, $rppExpected - $rppCompleted),
                'student_count' => $metrics->where('rpp_completed', '>', 0)->count(),
                'without_grade' => $metrics->where('has_target_grade', false)->count(),
                'percentage' => $this->percentage($rppCompleted, $rppExpected),
            ],
        ];
    }

    private function percentage(int $completed, int $expected): int
    {
        return $expected > 0 ? (int) round(($completed / $expected) * 100) : 0;
    }
}
