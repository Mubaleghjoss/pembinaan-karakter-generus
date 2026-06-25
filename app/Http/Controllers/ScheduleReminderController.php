<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\ScheduleReminder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleReminderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the schedule reminders page.
     */
    public function index(): View
    {
        $schedules = ScheduleReminder::with('creator')
            ->latest()
            ->paginate(15);
        $attendanceSchedules = AttendanceSchedule::latest()->get();

        return view('schedule-reminder.index', compact('schedules', 'attendanceSchedules'));
    }

    /**
     * Show form to create new schedule.
     */
    public function create(): View
    {
        return view('schedule-reminder.create');
    }

    /**
     * Store new schedule reminder.
     */
    public function store(Request $request)
    {
        // Build validation rules - end_time only needs after_or_equal if same day
        $endTimeRule = 'nullable|date_format:H:i';
        if ($request->start_date && $request->end_date && $request->start_date === $request->end_date) {
            $endTimeRule .= '|after_or_equal:start_time';
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => $endTimeRule,
            'target_audience' => 'required|in:siswa,pamong,all',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|required_if:is_recurring,true|in:daily,weekly,monthly',
            'recurrence_days' => 'nullable|array',
            'recurrence_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'create_attendance_schedule' => 'boolean',
            'attendance_name' => 'nullable|required_if:create_attendance_schedule,1|string|max:255',
            'attendance_description' => 'nullable|string',
            'attendance_target_audience' => 'nullable|required_if:create_attendance_schedule,1|in:all,pamong,siswa',
            'attendance_start_date' => 'nullable|required_if:create_attendance_schedule,1|date',
            'attendance_end_date' => 'nullable|date|after_or_equal:attendance_start_date',
            'attendance_open_time' => 'nullable|required_if:create_attendance_schedule,1|date_format:H:i',
            'attendance_late_threshold' => 'nullable|required_if:create_attendance_schedule,1|date_format:H:i|after_or_equal:attendance_open_time',
            'attendance_close_time' => 'nullable|required_if:create_attendance_schedule,1|date_format:H:i|after_or_equal:attendance_late_threshold',
            'attendance_days' => 'nullable|required_if:create_attendance_schedule,1|array',
            'attendance_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'attendance_is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_recurring'] = $request->boolean('is_recurring');
        $validated['is_active'] = $request->boolean('is_active', true);

        DB::transaction(function () use ($request, $validated) {
            ScheduleReminder::create(collect($validated)->except([
                'create_attendance_schedule',
                'attendance_name',
                'attendance_description',
                'attendance_target_audience',
                'attendance_start_date',
                'attendance_end_date',
                'attendance_open_time',
                'attendance_late_threshold',
                'attendance_close_time',
                'attendance_days',
                'attendance_is_active',
            ])->all());

            if (! $request->boolean('create_attendance_schedule')) {
                return;
            }

            $attendanceIsActive = $request->boolean('attendance_is_active', true);

            if ($attendanceIsActive) {
                AttendanceSchedule::where('is_active', true)->update(['is_active' => false]);
            }

            AttendanceSchedule::create([
                'name' => $validated['attendance_name'],
                'description' => $validated['attendance_description'] ?? $validated['description'] ?? null,
                'target_audience' => $validated['attendance_target_audience'],
                'start_date' => $validated['attendance_start_date'],
                'end_date' => $validated['attendance_end_date'] ?? $validated['attendance_start_date'],
                'open_time' => $validated['attendance_open_time'],
                'late_threshold' => $validated['attendance_late_threshold'],
                'close_time' => $validated['attendance_close_time'],
                'days' => $validated['attendance_days'],
                'is_active' => $attendanceIsActive,
            ]);
        });

        return redirect()->route('schedule-reminder.index')
            ->with('success', $request->boolean('create_attendance_schedule')
                ? 'Jadwal pengingat dan jadwal presensi berhasil dibuat!'
                : 'Jadwal pengingat berhasil dibuat!');
    }

    /**
     * Show edit form.
     */
    public function edit(ScheduleReminder $scheduleReminder): View
    {
        return view('schedule-reminder.edit', compact('scheduleReminder'));
    }

    /**
     * Update schedule reminder.
     */
    public function update(Request $request, ScheduleReminder $scheduleReminder)
    {
        // Build validation rules - end_time only needs after_or_equal if same day
        $endTimeRule = 'nullable|date_format:H:i';
        if ($request->start_date && $request->end_date && $request->start_date === $request->end_date) {
            $endTimeRule .= '|after_or_equal:start_time';
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => $endTimeRule,
            'target_audience' => 'required|in:siswa,pamong,all',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|required_if:is_recurring,true|in:daily,weekly,monthly',
            'recurrence_days' => 'nullable|array',
            'recurrence_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        $validated['is_recurring'] = $request->boolean('is_recurring');
        $validated['is_active'] = $request->boolean('is_active', true);

        $scheduleReminder->update($validated);

        return redirect()->route('schedule-reminder.index')
            ->with('success', 'Jadwal pengingat berhasil diperbarui!');
    }

    /**
     * Delete schedule reminder.
     */
    public function destroy(ScheduleReminder $scheduleReminder)
    {
        $scheduleReminder->delete();

        return redirect()->route('schedule-reminder.index')
            ->with('success', 'Jadwal pengingat berhasil dihapus!');
    }

    /**
     * Toggle schedule active status.
     */
    public function toggle(ScheduleReminder $scheduleReminder)
    {
        $scheduleReminder->update(['is_active' => !$scheduleReminder->is_active]);

        $status = $scheduleReminder->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Jadwal pengingat berhasil {$status}!");
    }

    /**
     * Get schedule events for calendar API.
     */
    public function getEvents(Request $request): JsonResponse
    {
        $startStr = $request->get('start', now()->startOfMonth()->toDateString());
        $endStr = $request->get('end', now()->endOfMonth()->toDateString());
        $start = Carbon::parse(substr($startStr, 0, 10))->startOfDay();
        $end = Carbon::parse(substr($endStr, 0, 10))->endOfDay();
        $audience = $request->get('audience', 'all');

        $schedules = ScheduleReminder::active()
            ->forAudience($audience)
            ->inDateRange($start, $end)
            ->get();

        $events = [];
        foreach ($schedules as $schedule) {
            $expandedEvents = $schedule->expandToEvents($start, $end);
            $events = array_merge($events, $expandedEvents);
        }

        $events = array_merge($events, $this->expandAttendanceScheduleEvents($start, $end, $audience));

        return response()->json($events);
    }

    protected function expandAttendanceScheduleEvents(Carbon $start, Carbon $end, string $audience): array
    {
        $events = [];
        $schedules = AttendanceSchedule::query()
            ->where('is_active', true)
            ->overlappingDateRange($start, $end)
            ->when(in_array($audience, ['siswa', 'pamong'], true), function ($query) use ($audience) {
                $query->whereIn('target_audience', [AttendanceSchedule::TARGET_ALL, $audience]);
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
                            'date' => $date,
                            'open_time' => Carbon::parse($schedule->open_time)->format('H:i'),
                            'late_threshold' => Carbon::parse($schedule->late_threshold)->format('H:i'),
                            'close_time' => Carbon::parse($schedule->close_time)->format('H:i'),
                            'url' => route('attendance-schedule.index'),
                        ],
                    ];
                }

                $cursor->addDay();
            }
        }

        return $events;
    }
}
