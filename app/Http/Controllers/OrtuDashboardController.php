<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\Berita;
use App\Models\Karakter;
use App\Models\Presensi;
use App\Models\ShareInfo;
use App\Models\SiswaKarakterChecklist;
use App\Support\BiometricStatus;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class OrtuDashboardController extends Controller
{
    public function index(GamificationService $gamificationService)
    {
        $siswa = Auth::guard('ortu')->user();
        $siswa->load('kelas');
        $dashboardData = Cache::remember(
            "ortu:dashboard:{$siswa->id}:" . today()->toDateString(),
            now()->addSeconds(90),
            function () use ($siswa, $gamificationService) {
                $gamificationStats = null;

                try {
                    $gamificationStats = $gamificationService->getSiswaStats($siswa);
                } catch (\Exception $e) {
                    \Log::warning('Failed to get gamification stats: ' . $e->getMessage());
                }

                $biometricStatus = BiometricStatus::resolve($siswa->id, 'ortu');

                return [
                    'todayPresensi' => Presensi::where('siswa_id', $siswa->id)
                        ->whereDate('tanggal', today())
                        ->first(),
                    'activeSchedule' => AttendanceSchedule::getActiveSchedule(),
                    'berita' => Berita::published()
                        ->orderByDesc('published_at')
                        ->take(3)
                        ->get(),
                    'shareInfos' => ShareInfo::active()
                        ->forTarget('ortu')
                        ->orderByDesc('created_at')
                        ->get(),
                    'totalTasks' => Karakter::active()->count(),
                    'verifiedTasks' => SiswaKarakterChecklist::where('siswa_id', $siswa->id)
                        ->whereNotNull('verified_at')
                        ->count(),
                    'gamificationStats' => $gamificationStats,
                    'allLevels' => \App\Models\Level::active()->orderBy('level')->get(),
                    'hasBiometricOrtu' => $biometricStatus['has_valid_credential'],
                    'biometricStatusOrtu' => $biometricStatus['status'],
                    'legacyBiometricOrtuCount' => $biometricStatus['legacy_count'],
                ];
            }
        );

        return view('ortu.dashboard', array_merge(['siswa' => $siswa], $dashboardData));
    }

    public function settings()
    {
        $siswa = Auth::guard('ortu')->user();
        $siswa->load('generusRegistration');
        return view('ortu.settings', compact('siswa'));
    }

    public function updateSettings(Request $request)
    {
        $siswa = Auth::guard('ortu')->user();

        $request->validate([
            'ortu_username' => 'required|string|min:3|max:50|unique:siswa,ortu_username,' . $siswa->id,
        ]);

        $siswa->ortu_username = $request->ortu_username;
        $siswa->save();

        return back()->with('success', 'Username berhasil diubah.');
    }

    public function updatePassword(Request $request)
    {
        $siswa = Auth::guard('ortu')->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        // Check against ortu's own password only (independent from siswa password)
        if (!$siswa->ortu_password || !\Hash::check($request->current_password, $siswa->ortu_password)) {
            return back()->withErrors(['current_password' => 'Password lama salah.']);
        }

        $siswa->ortu_password = $request->new_password;
        $siswa->save();

        return back()->with('success', 'Password berhasil diubah.');
    }
}
