<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Siswa;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        try {
            $today = Carbon::today();

            $totalStudents = Siswa::count();
            $presentToday = Presensi::whereDate('tanggal', $today)
                ->where('status', 'hadir')
                ->count();
            $absentToday = Presensi::whereDate('tanggal', $today)
                ->where('status', 'alpha')
                ->count();

            $lateToday = Presensi::whereDate('tanggal', $today)
                ->where('status', 'terlambat')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_students' => $totalStudents,
                    'present_today' => $presentToday,
                    'absent_today' => $absentToday,
                    'late_today' => $lateToday,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load stats',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function recentActivities()
    {
        try {
            $activities = Presensi::with(['siswa'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($presensi) {
                    return [
                        'id' => $presensi->id,
                        'student_name' => $presensi->siswa->nama ?? 'Unknown',
                        'action' => $this->getActionText($presensi->status),
                        'time' => $presensi->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $activities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load activities',
                'data' => [],
            ], 200); // Return 200 with empty data instead of 500
        }
    }

    private function getActionText($status)
    {
        switch ($status) {
            case 'hadir': return 'telah hadir';
            case 'terlambat': return 'terlambat hadir';
            case 'izin': return 'izin tidak hadir';
            case 'sakit': return 'sakit';
            case 'alpha': return 'tanpa keterangan';
            default: return 'melakukan presensi';
        }
    }
}
