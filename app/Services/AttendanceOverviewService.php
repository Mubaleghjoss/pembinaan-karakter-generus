<?php

namespace App\Services;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttendanceOverviewService
{
    public const UNASSIGNED_GROUP = '__unassigned__';

    public function groupSummary(User $user, string $date, array $filters = []): array
    {
        $students = $this->studentsForUser($user, $filters);
        $attendanceByStudent = Presensi::query()
            ->select(['id', 'siswa_id', 'status', 'jam_masuk', 'is_verified', 'qr_code_used', 'metadata'])
            ->whereDate('tanggal', $date)
            ->whereIn('siswa_id', $students->pluck('id'))
            ->latest('id')
            ->get()
            ->unique('siswa_id')
            ->keyBy('siswa_id');

        return collect($this->groupOptions())
            ->map(function (string $label, string $key) use ($students, $attendanceByStudent) {
                $members = $students
                    ->filter(fn (Siswa $student) => $this->groupKey($student) === $key)
                    ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values();
                $categories = [
                    'hadir' => [],
                    'sakit' => [],
                    'izin' => [],
                    'alpha' => [],
                    'belum_hadir' => [],
                ];

                foreach ($members as $student) {
                    $attendance = $attendanceByStudent->get($student->id);
                    $status = $this->normalizeStatus($attendance?->status);
                    $category = match ($status) {
                        'hadir', 'terlambat' => 'hadir',
                        'sakit' => 'sakit',
                        'izin' => 'izin',
                        'alpha' => 'alpha',
                        default => 'belum_hadir',
                    };
                    $categories[$category][] = [
                        'id' => $student->id,
                        'presensi_id' => $attendance?->id,
                        'nama' => $student->nama,
                        'nis' => $student->nis,
                        'kelas' => $student->school_grade_label ?: 'Kelas belum dikonfirmasi',
                        'school_grade' => $student->school_grade,
                        'foto_url' => $student->foto_url,
                        'status' => $status ?: 'belum_hadir',
                        'status_label' => $this->statusLabel($status),
                        'jam_masuk' => $this->formatTime($attendance?->jam_masuk),
                        'has_scan_proof' => (bool) ($attendance?->qr_code_used || $this->hasScanProof($attendance?->metadata)),
                    ];
                }

                return [
                    'key' => $key,
                    'label' => $label,
                    'total_siswa' => $members->count(),
                    'hadir_count' => count($categories['hadir']),
                    'sakit_count' => count($categories['sakit']),
                    'izin_count' => count($categories['izin']),
                    'alpha_count' => count($categories['alpha']),
                    'belum_hadir_count' => count($categories['belum_hadir']),
                ] + $categories;
            })
            ->values()
            ->all();
    }

    public function shareText(User $user, string $date, array $filters = [], ?string $onlyGroup = null): array
    {
        $groups = collect($this->groupSummary($user, $date, $filters));
        if ($onlyGroup) {
            $groups = $groups->where('key', $onlyGroup)->values();
        }

        $dateLabel = Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM YYYY');
        $lines = [
            '*REKAP PRESENSI GENERUS PKG*',
            'Tanggal: '.$dateLabel,
            'Total Generus: '.$groups->sum('total_siswa'),
        ];
        $filterLabels = [];
        if ($filters['school_grade'] ?? null) {
            $filterLabels[] = 'kelas '.($filters['school_grade']);
        }
        if ($filters['pamong_id'] ?? null) {
            $pamong = User::query()->find($filters['pamong_id'], ['id', 'name', 'username']);
            if ($pamong) {
                $filterLabels[] = 'Pamong '.($pamong->name ?: $pamong->username);
            }
        }
        if ($filters['kelompok'] ?? null) {
            $filterLabels[] = 'kelompok '.($this->groupOptions()[$filters['kelompok']] ?? $filters['kelompok']);
        }
        if ($filterLabels) {
            $lines[] = 'Filter: '.implode(', ', $filterLabels);
        }
        $lines[] = '';

        foreach ($groups as $group) {
            $lines[] = '*'.$group['label'].'* ('.$group['total_siswa'].' Generus)';
            $this->appendNames($lines, 'Hadir', $group['hadir'], true);
            $this->appendNames($lines, 'Sakit', $group['sakit']);
            $this->appendNames($lines, 'Izin', $group['izin']);
            $this->appendNames($lines, 'Alpa (Tanpa Keterangan)', $group['alpha']);
            $this->appendNames($lines, 'Belum Presensi', $group['belum_hadir']);
            $lines[] = '';
        }

        return [
            'title' => 'Rekap Presensi '.$dateLabel,
            'text' => trim(implode("\n", $lines)),
            'group_count' => $groups->count(),
            'student_count' => $groups->sum('total_siswa'),
        ];
    }

    public function studentsForUser(User $user, array $filters = []): Collection
    {
        $query = Siswa::query()
            ->select(['id', 'nis', 'nama', 'school_grade', 'kelompok', 'alamat', 'foto_path', 'status', 'is_active'])
            ->active()
            ->when($filters['school_grade'] ?? null, fn (Builder $builder, string $grade) => $builder->where('school_grade', $grade))
            ->when($filters['pamong_id'] ?? null, fn (Builder $builder, int $pamongId) => $builder->byPamong($pamongId));

        if (! $this->canViewAllStudents($user)) {
            $query->assignedTo($user->id);
        }

        $students = $query->orderBy('nama')->get();
        $groupFilter = $filters['kelompok'] ?? null;

        if ($groupFilter) {
            $students = $students
                ->filter(fn (Siswa $student) => $this->groupKey($student) === $groupFilter)
                ->values();
        }

        return $students;
    }

    public function groupOptions(): array
    {
        return Siswa::kelompokOptions() + [self::UNASSIGNED_GROUP => 'Belum Ada Data Kelompok'];
    }

    public function normalizeGroupFilter(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value === self::UNASSIGNED_GROUP) {
            return $value;
        }

        return Siswa::normalizeKelompok($value);
    }

    private function canViewAllStudents(User $user): bool
    {
        return $user->isAdmin() || $user->isPengurusPkg() || $user->isPamongExcluded();
    }

    private function groupKey(Siswa $student): string
    {
        $normalized = Siswa::normalizeKelompok($student->kelompok);

        return $normalized && array_key_exists($normalized, Siswa::kelompokOptions())
            ? $normalized
            : self::UNASSIGNED_GROUP;
    }

    private function normalizeStatus(?string $status): ?string
    {
        return $status === 'tidak_hadir' ? 'alpha' : $status;
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'alpha' => 'Alpa (Tanpa Keterangan)',
            default => 'Belum Presensi',
        };
    }

    private function formatTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function hasScanProof(?array $metadata): bool
    {
        return data_get($metadata, 'face.method') === 'face';
    }

    private function appendNames(array &$lines, string $label, array $students, bool $includeStatus = false): void
    {
        if ($students === []) {
            $lines[] = $label.': -';

            return;
        }

        $lines[] = $label.' ('.count($students).'):';
        foreach ($students as $index => $student) {
            $suffix = $includeStatus && $student['status'] === 'terlambat' ? ' (Terlambat)' : '';
            $lines[] = ($index + 1).'. '.$student['nama'].$suffix;
        }
    }
}
