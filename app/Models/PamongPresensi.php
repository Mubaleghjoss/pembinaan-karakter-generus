<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PamongPresensi extends Model
{
    protected $table = 'pamong_presensi';

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'status',
        'keterangan',
        'qr_code_used',
        'is_verified',
        'verified_by',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'datetime:H:i:s',
        'jam_keluar' => 'datetime:H:i:s',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the pamong (user) for this attendance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship.
     */
    public function pamong(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the verifier user.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope for today's attendance.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('tanggal', Carbon::today());
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * Scope for unverified attendance.
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Calculate late duration in minutes.
     */
    public function getLateDurationAttribute(): ?int
    {
        if ($this->status !== 'terlambat' || !$this->jam_masuk) {
            return null;
        }

        $schedule = AttendanceSchedule::getActiveSchedule(AttendanceSchedule::TARGET_PAMONG, $this->tanggal);
        if (!$schedule) {
            return null;
        }

        $lateThreshold = Carbon::parse($schedule->late_threshold);
        $jamMasuk = Carbon::parse($this->jam_masuk);

        // Set same date for comparison
        $lateThreshold->setDate($jamMasuk->year, $jamMasuk->month, $jamMasuk->day);

        if ($jamMasuk->gt($lateThreshold)) {
            return $jamMasuk->diffInMinutes($lateThreshold);
        }

        return 0;
    }

    /**
     * Get formatted late duration.
     */
    public function getLateDurationFormattedAttribute(): ?string
    {
        $minutes = $this->late_duration;
        
        if ($minutes === null) {
            return null;
        }

        if ($minutes < 60) {
            return "{$minutes} menit";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours} jam";
        }

        return "{$hours} jam {$remainingMinutes} menit";
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'hadir' => 'green',
            'terlambat' => 'yellow',
            'izin' => 'blue',
            'sakit' => 'purple',
            'alpha' => 'red',
            default => 'gray',
        };
    }
}
