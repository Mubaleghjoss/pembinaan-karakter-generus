<?php

namespace App\Http\Controllers;

use App\Exports\CalendarMonthExport;
use App\Models\AttendanceSchedule;
use App\Models\Karakter;
use App\Models\MateriRppJournal;
use App\Models\Presensi;
use App\Models\ScheduleReminder;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Models\ThemeSetting;
use App\Models\TracerKarakter;
use App\Services\MateriRppJournalWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth'])->only(['adminIndex', 'adminEvents', 'getDateStats', 'adminShareText', 'adminExport']);
    }

    public function publicIndex(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $theme = ThemeSetting::current();

        return view('public.calendar', compact('month', 'year', 'theme'));
    }

    public function publicEvents(Request $request)
    {
        $startStr = $request->get('start', now()->startOfMonth()->toDateString());
        $endStr = $request->get('end', now()->endOfMonth()->toDateString());
        $start = Carbon::parse(substr($startStr, 0, 10))->startOfDay();
        $end = Carbon::parse(substr($endStr, 0, 10))->endOfDay();

        $cacheKey = sprintf(
            'calendar:public:%s:%s:%s:%s:%s',
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            Karakter::max('updated_at') ?: 'no-karakter',
            $this->scheduleReminderCacheVersion(),
            AttendanceSchedule::max('updated_at') ?: 'no-schedules'
        );

        $events = Cache::remember($cacheKey, now()->addSeconds(90), function () use ($start, $end) {
            return $this->buildPublicEvents($start, $end);
        });

        return response()->json($events);
    }

    public function siswaIndex(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        return view('siswa.calendar.index', compact('month', 'year'));
    }

    public function siswaEvents(Request $request)
    {
        try {
            $siswa = Auth::guard('siswa')->user();
            $startStr = $request->get('start', now()->startOfMonth()->toDateString());
            $endStr = $request->get('end', now()->endOfMonth()->toDateString());
            $start = Carbon::parse(substr($startStr, 0, 10))->startOfDay();
            $end = Carbon::parse(substr($endStr, 0, 10))->endOfDay();

            $cacheKey = sprintf(
                'calendar:siswa:%d:%s:%s:%s:%s:%s:%s',
                $siswa->id,
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                Karakter::max('updated_at') ?: 'no-karakter',
                SiswaKarakterChecklist::where('siswa_id', $siswa->id)->max('updated_at') ?: 'no-checklists',
                $this->scheduleReminderCacheVersion(),
                AttendanceSchedule::max('updated_at') ?: 'no-schedules'
            );

            $events = Cache::remember($cacheKey, now()->addSeconds(90), function () use ($siswa, $start, $end) {
                $events = [];

                $presensi = Presensi::where('siswa_id', $siswa->id)
                    ->whereBetween('tanggal', [$start, $end])
                    ->get();

                foreach ($presensi as $p) {
                    $color = match ($p->status) {
                        'hadir' => '#10B981',
                        'terlambat' => '#F59E0B',
                        'izin' => '#3B82F6',
                        'sakit' => '#8B5CF6',
                        'alpha' => '#EF4444',
                        default => '#6B7280',
                    };

                    $events[] = [
                        'id' => 'presensi-' . $p->id,
                        'title' => 'Presensi: ' . ucfirst($p->status),
                        'start' => $p->tanggal->format('Y-m-d'),
                        'color' => $color,
                        'type' => 'presensi',
                        'extendedProps' => [
                            'status' => $p->status,
                            'jam_masuk' => $p->jam_masuk?->format('H:i'),
                            'jam_keluar' => $p->jam_keluar?->format('H:i'),
                        ],
                    ];
                }

                $pkgTasks = Karakter::query()
                    ->where('is_active', true)
                    ->whereNotNull('tanggal_selesai')
                    ->whereDate('tanggal_selesai', '>=', $start->toDateString())
                    ->where(function ($query) use ($end) {
                        $query->whereNull('tanggal_mulai')
                            ->orWhereDate('tanggal_mulai', '<=', $end->toDateString());
                    })
                    ->withCount([
                        'checklists as siswa_submission_count' => fn ($query) => $query
                            ->where('siswa_id', $siswa->id)
                            ->whereNull('deleted_at'),
                    ])
                    ->get(['id', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'kategori']);

                foreach ($pkgTasks as $task) {
                    $isSubmitted = (int) $task->siswa_submission_count > 0;

                    $events[] = [
                        'id' => 'pkg-' . $task->id,
                        'title' => 'Tugas PKG: ' . $task->nama,
                        'start' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->format('Y-m-d'),
                        'end' => $task->tanggal_selesai->copy()->addDay()->format('Y-m-d'),
                        'allDay' => true,
                        'color' => $isSubmitted ? '#10B981' : '#F59E0B',
                        'type' => 'pkg_task',
                        'extendedProps' => [
                            'judul' => $task->nama,
                            'mulai' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->format('d M Y'),
                            'deadline' => $task->tanggal_selesai->format('d M Y'),
                            'period' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->format('d M Y') . ' - ' . $task->tanggal_selesai->format('d M Y'),
                            'submitted' => $isSubmitted,
                            'kategori' => $task->kategori_label,
                            'url' => route('siswa.tugas-pkg.index'),
                        ],
                    ];
                }

                $karakterChecks = TracerKarakter::where('siswa_id', $siswa->id)
                    ->whereBetween('checked_at', [$start, $end])
                    ->with('karakter')
                    ->get()
                    ->groupBy(fn ($item) => $item->checked_at->format('Y-m-d'));

                foreach ($karakterChecks as $date => $checks) {
                    $events[] = [
                        'id' => 'karakter-' . $date,
                        'title' => 'Karakter: ' . $checks->count(),
                        'start' => $date,
                        'color' => '#8B5CF6',
                        'type' => 'karakter',
                        'extendedProps' => [
                            'count' => $checks->count(),
                            'karakters' => $checks->map(fn ($item) => $item->karakter->nama ?? '-')->toArray(),
                        ],
                    ];
                }

                $schedules = ScheduleReminder::with('creator')
                    ->active()
                    ->where(function ($query) {
                        $query->where('target_audience', 'siswa')
                            ->orWhere('target_audience', 'all');
                    })
                    ->inDateRange($start, $end)
                    ->get();

                foreach ($schedules as $schedule) {
                    $events = array_merge($events, $schedule->expandToEvents($start, $end));
                }

                foreach ($this->expandAttendanceScheduleEvents($siswa, $start, $end, 'siswa') as $event) {
                    $events[] = $event;
                }

                return $events;
            });

            return response()->json($events);
        } catch (\Throwable $e) {
            \Log::error('Calendar siswaEvents error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function adminIndex(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        return view('calendar.index', compact('month', 'year'));
    }

    public function adminEvents(Request $request)
    {
        [$user, $siswaIds] = $this->calendarUserScope();
        $startStr = $request->get('start', now()->startOfMonth()->toDateString());
        $endStr = $request->get('end', now()->endOfMonth()->toDateString());
        $start = Carbon::parse(substr($startStr, 0, 10))->startOfDay();
        $end = Carbon::parse(substr($endStr, 0, 10))->endOfDay();

        $cacheKey = sprintf(
            'calendar:admin:%d:%s:%s:%s:%s:%s:%s:%s:%s',
            $user->id,
            $user->role_id,
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            Karakter::max('updated_at') ?: 'no-karakter',
            SiswaKarakterChecklist::max('updated_at') ?: 'no-checklists',
            $this->scheduleReminderCacheVersion(),
            AttendanceSchedule::max('updated_at') ?: 'no-schedules',
            MateriRppJournal::max('updated_at') ?: 'no-rpp-journals'
        );

        $events = Cache::remember($cacheKey, now()->addSeconds(90), function () use ($user, $start, $end, $siswaIds) {
            return $this->buildAdminEvents($user, $start, $end, $siswaIds);
        });

        return response()->json($events);
    }

    public function adminShareText(Request $request)
    {
        [$start, $end, $periodLabel] = $this->calendarMonthRange($request);
        [$user, $siswaIds] = $this->calendarUserScope();
        $events = $this->buildAdminEvents($user, $start, $end, $siswaIds);
        $rows = $this->calendarEventRows($events);

        return response()->json([
            'success' => true,
            'text' => $this->calendarShareText($rows, $periodLabel),
        ]);
    }

    public function adminExport(Request $request)
    {
        [$start, $end, $periodLabel] = $this->calendarMonthRange($request);
        [$user, $siswaIds] = $this->calendarUserScope();
        $events = $this->buildAdminEvents($user, $start, $end, $siswaIds);
        $rows = $this->calendarEventRows($events);
        $filename = 'Kalender-PKG-' . $start->format('Y-m') . '.xlsx';

        return (new CalendarMonthExport($rows, $periodLabel))->download($filename);
    }

    protected function calendarUserScope(): array
    {
        $user = Auth::user();

        if ($user->isTeacher()) {
            return [$user, collect($user->getAssignedSiswaIds())->values()];
        }

        return [$user, Siswa::where('is_active', true)->pluck('id')];
    }

    protected function buildAdminEvents($user, Carbon $start, Carbon $end, Collection $siswaIds): array
    {
        $events = [];

        $presensiSummary = Presensi::whereIn('siswa_id', $siswaIds)
            ->whereBetween('tanggal', [$start, $end])
            ->selectRaw('tanggal, status, COUNT(*) as count')
            ->groupBy('tanggal', 'status')
            ->get()
            ->groupBy('tanggal');

        foreach ($presensiSummary as $date => $statuses) {
            $hadir = $statuses->where('status', 'hadir')->first()?->count ?? 0;
            $terlambat = $statuses->where('status', 'terlambat')->first()?->count ?? 0;
            $alpha = $statuses->where('status', 'alpha')->first()?->count ?? 0;
            $total = $statuses->sum('count');

            $events[] = [
                'id' => 'presensi-summary-' . $date,
                'title' => "Hadir {$hadir} | Terlambat {$terlambat} | Alpha {$alpha}",
                'start' => Carbon::parse($date)->format('Y-m-d'),
                'color' => '#3B82F6',
                'type' => 'presensi-summary',
                'extendedProps' => [
                    'type' => 'presensi-summary',
                    'hadir' => $hadir,
                    'terlambat' => $terlambat,
                    'alpha' => $alpha,
                    'total' => $total,
                ],
            ];
        }

        $pkgTasks = Karakter::query()
            ->where('is_active', true)
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '>=', $start->toDateString())
            ->where(function ($query) use ($end) {
                $query->whereNull('tanggal_mulai')
                    ->orWhereDate('tanggal_mulai', '<=', $end->toDateString());
            })
            ->get(['id', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'kategori']);
        $submissionCounts = SiswaKarakterChecklist::query()
            ->whereIn('siswa_id', $siswaIds)
            ->whereIn('karakter_id', $pkgTasks->pluck('id'))
            ->whereNull('deleted_at')
            ->selectRaw('karakter_id, COUNT(DISTINCT siswa_id) as submission_count')
            ->groupBy('karakter_id')
            ->pluck('submission_count', 'karakter_id');
        $totalSiswa = $siswaIds->count();

        foreach ($pkgTasks as $task) {
            $submissionCount = (int) ($submissionCounts[$task->id] ?? 0);

            $events[] = [
                'id' => 'pkg-' . $task->id,
                'title' => 'Tugas PKG: ' . $task->nama,
                'start' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->format('Y-m-d'),
                'end' => $task->tanggal_selesai->copy()->addDay()->format('Y-m-d'),
                'allDay' => true,
                'color' => $submissionCount >= $totalSiswa && $totalSiswa > 0 ? '#10B981' : '#F59E0B',
                'type' => 'pkg_task',
                'extendedProps' => [
                    'type' => 'pkg_task',
                    'judul' => $task->nama,
                    'mulai' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->format('d M Y'),
                    'deadline' => $task->tanggal_selesai->format('d M Y'),
                    'period' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->format('d M Y') . ' - ' . $task->tanggal_selesai->format('d M Y'),
                    'submissions' => $submissionCount,
                    'total' => $totalSiswa,
                    'kategori' => $task->kategori_label,
                    'url' => route('tugas-pkg.index', ['karakter_id' => $task->id]),
                ],
            ];
        }

        $karakterSummary = TracerKarakter::whereIn('siswa_id', $siswaIds)
            ->whereBetween('checked_at', [$start, $end])
            ->selectRaw('DATE(checked_at) as date, COUNT(DISTINCT siswa_id) as siswa_count, COUNT(*) as total_checks')
            ->groupBy('date')
            ->get();

        foreach ($karakterSummary as $summary) {
            $events[] = [
                'id' => 'karakter-summary-' . $summary->date,
                'title' => "Karakter {$summary->siswa_count} siswa ({$summary->total_checks} ceklis)",
                'start' => $summary->date,
                'color' => '#8B5CF6',
                'type' => 'karakter-summary',
                'extendedProps' => [
                    'type' => 'karakter-summary',
                    'siswa_count' => $summary->siswa_count,
                    'total_checks' => $summary->total_checks,
                ],
            ];
        }

        $schedulesQuery = ScheduleReminder::with('creator')->active();

        foreach ($schedulesQuery->inDateRange($start, $end)->get() as $schedule) {
            $events = array_merge($events, $schedule->expandToEvents($start, $end));
        }

        foreach ($this->expandAttendanceScheduleEvents($user, $start, $end, 'admin') as $event) {
            $events[] = $event;
        }

        return $this->attachRppJournalState($events, $user);
    }

    protected function attachRppJournalState(array $events, $user): array
    {
        $scheduleIds = collect($events)
            ->filter(fn (array $event) => ($event['extendedProps']['type'] ?? null) === ScheduleReminder::SOURCE_MATERI_RPP)
            ->pluck('extendedProps.schedule_id')
            ->filter()
            ->unique()
            ->values();

        if ($scheduleIds->isEmpty()) {
            return $events;
        }

        $journals = MateriRppJournal::query()
            ->whereIn('schedule_reminder_id', $scheduleIds)
            ->get()
            ->keyBy('schedule_reminder_id');

        $schedules = ScheduleReminder::query()
            ->with(['journalAssigneeUser', 'journalAssigneeSiswa', 'journalAssignees.user', 'journalAssignees.siswa'])
            ->whereIn('id', $scheduleIds)
            ->get()
            ->keyBy('id');
        $workflow = app(MateriRppJournalWorkflowService::class);

        return collect($events)
            ->map(function (array $event) use ($journals, $schedules, $workflow, $user) {
                if (($event['extendedProps']['type'] ?? null) !== ScheduleReminder::SOURCE_MATERI_RPP) {
                    return $event;
                }

                $scheduleId = $event['extendedProps']['schedule_id'] ?? null;
                $journal = $scheduleId ? $journals->get($scheduleId) : null;
                $schedule = $scheduleId ? $schedules->get($scheduleId) : null;

                if (! $schedule || ! $workflow->canViewStaffSchedule($user, $schedule)) {
                    return $event;
                }

                $event['extendedProps']['journal_id'] = $journal?->id;
                $event['extendedProps']['journal_status'] = $journal?->realization_status;
                $event['extendedProps']['journal_workflow_status'] = $journal?->workflow_status ?? 'pending';
                $event['extendedProps']['journal_status_label'] = $workflow->workflowLabel($schedule);
                $event['extendedProps']['journal_assignee_label'] = $schedule->journal_assignee_label;
                $event['extendedProps']['journal_url'] = route('materi-rpp-journals.schedule', $schedule);
                $event['extendedProps']['journal_button_label'] = match (true) {
                    ! $schedule->isJournalAvailable() && $workflow->canManageAll($user) => 'Atur Petugas',
                    $journal?->workflow_status === MateriRppJournal::WORKFLOW_PENDING_REVIEW => 'Tinjau Jurnal',
                    $journal !== null => 'Lihat Jurnal',
                    default => 'Isi Jurnal',
                };

                return $event;
            })
            ->all();
    }

    protected function buildPublicEvents(Carbon $start, Carbon $end): array
    {
        $events = [];

        $pkgTasks = Karakter::query()
            ->where('is_active', true)
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '>=', $start->toDateString())
            ->where(function ($query) use ($end) {
                $query->whereNull('tanggal_mulai')
                    ->orWhereDate('tanggal_mulai', '<=', $end->toDateString());
            })
            ->get(['id', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'kategori']);

        foreach ($pkgTasks as $task) {
            $events[] = [
                'id' => 'public-pkg-' . $task->id,
                'title' => 'Tugas PKG: ' . $task->nama,
                'start' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->format('Y-m-d'),
                'end' => $task->tanggal_selesai->copy()->addDay()->format('Y-m-d'),
                'allDay' => true,
                'color' => '#F59E0B',
                'type' => 'pkg_task',
                'extendedProps' => [
                    'type' => 'pkg_task',
                    'judul' => $task->nama,
                    'mulai' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->format('d M Y'),
                    'deadline' => $task->tanggal_selesai->format('d M Y'),
                    'period' => ($task->tanggal_mulai ?: $task->tanggal_selesai)->format('d M Y') . ' - ' . $task->tanggal_selesai->format('d M Y'),
                    'kategori' => $task->kategori_label,
                ],
            ];
        }

        $schedules = ScheduleReminder::with('creator')
            ->active()
            ->where(function ($query) {
                $query->where('target_audience', 'siswa')
                    ->orWhere('target_audience', 'all');
            })
            ->inDateRange($start, $end)
            ->get();

        foreach ($schedules as $schedule) {
            $events = array_merge($events, $schedule->expandToEvents($start, $end));
        }

        foreach ($this->expandAttendanceScheduleEvents(null, $start, $end, 'siswa') as $event) {
            $events[] = $event;
        }

        return $events;
    }

    protected function calendarMonthRange(Request $request): array
    {
        if ($request->filled('date')) {
            $date = Carbon::parse(substr($request->get('date'), 0, 10));
        } else {
            $month = max(1, min(12, (int) $request->integer('month', now()->month)));
            $year = (int) $request->integer('year', now()->year);
            $date = Carbon::create($year, $month, 1);
        }

        $start = $date->copy()->startOfMonth()->startOfDay();
        $end = $date->copy()->endOfMonth()->endOfDay();

        return [$start, $end, $start->isoFormat('MMMM YYYY')];
    }

    protected function calendarEventRows(array $events): Collection
    {
        return collect($events)
            ->map(function (array $event) {
                $props = $event['extendedProps'] ?? [];
                $type = $props['type'] ?? $event['type'] ?? 'event';
                $startRaw = (string) ($event['start'] ?? now()->toDateString());
                $date = Carbon::parse(substr($startRaw, 0, 10));
                $timeLabel = $this->calendarEventTimeLabel($event, $props);
                $title = $type === ScheduleReminder::SOURCE_MATERI_RPP
                    ? ($props['materi_title'] ?? $props['title'] ?? $event['title'] ?? '-')
                    : ($props['title'] ?? $props['judul'] ?? $event['title'] ?? '-');

                return [
                    'date_sort' => $date->format('Y-m-d'),
                    'time_sort' => $timeLabel === 'Sepanjang hari' ? '00:00' : $timeLabel,
                    'date_label' => $date->isoFormat('dddd, D MMMM YYYY'),
                    'time_label' => $timeLabel,
                    'type' => $type,
                    'type_label' => $this->calendarTypeLabel($type),
                    'title' => $title,
                    'detail' => $this->calendarEventDetail($type, $props),
                    'target_label' => $this->calendarEventTargetLabel($type, $props),
                    'location_label' => $this->calendarEventLocationLabel($type, $props),
                ];
            })
            ->sortBy(fn (array $row) => $row['date_sort'] . ' ' . $row['time_sort'] . ' ' . $row['title'])
            ->values();
    }

    protected function calendarEventTimeLabel(array $event, array $props): string
    {
        $startTime = $props['start_time'] ?? null;
        $endTime = $props['end_time'] ?? null;
        $startRaw = (string) ($event['start'] ?? '');
        $endRaw = (string) ($event['end'] ?? '');

        if (! $startTime && str_contains($startRaw, 'T')) {
            $startTime = Carbon::parse($startRaw)->format('H:i');
        }

        if (! $endTime && str_contains($endRaw, 'T')) {
            $endTime = Carbon::parse($endRaw)->format('H:i');
        }

        if (! $startTime) {
            return 'Sepanjang hari';
        }

        return $endTime ? "{$startTime} - {$endTime}" : $startTime;
    }

    protected function calendarTypeLabel(string $type): string
    {
        return match ($type) {
            'presensi-summary' => 'Ringkasan Presensi',
            'pkg_task' => 'Tugas PKG',
            'karakter-summary' => 'Aktivitas Karakter',
            ScheduleReminder::SOURCE_MATERI_RPP => 'RPP Materi',
            'schedule-reminder' => 'Jadwal Pengingat',
            'attendance-schedule' => 'Jadwal Presensi',
            default => 'Agenda',
        };
    }

    protected function calendarEventDetail(string $type, array $props): string
    {
        return match ($type) {
            'presensi-summary' => sprintf(
                'Hadir %s, terlambat %s, alpha %s, total %s siswa',
                $props['hadir'] ?? 0,
                $props['terlambat'] ?? 0,
                $props['alpha'] ?? 0,
                $props['total'] ?? 0
            ),
            'pkg_task' => trim(implode('; ', array_filter([
                isset($props['period']) ? 'Periode ' . $props['period'] : null,
                isset($props['kategori']) ? 'Kategori ' . $props['kategori'] : null,
                isset($props['submissions'], $props['total']) ? ($props['submissions'] . '/' . $props['total'] . ' siswa sudah mengerjakan') : null,
            ]))),
            'karakter-summary' => sprintf('%s siswa, %s ceklis', $props['siswa_count'] ?? 0, $props['total_checks'] ?? 0),
            ScheduleReminder::SOURCE_MATERI_RPP => trim(implode('; ', array_filter([
                isset($props['session_number']) ? 'Pertemuan ' . $props['session_number'] : null,
                $props['page_range'] ?? null,
                (($props['session_type'] ?? null) === 'catch_up' && ! empty($props['range_start_date']) && ! empty($props['range_end_date']))
                    ? 'Kejar target ' . Carbon::parse($props['range_start_date'])->format('d/m/Y') . ' s/d ' . Carbon::parse($props['range_end_date'])->format('d/m/Y')
                    : null,
            ]))),
            'schedule-reminder' => $props['description'] ?? '-',
            'attendance-schedule' => sprintf(
                'Target %s; buka %s; tepat waktu %s; tutup %s',
                $props['target_label'] ?? '-',
                $props['open_time'] ?? '-',
                $props['late_threshold'] ?? '-',
                $props['close_time'] ?? '-'
            ),
            default => $props['description'] ?? '-',
        };
    }

    protected function calendarEventTargetLabel(string $type, array $props): string
    {
        return match ($type) {
            ScheduleReminder::SOURCE_MATERI_RPP => filled($props['teacher_name'] ?? null) ? 'Pengajar: ' . $props['teacher_name'] : '-',
            'attendance-schedule' => filled($props['target_label'] ?? null) ? 'Target: ' . $props['target_label'] : '-',
            'schedule-reminder' => filled($props['target_audience'] ?? null) ? 'Target: ' . $this->calendarAudienceLabel($props['target_audience']) : '-',
            default => '-',
        };
    }

    protected function calendarEventLocationLabel(string $type, array $props): string
    {
        return match ($type) {
            'schedule-reminder' => $props['location'] ?? '-',
            'attendance-schedule' => $props['action_label'] ?? '-',
            ScheduleReminder::SOURCE_MATERI_RPP => '-',
            default => $props['url'] ?? '-',
        };
    }

    protected function calendarAudienceLabel(?string $audience): string
    {
        return match ($audience) {
            'all' => 'Semua',
            'siswa' => 'Siswa',
            'pamong' => 'Pamong',
            default => $audience ?: '-',
        };
    }

    protected function calendarShareText(Collection $rows, string $periodLabel): string
    {
        $shareRows = $rows
            ->reject(fn (array $row) => in_array($row['type'], ['presensi-summary', 'karakter-summary'], true))
            ->values();
        $lines = [
            'Kalender PKG',
            'Bulan: ' . $periodLabel,
            '',
        ];

        if ($shareRows->isEmpty()) {
            $lines[] = 'Belum ada agenda pada bulan ini.';
            $lines[] = '';
            $lines[] = 'Selengkapnya: ' . route('public.calendar.index');

            return implode("\n", $lines);
        }

        foreach ($shareRows as $index => $row) {
            $lines[] = ($index + 1) . '. ' . $row['date_label'];
            $lines[] = '   ' . $row['time_label'] . ' | ' . $row['title'];
            $lines[] = '   ' . $row['type_label'] . ($row['detail'] && $row['detail'] !== '-' ? ': ' . $row['detail'] : '');

            if (filled($row['target_label'] ?? null) && $row['target_label'] !== '-') {
                $lines[] = '   ' . $row['target_label'];
            }

            $lines[] = '';
        }

        $lines[] = 'Selengkapnya: ' . route('public.calendar.index');

        return rtrim(implode("\n", $lines));
    }

    public function getDateStats(Request $request)
    {
        $date = Carbon::parse($request->get('date', now()));
        $user = Auth::user();

        if ($user->isTeacher()) {
            $siswaIds = collect($user->getAssignedSiswaIds())->values();
        } else {
            $siswaIds = Siswa::where('is_active', true)->pluck('id');
        }

        $stats = Cache::remember(
            sprintf('calendar:date-stats:%d:%s', $user->id, $date->format('Y-m-d')),
            now()->addSeconds(60),
            function () use ($siswaIds, $date) {
                $presensi = Presensi::whereIn('siswa_id', $siswaIds)
                    ->whereDate('tanggal', $date)
                    ->with('siswa')
                    ->get();

                return [
                    'date' => $date->format('d M Y'),
                    'total' => $presensi->count(),
                    'hadir' => $presensi->where('status', 'hadir')->count(),
                    'terlambat' => $presensi->where('status', 'terlambat')->count(),
                    'izin' => $presensi->where('status', 'izin')->count(),
                    'sakit' => $presensi->where('status', 'sakit')->count(),
                    'alpha' => $presensi->where('status', 'alpha')->count(),
                    'records' => $presensi->map(fn ($item) => [
                        'siswa' => $item->siswa->nama,
                        'status' => $item->status,
                        'jam_masuk' => $item->jam_masuk?->format('H:i'),
                    ])->values(),
                ];
            }
        );

        return response()->json($stats);
    }

    protected function expandAttendanceScheduleEvents($user, Carbon $start, Carbon $end, string $viewer = 'admin'): array
    {
        $events = [];
        $dayNames = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];

        $schedules = AttendanceSchedule::query()
            ->where('is_active', true)
            ->overlappingDateRange($start, $end)
            ->when($viewer === 'siswa' || $viewer === 'ortu', function ($query) {
                $query->whereIn('target_audience', [AttendanceSchedule::TARGET_ALL, AttendanceSchedule::TARGET_SISWA]);
            })
            ->orderBy('open_time')
            ->orderBy('id')
            ->get();

        foreach ($schedules as $schedule) {
            $cursor = $start->copy()->startOfDay();
            $lastDate = $end->copy()->startOfDay();
            $days = $schedule->days ?? [];

            while ($cursor->lte($lastDate)) {
                $dayKey = strtolower($cursor->format('l'));

                if ($schedule->isDateActive($cursor) && (empty($days) || in_array($dayKey, $days, true))) {
                    $date = $cursor->format('Y-m-d');
                    $url = $this->attendanceScheduleUrl($user, $schedule, $date, $viewer);

                    $events[] = [
                        'id' => 'attendance-schedule-' . $schedule->id . '-' . $date,
                        'title' => 'Jadwal Presensi: ' . $schedule->name,
                        'start' => $date . 'T' . Carbon::parse($schedule->open_time)->format('H:i:s'),
                        'end' => $date . 'T' . Carbon::parse($schedule->close_time)->format('H:i:s'),
                        'color' => '#0F766E',
                        'type' => 'attendance-schedule',
                        'extendedProps' => [
                            'type' => 'attendance-schedule',
                            'title' => $schedule->name,
                            'description' => $schedule->description,
                            'target_audience' => $schedule->target_audience,
                            'target_label' => $schedule->targetLabel(),
                            'period' => $schedule->dateRangeLabel(),
                            'day_label' => $dayNames[$dayKey] ?? $cursor->isoFormat('dddd'),
                            'date' => $date,
                            'open_time' => Carbon::parse($schedule->open_time)->format('H:i'),
                            'late_threshold' => Carbon::parse($schedule->late_threshold)->format('H:i'),
                            'close_time' => Carbon::parse($schedule->close_time)->format('H:i'),
                            'url' => $url,
                            'action_label' => $viewer === 'siswa' || $viewer === 'ortu'
                                ? 'Buka Scan Presensi'
                                : ($schedule->targetsPamong()
                                ? 'Buka Absen Manual Pamong'
                                : 'Buka Input Manual Siswa'),
                        ],
                    ];
                }

                $cursor->addDay();
            }
        }

        return $events;
    }

    protected function attendanceScheduleUrl($user, AttendanceSchedule $schedule, string $date, string $viewer = 'admin'): string
    {
        if ($viewer === 'siswa' || $viewer === 'ortu') {
            return route('public.scanner');
        }

        if ($schedule->targetsPamong()) {
            return route('pamong-presensi.index', [
                'start_date' => $date,
                'end_date' => $date,
                'date' => $date,
                'manual' => 1,
            ]) . '#manual-pamong';
        }

        return route('presensi.index', [
            'tab' => 'input',
            'date' => $date,
        ]);
    }

    private function scheduleReminderCacheVersion(): string
    {
        return implode(':', [
            ScheduleReminder::count(),
            ScheduleReminder::max('id') ?: 0,
            ScheduleReminder::max('updated_at') ?: 'no-reminders',
        ]);
    }
}
