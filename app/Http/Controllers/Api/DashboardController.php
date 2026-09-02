<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesSiswaToken;
use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesSiswaToken;

    public function stats(Request $request)
    {
        // Dasbor ini bersifat seluruh sekolah (total siswa, nama siswa lain).
        // Token milik model Siswa (akun siswa & orang tua) tidak boleh
        // melihatnya: rutenya berada di luar middleware `role.permission`,
        // jadi penjagaannya harus di controller.
        if ($this->siswaFromToken($request) !== null) {
            return $this->forbiddenStaffOnly();
        }

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

    public function recentActivities(Request $request)
    {
        if ($this->siswaFromToken($request) !== null) {
            return $this->forbiddenStaffOnly();
        }

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

    /**
     * Respons 403 standar untuk endpoint yang hanya boleh diakses staf.
     */
    private function forbiddenStaffOnly()
    {
        return response()->json([
            'success' => false,
            'error' => 'Forbidden',
            'message' => 'Dasbor sekolah hanya untuk akun staf/pamong',
            'code' => 'STAFF_ONLY',
        ], 403);
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
