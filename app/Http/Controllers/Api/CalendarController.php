<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Karakter;
use App\Models\Presensi;
use App\Models\ScheduleReminder;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Models\TracerKarakter;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ]);

        $start = CarbonImmutable::createFromFormat('Y-m-d', $validated['start'])->startOfDay();
        $end = CarbonImmutable::createFromFormat('Y-m-d', $validated['end'])->endOfDay();

        if ($start->diffInDays($end) > 92) {
            throw ValidationException::withMessages([
                'end' => ['Rentang kalender maksimal 93 hari.'],
            ]);
        }

        [$actor, $scope] = $this->actorScope($request);
        $events = [
            ...$this->attendanceEvents($request, $start, $end),
            ...$this->pkgTaskEvents($request, $start, $end),
            ...$this->characterEvents($request, $start, $end),
            ...$this->scheduleEvents($request, $start, $end),
        ];
        usort($events, fn (array $left, array $right) => [$left['start'], $left['title']] <=> [$right['start'], $right['title']]);

        return response()->json([
            'success' => true,
            'data' => $events,
            'meta' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'actor' => $actor,
                'scope' => $scope,
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function attendanceEvents(
        Request $request,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $actor = $request->user();

        if ($actor instanceof Siswa) {
            return Presensi::query()
                ->where('siswa_id', $actor->id)
                ->whereBetween('tanggal', [$start, $end])
                ->orderBy('tanggal')
                ->get()
                ->map(fn (Presensi $presensi) => [
                    'id' => 'presensi-'.$presensi->id,
                    'title' => 'Presensi: '.ucfirst($presensi->status),
                    'start' => $presensi->tanggal->toDateString(),
                    'end' => null,
                    'all_day' => true,
                    'type' => 'presensi',
                    'color' => $this->attendanceColor($presensi->status),
                    'details' => [
                        'status' => $presensi->status,
                        'jam_masuk' => $presensi->jam_masuk?->format('H:i'),
                        'jam_keluar' => $presensi->jam_keluar?->format('H:i'),
                    ],
                ])
                ->all();
        }

        if (! $actor instanceof User) {
            return [];
        }

        $siswaIds = $actor->isTeacher()
            ? $actor->getAssignedSiswaIds()
            : Siswa::query()->active()->pluck('id')->all();

        return Presensi::query()
            ->whereIn('siswa_id', $siswaIds ?: [0])
            ->whereBetween('tanggal', [$start, $end])
            ->selectRaw('tanggal, status, COUNT(*) as count')
            ->groupBy('tanggal', 'status')
            ->orderBy('tanggal')
            ->get()
            ->groupBy(fn ($row) => $row->tanggal->toDateString())
            ->map(function ($statuses, string $date): array {
                $counts = $statuses->pluck('count', 'status')->map(fn ($count) => (int) $count);
                $hadir = (int) ($counts['hadir'] ?? 0);
                $terlambat = (int) ($counts['terlambat'] ?? 0);
                $alpha = (int) ($counts['alpha'] ?? 0);

                return [
                    'id' => 'presensi-summary-'.$date,
                    'title' => "Hadir {$hadir} | Terlambat {$terlambat} | Alpha {$alpha}",
                    'start' => $date,
                    'end' => null,
                    'all_day' => true,
                    'type' => 'presensi_summary',
                    'color' => '#3B82F6',
                    'details' => [
                        'hadir' => $hadir,
                        'terlambat' => $terlambat,
                        'alpha' => $alpha,
                        'total' => $counts->sum(),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pkgTaskEvents(
        Request $request,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $actor = $request->user();
        $siswaIds = $actor instanceof Siswa
            ? [$actor->id]
            : ($actor instanceof User && $actor->isTeacher()
                ? $actor->getAssignedSiswaIds()
                : Siswa::query()->active()->pluck('id')->all());
        $tasks = Karakter::query()
            ->where('is_active', true)
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '>=', $start->toDateString())
            ->where(function ($query) use ($end) {
                $query->whereNull('tanggal_mulai')
                    ->orWhereDate('tanggal_mulai', '<=', $end->toDateString());
            })
            ->get(['id', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'kategori']);
        $submissionCounts = SiswaKarakterChecklist::query()
            ->whereIn('siswa_id', $siswaIds ?: [0])
            ->whereIn('karakter_id', $tasks->pluck('id'))
            ->whereNull('deleted_at')
            ->selectRaw('karakter_id, COUNT(DISTINCT siswa_id) as submission_count')
            ->groupBy('karakter_id')
            ->pluck('submission_count', 'karakter_id');

        return $tasks->map(function (Karakter $task) use ($actor, $siswaIds, $submissionCounts): array {
            $submitted = (int) ($submissionCounts[$task->id] ?? 0);
            $total = count($siswaIds);
            $details = [
                'deadline' => $task->tanggal_selesai->toDateString(),
                'submitted' => $actor instanceof Siswa ? $submitted > 0 : $submitted,
                'kategori' => $task->kategori_label,
            ];
            if ($actor instanceof User) {
                $details['total'] = $total;
            }

            return [
                'id' => 'pkg-'.$task->id,
                'title' => 'Tugas PKG: '.$task->nama,
                'start' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->toDateString(),
                'end' => $task->tanggal_selesai->copy()->addDay()->toDateString(),
                'all_day' => true,
                'type' => 'pkg_task',
                'color' => $submitted >= $total && $total > 0 ? '#10B981' : '#F59E0B',
                'details' => $details,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function characterEvents(
        Request $request,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $actor = $request->user();
        $siswaIds = $actor instanceof Siswa
            ? [$actor->id]
            : ($actor instanceof User && $actor->isTeacher()
                ? $actor->getAssignedSiswaIds()
                : Siswa::query()->active()->pluck('id')->all());
        $checks = TracerKarakter::query()
            ->whereIn('siswa_id', $siswaIds ?: [0])
            ->whereBetween('checked_at', [$start, $end])
            ->with('karakter')
            ->get()
            ->groupBy(fn (TracerKarakter $check) => $check->checked_at->toDateString());

        return $checks->map(function ($dailyChecks, string $date) use ($actor): array {
            if ($actor instanceof Siswa) {
                return [
                    'id' => 'karakter-'.$date,
                    'title' => 'Karakter: '.$dailyChecks->count(),
                    'start' => $date,
                    'end' => null,
                    'all_day' => true,
                    'type' => 'karakter',
                    'color' => '#8B5CF6',
                    'details' => [
                        'count' => $dailyChecks->count(),
                        'karakters' => $dailyChecks
                            ->map(fn (TracerKarakter $check) => $check->karakter?->nama ?? '-')
                            ->values()
                            ->all(),
                    ],
                ];
            }

            return [
                'id' => 'karakter-summary-'.$date,
                'title' => 'Karakter '.$dailyChecks->pluck('siswa_id')->unique()->count().' siswa ('.$dailyChecks->count().' ceklis)',
                'start' => $date,
                'end' => null,
                'all_day' => true,
                'type' => 'karakter_summary',
                'color' => '#8B5CF6',
                'details' => [
                    'siswa_count' => $dailyChecks->pluck('siswa_id')->unique()->count(),
                    'total_checks' => $dailyChecks->count(),
                ],
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scheduleEvents(
        Request $request,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $actor = $request->user();
        $audience = $actor instanceof Siswa ? 'siswa' : 'pamong';
        $rangeStart = Carbon::parse($start->toDateString())->startOfDay();
        $rangeEnd = Carbon::parse($end->toDateString())->endOfDay();

        return ScheduleReminder::query()
            ->with('creator')
            ->active()
            ->forAudience($audience)
            ->inDateRange($rangeStart, $rangeEnd)
            ->get()
            ->flatMap(fn (ScheduleReminder $schedule) => $schedule->expandToEvents($rangeStart, $rangeEnd))
            ->map(function (array $event): array {
                $details = $event['extendedProps'] ?? [];
                unset($details['url'], $details['admin_url'], $details['journal_url']);

                return [
                    'id' => (string) $event['id'],
                    'title' => (string) $event['title'],
                    'start' => (string) $event['start'],
                    'end' => $event['end'] ?? null,
                    'all_day' => (bool) ($event['allDay'] ?? false),
                    'type' => ($event['type'] ?? null) === ScheduleReminder::SOURCE_MATERI_RPP
                        ? 'materi_rpp'
                        : 'schedule',
                    'color' => (string) ($event['color'] ?? '#F97316'),
                    'details' => $details,
                ];
            })
            ->all();
    }

    private function attendanceColor(string $status): string
    {
        return match ($status) {
            'hadir' => '#10B981',
            'terlambat' => '#F59E0B',
            'izin' => '#3B82F6',
            'sakit' => '#8B5CF6',
            'alpha' => '#EF4444',
            default => '#6B7280',
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function actorScope(Request $request): array
    {
        $actor = $request->user();

        if ($actor instanceof User) {
            return ['staff', $actor->isTeacher() ? 'binaan' : 'semua'];
        }

        if ($actor instanceof Siswa) {
            $isParent = $actor->currentAccessToken()?->can('ortu') ?? false;

            return $isParent ? ['ortu', 'anak'] : ['siswa', 'sendiri'];
        }

        abort(403, 'Token tidak didukung.');
    }
}
