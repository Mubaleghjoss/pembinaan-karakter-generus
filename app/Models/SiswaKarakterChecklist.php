<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SiswaKarakterChecklist extends Model
{
    use HasFactory, SoftDeletes;

    protected static ?array $unavailableMediaLookup = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'siswa_karakter_checklist';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
        'karakter_id',
        'checked_at',
        'verified_by',
        'verified_at',
        'notes',
        'student_note',
        'deleted_by',
        'deleted_reason',
        'hasil_teks',
        'click_history',
        'proof_path',
        'proof_original_size_kb',
        'proof_compressed_size_kb',
        'voice_note_path',
        'voice_note_size_kb',
        'voice_note_duration_seconds',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checked_at' => 'datetime',
        'verified_at' => 'datetime',
        'deleted_at' => 'datetime',
        'click_history' => 'array',
        'proof_original_size_kb' => 'integer',
        'proof_compressed_size_kb' => 'integer',
        'voice_note_size_kb' => 'integer',
        'voice_note_duration_seconds' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $checklist) {
            if ($checklist->isForceDeleting() && $checklist->proof_path) {
                Storage::disk('public')->delete($checklist->proof_path);
            }

            if ($checklist->isForceDeleting() && $checklist->voice_note_path) {
                Storage::disk('public')->delete($checklist->voice_note_path);
            }
        });
    }

    public function clearStoredEvidenceFiles(): void
    {
        if ($this->proof_path) {
            Storage::disk('public')->delete($this->proof_path);
        }

        if ($this->voice_note_path) {
            Storage::disk('public')->delete($this->voice_note_path);
        }
    }

    /**
     * Get the siswa that owns this checklist.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Get the karakter for this checklist.
     */
    public function karakter(): BelongsTo
    {
        return $this->belongsTo(Karakter::class);
    }

    /**
     * Get the user who verified this checklist.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the user who deleted this checklist.
     */
    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get ortu comments for this checklist.
     */
    public function ortuComments(): HasMany
    {
        return $this->hasMany(OrtuComment::class, 'siswa_karakter_checklist_id');
    }

    /**
     * Check if this entry is verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Scope for verified entries only.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope for unverified entries only.
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }

    public function getHasProofAttribute(): bool
    {
        return $this->has_photo_proof || $this->has_voice_note;
    }

    public function getHasPhotoProofAttribute(): bool
    {
        return ! empty($this->proof_path);
    }

    public function getHasVoiceNoteAttribute(): bool
    {
        return ! empty($this->voice_note_path);
    }

    public function getProofMediaAvailableAttribute(): bool
    {
        return $this->mediaAvailable($this->proof_path);
    }

    public function getVoiceNoteMediaAvailableAttribute(): bool
    {
        return $this->mediaAvailable($this->voice_note_path);
    }

    public function getProofMediaUnavailableAttribute(): bool
    {
        return $this->mediaUnavailable($this->proof_path);
    }

    public function getVoiceNoteMediaUnavailableAttribute(): bool
    {
        return $this->mediaUnavailable($this->voice_note_path);
    }

    public function getProofUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->proof_path);
    }

    public function getVoiceNoteUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->voice_note_path, $this->voiceNoteNeedsControlledResponse($this->voice_note_path));
    }

    public function getVoiceNoteMimeTypeAttribute(): string
    {
        $extension = strtolower(pathinfo((string) $this->voice_note_path, PATHINFO_EXTENSION));

        if ($this->localMediaStartsWithAdtsFrame($this->voice_note_path)) {
            return 'audio/aac';
        }

        return match ($extension) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg', 'oga' => 'audio/ogg',
            'aac' => 'audio/aac',
            'webm' => 'audio/webm',
            'mp4', 'm4a' => 'audio/mp4',
            default => 'audio/*',
        };
    }

    protected function mediaUrl(?string $path, bool $forceProxy = false): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (! $forceProxy && $this->isExistingUsablePublicMediaPath($path)) {
            return asset('storage/' . $path) . '?v=' . Storage::disk('public')->lastModified($path);
        }

        return route('admin.media.sync-proxy', [
            'path' => $path,
            'v' => $this->updated_at?->timestamp ?? $this->checked_at?->timestamp ?? time(),
        ]);
    }

    protected function mediaAvailable(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        if ($this->isExistingUsablePublicMediaPath($path)) {
            return true;
        }

        return ! isset($this->unavailableMediaLookup()[$path]);
    }

    protected function mediaUnavailable(?string $path): bool
    {
        return ! empty($path)
            && ! $this->isExistingUsablePublicMediaPath($path)
            && isset($this->unavailableMediaLookup()[$path]);
    }

    protected function isExistingUsablePublicMediaPath(?string $path): bool
    {
        if (!$path || ! Storage::disk('public')->exists($path)) {
            return false;
        }

        try {
            $absolutePath = Storage::disk('public')->path($path);
            if (! is_file($absolutePath) || filesize($absolutePath) <= 0) {
                return false;
            }

            $handle = fopen($absolutePath, 'rb');
            if (! $handle) {
                return false;
            }

            $prefix = fread($handle, 512) ?: '';
            fclose($handle);

            return $this->isUsableMediaPrefix($path, $prefix);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function voiceNoteNeedsControlledResponse(?string $path): bool
    {
        return $this->localMediaStartsWithAdtsFrame($path);
    }

    protected function localMediaStartsWithAdtsFrame(?string $path): bool
    {
        if (!$path || ! Storage::disk('public')->exists($path)) {
            return false;
        }

        try {
            $absolutePath = Storage::disk('public')->path($path);
            if (! is_file($absolutePath) || filesize($absolutePath) <= 0) {
                return false;
            }

            $handle = fopen($absolutePath, 'rb');
            if (! $handle) {
                return false;
            }

            $prefix = fread($handle, 16) ?: '';
            fclose($handle);

            return $this->startsWithAdtsFrame($prefix);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isUsableMediaPrefix(?string $path, string $prefix): bool
    {
        if ($prefix === '') {
            return false;
        }

        $textPrefix = strtolower(ltrim($prefix));

        if (str_starts_with($textPrefix, '<!doctype html')
            || str_starts_with($textPrefix, '<html')
            || str_starts_with($textPrefix, '{')) {
            return false;
        }

        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => $this->hasImageSignature($prefix, $textPrefix),
            'mp3', 'wav', 'ogg', 'oga', 'webm', 'm4a', 'mp4', 'aac' => $this->hasAudioSignature($prefix),
            default => true,
        };
    }

    protected function hasImageSignature(string $prefix, string $textPrefix): bool
    {
        return str_starts_with($prefix, "\xFF\xD8")
            || str_starts_with($prefix, "\x89PNG\r\n\x1A\n")
            || str_starts_with($prefix, 'GIF87a')
            || str_starts_with($prefix, 'GIF89a')
            || (substr($prefix, 0, 4) === 'RIFF' && substr($prefix, 8, 4) === 'WEBP')
            || str_contains(substr($textPrefix, 0, 240), '<svg');
    }

    protected function hasAudioSignature(string $prefix): bool
    {
        return str_starts_with($prefix, 'ID3')
            || $this->startsWithMpegFrame($prefix)
            || (substr($prefix, 0, 4) === 'RIFF' && substr($prefix, 8, 4) === 'WAVE')
            || str_starts_with($prefix, 'OggS')
            || str_starts_with($prefix, "\x1A\x45\xDF\xA3")
            || substr($prefix, 4, 4) === 'ftyp'
            || $this->startsWithAdtsFrame($prefix);
    }

    protected function startsWithMpegFrame(string $prefix): bool
    {
        return strlen($prefix) >= 2
            && ord($prefix[0]) === 0xFF
            && (ord($prefix[1]) & 0xE0) === 0xE0;
    }

    protected function startsWithAdtsFrame(string $prefix): bool
    {
        return strlen($prefix) >= 2
            && ord($prefix[0]) === 0xFF
            && (ord($prefix[1]) & 0xF0) === 0xF0;
    }

    protected function unavailableMediaLookup(): array
    {
        if (self::$unavailableMediaLookup !== null) {
            return self::$unavailableMediaLookup;
        }

        $decoded = json_decode((string) Setting::get('sync_media_unavailable_paths', '[]'), true);
        if (! is_array($decoded)) {
            return self::$unavailableMediaLookup = [];
        }

        return self::$unavailableMediaLookup = collect($decoded)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->mapWithKeys(fn ($path) => [$path => true])
            ->all();
    }

    public function getPhotoProofBonusPointsAttribute(): int
    {
        if (empty($this->proof_path) || ! $this->karakter?->allows_photo_proof) {
            return 0;
        }

        return (int) ($this->karakter->photo_proof_bonus_points ?? 0);
    }

    public function getVoiceNoteBonusPointsAttribute(): int
    {
        if (empty($this->voice_note_path) || ! $this->karakter?->allows_voice_note_proof) {
            return 0;
        }

        return (int) ($this->karakter->voice_note_bonus_points ?? 0);
    }

    public function getProofBonusPointsAttribute(): int
    {
        return $this->photo_proof_bonus_points + $this->voice_note_bonus_points;
    }

    public function getAwardedPointsAttribute(): int
    {
        return (int) ($this->karakter->poin ?? 0) + $this->proof_bonus_points;
    }

    public function getVoiceNoteDurationLabelAttribute(): ?string
    {
        if (! $this->voice_note_duration_seconds) {
            return null;
        }

        $minutes = intdiv((int) $this->voice_note_duration_seconds, 60);
        $seconds = (int) $this->voice_note_duration_seconds % 60;

        if ($minutes > 0) {
            return trim($minutes . ' menit ' . ($seconds > 0 ? $seconds . ' detik' : ''));
        }

        return $seconds . ' detik';
    }
}
