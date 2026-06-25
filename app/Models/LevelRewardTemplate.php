<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LevelRewardTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'reward_type',
        'template_path',
        'name_y',
        'font_size',
        'font_color',
        'is_active',
    ];

    protected $casts = [
        'name_y' => 'integer',
        'font_size' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Reward type labels in Indonesian.
     */
    public const REWARD_TYPES = [
        'sertifikat' => ['label' => 'Sertifikat', 'icon' => 'CERT', 'desc' => 'Sertifikat pencapaian level'],
        'pin'        => ['label' => 'Pin Digital', 'icon' => 'PIN', 'desc' => 'Kartu pin/badge khusus'],
        'nominasi'   => ['label' => 'Surat Nominasi', 'icon' => 'NOM', 'desc' => 'Surat nominasi siswa berprestasi'],
        'piagam'     => ['label' => 'Piagam Penghargaan', 'icon' => 'PIA', 'desc' => 'Piagam penghargaan formal'],
        'apresiasi'  => ['label' => 'Surat Apresiasi', 'icon' => 'APR', 'desc' => 'Surat apresiasi dari pembina'],
        'piala'      => ['label' => 'Piala Digital', 'icon' => 'TROFI', 'desc' => 'Kartu piala/trofi prestasi'],
    ];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function hasTemplate(): bool
    {
        return !empty($this->template_path) && Storage::disk('public')->exists($this->template_path);
    }

    public function getTemplateUrlAttribute(): ?string
    {
        return $this->template_path ? Storage::url($this->template_path) : null;
    }

    public function getLabelAttribute(): string
    {
        return self::REWARD_TYPES[$this->reward_type]['label'] ?? ucfirst($this->reward_type);
    }

    public function getIconAttribute(): string
    {
        return self::REWARD_TYPES[$this->reward_type]['icon'] ?? 'GIFT';
    }

    /**
     * Get the matching benefit keyword for this reward type.
     */
    public function matchesBenefit(string $benefit): bool
    {
        $benefitLower = strtolower($benefit);
        return match($this->reward_type) {
            'sertifikat' => str_contains($benefitLower, 'sertifikat'),
            'pin'        => str_contains($benefitLower, 'pin'),
            'nominasi'   => str_contains($benefitLower, 'nominasi'),
            'piagam'     => str_contains($benefitLower, 'piagam'),
            'apresiasi'  => str_contains($benefitLower, 'apresiasi'),
            'piala'      => str_contains($benefitLower, 'piala'),
            default      => false,
        };
    }
}