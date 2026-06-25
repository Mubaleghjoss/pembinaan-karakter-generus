<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'deskripsi',
        'icon',
        'kriteria',
        'poin_reward',
        'kategori',
        'warna',
        'is_active'
    ];

    protected $casts = [
        'kriteria' => 'array',
        'is_active' => 'boolean',
        'poin_reward' => 'integer'
    ];

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function users()
    {
        return $this->belongsToMany(Siswa::class, 'user_badges', 'badge_id', 'siswa_id')
                    ->withPivot('earned_at', 'metadata')
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('kategori', $category);
    }

    /**
     * Check if siswa is eligible for this pin.
     * Simplified: only attendance_count and verified_character_count
     */
    public function checkEligibility(Siswa $siswa): bool
    {
        $criteria = $this->kriteria;
        if (!$criteria) return false;
        if ($siswa->badges()->where('badge_id', $this->id)->exists()) return false;
        return $this->evaluateCriteria($criteria, $siswa);
    }

    /**
     * Criteria evaluation: attendance, character, and level
     */
    private function evaluateCriteria(array $criteria, Siswa $siswa): bool
    {
        $type = $criteria['type'] ?? null;
        $value = $criteria['value'] ?? 0;

        switch ($type) {
            case 'attendance_count':
                return $siswa->presensi()->where('status', 'hadir')->count() >= $value;
            case 'verified_character_count':
                return \App\Models\SiswaKarakterChecklist::where('siswa_id', $siswa->id)
                    ->verified()
                    ->count() >= $value;
            case 'level_reached':
                $currentLevel = $siswa->siswaPoint?->level ?? 1;
                return $currentLevel >= (int)$value;
            default:
                return false;
        }
    }

    /**
     * Get current progress value for this pin
     */
    public function getCurrentProgress(Siswa $siswa): int
    {
        $criteria = $this->kriteria;
        if (!$criteria) return 0;

        $type = $criteria['type'] ?? null;

        switch ($type) {
            case 'attendance_count':
                return $siswa->presensi()->where('status', 'hadir')->count();
            case 'verified_character_count':
                return \App\Models\SiswaKarakterChecklist::where('siswa_id', $siswa->id)
                    ->verified()
                    ->count();
            case 'level_reached':
                return $siswa->siswaPoint?->level ?? 1;
            default:
                return 0;
        }
    }

    /**
     * Get target value for this pin
     */
    public function getTargetValue(): int
    {
        return $this->kriteria['value'] ?? 0;
    }

    /**
     * Get human-readable criteria description
     */
    public function getCriteriaDescriptionAttribute(): string
    {
        $type = $this->kriteria['type'] ?? '-';
        $value = $this->kriteria['value'] ?? 0;

        return match($type) {
            'attendance_count' => "Hadir minimal {$value} kali",
            'verified_character_count' => "Selesaikan & verifikasi {$value} tugas PKG",
            'level_reached' => "Mencapai Level {$value}",
            default => '-',
        };
    }

    public function getIconUrlAttribute(): string
    {
        if ($this->icon) {
            return $this->icon;
        }
        $defaultIcons = ['attendance' => 'ATT', 'character' => 'STAR'];
        return $defaultIcons[$this->kategori] ?? 'BADGE';
    }
}