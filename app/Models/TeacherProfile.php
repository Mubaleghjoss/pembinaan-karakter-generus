<?php

namespace App\Models;

use App\Support\ParticipantProfileOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherProfile extends Model
{
    public const ROLE_BOTH = 'main_backup';
    public const ROLE_MAIN = 'main';
    public const ROLE_BACKUP = 'backup';
    public const ROLE_AS_NEEDED = 'as_needed';
    public const ROLE_UNAVAILABLE = 'unavailable';

    public const PARTICIPATION_ROLES = [
        self::ROLE_BOTH => 'Utama & cadangan',
        self::ROLE_MAIN => 'Utama',
        self::ROLE_BACKUP => 'Cadangan',
        self::ROLE_AS_NEEDED => 'Sesuai kebutuhan',
        self::ROLE_UNAVAILABLE => 'Belum memungkinkan',
    ];

    public const ROMBELS = ['smp' => 'SMP', 'sma' => 'SMA', 'pranikah' => 'Pranikah'];
    public const NIGHTS = ['monday' => 'Senin malam', 'tuesday' => 'Selasa malam', 'friday' => 'Jumat malam'];
    public const COMPETENCIES = [
        'quran' => "Makna Al-Qur'an",
        'hadith' => 'Makna Al-Hadits',
        'memorization' => 'Hafalan',
        'practice' => 'Praktik',
        'class_support' => 'Pendampingan kelas',
        'all_materials' => 'Bersedia seluruh materi',
    ];

    protected $fillable = [
        'invite_id', 'user_id', 'name', 'public_name', 'kelompok', 'whatsapp',
        'whatsapp_normalized', 'participation_role', 'rombels', 'available_nights',
        'night_priorities', 'monthly_limit', 'competencies', 'material_readiness',
        'backup_contact_preference', 'constraints', 'signature_path', 'document_token_hash',
        'consent_version', 'consented_at', 'submitted_at', 'is_active',
    ];

    protected $casts = [
        'rombels' => 'array',
        'available_nights' => 'array',
        'night_priorities' => 'array',
        'monthly_limit' => 'integer',
        'competencies' => 'array',
        'consented_at' => 'datetime',
        'submitted_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherScheduleAssignment::class);
    }

    public function scopeEligible(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('participation_role', '!=', self::ROLE_UNAVAILABLE);
    }

    public function publicDisplayName(): string
    {
        if (filled($this->public_name)) {
            return trim($this->public_name);
        }

        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return $parts[0] ?? 'Pengajar';
    }

    public function kelompokLabel(): string
    {
        return ParticipantProfileOptions::groups()[$this->kelompok] ?? $this->kelompok;
    }

    public function canServeRole(string $role): bool
    {
        return match ($this->participation_role) {
            self::ROLE_BOTH, self::ROLE_AS_NEEDED => in_array($role, ['main', 'backup'], true),
            self::ROLE_MAIN => $role === 'main',
            self::ROLE_BACKUP => $role === 'backup',
            default => false,
        };
    }
}
