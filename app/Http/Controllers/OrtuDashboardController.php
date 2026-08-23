<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Karakter;
use App\Models\Presensi;
use App\Models\QuranReadingEntry;
use App\Models\ShareInfo;
use App\Models\SiswaKarakterChecklist;
use App\Support\BiometricStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class OrtuDashboardController extends Controller
{
    /**
     * Bulan awal program PKG. Rekap presensi ditampilkan mulai dari bulan ini.
     */
    private const PKG_START = '2024-11-01';

    public function index()
    {
        $siswa = Auth::guard('ortu')->user();
        $siswa->load('kelas');

        $dashboardData = Cache::remember(
            "ortu:dashboard:v2:{$siswa->id}:" . today()->toDateString(),
            now()->addSeconds(90),
            function () use ($siswa) {
                $biometricStatus = BiometricStatus::resolve($siswa->id, 'ortu');

                return [
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
                    'attendanceMonths' => $this->monthlyAttendance($siswa->id),
                    'attendanceTotals' => $this->attendanceTotals($siswa->id),
                    'quranSummary' => $this->quranSummary($siswa->id),
                    'hasBiometricOrtu' => $biometricStatus['has_valid_credential'],
                    'biometricStatusOrtu' => $biometricStatus['status'],
                    'legacyBiometricOrtuCount' => $biometricStatus['legacy_count'],
                ];
            }
        );

        return view('ortu.dashboard', array_merge(['siswa' => $siswa], $dashboardData));
    }

    /**
     * Rekap presensi PKG per bulan (hanya bulan yang punya data), terbaru di atas.
     * Sumber: tabel presensi milik anak sejak bulan awal program PKG.
     */
    private function monthlyAttendance(int $siswaId): array
    {
        $rows = Presensi::query()
            ->where('siswa_id', $siswaId)
            ->whereDate('tanggal', '>=', self::PKG_START)
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as periode")
            ->selectRaw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir")
            ->selectRaw("SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat")
            ->selectRaw("SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin")
            ->selectRaw("SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit")
            ->selectRaw("SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('periode')
            ->orderByDesc('periode')
            ->get();

        return $rows->map(function ($row) {
            $label = Carbon::createFromFormat('Y-m', $row->periode)->translatedFormat('F Y');

            return [
                'periode' => $row->periode,
                'label' => $label,
                'hadir' => (int) $row->hadir,
                'terlambat' => (int) $row->terlambat,
                'izin' => (int) $row->izin,
                'sakit' => (int) $row->sakit,
                'alpha' => (int) $row->alpha,
                'total' => (int) $row->total,
            ];
        })->all();
    }

    /**
     * Total presensi keseluruhan sejak program PKG dimulai.
     */
    private function attendanceTotals(int $siswaId): array
    {
        $row = Presensi::query()
            ->where('siswa_id', $siswaId)
            ->whereDate('tanggal', '>=', self::PKG_START)
            ->selectRaw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir")
            ->selectRaw("SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat")
            ->selectRaw("SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin")
            ->selectRaw("SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit")
            ->selectRaw("SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha")
            ->selectRaw('COUNT(*) as total')
            ->first();

        return [
            'hadir' => (int) ($row->hadir ?? 0),
            'terlambat' => (int) ($row->terlambat ?? 0),
            'izin' => (int) ($row->izin ?? 0),
            'sakit' => (int) ($row->sakit ?? 0),
            'alpha' => (int) ($row->alpha ?? 0),
            'total' => (int) ($row->total ?? 0),
        ];
    }

    /**
     * Ringkasan singkat bacaan Al-Qur'an untuk kartu pengingat.
     */
    private function quranSummary(int $siswaId): array
    {
        $verified = QuranReadingEntry::query()
            ->where('siswa_id', $siswaId)
            ->where('status', QuranReadingEntry::STATUS_VERIFIED);

        $lastDate = (clone $verified)->max('reading_date');
        $thisMonth = (clone $verified)
            ->whereBetween('reading_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        $pending = QuranReadingEntry::query()
            ->where('siswa_id', $siswaId)
            ->where('status', QuranReadingEntry::STATUS_PENDING)
            ->count();

        return [
            'verified_total' => (clone $verified)->count(),
            'verified_this_month' => $thisMonth,
            'pending' => $pending,
            'last_date' => $lastDate ? Carbon::parse($lastDate) : null,
        ];
    }

    public function settings()
    {
        $siswa = Auth::guard('ortu')->user();
        $siswa->load(['generusRegistration', 'pamongAssignments.pamong:id,name,username']);
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
