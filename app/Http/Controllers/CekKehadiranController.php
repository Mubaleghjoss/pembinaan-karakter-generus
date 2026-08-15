<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CekKehadiranController extends Controller
{
    /**
     * Admin/Pamong: list attendance point transactions with filters.
     */
    public function index(Request $request)
    {
        $query = PointTransaction::where('source', 'attendance')
            ->with(['siswa.kelas']);

        // Filter by siswa
        if ($request->siswa_id) {
            $query->where('siswa_id', $request->siswa_id);
        }

        // Filter by date range
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by pamong's bimbingan students only (not admin)
        $user = Auth::user();
        $pamongSiswaIds = null;
        if ($user && $user->isTeacher()) {
            $pamongSiswaIds = \App\Models\PamongSiswa::active()->where('pamong_id', $user->id)
                ->pluck('siswa_id');
            $query->whereIn('siswa_id', $pamongSiswaIds);
        }

        $transactions = $query->latest()->paginate(20)->withQueryString();

        $stats = Cache::remember(
            'cek-kehadiran:stats:' . ($user && $user->isTeacher() ? 'pamong:' . $user->id : 'global'),
            now()->addSeconds(90),
            function () use ($user, $pamongSiswaIds) {
                $statsQuery = PointTransaction::query()
                    ->where('source', 'attendance')
                    ->where('points', '>', 0);

                if ($user && $user->isTeacher()) {
                    $statsQuery->whereIn('siswa_id', $pamongSiswaIds ?? []);
                }

                return (array) $statsQuery
                    ->selectRaw('COALESCE(SUM(points), 0) as total_attendance_points, COUNT(DISTINCT siswa_id) as total_students')
                    ->first();
            }
        );

        $totalAttendancePoints = (int) ($stats['total_attendance_points'] ?? 0);
        $totalStudents = (int) ($stats['total_students'] ?? 0);

        // Siswa list for filter dropdown
        if ($user && $user->isTeacher()) {
            $siswaList = Siswa::whereIn('id', $pamongSiswaIds ?? [])->orderBy('nama')->get();
        } else {
            $siswaList = Siswa::orderBy('nama')->get();
        }

        return view('cek-kehadiran.index', compact(
            'transactions', 'totalAttendancePoints', 'totalStudents', 'siswaList'
        ));
    }

    /**
     * Admin/Pamong: delete a specific attendance point transaction.
     */
    public function destroy(Request $request, PointTransaction $transaction)
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        // Ensure it's an attendance transaction
        if ($transaction->source !== 'attendance') {
            return back()->with('error', 'Transaksi ini bukan transaksi kehadiran.');
        }

        $reason = $request->reason;
        $actor = Auth::user()->username ?? Auth::user()->name ?? 'Admin';
        $pointsToReverse = $transaction->points;

        // Reverse the points
        if ($pointsToReverse > 0) {
            try {
                $gamificationService = app(GamificationService::class);
                $siswa = Siswa::find($transaction->siswa_id);
                if ($siswa) {
                    $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                    $siswaPoint->addPoints(
                        -$pointsToReverse,
                        'attendance',
                        'Hapus poin kehadiran: ' . $transaction->description . ' (-' . $pointsToReverse . ' poin). Alasan: ' . $reason . ' (oleh ' . $actor . ')',
                        null
                    );
                }
            } catch (\Exception $e) {
                \Log::warning('Gagal reverse poin kehadiran: ' . $e->getMessage());
            }
        }

        // Delete the original transaction
        $transaction->delete();

        return redirect()->route('cek-kehadiran.index')
            ->with('success', 'Poin kehadiran berhasil dihapus dan dikurangi dari leaderboard.');
    }

    /**
     * Siswa: view own attendance point history.
     */
    public function siswaIndex(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:hadir,terlambat,izin,sakit,alpha',
        ]);

        $siswa = Auth::guard('siswa')->user();

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->startOfMonth();
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfMonth();
        $status = $validated['status'] ?? null;

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $recordsQuery = Presensi::query()
            ->with(['verifier:id,name'])
            ->where('siswa_id', $siswa->id)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

        $records = (clone $recordsQuery)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk')
            ->paginate(20)
            ->withQueryString();

        $statusCounts = (clone $recordsQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value);

        $totalRecords = (int) $statusCounts->sum();
        $presentRecords = (int) (($statusCounts['hadir'] ?? 0) + ($statusCounts['terlambat'] ?? 0));
        $attendancePercentage = $totalRecords > 0
            ? round(($presentRecords / $totalRecords) * 100, 1)
            : 0;

        $summary = [
            'total' => $totalRecords,
            'hadir' => (int) ($statusCounts['hadir'] ?? 0),
            'terlambat' => (int) ($statusCounts['terlambat'] ?? 0),
            'izin' => (int) ($statusCounts['izin'] ?? 0),
            'sakit' => (int) ($statusCounts['sakit'] ?? 0),
            'alpha' => (int) ($statusCounts['alpha'] ?? 0),
            'present' => $presentRecords,
            'percentage' => $attendancePercentage,
        ];

        $pointTransactions = PointTransaction::where('siswa_id', $siswa->id)
            ->where('source', 'attendance')
            ->latest()
            ->limit(5)
            ->get();

        ['totalPoints' => $totalPoints, 'totalHadir' => $totalHadir] = $this->getAttendanceSummary($siswa->id, 'siswa');

        $adminContact = User::query()
            ->with('role:id,name')
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('name', 'admin'))
            ->orderBy('id')
            ->first(['id', 'name', 'username', 'role_id']);

        $statusLabels = $this->attendanceStatusLabels();

        return view('siswa.kehadiran.index', compact(
            'records',
            'summary',
            'totalPoints',
            'totalHadir',
            'pointTransactions',
            'adminContact',
            'statusLabels',
            'startDate',
            'endDate',
            'status'
        ));
    }

    /**
     * Ortu: view child's attendance point history.
     */
    public function ortuIndex(Request $request)
    {
        $siswa = Auth::guard('ortu')->user();
        
        $transactions = PointTransaction::where('siswa_id', $siswa->id)
            ->where('source', 'attendance')
            ->latest()
            ->paginate(20);

        ['totalPoints' => $totalPoints, 'totalHadir' => $totalHadir] = $this->getAttendanceSummary($siswa->id, 'ortu');

        return view('ortu.kehadiran.index', compact('transactions', 'totalPoints', 'totalHadir'));
    }

    protected function getAttendanceSummary(int $siswaId, string $context): array
    {
        $summary = Cache::remember(
            "cek-kehadiran:summary:{$context}:{$siswaId}",
            now()->addSeconds(90),
            function () use ($siswaId) {
                return (array) PointTransaction::query()
                    ->where('siswa_id', $siswaId)
                    ->where('source', 'attendance')
                    ->selectRaw('COALESCE(SUM(points), 0) as total_points')
                    ->selectRaw('SUM(CASE WHEN points > 0 THEN 1 ELSE 0 END) as total_hadir')
                    ->first();
            }
        );

        return [
            'totalPoints' => (int) ($summary['total_points'] ?? 0),
            'totalHadir' => (int) ($summary['total_hadir'] ?? 0),
        ];
    }

    protected function attendanceStatusLabels(): array
    {
        return [
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpha' => 'Tidak Hadir',
        ];
    }
}
