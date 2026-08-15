<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\Karakter;
use App\Models\LaporanPenyaksian;
use App\Models\PamongPresensi;
use App\Models\Presensi;
use App\Models\ShareInfo;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Models\SiswaPoint;
use App\Models\TracerKarakter;
use App\Models\User;
use App\Support\BiometricStatus;
use App\Services\Contracts\PamongQrServiceInterface;
use App\Services\MateriRppJournalWorkflowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        protected MateriRppJournalWorkflowService $journalWorkflow,
        protected PamongQrServiceInterface $pamongQrService
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user()->loadMissing('role');
        $today = Carbon::today();
        $dashboardQrData = $this->pamongQrService->isPamong($user)
            ? $this->pamongQrService->getQrData($user)
            : null;

        return view('dashboard', array_merge(
            $this->getPrimaryDashboardData($user, $today),
            [
                'user' => $user,
                'pageTitle' => 'Dashboard',
                'dashboardQrData' => $dashboardQrData,
            ]
        ));
    }

    public function secondaryPanels()
    {
        $user = Auth::user()->loadMissing('role');
        $today = Carbon::today();

        return view('dashboard.partials.secondary-panels', $this->getSecondaryDashboardData($user, $today));
    }

    private function getScopedSiswaIds(User $user): array
    {
        return $user->isTeacher()
            ? $user->getAssignedSiswaIds()
            : Siswa::active()->pluck('id')->all();
    }

    private function getPrimaryDashboardData(User $user, Carbon $today): array
    {
        $cacheKey = sprintf(
            'dashboard:primary:%s:%d:%s',
            preg_replace('/[^a-z0-9_]+/i', '_', $user->role->name ?? 'user'),
            $user->id,
            $today->format('YmdHi')
        );

        return Cache::remember($cacheKey, now()->addSeconds(60), function () use ($user, $today) {
            $siswaIds = $this->getScopedSiswaIds($user);
            $totalSiswa = count($siswaIds);
            $siswaColumns = ['id', 'nama', 'nis', 'alamat'];

            if (Siswa::hasKelompokColumn()) {
                $siswaColumns[] = 'kelompok';
            }

            $todayPresensi = Presensi::whereIn('siswa_id', $siswaIds)
                ->whereDate('tanggal', $today)
                ->with(['siswa' => fn ($query) => $query->select($siswaColumns)])
                ->get();

            $hadirSiswaIds = $todayPresensi->pluck('siswa_id')->toArray();
            $alphaSiswa = Siswa::whereIn('id', $siswaIds)
                ->whereNotIn('id', $hadirSiswaIds)
                ->where('is_active', true)
                ->select($siswaColumns)
                ->get();

            $attendanceStats = [
                'hadir' => $todayPresensi->where('status', 'hadir')->count(),
                'terlambat' => $todayPresensi->where('status', 'terlambat')->count(),
                'izin' => $todayPresensi->where('status', 'izin')->count(),
                'sakit' => $todayPresensi->where('status', 'sakit')->count(),
                'alpha' => $totalSiswa - $todayPresensi->count(),
                'total' => $totalSiswa,
                'percentage' => $totalSiswa > 0 ? round(($todayPresensi->count() / $totalSiswa) * 100, 1) : 0,
                'siswa_hadir' => $todayPresensi->where('status', 'hadir')->pluck('siswa')->values(),
                'siswa_terlambat' => $todayPresensi->where('status', 'terlambat')->pluck('siswa')->values(),
                'siswa_izin' => $todayPresensi->where('status', 'izin')->pluck('siswa')->values(),
                'siswa_sakit' => $todayPresensi->where('status', 'sakit')->pluck('siswa')->values(),
                'siswa_alpha' => $alphaSiswa,
            ];

            $periodStart = $today->copy()->subDays(6);
            $weeklyCounts = Presensi::whereIn('siswa_id', $siswaIds)
                ->whereDate('tanggal', '>=', $periodStart)
                ->whereDate('tanggal', '<=', $today)
                ->whereIn('status', ['hadir', 'terlambat'])
                ->selectRaw('DATE(tanggal) as attendance_date, COUNT(*) as aggregate_count')
                ->groupBy('attendance_date')
                ->pluck('aggregate_count', 'attendance_date');

            $weeklyTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = $today->copy()->subDays($i);
                $count = (int) ($weeklyCounts[$date->toDateString()] ?? 0);

                $weeklyTrend[] = [
                    'date' => $date->format('D'),
                    'count' => $count,
                    'percentage' => $totalSiswa > 0 ? round(($count / $totalSiswa) * 100, 1) : 0,
                ];
            }

            $totalKarakter = Karakter::active()->count();
            $todayKarakterChecks = TracerKarakter::whereIn('siswa_id', $siswaIds)
                ->whereDate('checked_at', $today)
                ->count();

            $karakterProgress = TracerKarakter::whereIn('siswa_id', $siswaIds)
                ->selectRaw('siswa_id, COUNT(DISTINCT karakter_id) as checked_count')
                ->groupBy('siswa_id')
                ->get();

            $avgKarakterProgress = $karakterProgress->count() > 0
                ? round($karakterProgress->avg('checked_count') / max($totalKarakter, 1) * 100, 1)
                : 0;

            $topStudents = SiswaPoint::whereIn('siswa_id', $siswaIds)
                ->with('siswa')
                ->orderBy('total_points', 'desc')
                ->limit(10)
                ->get();

            $availableKarakter = Karakter::active()->get()->filter(fn ($karakter) => $karakter->isAvailable());
            $karakterByKategori = [
                'harian' => $availableKarakter->where('kategori', 'harian')->count(),
                'mingguan' => $availableKarakter->where('kategori', 'mingguan')->count(),
                'bulanan' => $availableKarakter->where('kategori', 'bulanan')->count(),
                'total' => $availableKarakter->count(),
            ];

            $pendingVerifications = SiswaKarakterChecklist::whereIn('siswa_id', $siswaIds)
                ->whereNull('verified_at')
                ->count();
            $totalTugasSubmitted = SiswaKarakterChecklist::whereIn('siswa_id', $siswaIds)->count();

            $pamongAttendanceStats = [];
            if ($user->isAdmin()) {
                $totalPamong = User::whereHas('role', function ($query) {
                    $query->whereIn('name', User::attendanceRoleNames());
                })->where('status', 'active')->count();

                $todayPamongPresensi = PamongPresensi::whereDate('tanggal', $today)
                    ->with('user:id,username,name')
                    ->get();

                $hadirPamongIds = $todayPamongPresensi->pluck('user_id')->toArray();
                $alphaPamong = User::whereHas('role', function ($query) {
                    $query->whereIn('name', User::attendanceRoleNames());
                })
                    ->where('status', 'active')
                    ->whereNotIn('id', $hadirPamongIds)
                    ->select('id', 'username', 'name')
                    ->get();

                $pamongAttendanceStats = [
                    'hadir' => $todayPamongPresensi->where('status', 'hadir')->count(),
                    'terlambat' => $todayPamongPresensi->where('status', 'terlambat')->count(),
                    'izin' => $todayPamongPresensi->where('status', 'izin')->count(),
                    'sakit' => $todayPamongPresensi->where('status', 'sakit')->count(),
                    'alpha' => $totalPamong - $todayPamongPresensi->count(),
                    'total' => $totalPamong,
                    'percentage' => $totalPamong > 0 ? round(($todayPamongPresensi->count() / $totalPamong) * 100, 1) : 0,
                    'pamong_hadir' => $todayPamongPresensi->where('status', 'hadir')->pluck('user')->values(),
                    'pamong_terlambat' => $todayPamongPresensi->where('status', 'terlambat')->pluck('user')->values(),
                    'pamong_izin' => $todayPamongPresensi->where('status', 'izin')->pluck('user')->values(),
                    'pamong_sakit' => $todayPamongPresensi->where('status', 'sakit')->pluck('user')->values(),
                    'pamong_alpha' => $alphaPamong,
                ];
            }

            $activeSchedule = $user->usesPamongPermissionSystem()
                ? AttendanceSchedule::getActiveSchedule(AttendanceSchedule::TARGET_PAMONG)
                : AttendanceSchedule::getActiveSchedule();
            $hasScheduleToday = false;
            if ($activeSchedule) {
                $todayDay = strtolower(now()->format('l'));
                $hasScheduleToday = empty($activeSchedule->days) || in_array($todayDay, $activeSchedule->days);
            }

            $myAttendanceToday = null;
            $attendanceScheduleOpen = false;
            if ($user->usesPamongPermissionSystem()) {
                $myAttendanceToday = PamongPresensi::where('user_id', $user->id)
                    ->whereDate('tanggal', $today)
                    ->first();

                $attendanceScheduleOpen = $activeSchedule && $activeSchedule->isOpen();
            }

            $shareInfos = ShareInfo::active()
                ->forTarget('pamong')
                ->orderByDesc('created_at')
                ->get();

            $laporanPending = LaporanPenyaksian::when(! $user->isAdmin(), fn($query) => $query->forPamong($user->id))
                ->pending()
                ->count();
            $journalTasks = $this->journalWorkflow->staffTasks($user);

            return [
                'totalSiswa' => $totalSiswa,
                'attendanceStats' => $attendanceStats,
                'weeklyTrend' => $weeklyTrend,
                'totalKarakter' => $totalKarakter,
                'todayKarakterChecks' => $todayKarakterChecks,
                'avgKarakterProgress' => $avgKarakterProgress,
                'topStudents' => $topStudents,
                'pamongAttendanceStats' => $pamongAttendanceStats,
                'myAttendanceToday' => $myAttendanceToday,
                'attendanceScheduleOpen' => $attendanceScheduleOpen,
                'shareInfos' => $shareInfos,
                'hasScheduleToday' => $hasScheduleToday,
                'karakterByKategori' => $karakterByKategori,
                'pendingVerifications' => $pendingVerifications,
                'totalTugasSubmitted' => $totalTugasSubmitted,
                'laporanPending' => $laporanPending,
                'journalTasks' => $journalTasks,
            ];
        });
    }

    private function getSecondaryDashboardData(User $user, Carbon $today): array
    {
        $cacheKey = sprintf(
            'dashboard:secondary:%s:%d:%s',
            preg_replace('/[^a-z0-9_]+/i', '_', $user->role->name ?? 'user'),
            $user->id,
            $today->format('YmdHi')
        );

        return Cache::remember($cacheKey, now()->addSeconds(60), function () use ($user, $today) {
            $siswaIds = $this->getScopedSiswaIds($user);

            $topStudents = SiswaPoint::whereIn('siswa_id', $siswaIds)
                ->with('siswa')
                ->orderBy('total_points', 'desc')
                ->limit(10)
                ->get();

            $recentPresensi = Presensi::whereIn('siswa_id', $siswaIds)
                ->with('siswa')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $recentKarakter = SiswaKarakterChecklist::whereIn('siswa_id', $siswaIds)
                ->whereNotNull('verified_at')
                ->with(['siswa', 'karakter'])
                ->orderBy('verified_at', 'desc')
                ->limit(5)
                ->get();

            $verifiedPerSiswa = SiswaKarakterChecklist::whereIn('siswa_id', $siswaIds)
                ->whereNotNull('verified_at')
                ->selectRaw('siswa_id, COUNT(*) as total_verified')
                ->groupBy('siswa_id')
                ->with('siswa:id,nama,nis')
                ->orderByDesc('total_verified')
                ->limit(10)
                ->get();

            $biometricStatus = BiometricStatus::resolve($user->id, 'admin');

            return [
                'topStudents' => $topStudents,
                'recentPresensi' => $recentPresensi,
                'recentKarakter' => $recentKarakter,
                'verifiedPerSiswa' => $verifiedPerSiswa,
                'hasBiometricAdmin' => $biometricStatus['has_valid_credential'],
                'biometricStatusAdmin' => $biometricStatus['status'],
                'legacyBiometricAdminCount' => $biometricStatus['legacy_count'],
            ];
        });
    }
}
