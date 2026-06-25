<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presensi extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'presensi';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'status',
        'qr_code_used',
        'scan_location',
        'scan_device_info',
        'scan_ip_address',
        'is_verified',
        'verified_by',
        'verified_at',
        'keterangan',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'datetime',
        'jam_keluar' => 'datetime',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'scan_device_info' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the siswa that owns the presensi.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Get the user who verified this attendance.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Record attendance via QR scan
     */
    public static function recordQrAttendance(
        Siswa $siswa,
        string $qrToken,
        array $scanData = []
    ): ?self {
        if (! $siswa->verifyQrToken($qrToken)) {
            return null;
        }

        $today = Carbon::today();
        $now = Carbon::now();

        // Check if already present today
        $existing = self::where('siswa_id', $siswa->id)
            ->where('tanggal', $today)
            ->first();

        if ($existing) {
            // Update jam_keluar if not set
            if (! $existing->jam_keluar && $existing->status === 'hadir') {
                $existing->update([
                    'jam_keluar' => $now,
                    'scan_location' => $scanData['location'] ?? null,
                    'scan_device_info' => $scanData['device_info'] ?? null,
                    'scan_ip_address' => $scanData['ip_address'] ?? null,
                ]);
            }

            return $existing;
        }

        // Create new attendance record
        $presensi = self::create([
            'siswa_id' => $siswa->id,
            'tanggal' => $today,
            'jam_masuk' => $now,
            'status' => 'hadir',
            'qr_code_used' => $qrToken,
            'scan_location' => $scanData['location'] ?? null,
            'scan_device_info' => $scanData['device_info'] ?? null,
            'scan_ip_address' => $scanData['ip_address'] ?? null,
            'is_verified' => false,
        ]);

        // Record QR scan
        $siswa->recordQrScan();

        return $presensi;
    }

    /**
     * Verify attendance
     */
    public function verify(User $verifier): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $verifier->id,
            'verified_at' => Carbon::now(),
        ]);
    }

    /**
     * Check if attendance is late
     */
    public function isLate(string $cutoffTime = '07:30'): bool
    {
        if (! $this->jam_masuk) {
            return false;
        }

        $cutoff = Carbon::createFromFormat('H:i', $cutoffTime);

        return $this->jam_masuk->format('H:i') > $cutoffTime;
    }

    /**
     * Get attendance duration in minutes
     */
    public function getDurationAttribute(): ?int
    {
        if (! $this->jam_masuk || ! $this->jam_keluar) {
            return null;
        }

        return $this->jam_masuk->diffInMinutes($this->jam_keluar);
    }

    /**
     * Scope for today's attendance
     */
    public function scopeToday($query)
    {
        return $query->where('tanggal', Carbon::today());
    }

    /**
     * Scope for attendance by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for verified attendance
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for unverified attendance
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Calculate late duration in minutes
     */
    public function getLateDurationAttribute(): ?int
    {
        if ($this->status !== 'terlambat' || !$this->jam_masuk) {
            return null;
        }

        $schedule = AttendanceSchedule::getActiveSchedule(AttendanceSchedule::TARGET_SISWA, $this->tanggal);
        if (!$schedule || !$schedule->late_threshold) {
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
     * Get formatted late duration
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
     * Get status badge color
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
