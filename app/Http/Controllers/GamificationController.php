<?php

namespace App\Http\Controllers;

use App\Services\GamificationService;
use App\Models\Siswa;
use App\Models\Badge;
use App\Models\Level;
use App\Models\PointPeriod;
use App\Models\PointTransaction;
use App\Models\SiswaKarakterChecklist;
use App\Models\Setting;
use App\Models\SiswaPoint;
use App\Models\UserBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GamificationController extends Controller
{
    protected GamificationService $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;

        $this->middleware('auth')->only([
            'adminBadges',
            'adminCreateBadge',
            'adminUpdateBadge',
            'adminDeleteBadge',
            'adminLevels',
            'adminSavePointConfig',
            'adminStorePeriod',
            'adminActivatePeriod',
            'adminClosePeriod',
            'adminSyncActivePeriodTransactions',
            'adminSyncPeriodTransactions',
            'adminRestoreArchivedPeriodTasks',
            'adminCreateLevel',
            'adminUpdateLevel',
            'adminDeleteLevel',
            'adminAnalytics',
            'exportAnalytics',
            'adminAdjustPoints',
            'adminDeleteTransaction',
            'adminTransactions',
            'adminUpdateTransaction',
            'adminResetCharacterPoints',
            'adminResetBadges',
            'adminFullReset',
        ]);

        $this->middleware('pamong.permission:gamification,view')->only([
            'adminBadges',
            'adminLevels',
            'adminAnalytics',
            'adminTransactions',
        ]);
        $this->middleware('pamong.permission:gamification,create')->only([
            'adminCreateBadge',
            'adminCreateLevel',
            'adminSavePointConfig',
            'adminStorePeriod',
        ]);
        $this->middleware('pamong.permission:gamification,edit')->only([
            'adminUpdateBadge',
            'adminUpdateLevel',
            'adminActivatePeriod',
            'adminClosePeriod',
            'adminSyncActivePeriodTransactions',
            'adminSyncPeriodTransactions',
            'adminRestoreArchivedPeriodTasks',
            'adminUpdateTransaction',
        ]);
        $this->middleware('pamong.permission:gamification,delete')->only([
            'adminDeleteBadge',
            'adminDeleteLevel',
            'adminDeleteTransaction',
        ]);
        $this->middleware('pamong.permission:gamification,export')->only([
            'exportAnalytics',
        ]);
        $this->middleware('pamong.permission:gamification,adjust')->only([
            'adminAdjustPoints',
        ]);
        $this->middleware('pamong.permission:gamification,reset')->only([
            'adminResetCharacterPoints',
            'adminResetBadges',
            'adminFullReset',
        ]);
    }

    /**
     * Dashboard gamifikasi untuk siswa
     */
    public function dashboard()
    {
        $siswa = Auth::guard('siswa')->user();
        $stats = $this->gamificationService->getSiswaStats($siswa);
        $leaderboard = $this->gamificationService->getLeaderboard(5);
        
        // Auto-check & award any badges the student has qualified for
        $this->gamificationService->checkBadgeEligibility($siswa);
        
        $allBadges = $this->gamificationService->getBadgesWithProgress($siswa);
        $currentLevel = $stats['current_level']->level ?? 0;
        $reachedLevels = Level::active()
            ->with('rewardTemplates')
            ->where('level', '<=', $currentLevel)
            ->orderBy('level', 'desc')
            ->get();
        $totalRewards = $reachedLevels->sum(fn (Level $level) => count($level->benefits ?? []));

        return view('siswa.gamification.dashboard', compact(
            'stats',
            'leaderboard',
            'allBadges',
            'currentLevel',
            'reachedLevels',
            'totalRewards'
        ));
    }

    /**
     * Halaman leaderboard
     */
    public function leaderboard(Request $request)
    {
        $period = $request->get('period', 'all');
        $leaderboard = $this->gamificationService->getLeaderboard(50, $period === 'all' ? null : $period);
        
        $siswa = Auth::guard('siswa')->user();
        $myRank = null;
        if ($siswa) {
            $myPoints = $siswa->siswaPoint?->total_points ?? 0;
            $myRank = SiswaPoint::where('total_points', '>', $myPoints)->count() + 1;
        }

        if ($request->ajax()) {
            return response()->json([
                'leaderboard' => $leaderboard,
                'my_rank' => $myRank
            ]);
        }

        return view('siswa.gamification.leaderboard', compact('leaderboard', 'period', 'myRank'));
    }

    /**
     * Halaman koleksi badge
     */
    public function badges()
    {
        $siswa = Auth::guard('siswa')->user();
        $badges = $this->gamificationService->getBadgesWithProgress($siswa);
        
        $earnedCount = collect($badges)->where('earned', true)->count();
        $totalCount = count($badges);

        return view('siswa.gamification.badges', compact('badges', 'earnedCount', 'totalCount'));
    }

    /**
     * Detail badge
     */
    public function badgeDetail(Badge $badge)
    {
        $siswa = Auth::guard('siswa')->user();
        $earned = $siswa->badges()->where('badge_id', $badge->id)->exists();
        $earnedAt = $earned 
            ? $siswa->badges()->where('badge_id', $badge->id)->first()->pivot->earned_at 
            : null;

        // Get recent earners
        $recentEarners = $badge->users()
            ->orderByPivot('earned_at', 'desc')
            ->limit(10)
            ->get();

        return view('siswa.gamification.badge-detail', compact('badge', 'earned', 'earnedAt', 'recentEarners'));
    }

    /**
     * History poin
     */
    public function pointHistory(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        $periodId = $request->integer('period_id') ?: null;
        $periods = PointPeriod::query()->orderByDesc('start_date')->get();

        $transactions = $siswa->pointTransactions()
            ->when($periodId, function ($query) use ($periodId) {
                $query->where(function ($periodQuery) use ($periodId) {
                    $periodQuery->where('metadata->point_period_id', $periodId)
                        ->orWhere('metadata->period_id', $periodId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $stats = $this->gamificationService->getSiswaStats($siswa);

        if ($request->ajax()) {
            return response()->json([
                'transactions' => $transactions,
                'stats' => $stats
            ]);
        }

        return view('siswa.gamification.point-history', compact('transactions', 'stats', 'periods', 'periodId'));
    }

    /**
     * API: Get gamification widget data
     */
    public function widgetData()
    {
        $siswa = Auth::guard('siswa')->user();
        $stats = $this->gamificationService->getSiswaStats($siswa);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    // ============ ADMIN METHODS ============

    /**
     * Admin: Manage badges
     */
    public function adminBadges()
    {
        $badges = Badge::withCount('userBadges')->get();
        
        // Get global leaderboard (all students with points)
        $leaderboard = \App\Models\SiswaPoint::with('siswa.kelas')
            ->orderBy('total_points', 'desc')
            ->take(100)
            ->get();
        $levels = Level::query()->orderBy('level')->get(['id', 'level', 'nama']);
        
        return view('admin.gamification.badges', compact('badges', 'leaderboard', 'levels'));
    }

    /**
     * Admin: Create badge
     */
    public function adminCreateBadge(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'deskripsi' => 'required|string',
                'kategori' => 'required|in:attendance,character,general,level',
                'warna' => 'required|string|max:20',
                'icon' => 'nullable|string|max:20',
                'poin_reward' => 'nullable|integer|min:0',
                'kriteria' => 'required|array',
                'kriteria.type' => 'required|string',
                'kriteria.value' => 'required'
            ]);

            $validated['poin_reward'] = $validated['poin_reward'] ?? 0;
            $badge = Badge::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Badge berhasil dibuat',
                'data' => $badge
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', collect($e->errors())->flatten()->toArray())
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Update badge
     */
    public function adminUpdateBadge(Request $request, Badge $badge)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'deskripsi' => 'required|string',
                'kategori' => 'required|in:attendance,character,general,level',
                'warna' => 'required|string|max:20',
                'icon' => 'nullable|string|max:20',
                'poin_reward' => 'nullable|integer|min:0',
                'kriteria' => 'required|array',
                'is_active' => 'boolean'
            ]);

            $validated['poin_reward'] = $validated['poin_reward'] ?? 0;
            $badge->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Badge berhasil diupdate',
                'data' => $badge
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', collect($e->errors())->flatten()->toArray())
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Delete badge
     */
    public function adminDeleteBadge(Badge $badge)
    {
        $badge->delete();

        return response()->json([
            'success' => true,
            'message' => 'Badge berhasil dihapus'
        ]);
    }

    /**
     * Admin: Manage levels
     */
    public function adminLevels()
    {
        $levels = Level::withCount('siswaPoints')->orderBy('level')->get();
        $pointConfig = PointPeriod::defaultPointSettings();
        $periods = PointPeriod::query()->orderByDesc('start_date')->orderByDesc('id')->get();
        $activePeriod = PointPeriod::current();
        $levelBadges = Badge::query()
            ->where('kategori', 'level')
            ->where('is_active', true)
            ->get();
        $linkedPinsByLevel = $levelBadges
            ->filter(fn (Badge $badge) => ($badge->kriteria['type'] ?? '') === 'level_reached')
            ->groupBy(fn (Badge $badge) => (int) ($badge->kriteria['value'] ?? 0));
        $periodSummaries = $periods->mapWithKeys(function (PointPeriod $period) {
            $summary = $this->buildPeriodSummary($period);

            return [$period->id => $summary];
        });

        return view('admin.gamification.levels', compact('levels', 'pointConfig', 'periods', 'activePeriod', 'periodSummaries', 'levelBadges', 'linkedPinsByLevel'));
    }

    public function adminSavePointConfig(Request $request)
    {
        $validated = $request->validate([
            'points_hadir' => 'required|integer|min:0|max:1000',
            'points_terlambat' => 'required|integer|min:0|max:1000',
            'points_izin' => 'required|integer|min:0|max:1000',
            'points_sakit' => 'required|integer|min:0|max:1000',
            'points_alpha' => 'required|integer|min:0|max:1000',
            'points_karakter' => 'required|integer|min:0|max:1000',
            'points_streak_7' => 'required|integer|min:0|max:5000',
            'points_streak_30' => 'required|integer|min:0|max:5000',
            'points_perfect_month' => 'required|integer|min:0|max:5000',
            'apply_to_active_period' => 'nullable|boolean',
        ]);

        $config = collect($validated)
            ->except(['apply_to_active_period'])
            ->map(fn ($value) => (string) $value)
            ->all();

        Setting::setMany($config, 'gamification');

        if ($request->boolean('apply_to_active_period')) {
            $activePeriod = PointPeriod::current();
            if ($activePeriod) {
                $activePeriod->update([
                    'point_settings' => array_map('intval', collect($config)->all()),
                ]);
            }
        }

        return back()->with('success', 'Konfigurasi poin berhasil disimpan.');
    }

    public function adminStorePeriod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
            'activate_now' => 'nullable|boolean',
        ]);

        $period = new PointPeriod([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $request->boolean('activate_now') ? 'active' : 'draft',
            'activated_at' => $request->boolean('activate_now') ? now() : null,
            'point_settings' => PointPeriod::defaultPointSettings(),
        ]);
        $period->ensureSlug();

        if ($request->boolean('activate_now')) {
            PointPeriod::where('status', 'active')->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
        }

        $period->save();

        return back()->with('success', 'Periode poin berhasil dibuat.');
    }

    public function adminActivatePeriod(PointPeriod $period)
    {
        PointPeriod::where('status', 'active')->where('id', '!=', $period->id)->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $period->update([
            'status' => 'active',
            'activated_at' => now(),
            'closed_at' => null,
        ]);

        return back()->with('success', "Periode {$period->name} sekarang aktif.");
    }

    public function adminClosePeriod(PointPeriod $period)
    {
        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return back()->with('success', "Periode {$period->name} ditutup.");
    }

    public function adminSyncActivePeriodTransactions()
    {
        $period = PointPeriod::current();

        if (!$period) {
            return back()->with('error', 'Aktifkan periode poin terlebih dahulu sebelum menyinkronkan poin yang sudah ada.');
        }

        $updated = $this->syncTransactionsToPeriod($period);
        $resetSummary = $this->archiveRunningPointsAfterSync($period);
        $taskResetSummary = $this->archivePendingTasksForPeriod($period, now());

        if ($updated === 0 && $resetSummary['students_reset'] === 0 && $taskResetSummary['tasks_reset'] === 0) {
            return back()->with('success', "Tidak ada transaksi lama yang perlu disinkronkan ke periode aktif {$period->name}, saldo poin berjalan sudah 0, dan tidak ada tugas PKG pending yang perlu direset.");
        }

        return back()->with(
            'success',
            "{$updated} transaksi poin tersimpan ke periode aktif {$period->name}. "
            ."{$resetSummary['students_reset']} siswa direset saldo berjalannya dengan total "
            .number_format($resetSummary['points_archived'])." poin yang tetap terekam. "
            ."{$taskResetSummary['tasks_reset']} tugas PKG pending diarsipkan dari {$taskResetSummary['students_affected']} siswa. "
            .'Yuk semangat kumpulkan poin lagi untuk benefit yang ada.'
        );
    }

    public function adminSyncPeriodTransactions(PointPeriod $period)
    {
        $updated = $this->syncTransactionsToPeriod($period);
        $taskResetSummary = $this->archivePendingTasksForPeriod($period);

        if ($updated === 0 && $taskResetSummary['tasks_reset'] === 0) {
            return back()->with('success', "Tidak ada transaksi atau tugas pending yang perlu disinkronkan pada periode {$period->name}.");
        }

        return back()->with(
            'success',
            "{$updated} transaksi poin berhasil disinkronkan ke periode {$period->name}. "
            ."{$taskResetSummary['tasks_reset']} tugas PKG pending diarsipkan dari {$taskResetSummary['students_affected']} siswa."
        );
    }

    public function adminRestoreArchivedPeriodTasks(PointPeriod $period)
    {
        $deletedBy = Auth::id();
        $restored = 0;

        SiswaKarakterChecklist::onlyTrashed()
            ->whereNull('verified_at')
            ->where('deleted_reason', 'like', 'Reset otomatis saat sinkron periode ' . $period->name . '%')
            ->orderBy('id')
            ->chunkById(200, function ($checklists) use (&$restored, $deletedBy) {
                foreach ($checklists as $checklist) {
                    $checklist->restore();
                    $checklist->forceFill([
                        'deleted_by' => null,
                        'deleted_reason' => null,
                    ])->save();
                    $restored++;
                }
            });

        if ($restored === 0) {
            return back()->with('success', "Tidak ada tugas pending arsip yang perlu dipulihkan untuk periode {$period->name}.");
        }

        return back()->with('success', "{$restored} tugas pending periode {$period->name} berhasil dipulihkan ke status menunggu verifikasi. Bukti file yang sudah terhapus tidak dapat dipulihkan otomatis.");
    }

    protected function syncTransactionsToPeriod(PointPeriod $period): int
    {
        $periodMetadata = [
            'point_period_id' => $period->id,
            'period_id' => $period->id,
            'period_name' => $period->name,
            'period_start' => optional($period->start_date)->toDateString(),
            'period_end' => optional($period->end_date)->toDateString(),
        ];

        $query = PointTransaction::query()
            ->when($period->start_date, fn ($inner) => $inner->whereDate('created_at', '>=', $period->start_date))
            ->when($period->end_date, fn ($inner) => $inner->whereDate('created_at', '<=', $period->end_date))
            ->where(function ($inner) use ($period) {
                $inner->whereNull('metadata')
                    ->orWhereNull('metadata->point_period_id')
                    ->orWhereNull('metadata->period_id')
                    ->orWhere('metadata->point_period_id', '!=', $period->id)
                    ->orWhere('metadata->period_id', '!=', $period->id);
            })
            ->orderBy('id');

        $updated = 0;

        $query->chunkById(200, function ($transactions) use (&$updated, $periodMetadata) {
            foreach ($transactions as $transaction) {
                $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];

                $nextMetadata = array_merge($metadata, $periodMetadata);
                if (($metadata['point_period_id'] ?? null) == $periodMetadata['point_period_id']
                    && ($metadata['period_id'] ?? null) == $periodMetadata['period_id']) {
                    continue;
                }

                $transaction->metadata = $nextMetadata;
                $transaction->save();
                $updated++;
            }
        });

        return $updated;
    }

    protected function buildPeriodSummary(PointPeriod $period): array
    {
        $taskWindowStart = $this->getPeriodTaskWindowStart($period);
        $lastResetInfo = $this->getPeriodLastResetInfo($period);
        $archivedPendingSummary = $this->getArchivedPendingTaskSummary($period);

        $pointSummary = PointTransaction::query()
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('COUNT(DISTINCT siswa_id) as siswa_count')
            ->selectRaw('COALESCE(SUM(points), 0) as total_points')
            ->selectRaw("COALESCE(SUM(CASE WHEN points > 0 THEN points ELSE 0 END), 0) as incoming_points")
            ->selectRaw("COALESCE(SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END), 0) as outgoing_points")
            ->where(function ($query) use ($period) {
                $query->where('metadata->point_period_id', $period->id)
                    ->orWhere('metadata->period_id', $period->id);
            })
            ->first();

        $taskBaseQuery = SiswaKarakterChecklist::query()
            ->when($period->start_date, fn ($query) => $query->whereDate('checked_at', '>=', $period->start_date))
            ->when($period->end_date, fn ($query) => $query->whereDate('checked_at', '<=', $period->end_date))
            ->when($taskWindowStart, fn ($query) => $query->where('checked_at', '>', $taskWindowStart));

        $taskSummary = (clone $taskBaseQuery)
            ->selectRaw('COUNT(*) as task_count')
            ->selectRaw("SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_task_count")
            ->selectRaw("SUM(CASE WHEN verified_at IS NULL THEN 1 ELSE 0 END) as pending_task_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN verified_at IS NULL THEN siswa_id END) as pending_task_siswa_count")
            ->first();

        $livePendingStudentIds = (clone $taskBaseQuery)
            ->whereNull('verified_at')
            ->distinct()
            ->pluck('siswa_id');

        $historicalPendingTaskCount = (int) ($taskSummary->pending_task_count ?? 0) + (int) ($archivedPendingSummary['pending_task_count'] ?? 0);
        $historicalTaskCount = (int) ($taskSummary->task_count ?? 0) + (int) ($archivedPendingSummary['pending_task_count'] ?? 0);
        $historicalPendingStudentCount = $livePendingStudentIds
            ->merge($archivedPendingSummary['pending_student_ids'] ?? collect())
            ->unique()
            ->count();

        return [
            'transaction_count' => (int) ($pointSummary->transaction_count ?? 0),
            'siswa_count' => (int) ($pointSummary->siswa_count ?? 0),
            'total_points' => (int) ($pointSummary->total_points ?? 0),
            'incoming_points' => (int) ($pointSummary->incoming_points ?? 0),
            'outgoing_points' => (int) ($pointSummary->outgoing_points ?? 0),
            'task_count' => $historicalTaskCount,
            'verified_task_count' => (int) ($taskSummary->verified_task_count ?? 0),
            'pending_task_count' => $historicalPendingTaskCount,
            'pending_task_siswa_count' => $historicalPendingStudentCount,
            'archived_pending_task_count' => (int) ($archivedPendingSummary['pending_task_count'] ?? 0),
            'archived_pending_task_siswa_count' => (int) ($archivedPendingSummary['pending_task_siswa_count'] ?? 0),
            'last_reset_at' => $lastResetInfo['reset_at'] ?? null,
            'last_reset_label' => $lastResetInfo['label'] ?? null,
        ];
    }

    protected function getArchivedPendingTaskSummary(PointPeriod $period): array
    {
        $archivedBaseQuery = SiswaKarakterChecklist::onlyTrashed()
            ->whereNull('verified_at')
            ->where('deleted_reason', 'like', 'Reset otomatis saat sinkron periode ' . $period->name . '%')
            ->when($period->start_date, fn ($query) => $query->whereDate('checked_at', '>=', $period->start_date))
            ->when($period->end_date, fn ($query) => $query->whereDate('checked_at', '<=', $period->end_date));

        $summary = (clone $archivedBaseQuery)
            ->selectRaw('COUNT(*) as pending_task_count')
            ->selectRaw('COUNT(DISTINCT siswa_id) as pending_task_siswa_count')
            ->first();

        return [
            'pending_task_count' => (int) ($summary->pending_task_count ?? 0),
            'pending_task_siswa_count' => (int) ($summary->pending_task_siswa_count ?? 0),
            'pending_student_ids' => (clone $archivedBaseQuery)->distinct()->pluck('siswa_id'),
        ];
    }

    protected function getPeriodTaskWindowStart(PointPeriod $period): ?Carbon
    {
        $lastReset = PointTransaction::query()
            ->where('metadata->event', 'period_reset')
            ->where('metadata->archived_period_id', $period->id)
            ->latest('id')
            ->first(['created_at']);

        return $lastReset?->created_at;
    }

    protected function getPeriodLastResetInfo(PointPeriod $period): array
    {
        $lastReset = PointTransaction::query()
            ->where('metadata->event', 'period_reset')
            ->where('metadata->archived_period_id', $period->id)
            ->latest('id')
            ->first(['created_at', 'metadata']);

        if (! $lastReset) {
            return [];
        }

        $resetAt = $lastReset->created_at;
        $batchMarker = '[sync_at=' . $resetAt->format('Y-m-d H:i:s') . ']';
        $archivedTaskCount = SiswaKarakterChecklist::onlyTrashed()
            ->where('deleted_reason', 'like', 'Reset otomatis saat sinkron periode ' . $period->name . '%')
            ->where('deleted_reason', 'like', '%' . $batchMarker . '%')
            ->count();

        $label = 'Pending diarsipkan saat sinkron ' . $resetAt->format('d M Y H:i');
        if ($archivedTaskCount > 0) {
            $label .= ' (' . number_format($archivedTaskCount) . ' tugas)';
        }

        return [
            'reset_at' => $resetAt,
            'label' => $label,
        ];
    }

    protected function archiveRunningPointsAfterSync(PointPeriod $period): array
    {
        $studentsReset = 0;
        $pointsArchived = 0;

        SiswaPoint::query()
            ->where('total_points', '!=', 0)
            ->orderBy('id')
            ->chunkById(200, function ($siswaPoints) use ($period, &$studentsReset, &$pointsArchived) {
                foreach ($siswaPoints as $siswaPoint) {
                    $archivedPoints = (int) $siswaPoint->total_points;
                    $transaction = $siswaPoint->archiveCurrentBalanceToPeriod($period);

                    if ($transaction) {
                        $studentsReset++;
                        $pointsArchived += $archivedPoints;
                    }
                }
            });

        return [
            'students_reset' => $studentsReset,
            'points_archived' => $pointsArchived,
        ];
    }

    protected function archivePendingTasksForPeriod(PointPeriod $period, ?Carbon $upTo = null): array
    {
        $deletedBy = Auth::id();
        $tasksReset = 0;
        $studentsAffected = [];
        $syncAt = $upTo ? $upTo->copy() : now();
        $resetReason = 'Reset otomatis saat sinkron periode ' . $period->name
            . ' [sync_at=' . $syncAt->format('Y-m-d H:i:s') . ']';
        $upperBound = $upTo ?? ($period->end_date ? $period->end_date->copy()->endOfDay() : $syncAt);

        SiswaKarakterChecklist::query()
            ->whereNull('verified_at')
            ->when($period->start_date, fn ($query) => $query->whereDate('checked_at', '>=', $period->start_date))
            ->where('checked_at', '<=', $upperBound)
            ->orderBy('id')
            ->chunkById(200, function ($checklists) use (&$tasksReset, &$studentsAffected, $deletedBy, $resetReason) {
                foreach ($checklists as $checklist) {
                    $studentsAffected[$checklist->siswa_id] = true;

                    $checklist->update([
                        'deleted_by' => $deletedBy,
                        'deleted_reason' => $resetReason,
                    ]);
                    $checklist->clearStoredEvidenceFiles();
                    $checklist->delete();
                    $tasksReset++;
                }
            });

        return [
            'tasks_reset' => $tasksReset,
            'students_affected' => count($studentsAffected),
        ];
    }

    /**
     * Admin: Create level
     */
    public function adminCreateLevel(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|integer|min:1|unique:levels,level',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'min_points' => 'required|integer|min:0',
            'max_points' => 'nullable|integer',
            'warna' => 'required|string|max:7',
            'benefits' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        $level = Level::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Level berhasil dibuat',
            'data' => $level
        ]);
    }

    /**
     * Admin: Update level
     */
    public function adminUpdateLevel(Request $request, Level $level)
    {
        $validated = $request->validate([
            'level' => 'required|integer|min:1|unique:levels,level,' . $level->id,
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'min_points' => 'required|integer|min:0',
            'max_points' => 'nullable|integer',
            'warna' => 'required|string|max:7',
            'benefits' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        $level->update($validated);

        // Recalculate all student levels based on new thresholds
        $gamificationService = app(\App\Services\GamificationService::class);
        $recalcResult = $gamificationService->recalculateAllLevels();

        return response()->json([
            'success' => true,
            'message' => "Level berhasil diupdate. {$recalcResult['total_updated']} siswa disesuaikan levelnya.",
            'data' => $level,
            'recalculation' => $recalcResult
        ]);
    }

    /**
     * Admin: Delete level
     */
    public function adminDeleteLevel(Level $level)
    {
        // Check if any siswa is using this level
        if ($level->siswaPoints()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus level yang sedang digunakan siswa'
            ], 400);
        }

        $level->delete();

        return response()->json([
            'success' => true,
            'message' => 'Level berhasil dihapus'
        ]);
    }

    /**
     * Admin: Gamification analytics
     */
    public function adminAnalytics()
    {
        $cacheKey = 'gamification_admin_analytics:' . now()->format('YmdHi');

        $analyticsData = Cache::remember($cacheKey, now()->addSeconds(120), function () {
            $totalPoints = SiswaPoint::sum('total_points');
            $totalBadgesEarned = UserBadge::count();
            $avgPointsPerSiswa = SiswaPoint::avg('total_points');
            $activePointStudents = SiswaPoint::count();

            $levelDistribution = Level::withCount('siswaPoints')
                ->orderBy('level')
                ->get()
                ->map(fn($level) => ['name' => $level->nama, 'count' => $level->siswa_points_count]);

            $topBadges = Badge::withCount('userBadges')
                ->orderBy('user_badges_count', 'desc')
                ->limit(5)
                ->get();

            $recentActivity = \App\Models\PointTransaction::with('siswa')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $totalActiveSiswa = \App\Models\Siswa::active()->count();

            $taskAnalytics = \App\Models\Karakter::where('is_active', true)
                ->withCount(['checklists as total_completions'])
                ->withCount(['checklists as verified_completions' => function ($query) {
                    $query->whereNotNull('verified_at');
                }])
                ->withCount(['checklists as unique_students' => function ($query) {
                    $query->select(\Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT siswa_id)'));
                }])
                ->orderBy('total_completions', 'desc')
                ->get()
                ->map(function ($karakter) use ($totalActiveSiswa) {
                    $karakter->participation_rate = $totalActiveSiswa > 0
                        ? round(($karakter->unique_students / $totalActiveSiswa) * 100, 1)
                        : 0;
                    $karakter->verification_rate = $karakter->total_completions > 0
                        ? round(($karakter->verified_completions / $karakter->total_completions) * 100, 1)
                        : 0;
                    return $karakter;
                });

            $studentTaskStats = \App\Models\SiswaKarakterChecklist::select('siswa_id')
                ->selectRaw('COUNT(*) as total_tasks')
                ->selectRaw('SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_tasks')
                ->groupBy('siswa_id')
                ->orderBy('total_tasks', 'desc')
                ->with('siswa')
                ->limit(20)
                ->get();

            $dailyConsistency = \App\Models\SiswaKarakterChecklist::select('siswa_id')
                ->selectRaw('COUNT(DISTINCT DATE(checked_at)) as active_days')
                ->selectRaw('COUNT(*) as total_completions')
                ->selectRaw('MIN(DATE(checked_at)) as first_activity')
                ->selectRaw('MAX(DATE(checked_at)) as last_activity')
                ->groupBy('siswa_id')
                ->with('siswa')
                ->orderBy('active_days', 'desc')
                ->get()
                ->map(function ($item) {
                    $firstDate = $item->first_activity ? \Carbon\Carbon::parse($item->first_activity) : now();
                    $lastDate = $item->last_activity ? \Carbon\Carbon::parse($item->last_activity) : now();
                    $totalDays = max($firstDate->diffInDays($lastDate) + 1, 1);
                    $weekCount = max($totalDays / 7, 1);
                    $daysPerWeek = round($item->active_days / $weekCount, 1);
                    $item->total_span_days = $totalDays;
                    $item->days_per_week = $daysPerWeek;
                    $item->skip_days = $totalDays - $item->active_days;
                    $item->is_consistent = $daysPerWeek >= 3;
                    return $item;
                });

            $dailyActivity = \App\Models\SiswaKarakterChecklist::selectRaw('DATE(checked_at) as date')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('COUNT(DISTINCT siswa_id) as unique_siswa')
                ->where('checked_at', '>=', now()->subDays(14))
                ->groupByRaw('DATE(checked_at)')
                ->orderBy('date')
                ->get();

            $siswaOptions = Siswa::query()
                ->select(['id', 'nama', 'nis'])
                ->orderBy('nama')
                ->get();

            return compact(
                'totalPoints',
                'totalBadgesEarned',
                'avgPointsPerSiswa',
                'activePointStudents',
                'levelDistribution',
                'topBadges',
                'recentActivity',
                'taskAnalytics',
                'studentTaskStats',
                'totalActiveSiswa',
                'dailyConsistency',
                'dailyActivity',
                'siswaOptions'
            );
        });

        return view('admin.gamification.analytics', $analyticsData);
    }

    /**
     * Admin: Export analytics to CSV
     */
    public function exportAnalytics(Request $request)
    {
        $type = $request->get('type', 'consistency');
        $filename = "analytics_{$type}_" . date('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($type) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($type === 'consistency') {
                fputcsv($file, ['Nama', 'NIS', 'Hari Aktif', 'Hari Skip', 'Total Hari', 'Rata-rata/Minggu', 'Status']);
                
                $data = \App\Models\SiswaKarakterChecklist::query()
                    ->join('siswa', 'siswa.id', '=', 'siswa_karakter_checklists.siswa_id')
                    ->selectRaw('COUNT(DISTINCT DATE(checked_at)) as active_days')
                    ->selectRaw('MIN(DATE(checked_at)) as first_activity')
                    ->selectRaw('MAX(DATE(checked_at)) as last_activity')
                    ->addSelect('siswa_karakter_checklists.siswa_id', 'siswa.nama', 'siswa.nis')
                    ->groupBy('siswa_karakter_checklists.siswa_id', 'siswa.nama', 'siswa.nis')
                    ->orderByDesc('active_days');

                foreach ($data->cursor() as $item) {
                    $firstDate = $item->first_activity ? \Carbon\Carbon::parse($item->first_activity) : now();
                    $lastDate = $item->last_activity ? \Carbon\Carbon::parse($item->last_activity) : now();
                    $totalDays = max($firstDate->diffInDays($lastDate) + 1, 1);
                    $weekCount = max($totalDays / 7, 1);
                    $daysPerWeek = round($item->active_days / $weekCount, 1);
                    $skipDays = $totalDays - $item->active_days;
                    $status = $daysPerWeek >= 3 ? 'Rutin' : 'Tidak Rutin';

                    fputcsv($file, [
                        $item->nama ?? '-',
                        $item->nis ?? '-',
                        $item->active_days,
                        $skipDays,
                        $totalDays,
                        $daysPerWeek,
                        $status,
                    ]);
                }
            } elseif ($type === 'tasks') {
                fputcsv($file, ['Tugas', 'Kategori', 'Total Selesai', 'Siswa Unik', 'Partisipasi %', 'Verifikasi %']);
                $totalActiveSiswa = \App\Models\Siswa::active()->count();
                $tasks = \App\Models\Karakter::where('is_active', true)
                    ->withCount(['checklists as total_completions'])
                    ->withCount(['checklists as verified_completions' => fn($q) => $q->whereNotNull('verified_at')])
                    ->withCount(['checklists as unique_students' => fn($q) => $q->select(\Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT siswa_id)'))])
                    ->orderBy('total_completions', 'desc')
                    ->get();

                foreach ($tasks as $task) {
                    $participation = $totalActiveSiswa > 0 ? round(($task->unique_students / $totalActiveSiswa) * 100, 1) : 0;
                    $verification = $task->total_completions > 0 ? round(($task->verified_completions / $task->total_completions) * 100, 1) : 0;
                    fputcsv($file, [$task->nama, $task->kategori_label, $task->total_completions, $task->unique_students, $participation, $verification]);
                }
            } elseif ($type === 'ranking') {
                fputcsv($file, ['#', 'Nama', 'NIS', 'Total Tugas', 'Terverifikasi', 'Persentase']);
                $stats = \App\Models\SiswaKarakterChecklist::query()
                    ->join('siswa', 'siswa.id', '=', 'siswa_karakter_checklists.siswa_id')
                    ->selectRaw('COUNT(*) as total_tasks')
                    ->selectRaw('SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_tasks')
                    ->addSelect('siswa_karakter_checklists.siswa_id', 'siswa.nama', 'siswa.nis')
                    ->groupBy('siswa_karakter_checklists.siswa_id', 'siswa.nama', 'siswa.nis')
                    ->orderByDesc('total_tasks');

                $rank = 1;
                foreach ($stats->cursor() as $stat) {
                    $pct = $stat->total_tasks > 0 ? round(($stat->verified_tasks / $stat->total_tasks) * 100) : 0;
                    fputcsv($file, [$rank++, $stat->nama ?? '-', $stat->nis ?? '-', $stat->total_tasks, $stat->verified_tasks, $pct . '%']);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Admin: Manual point adjustment
     */
    public function adminAdjustPoints(Request $request)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'points' => 'required|integer',
            'description' => 'required|string|max:255'
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $siswaPoint = $this->gamificationService->getOrCreateSiswaPoint($siswa);
        
        $transaction = $siswaPoint->addPoints(
            $validated['points'],
            'manual',
            $validated['description']
        );

        return response()->json([
            'success' => true,
            'message' => 'Poin berhasil disesuaikan',
            'data' => $transaction
        ]);
    }

    /**
     * Admin: Delete a point transaction and recalculate totals
     */
    public function adminDeleteTransaction(\App\Models\PointTransaction $transaction)
    {
        $siswaId = $transaction->siswa_id;
        $description = $transaction->description;
        $points = $transaction->points;

        // Delete the transaction
        $transaction->delete();

        // Recalculate total points for the student
        $siswaPoint = SiswaPoint::where('siswa_id', $siswaId)->first();
        if ($siswaPoint) {
            $siswaPoint->syncCurrentCycleTotals();
        }

        return back()->with('success', "Log \"{$description}\" ({$points} poin) berhasil dihapus.");
    }

    /**
     * Admin: List all point transactions with filters
     */
    public function adminTransactions(Request $request)
    {
        $periodId = $request->integer('period_id') ?: null;
        $periods = PointPeriod::query()->orderByDesc('start_date')->orderByDesc('id')->get();
        $selectedPeriod = $periodId ? $periods->firstWhere('id', $periodId) : null;
        $activePeriod = PointPeriod::current();
        $periodSummaries = $periods->mapWithKeys(function (PointPeriod $period) {
            return [$period->id => $this->buildPeriodSummary($period)];
        });

        $query = \App\Models\PointTransaction::with('siswa')
            ->orderBy('created_at', 'desc');

        if ($periodId) {
            $query->where(function ($periodQuery) use ($periodId) {
                $periodQuery->where('metadata->point_period_id', $periodId)
                    ->orWhere('metadata->period_id', $periodId);
            });
        }

        // Filter by student
        if ($request->siswa_id) {
            $query->where('siswa_id', $request->siswa_id);
        }

        // Filter by source
        if ($request->source) {
            if ($request->source === 'period_reset') {
                $query->where('metadata->event', 'period_reset');
            } elseif ($request->source === 'manual') {
                $query->where('source', 'manual')
                    ->where(function ($manualQuery) {
                        $manualQuery->whereNull('metadata->event')
                            ->orWhere('metadata->event', '!=', 'period_reset');
                    });
            } else {
                $query->where('source', $request->source);
            }
        }

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Search description
        if ($request->q) {
            $query->where('description', 'like', '%' . $request->q . '%');
        }

        $summaryQuery = (clone $query)->reorder();
        $summary = $summaryQuery
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('COUNT(DISTINCT siswa_id) as siswa_count')
            ->selectRaw('COALESCE(SUM(points), 0) as total_points')
            ->selectRaw("COALESCE(SUM(CASE WHEN points > 0 THEN points ELSE 0 END), 0) as incoming_points")
            ->selectRaw("COALESCE(SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END), 0) as outgoing_points")
            ->first();

        $transactions = $query->paginate(25)->appends($request->query());
        $siswaList = \App\Models\Siswa::active()->orderBy('nama')->get(['id', 'nama', 'nis']);

        return view('admin.gamification.transactions', compact(
            'transactions',
            'siswaList',
            'periods',
            'activePeriod',
            'periodSummaries',
            'selectedPeriod',
            'summary'
        ));
    }

    /**
     * Admin: Update a point transaction and recalculate totals
     */
    public function adminUpdateTransaction(Request $request, \App\Models\PointTransaction $transaction)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'points' => 'required|integer',
        ]);

        $transaction->update($validated);

        // Recalculate total points
        $siswaPoint = SiswaPoint::where('siswa_id', $transaction->siswa_id)->first();
        if ($siswaPoint) {
            $siswaPoint->syncCurrentCycleTotals();
        }

        return back()->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Admin: Reset character points for a student
     */
    public function adminResetCharacterPoints(Request $request)
    {
        $request->validate(['siswa_id' => 'required|exists:siswa,id']);
        $siswaId = $request->siswa_id;
        $siswa = \App\Models\Siswa::findOrFail($siswaId);

        // Delete character-source transactions
        $deleted = \App\Models\PointTransaction::where('siswa_id', $siswaId)
            ->where('source', 'character')
            ->delete();

        // Recalculate points
        $siswaPoint = SiswaPoint::where('siswa_id', $siswaId)->first();
        if ($siswaPoint) {
            $siswaPoint->character_streak = 0;
            $siswaPoint->syncCurrentCycleTotals();
            $siswaPoint->checkLevelUp();
            $siswaPoint->save();
        }

        return back()->with('success', "Poin karakter {$siswa->nama} berhasil direset ({$deleted} transaksi dihapus).");
    }

    /**
     * Admin: Reset badges/pins for a student
     */
    public function adminResetBadges(Request $request)
    {
        $request->validate(['siswa_id' => 'required|exists:siswa,id']);
        $siswaId = $request->siswa_id;
        $siswa = \App\Models\Siswa::findOrFail($siswaId);

        // Delete badge-source transactions
        $deletedTx = \App\Models\PointTransaction::where('siswa_id', $siswaId)
            ->where('source', 'badge')
            ->delete();

        // Delete all user badges
        $deletedBadges = UserBadge::where('siswa_id', $siswaId)->delete();

        // Recalculate points
        $siswaPoint = SiswaPoint::where('siswa_id', $siswaId)->first();
        if ($siswaPoint) {
            $siswaPoint->syncCurrentCycleTotals();
            $siswaPoint->checkLevelUp();
            $siswaPoint->save();
        }

        return back()->with('success', "Pin penghargaan {$siswa->nama} berhasil direset ({$deletedBadges} pin, {$deletedTx} transaksi dihapus).");
    }

    /**
     * Admin: Full reset - points, badges, streaks
     */
    public function adminFullReset(Request $request)
    {
        $request->validate(['siswa_id' => 'required|exists:siswa,id']);
        $siswaId = $request->siswa_id;
        $siswa = \App\Models\Siswa::findOrFail($siswaId);

        // Delete all transactions
        \App\Models\PointTransaction::where('siswa_id', $siswaId)->delete();

        // Delete all badges
        UserBadge::where('siswa_id', $siswaId)->delete();

        // Reset all point stats
        $siswaPoint = SiswaPoint::where('siswa_id', $siswaId)->first();
        if ($siswaPoint) {
            $siswaPoint->update([
                'total_points' => 0,
                'attendance_points' => 0,
                'character_points' => 0,
                'bonus_points' => 0,
                'spent_points' => 0,
                'level' => 1,
                'attendance_streak' => 0,
                'character_streak' => 0,
            ]);
        }

        return back()->with('success', "Semua data poin & pin {$siswa->nama} berhasil direset total.");
    }
}
