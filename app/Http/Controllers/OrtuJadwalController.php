<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\Karakter;
use App\Models\Presensi;
use App\Models\ScheduleReminder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrtuJadwalController extends Controller
{
    public function index()
    {
        $siswa = Auth::guard('ortu')->user();
        return view('ortu.jadwal.index', compact('siswa'));
    }

    public function getEvents(Request $request)
    {
        $siswa = Auth::guard('ortu')->user();

        $start = $request->input('start');
        $end = $request->input('end');
        $startDate = $start ? Carbon::parse(substr($start, 0, 10))->startOfDay() : now()->startOfMonth();
        $endDate = $end ? Carbon::parse(substr($end, 0, 10))->endOfDay() : now()->endOfMonth();
        $events = [];

        // Attendance events
        $presensi = Presensi::where('siswa_id', $siswa->id)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        foreach ($presensi as $p) {
            $statusColors = [
                'hadir' => '#10B981',
                'izin' => '#F59E0B',
                'sakit' => '#EF4444',
                'alpha' => '#6B7280',
            ];

            $events[] = [
                'title' => 'Presensi: ' . ucfirst($p->status),
                'start' => $p->tanggal->format('Y-m-d'),
                'backgroundColor' => $statusColors[$p->status] ?? '#6B7280',
                'borderColor' => $statusColors[$p->status] ?? '#6B7280',
                'extendedProps' => [
                    'type' => 'presensi',
                    'status' => $p->status,
                    'jam_masuk' => $p->jam_masuk,
                    'jam_keluar' => $p->jam_keluar,
                ],
            ];
        }

        $pkgTasks = Karakter::query()
            ->where('is_active', true)
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '>=', $startDate->toDateString())
            ->where(function ($query) use ($endDate) {
                $query->whereNull('tanggal_mulai')
                    ->orWhereDate('tanggal_mulai', '<=', $endDate->toDateString());
            })
            ->withCount([
                'checklists as siswa_submission_count' => fn ($query) => $query
                    ->where('siswa_id', $siswa->id)
                    ->whereNull('deleted_at'),
            ])
            ->get(['id', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'kategori']);

        foreach ($pkgTasks as $task) {
            $taskStart = $task->tanggal_mulai ?: $task->tanggal_selesai;
            $isSubmitted = (int) $task->siswa_submission_count > 0;
            $color = $isSubmitted ? '#10B981' : '#F59E0B';

            $events[] = [
                'title' => 'Tugas PKG: ' . $task->nama,
                'start' => $taskStart->format('Y-m-d'),
                'end' => $task->tanggal_selesai->copy()->addDay()->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'type' => 'pkg_task',
                    'judul' => $task->nama,
                    'mulai' => $taskStart->format('d M Y'),
                    'deadline' => $task->tanggal_selesai->format('d M Y'),
                    'period' => $taskStart->format('d M Y') . ' - ' . $task->tanggal_selesai->format('d M Y'),
                    'submitted' => $isSubmitted,
                    'kategori' => $task->kategori_label,
                    'url' => route('ortu.tugas'),
                ],
            ];
        }

        $reminders = ScheduleReminder::with('creator')
            ->active()
            ->inDateRange($startDate, $endDate)
            ->get();

        foreach ($reminders as $reminder) {
            $events = array_merge($events, $reminder->expandToEvents($startDate, $endDate));
        }
        $events = array_merge($events, $this->attendanceScheduleEvents($startDate, $endDate));

        return response()->json($events);
    }

    protected function attendanceScheduleEvents(Carbon $start, Carbon $end): array
    {
        $events = [];
        $schedules = AttendanceSchedule::query()
            ->where('is_active', true)
            ->overlappingDateRange($start, $end)
            ->whereIn('target_audience', [AttendanceSchedule::TARGET_ALL, AttendanceSchedule::TARGET_SISWA])
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
                    $events[] = [
                        'title' => 'Jadwal Presensi: ' . $schedule->name,
                        'start' => $date . 'T' . Carbon::parse($schedule->open_time)->format('H:i:s'),
                        'end' => $date . 'T' . Carbon::parse($schedule->close_time)->format('H:i:s'),
                        'backgroundColor' => '#0F766E',
                        'borderColor' => '#0F766E',
                        'extendedProps' => [
                            'type' => 'attendance-schedule',
                            'description' => $schedule->description,
                            'target_label' => $schedule->targetLabel(),
                            'period' => $schedule->dateRangeLabel(),
                            'open_time' => Carbon::parse($schedule->open_time)->format('H:i'),
                            'late_threshold' => Carbon::parse($schedule->late_threshold)->format('H:i'),
                            'close_time' => Carbon::parse($schedule->close_time)->format('H:i'),
                            'url' => route('public.scanner'),
                        ],
                    ];
                }

                $cursor->addDay();
            }
        }

        return $events;
    }
}
