<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\SiswaPoint;
use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\Level;
use App\Models\PointPeriod;
use App\Models\PointTransaction;
use Carbon\Carbon;

class GamificationService
{

    /**
     * Get or create siswa points record
     */
    public function getOrCreateSiswaPoint(Siswa $siswa): SiswaPoint
    {
        return SiswaPoint::firstOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'total_points' => 0,
                'level' => 1,
                'attendance_points' => 0,
                'character_points' => 0,
                'bonus_points' => 0,
                'spent_points' => 0,
                'attendance_streak' => 0,
                'character_streak' => 0
            ]
        );
    }

    /**
     * Award points for attendance
     */
    public function awardAttendancePoints(Siswa $siswa, string $status, $presensi = null): ?PointTransaction
    {
        return $this->awardAttendancePointsForPeriod(
            $siswa,
            $status,
            $presensi,
            PointPeriod::current(),
            true
        );
    }

    /**
     * Award attendance points for a specific period.
     */
    public function awardAttendancePointsForPeriod(
        Siswa $siswa,
        string $status,
        $presensi = null,
        ?PointPeriod $period = null,
        bool $trackStreak = true
    ): ?PointTransaction
    {
        $config = $period?->resolved_point_settings ?? $this->getPointConfig();
        $points = match($status) {
            'hadir' => (int) ($config['points_hadir'] ?? 10),
            'terlambat' => (int) ($config['points_terlambat'] ?? 5),
            'izin' => (int) ($config['points_izin'] ?? 2),
            'sakit' => (int) ($config['points_sakit'] ?? 2),
            default => (int) ($config['points_alpha'] ?? 0)
        };

        if ($points <= 0) return null;

        $siswaPoint = $this->getOrCreateSiswaPoint($siswa);

        if ($trackStreak) {
            $today = Carbon::today();
            $siswaPoint->updateAttendanceStreak($today);
        }
        
        // Award base points
        $transaction = $siswaPoint->addPoints(
            $points,
            'attendance',
            "Poin kehadiran: {$status}",
            $presensi,
            $this->buildPeriodMetadata($period)
        );

        if ($trackStreak) {
            $this->checkStreakBonus($siswaPoint, 'attendance');
            $this->checkBadgeEligibility($siswa);
        }

        return $transaction;
    }

    /**
     * Award points for character check
     */
    public function awardCharacterPoints(Siswa $siswa, $tracerKarakter = null): PointTransaction
    {
        $siswaPoint = $this->getOrCreateSiswaPoint($siswa);
        $period = PointPeriod::current();
        $config = $this->getPointConfig();
        
        // Update character streak
        $today = Carbon::today();
        if (!$siswaPoint->last_character_date || !$siswaPoint->last_character_date->isSameDay($today)) {
            if ($siswaPoint->last_character_date && $siswaPoint->last_character_date->addDay()->isSameDay($today)) {
                $siswaPoint->character_streak++;
            } else {
                $siswaPoint->character_streak = 1;
            }
            $siswaPoint->last_character_date = $today;
            $siswaPoint->save();
        }

        $transaction = $siswaPoint->addPoints(
            (int) ($config['points_karakter'] ?? 5),
            'character',
            'Poin karakter positif',
            $tracerKarakter,
            $this->buildPeriodMetadata($period)
        );

        // Check for streak bonuses
        $this->checkStreakBonus($siswaPoint, 'character');
        
        // Check for badge eligibility
        $this->checkBadgeEligibility($siswa);

        return $transaction;
    }

    /**
     * Award points from mini-game duel (rangkai kata / tebak karakter).
     * Semua poin masuk ke akumulasi leaderboard yang sama (source 'game').
     * Menang +10, seri +1, kalah +3 (default), bisa dioverride lewat argumen.
     */
    public function awardGamePoints(Siswa $siswa, int $points, string $description, $reference = null): ?PointTransaction
    {
        if ($points === 0) {
            return null;
        }

        $siswaPoint = $this->getOrCreateSiswaPoint($siswa);
        $transaction = $siswaPoint->addPoints(
            $points,
            'game',
            $description,
            $reference,
            $this->buildPeriodMetadata(PointPeriod::current())
        );

        $this->checkBadgeEligibility($siswa);

        return $transaction;
    }

    /**
     * Check and award streak bonuses
     */
    private function checkStreakBonus(SiswaPoint $siswaPoint, string $type): void
    {
        $streak = $type === 'attendance' 
            ? $siswaPoint->attendance_streak 
            : $siswaPoint->character_streak;

        // 7-day streak bonus
        if ($streak === 7) {
            $siswaPoint->addPoints(
                (int) ($this->getPointConfig()['points_streak_7'] ?? 20),
                'streak',
                "Bonus streak {$type} 7 hari!",
                null,
                $this->buildPeriodMetadata(PointPeriod::current())
            );
        }

        // 30-day streak bonus
        if ($streak === 30) {
            $siswaPoint->addPoints(
                (int) ($this->getPointConfig()['points_streak_30'] ?? 50),
                'streak',
                "Bonus streak {$type} 30 hari!",
                null,
                $this->buildPeriodMetadata(PointPeriod::current())
            );
        }
    }

    /**
     * Check badge eligibility for siswa
     */
    public function checkBadgeEligibility(Siswa $siswa): array
    {
        $earnedBadges = [];
        $badges = Badge::active()->get();

        foreach ($badges as $badge) {
            if ($badge->checkEligibility($siswa)) {
                $userBadge = $this->awardBadge($siswa, $badge);
                if ($userBadge) {
                    $earnedBadges[] = $badge;
                }
            }
        }

        return $earnedBadges;
    }

    /**
     * Award badge (pin penghargaan) to siswa
     * Gives bonus points if poin_reward > 0
     */
    public function awardBadge(Siswa $siswa, Badge $badge): ?UserBadge
    {
        // Check if already has badge
        if ($siswa->badges()->where('badge_id', $badge->id)->exists()) {
            return null;
        }

        $userBadge = UserBadge::create([
            'siswa_id' => $siswa->id,
            'badge_id' => $badge->id,
            'earned_at' => now(),
            'metadata' => ['awarded_automatically' => true]
        ]);

        // Award bonus points if configured
        if ($badge->poin_reward > 0) {
            $siswaPoint = $this->getOrCreateSiswaPoint($siswa);
            $siswaPoint->addPoints(
                $badge->poin_reward,
                'badge',
                "Bonus pin: {$badge->nama} (+{$badge->poin_reward} poin)",
                null,
                $this->buildPeriodMetadata(PointPeriod::current())
            );
        }

        // Queue notification for pin earned
        $this->queueNotification('badge', [
            'name' => $badge->nama,
            'description' => $badge->deskripsi,
            'icon' => $badge->icon_url,
            'color' => $badge->warna,
            'poin_reward' => $badge->poin_reward,
        ]);

        return $userBadge;
    }

    /**
     * Queue a gamification notification
     */
    public function queueNotification(string $type, array $data): void
    {
        $notifications = session()->get('gamification_notifications', []);
        $notifications[] = ['type' => $type, 'data' => $data];
        session()->put('gamification_notifications', $notifications);
    }

    /**
     * Get and clear pending notifications
     */
    public static function getPendingNotifications(): array
    {
        $notifications = session()->get('gamification_notifications', []);
        session()->forget('gamification_notifications');
        return $notifications;
    }

    /**
     * Notify level up
     */
    public function notifyLevelUp(Level $level): void
    {
        $this->queueNotification('level_up', [
            'name' => $level->nama,
            'icon' => $level->badge_icon_url,
            'color' => $level->warna,
            'benefits' => $level->benefits ? implode(', ', $level->benefits) : ''
        ]);
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(int $limit = 10, ?string $period = null): array
    {
        $query = SiswaPoint::with(['siswa', 'currentLevel'])
            ->where('total_points', '>', 0)
            ->orderBy('total_points', 'desc');

        if ($period) {
            $startDate = match($period) {
                'daily' => Carbon::today(),
                'weekly' => Carbon::now()->startOfWeek(),
                'monthly' => Carbon::now()->startOfMonth(),
                default => null
            };

            if ($startDate) {
                // Get points earned in period
                $query->withSum(['transactions as period_points' => function($q) use ($startDate) {
                    $q->where('created_at', '>=', $startDate)
                      ->where('type', 'earned');
                }], 'points')
                ->orderBy('period_points', 'desc');
            }
        }

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Get siswa gamification stats
     */
    public function getSiswaStats(Siswa $siswa): array
    {
        $siswaPoint = $this->getOrCreateSiswaPoint($siswa);
        $currentLevel = Level::where('level', $siswaPoint->level)->first();
        $nextLevel = Level::where('level', $siswaPoint->level + 1)->first();

        $recentBadges = $siswa->userBadges()
            ->with('badge')
            ->orderBy('earned_at', 'desc')
            ->limit(5)
            ->get();

        $recentTransactions = $siswa->pointTransactions()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get rank
        $rank = SiswaPoint::where('total_points', '>', $siswaPoint->total_points)->count() + 1;
        $lastPeriodReset = $siswa->pointTransactions()
            ->where('metadata->event', 'period_reset')
            ->latest('id')
            ->first();

        return [
            'points' => $siswaPoint,
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'progress_to_next' => $siswaPoint->progress_to_next_level,
            'points_to_next' => $siswaPoint->points_to_next_level,
            'rank' => $rank,
            'total_badges' => $siswa->badges()->count(),
            'recent_badges' => $recentBadges,
            'recent_transactions' => $recentTransactions,
            'attendance_streak' => $siswaPoint->attendance_streak,
            'character_streak' => $siswaPoint->character_streak,
            'active_period' => $this->getActivePeriodSummary(),
            'active_period_points' => $this->getPeriodPointsForSiswa($siswa),
            'cumulative_points' => $siswaPoint->total_points,
            'last_period_reset' => $lastPeriodReset ? [
                'points' => abs((int) $lastPeriodReset->points),
                'period_name' => data_get($lastPeriodReset->metadata, 'archived_period_name'),
                'message' => data_get($lastPeriodReset->metadata, 'reset_message'),
                'archived_at' => $lastPeriodReset->created_at,
            ] : null,
        ];
    }

    public function getPointConfig(): array
    {
        $period = PointPeriod::current();

        return $period?->resolved_point_settings ?? PointPeriod::defaultPointSettings();
    }

    public function getActivePeriodSummary(): ?array
    {
        $period = PointPeriod::current();
        if (!$period) {
            return null;
        }

        return [
            'id' => $period->id,
            'name' => $period->name,
            'start_date' => $period->start_date,
            'end_date' => $period->end_date,
            'status' => $period->status,
        ];
    }

    public function getPeriodPointsForSiswa(Siswa $siswa, ?PointPeriod $period = null): int
    {
        $period = $period ?: PointPeriod::current();
        if (!$period) {
            return $siswa->siswaPoint?->total_points ?? 0;
        }

        $query = PointTransaction::where('siswa_id', $siswa->id)
            ->whereDate('created_at', '>=', $period->start_date);

        if ($period->end_date) {
            $query->whereDate('created_at', '<=', $period->end_date);
        }

        return (int) $query->sum('points');
    }

    protected function buildPeriodMetadata(?PointPeriod $period): array
    {
        if (!$period) {
            return [];
        }

        return [
            'point_period_id' => $period->id,
            'period_id' => $period->id,
            'period_name' => $period->name,
            'period_start' => optional($period->start_date)->toDateString(),
            'period_end' => optional($period->end_date)->toDateString(),
        ];
    }

    /**
     * Get all badges (pin penghargaan) with siswa progress
     */
    public function getBadgesWithProgress(Siswa $siswa): array
    {
        $badges = Badge::active()->get();
        $earnedBadgeIds = $siswa->badges()->pluck('badge_id')->toArray();

        return $badges->map(function($badge) use ($siswa, $earnedBadgeIds) {
            $earned = in_array($badge->id, $earnedBadgeIds);
            $current = $badge->getCurrentProgress($siswa);
            $target = $badge->getTargetValue();
            $progress = $earned ? 100 : ($target > 0 ? min(100, (int)(($current / $target) * 100)) : 0);
            
            return [
                'badge' => $badge,
                'earned' => $earned,
                'progress' => $progress,
                'current' => $current,
                'target' => $target,
                'earned_at' => $earned 
                    ? $siswa->badges()->where('badge_id', $badge->id)->first()?->pivot->earned_at 
                    : null
            ];
        })->toArray();
    }

    /**
     * Recalculate levels for ALL students based on current level thresholds.
     * Should be called when admin updates level min_points.
     */
    public function recalculateAllLevels(): array
    {
        $allSiswaPoints = SiswaPoint::all();
        $levels = Level::where('is_active', true)->orderBy('level', 'desc')->get();
        
        $updated = 0;
        $details = [];

        foreach ($allSiswaPoints as $sp) {
            $correctLevel = 1; // default
            foreach ($levels as $level) {
                if ($sp->total_points >= $level->min_points) {
                    $correctLevel = $level->level;
                    break;
                }
            }

            if ($sp->level !== $correctLevel) {
                $oldLevel = $sp->level;
                $sp->level = $correctLevel;
                $sp->save();
                $updated++;
                $details[] = [
                    'siswa_id' => $sp->siswa_id,
                    'old_level' => $oldLevel,
                    'new_level' => $correctLevel,
                    'points' => $sp->total_points,
                ];
            }
        }

        return [
            'total_checked' => $allSiswaPoints->count(),
            'total_updated' => $updated,
            'details' => $details,
        ];
    }
}
