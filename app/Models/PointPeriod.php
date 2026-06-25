<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PointPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'start_date',
        'end_date',
        'status',
        'point_settings',
        'notes',
        'activated_at',
        'closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'point_settings' => 'array',
        'activated_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function defaultPointSettings(): array
    {
        return [
            'points_hadir' => (int) Setting::get('points_hadir', 10),
            'points_terlambat' => (int) Setting::get('points_terlambat', 5),
            'points_izin' => (int) Setting::get('points_izin', 2),
            'points_sakit' => (int) Setting::get('points_sakit', 2),
            'points_alpha' => (int) Setting::get('points_alpha', 0),
            'points_karakter' => (int) Setting::get('points_karakter', 5),
            'points_streak_7' => (int) Setting::get('points_streak_7', 20),
            'points_streak_30' => (int) Setting::get('points_streak_30', 50),
            'points_perfect_month' => (int) Setting::get('points_perfect_month', 100),
        ];
    }

    public static function current(): ?self
    {
        return static::query()
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getResolvedPointSettingsAttribute(): array
    {
        return array_merge(self::defaultPointSettings(), $this->point_settings ?? []);
    }

    public function ensureSlug(): void
    {
        if ($this->slug) {
            return;
        }

        $base = Str::slug($this->name) ?: 'periode-poin';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        $this->slug = $slug;
    }
}
