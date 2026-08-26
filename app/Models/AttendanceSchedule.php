<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AttendanceSchedule extends Model
{
    public const TARGET_ALL = 'all';

    public const TARGET_PAMONG = 'pamong';

    public const TARGET_SISWA = 'siswa';

    protected $fillable = [
        'name',
        'open_time',
        'late_threshold',
        'close_time',
        'days',
        'target_audience',
        'start_date',
        'end_date',
        'is_active',
        'description',
    ];

    protected $casts = [
        'days' => 'array',
        'is_active' => 'boolean',
        'open_time' => 'datetime:H:i:s',
        'late_threshold' => 'datetime:H:i:s',
        'close_time' => 'datetime:H:i:s',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the active schedule
     */
    public static function getActiveSchedule(?string $targetAudience = null, $date = null)
    {
        return self::query()
            ->where('is_active', true)
            ->activeOn($date)
            ->when($targetAudience, function ($query) use ($targetAudience) {
                $query->whereIn('target_audience', [self::TARGET_ALL, $targetAudience]);
            })
            ->orderBy('open_time')
            ->orderBy('id')
            ->first();
    }

    public function scopeActiveOn($query, $date = null)
    {
        $dateString = self::normalizeDate($date)->toDateString();

        return $query
            ->where(function ($query) use ($dateString) {
                $query->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $dateString);
            })
            ->where(function ($query) use ($dateString) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $dateString);
            });
    }

    public function scopeOverlappingDateRange($query, $start, $end)
    {
        $startDate = self::normalizeDate($start)->toDateString();
        $endDate = self::normalizeDate($end)->toDateString();

        return $query
            ->where(function ($query) use ($endDate) {
                $query->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $endDate);
            })
            ->where(function ($query) use ($startDate) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $startDate);
            });
    }

    public static function targetOptions(): array
    {
        return [
            self::TARGET_ALL => 'Siswa dan Pamong',
            self::TARGET_PAMONG => 'Pamong saja',
            self::TARGET_SISWA => 'Siswa saja',
        ];
    }

    public function targetLabel(): string
    {
        return self::targetOptions()[$this->target_audience ?: self::TARGET_ALL] ?? self::targetOptions()[self::TARGET_ALL];
    }

    public function targetsPamong(): bool
    {
        return in_array($this->target_audience ?: self::TARGET_ALL, [self::TARGET_ALL, self::TARGET_PAMONG], true);
    }

    public function targetsSiswa(): bool
    {
        return in_array($this->target_audience ?: self::TARGET_ALL, [self::TARGET_ALL, self::TARGET_SISWA], true);
    }

    /**
     * Check if attendance is currently open
     */
    public function isOpen(?Carbon $now = null): bool
    {
        $now = $now ?: Carbon::now();

        if (! $this->isDateActive($now)) {
            return false;
        }

        // Check if today is in active days
        $dayName = strtolower($now->format('l')); // e.g., "monday"
        if (! empty($this->days) && ! in_array($dayName, $this->days)) {
            return false;
        }

        $currentTime = $now->format('H:i:s');
        $openTime = Carbon::parse($this->open_time)->format('H:i:s');
        $closeTime = Carbon::parse($this->close_time)->format('H:i:s');

        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    public function isDateActive($date = null): bool
    {
        $date = self::normalizeDate($date)->startOfDay();

        if ($this->start_date && $date->lt($this->start_date->copy()->startOfDay())) {
            return false;
        }

        if ($this->end_date && $date->gt($this->end_date->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * Check attendance status based on time
     * Returns: 'hadir', 'terlambat', or 'closed'
     */
    public function getAttendanceStatus(): string
    {
        if (! $this->isOpen()) {
            return 'closed';
        }

        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $lateThreshold = Carbon::parse($this->late_threshold)->format('H:i:s');

        return $currentTime <= $lateThreshold ? 'hadir' : 'terlambat';
    }

    /**
     * Get human-readable schedule info
     */
    public function getScheduleInfoAttribute(): string
    {
        $openTime = Carbon::parse($this->open_time)->format('H:i');
        $lateTime = Carbon::parse($this->late_threshold)->format('H:i');
        $closeTime = Carbon::parse($this->close_time)->format('H:i');

        return "Buka: {$openTime} | Terlambat: {$lateTime} | Tutup: {$closeTime}";
    }

    public function dateRangeLabel(): string
    {
        if (! $this->start_date && ! $this->end_date) {
            return 'Tanpa batas tanggal';
        }

        $startDate = $this->start_date ?: $this->end_date;
        $endDate = $this->end_date ?: $this->start_date;

        if ($startDate->isSameDay($endDate)) {
            return $startDate->translatedFormat('d M Y');
        }

        return $startDate->translatedFormat('d M Y') . ' - ' . $endDate->translatedFormat('d M Y');
    }

    public function nextOccurrence(?Carbon $from = null): ?Carbon
    {
        $from = ($from ?: now())->copy();
        $cursor = $from->copy()->startOfDay();
        $days = $this->days ?? [];
        $limit = $this->end_date?->copy()->startOfDay() ?: $from->copy()->addYear()->startOfDay();

        if ($this->start_date && $cursor->lt($this->start_date->copy()->startOfDay())) {
            $cursor = $this->start_date->copy()->startOfDay();
        }

        while ($cursor->lte($limit)) {
            $dayKey = strtolower($cursor->format('l'));

            if ((empty($days) || in_array($dayKey, $days, true)) && $this->isDateActive($cursor)) {
                return $cursor->copy();
            }

            $cursor->addDay();
        }

        return null;
    }

    protected static function normalizeDate($date = null): Carbon
    {
        if ($date instanceof Carbon) {
            return $date->copy();
        }

        return $date ? Carbon::parse($date) : Carbon::now();
    }

    /**
     * Tanggal-tanggal yang benar-benar berjadwal presensi dalam rentang.
     *
     * Dipakai sebagai SATU SUMBER KEBENARAN untuk statistik kehadiran agar
     * persentase tidak dihitung dari semua hari kalender (yang membuat hari
     * tanpa kegiatan selalu tampil 0%). Ketika jadwal diubah (mis. dari 4x
     * jadi 1x sebulan), fungsi ini otomatis mengikuti karena membaca ulang
     * konfigurasi jadwal aktif.
     *
     * @return array<int, string> daftar tanggal Y-m-d, urut menaik & unik
     */
    public static function scheduledDatesBetween($start, $end, ?string $targetAudience = null): array
    {
        $start = self::normalizeDate($start)->startOfDay();
        $end = self::normalizeDate($end)->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $schedules = self::query()
            ->where('is_active', true)
            ->overlappingDateRange($start, $end)
            ->when($targetAudience, function ($query) use ($targetAudience) {
                $query->whereIn('target_audience', [self::TARGET_ALL, $targetAudience]);
            })
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $dates = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dayKey = strtolower($cursor->format('l'));

            foreach ($schedules as $schedule) {
                $days = $schedule->days ?? [];
                $matchesDay = empty($days) || in_array($dayKey, $days, true);

                if ($matchesDay && $schedule->isDateActive($cursor)) {
                    $dates[$cursor->toDateString()] = true;
                    break;
                }
            }

            $cursor->addDay();
        }

        return array_keys($dates);
    }

    /**
     * Jumlah hari berjadwal presensi pada satu bulan (untuk denominator %).
     */
    public static function scheduledDaysInMonth($month, ?string $targetAudience = null): int
    {
        $ref = self::normalizeDate($month);

        return count(self::scheduledDatesBetween(
            $ref->copy()->startOfMonth(),
            $ref->copy()->endOfMonth(),
            $targetAudience
        ));
    }
}
