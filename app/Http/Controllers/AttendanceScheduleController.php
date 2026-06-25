<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\ScheduleReminder;
use Illuminate\Http\Request;

class AttendanceScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('pamong.permission:jadwal,view')->only(['index']);
        $this->middleware('pamong.permission:jadwal,create')->only(['create', 'store']);
        $this->middleware('pamong.permission:jadwal,edit')->only(['edit', 'update', 'activate', 'deactivate']);
        $this->middleware('pamong.permission:jadwal,delete')->only(['destroy']);
    }

    /**
     * Display the schedule settings page
     */
    public function index()
    {
        $schedules = AttendanceSchedule::latest()->get();
        $activeSchedule = AttendanceSchedule::getActiveSchedule();
        $calendarReminders = ScheduleReminder::latest()->get();

        return view('attendance-schedule.index', compact('schedules', 'activeSchedule', 'calendarReminders'));
    }

    /**
     * Show form to create new schedule
     */
    public function create()
    {
        $targetOptions = AttendanceSchedule::targetOptions();

        return view('attendance-schedule.create', compact('targetOptions'));
    }

    /**
     * Store new schedule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'open_time' => 'required|date_format:H:i',
            'late_threshold' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i',
            'days' => 'required|array',
            'days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'target_audience' => 'required|in:all,pamong,siswa',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $validated['end_date'] = $validated['end_date'] ?? $validated['start_date'];

        // If setting as active, deactivate others
        if ($request->is_active) {
            AttendanceSchedule::where('is_active', true)->update(['is_active' => false]);
        }

        AttendanceSchedule::create($validated);

        return redirect()->route('attendance-schedule.index')
            ->with('success', 'Jadwal presensi berhasil dibuat!');
    }

    /**
     * Show edit form
     */
    public function edit(AttendanceSchedule $attendanceSchedule)
    {
        $targetOptions = AttendanceSchedule::targetOptions();

        return view('attendance-schedule.edit', compact('attendanceSchedule', 'targetOptions'));
    }

    /**
     * Update schedule
     */
    public function update(Request $request, AttendanceSchedule $attendanceSchedule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'open_time' => 'required|date_format:H:i',
            'late_threshold' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i',
            'days' => 'required|array',
            'days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'target_audience' => 'required|in:all,pamong,siswa',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['end_date'] = $validated['end_date'] ?? $validated['start_date'];

        // If setting as active, deactivate others
        if ($validated['is_active']) {
            AttendanceSchedule::where('id', '!=', $attendanceSchedule->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $attendanceSchedule->update($validated);

        return redirect()->route('attendance-schedule.index')
            ->with('success', 'Jadwal presensi berhasil diperbarui!');
    }

    /**
     * Activate a schedule
     */
    public function activate(AttendanceSchedule $attendanceSchedule)
    {
        // Deactivate all schedules
        AttendanceSchedule::where('is_active', true)->update(['is_active' => false]);

        // Activate this schedule
        $attendanceSchedule->update(['is_active' => true]);

        return redirect()->route('attendance-schedule.index')
            ->with('success', 'Jadwal presensi berhasil diaktifkan!');
    }

    /**
     * Deactivate a schedule
     */
    public function deactivate(AttendanceSchedule $attendanceSchedule)
    {
        $attendanceSchedule->update(['is_active' => false]);

        return redirect()->route('attendance-schedule.index')
            ->with('success', 'Jadwal presensi berhasil dinonaktifkan!');
    }

    /**
     * Delete schedule
     */
    public function destroy(AttendanceSchedule $attendanceSchedule)
    {
        if ($attendanceSchedule->is_active) {
            return back()->with('error', 'Tidak dapat menghapus jadwal yang sedang aktif!');
        }

        $attendanceSchedule->delete();

        return redirect()->route('attendance-schedule.index')
            ->with('success', 'Jadwal presensi berhasil dihapus!');
    }
}
