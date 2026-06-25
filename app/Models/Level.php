<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'nama',
        'deskripsi',
        'min_points',
        'max_points',
        'badge_icon',
        'warna',
        'benefits',
        'is_active',
        'certificate_template',
        'certificate_name_y',
        'certificate_font_size',
        'certificate_font_color',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_points' => 'integer',
        'max_points' => 'integer',
        'benefits' => 'array',
        'is_active' => 'boolean',
        'certificate_name_y' => 'integer',
        'certificate_font_size' => 'integer',
    ];

    public function hasCertificate(): bool
    {
        // Check new reward templates table first, then fallback to legacy fields
        $rewardTemplate = $this->rewardTemplates()->where('reward_type', 'sertifikat')->first();
        if ($rewardTemplate && $rewardTemplate->hasTemplate()) {
            return true;
        }
        return !empty($this->certificate_template) && file_exists(storage_path('app/public/' . $this->certificate_template));
    }

    public function getCertificateTemplateUrlAttribute(): ?string
    {
        if ($this->certificate_template) {
            return asset('storage/' . $this->certificate_template);
        }
        return null;
    }

    public function rewardTemplates(): HasMany
    {
        return $this->hasMany(LevelRewardTemplate::class);
    }

    public function getRewardTemplate(string $type): ?LevelRewardTemplate
    {
        return $this->rewardTemplates()->where('reward_type', $type)->where('is_active', true)->first();
    }

    public function siswaPoints(): HasMany
    {
        return $this->hasMany(SiswaPoint::class, 'level', 'level');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getByPoints(int $points): ?Level
    {
        return static::where('min_points', '<=', $points)
            ->where(function ($query) use ($points) {
                $query->where('max_points', '>=', $points)
                      ->orWhereNull('max_points');
            })
            ->where('is_active', true)
            ->orderBy('level', 'desc')
            ->first();
    }

    public function getNextLevelAttribute(): ?Level
    {
        return static::where('level', $this->level + 1)
            ->where('is_active', true)
            ->first();
    }

    public function getIsMaxLevelAttribute(): bool
    {
        return is_null($this->max_points) || 
               !static::where('level', '>', $this->level)->where('is_active', true)->exists();
    }

    public function getPointsRangeAttribute(): string
    {
        if ($this->is_max_level) {
            return number_format($this->min_points) . '+ poin';
        }
        return number_format($this->min_points) . ' - ' . number_format($this->max_points) . ' poin';
    }

    public function getBadgeIconUrlAttribute(): string
    {
        $defaultIcons = [1 => 'L1', 2 => 'L2', 3 => 'L3', 4 => 'L4', 5 => 'L5'];
        return $defaultIcons[$this->level] ?? 'LVL';
    }
}