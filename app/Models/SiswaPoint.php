<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PointPeriod;

class SiswaPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'total_points',
        'level',
        'attendance_points',
        'character_points',
        'bonus_points',
        'spent_points',
        'last_attendance_date',
        'attendance_streak',
        'last_character_date',
        'character_streak'
    ];

    protected $casts = [
        'total_points' => 'integer',
        'level' => 'integer',
        'attendance_points' => 'integer',
        'character_points' => 'integer',
        'bonus_points' => 'integer',
        'spent_points' => 'integer',
        'attendance_streak' => 'integer',
        'character_streak' => 'integer',
        'last_attendance_date' => 'date',
        'last_character_date' => 'date'
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class, 'siswa_id', 'siswa_id');
    }

    public function currentLevel(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level', 'level');
    }

    public function getNextLevelAttribute()
    {
        return Level::where('level', $this->level + 1)->first();
    }

    public function getAvailablePointsAttribute(): int
    {
        return $this->total_points - $this->spent_points;
    }

    public function getProgressToNextLevelAttribute(): int
    {
        $currentLevel = $this->currentLevel;
        $nextLevel = $this->next_level;
        
        if (!$currentLevel || !$nextLevel) return 100;
        
        $progress = (($this->total_points - $currentLevel->min_points) / 
                    ($nextLevel->min_points - $currentLevel->min_points)) * 100;
        return max(0, min(100, (int) $progress));
    }

    public function getPointsToNextLevelAttribute(): int
    {
        $nextLevel = $this->next_level;
        if (!$nextLevel) return 0;
        return max(0, $nextLevel->min_points - $this->total_points);
    }

    public function addPoints(int $points, string $source, string $description, $reference = null, array $metadata = []): PointTransaction
    {
        $transaction = PointTransaction::create([
            'siswa_id' => $this->siswa_id,
            'type' => $points > 0 ? 'earned' : 'spent',
            'source' => $source,
            'points' => $points,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'metadata' => $metadata ?: null,
        ]);

        switch ($source) {
            case 'attendance': $this->attendance_points += $points; break;
            case 'character': $this->character_points += $points; break;
            default: $this->bonus_points += $points; break;
        }

        if ($points < 0) $this->spent_points += abs($points);
        $this->total_points += $points;
        $this->checkLevelUp();
        $this->save();
        
        return $transaction;
    }

    public static function currentCycleTransactionsQuery(int $siswaId)
    {
        $latestResetId = PointTransaction::query()
            ->where('siswa_id', $siswaId)
            ->where('metadata->event', 'period_reset')
            ->max('id');

        return PointTransaction::query()
            ->where('siswa_id', $siswaId)
            ->where(function ($query) {
                $query->whereNull('metadata->event')
                    ->orWhere('metadata->event', '!=', 'period_reset');
            })
            ->when($latestResetId, fn ($query) => $query->where('id', '>', $latestResetId));
    }

    public static function calculateCurrentCycleTotals(int $siswaId): array
    {
        $summary = static::currentCycleTransactionsQuery($siswaId)
            ->selectRaw('COALESCE(SUM(points), 0) as total_points')
            ->selectRaw("COALESCE(SUM(CASE WHEN source = 'attendance' THEN points ELSE 0 END), 0) as attendance_points")
            ->selectRaw("COALESCE(SUM(CASE WHEN source = 'character' THEN points ELSE 0 END), 0) as character_points")
            ->selectRaw("COALESCE(SUM(CASE WHEN source NOT IN ('attendance', 'character') THEN points ELSE 0 END), 0) as bonus_points")
            ->selectRaw("COALESCE(SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END), 0) as spent_points")
            ->first();

        return [
            'total_points' => (int) ($summary->total_points ?? 0),
            'attendance_points' => (int) ($summary->attendance_points ?? 0),
            'character_points' => (int) ($summary->character_points ?? 0),
            'bonus_points' => (int) ($summary->bonus_points ?? 0),
            'spent_points' => (int) ($summary->spent_points ?? 0),
        ];
    }

    public function syncCurrentCycleTotals(): void
    {
        $totals = static::calculateCurrentCycleTotals($this->siswa_id);
        $baseLevel = Level::where('is_active', true)->orderBy('level')->value('level') ?? 1;
        $resolvedLevel = Level::where('min_points', '<=', $totals['total_points'])
            ->where('is_active', true)
            ->orderBy('level', 'desc')
            ->value('level') ?? $baseLevel;

        $this->fill([
            'total_points' => $totals['total_points'],
            'attendance_points' => $totals['attendance_points'],
            'character_points' => $totals['character_points'],
            'bonus_points' => $totals['bonus_points'],
            'spent_points' => $totals['spent_points'],
            'level' => $resolvedLevel,
        ]);

        $this->save();
    }

    public function archiveCurrentBalanceToPeriod(PointPeriod $period): ?PointTransaction
    {
        $currentPoints = (int) $this->total_points;

        if ($currentPoints === 0) {
            return null;
        }

        $message = "Poin sebelumnya sudah terekam di {$period->name}. Yuk semangat kumpulkan poin lagi untuk benefit yang ada.";

        $transaction = PointTransaction::create([
            'siswa_id' => $this->siswa_id,
            'type' => 'spent',
            'source' => 'manual',
            'points' => -$currentPoints,
            'description' => "Arsip periode {$period->name}: {$currentPoints} poin tersimpan, saldo berjalan direset ke 0.",
            'metadata' => [
                'event' => 'period_reset',
                'archived_period_id' => $period->id,
                'archived_period_name' => $period->name,
                'archived_points_before_reset' => $currentPoints,
                'reset_message' => $message,
                'reset_at' => now()->toIso8601String(),
            ],
        ]);

        $baseLevel = Level::where('is_active', true)->orderBy('level')->value('level') ?? 1;

        $this->fill([
            'total_points' => 0,
            'attendance_points' => 0,
            'character_points' => 0,
            'bonus_points' => 0,
            'spent_points' => 0,
            'level' => $baseLevel,
        ]);

        $this->save();

        return $transaction;
    }

    public function checkLevelUp(): bool
    {
        $newLevel = Level::where('min_points', '<=', $this->total_points)
            ->where('is_active', true)
            ->orderBy('level', 'desc')
            ->first();

        if ($newLevel && $newLevel->level > $this->level) {
            $oldLevel = $this->level;
            $this->level = $newLevel->level;
            
            // Queue level up notification
            $notifications = session()->get('gamification_notifications', []);
            $notifications[] = [
                'type' => 'level_up',
                'data' => [
                    'name' => $newLevel->nama,
                    'icon' => $newLevel->badge_icon_url,
                    'color' => $newLevel->warna,
                    'benefits' => $newLevel->benefits ? implode(', ', $newLevel->benefits) : ''
                ]
            ];
            session()->put('gamification_notifications', $notifications);
            
            // Auto-check level-based badges
            try {
                $siswa = $this->siswa;
                if ($siswa) {
                    $gamificationService = app(\App\Services\GamificationService::class);
                    $gamificationService->checkBadgeEligibility($siswa);
                }
            } catch (\Throwable $e) {
                \Log::error('Badge check on level up failed: ' . $e->getMessage());
            }
            
            return true;
        }
        return false;
    }

    public function updateAttendanceStreak(\Carbon\Carbon $date): void
    {
        if (!$this->last_attendance_date) {
            $this->attendance_streak = 1;
        } elseif ($this->last_attendance_date->addDay()->isSameDay($date)) {
            $this->attendance_streak++;
        } elseif (!$this->last_attendance_date->isSameDay($date)) {
            $this->attendance_streak = 1;
        }
        $this->last_attendance_date = $date;
    }

    public function scopeLeaderboard($query, $limit = 10)
    {
        return $query->orderBy('total_points', 'desc')
                    ->limit($limit)
                    ->with(['siswa', 'currentLevel']);
    }
}
