<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karakter extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'karakter';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'deskripsi',
        'kategori',
        'tanggal_mulai',
        'tanggal_selesai',
        'poin',
        'is_active',
        'jenis_penyelesaian',
        'target_teks',
        'target_klik',
        'allows_photo_proof',
        'photo_proof_bonus_points',
        'photo_proof_instruction',
        'allows_voice_note_proof',
        'voice_note_bonus_points',
        'voice_note_instruction',
        'proof_requirement',
        'voice_note_max_seconds',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'poin' => 'integer',
        'allows_photo_proof' => 'boolean',
        'photo_proof_bonus_points' => 'integer',
        'allows_voice_note_proof' => 'boolean',
        'voice_note_bonus_points' => 'integer',
        'voice_note_max_seconds' => 'integer',
    ];

    /**
     * Get the tracer karakter records for this karakter.
     */
    public function tracerKarakter(): HasMany
    {
        return $this->hasMany(TracerKarakter::class);
    }

    /**
     * Get the checklist records for this karakter.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(SiswaKarakterChecklist::class);
    }

    /**
     * Scope for active karakter only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by kategori.
     */
    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    /**
     * Scope tasks that are available on a specific date.
     */
    public function scopeAvailableOn($query, $date)
    {
        $date = Carbon::parse($date)->toDateString();

        return $query
            ->where(function ($q) use ($date) {
                $q->whereNull('tanggal_mulai')
                    ->orWhereDate('tanggal_mulai', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $date);
            });
    }

    /**
     * Deactivate active tasks whose period ended before the given date.
     */
    public static function deactivateExpiredTasks(Carbon|string|null $date = null): int
    {
        $date = Carbon::parse($date ?? now())->toDateString();

        return static::query()
            ->where('is_active', true)
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '<', $date)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    /**
     * Check if the task time has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->tanggal_selesai) {
            return false;
        }
        return now()->startOfDay()->gt($this->tanggal_selesai);
    }

    /**
     * Check if the task is currently available (within date range).
     */
    public function isAvailable(): bool
    {
        $today = now()->startOfDay();

        if ($this->tanggal_mulai && $today->lt($this->tanggal_mulai)) {
            return false; // Not started yet
        }

        if ($this->tanggal_selesai && $today->gt($this->tanggal_selesai)) {
            return false; // Expired
        }

        return true;
    }

    /**
     * Check if the task is available for the selected work date.
     */
    public function isAvailableOn($date): bool
    {
        $targetDate = Carbon::parse($date)->startOfDay();

        if ($this->tanggal_mulai && $targetDate->lt($this->tanggal_mulai->copy()->startOfDay())) {
            return false;
        }

        if ($this->tanggal_selesai && $targetDate->gt($this->tanggal_selesai->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * Get formatted date range.
     */
    public function getFormattedPeriodAttribute(): ?string
    {
        if (!$this->tanggal_mulai && !$this->tanggal_selesai) {
            return null;
        }

        $mulai = $this->tanggal_mulai ? $this->tanggal_mulai->format('d M Y') : '?';
        $selesai = $this->tanggal_selesai ? $this->tanggal_selesai->format('d M Y') : '?';

        return "{$mulai} - {$selesai}";
    }

    /**
     * Get usage count (how many times this karakter has been checked).
     */
    public function getUsageCountAttribute(): int
    {
        return $this->tracerKarakter()->count();
    }

    /**
     * Get label for kategori.
     */
    public function getKategoriLabelAttribute(): string
    {
        return match($this->kategori) {
            'harian' => 'Harian',
            'mingguan' => 'Mingguan',
            'bulanan' => 'Bulanan',
            default => ucfirst($this->kategori),
        };
    }

    public function getProofRequirementLabelAttribute(): string
    {
        return match($this->proof_requirement) {
            'required_any' => 'Minimal satu bukti wajib',
            default => 'Bukti opsional',
        };
    }
}
