<?php

namespace App\Models;

use Carbon\Carbon;
use App\Support\TargetGrade;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Siswa extends Authenticatable
{
    use HasFactory;

    public const KELOMPOK_PANUNGGANGAN_UTARA = 'panunggangan utara';
    public const KELOMPOK_SAWAH_DALAM = 'sawah dalam';
    public const KELOMPOK_PAKULONAN = 'pakulonan';

    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'password',
        'nama',
        'jenis_kelamin',
        'tanggal_lahir',
        'alamat',
        'kelompok',
        'phone',
        'kelas_id',
        'target_grade_override',
        'foto_path',
        'status',
        'nama_wali',
        'phone_wali',
        'email_wali',
        'ortu_username',
        'ortu_password',
        'metadata',
        'is_active',
        'qr_token',
        'qr_token_expires_at',
        'last_login_at',
        'ortu_last_login_at',
    ];

    protected $hidden = [
        'password',
        'password_plain',
        'ortu_password',
        'ortu_password_plain',
        'remember_token',
        'qr_secret_salt',
        'qr_token',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'qr_token_expires_at' => 'datetime',
        'last_qr_scan_at' => 'datetime',
        'last_login_at' => 'datetime',
        'ortu_last_login_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($siswa) {
            if (empty($siswa->qr_secret_salt)) {
                $siswa->qr_secret_salt = Str::random(64);
            }
        });

        static::saving(function ($siswa) {
            foreach (['password_plain', 'ortu_password_plain'] as $plainPasswordColumn) {
                if ($siswa->isDirty($plainPasswordColumn)) {
                    $siswa->attributes[$plainPasswordColumn] = null;
                }
            }

            if (! static::hasKelompokColumn()) {
                $normalized = static::normalizeKelompok($siswa->attributes['kelompok'] ?? null);

                if ($normalized && empty($siswa->attributes['alamat'])) {
                    $siswa->attributes['alamat'] = $normalized;
                }

                unset($siswa->attributes['kelompok']);
            }
        });
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class);
    }

    public function materiTargetProgress(): HasMany
    {
        return $this->hasMany(SiswaMateriTargetProgress::class);
    }

    public function generateQrToken(int $expiryMinutes = 60): string
    {
        // Generate token yang konsisten berdasarkan ID dan qr_secret_salt
        // Token tidak akan berubah kecuali qr_secret_salt berubah
        $token = hash('sha256', $this->id . $this->qr_secret_salt . $this->nis);

        $this->update([
            'qr_token' => $token,
            'qr_token_expires_at' => Carbon::now()->addMinutes($expiryMinutes),
        ]);

        return $token;
    }

    public function verifyQrToken(string $token): bool
    {
        // Hanya cek apakah token cocok, tidak cek expiry
        // QR code akan tetap valid selama token tidak berubah
        return $this->qr_token === $token;
    }

    public function recordQrScan(): void
    {
        $this->increment('qr_scan_count');
        $this->update(['last_qr_scan_at' => Carbon::now()]);
    }

    public function getQrData(): array
    {
        $token = $this->generateQrToken();
        $this->refresh();

        return [
            'student_id' => $this->id,
            'nis' => $this->nis,
            'token' => $token,
            'expires_at' => $this->qr_token_expires_at?->toISOString() ?? now()->addMinutes(60)->toISOString(),
            'hash' => hash('sha256', $this->id.$token.$this->qr_secret_salt),
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_active;
    }

    public function getFullIdentityAttribute(): string
    {
        return "{$this->nama} ({$this->nis})";
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (empty($this->foto_path) || ! Storage::disk('public')->exists($this->foto_path)) {
            return null;
        }

        return asset('storage/' . $this->foto_path) . '?v=' . Storage::disk('public')->lastModified($this->foto_path);
    }

    public function getAgeAttribute(): int
    {
        return $this->tanggal_lahir ? Carbon::parse($this->tanggal_lahir)->age : 0;
    }

    public function getTargetGradeAttribute(): ?string
    {
        return TargetGrade::resolveForSiswa($this);
    }

    public function getTargetGradeLabelAttribute(): ?string
    {
        return TargetGrade::label($this->target_grade);
    }

    public function getMissingBiodataFieldsAttribute(): array
    {
        $missingFields = [];
        $kelompokValue = $this->kelompok;
        
        if (empty($this->nama)) $missingFields[] = 'Nama Lengkap';
        if (empty($kelompokValue)) $missingFields[] = 'Kelompok';
        if (empty($this->tanggal_lahir)) $missingFields[] = 'Tanggal Lahir';
        if (empty($this->phone)) $missingFields[] = 'No. HP';
        if (empty($this->phone_wali)) $missingFields[] = 'No. HP Wali';
        if (empty($this->foto_path)) $missingFields[] = 'Foto Profil';
        
        return $missingFields;
    }

    public static function kelompokOptions(): array
    {
        return [
            self::KELOMPOK_PANUNGGANGAN_UTARA => 'Panunggangan Utara',
            self::KELOMPOK_SAWAH_DALAM => 'Sawah Dalam',
            self::KELOMPOK_PAKULONAN => 'Pakulonan',
        ];
    }

    public static function normalizeKelompok(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return array_key_exists($normalized, self::kelompokOptions()) ? $normalized : null;
    }

    public static function hasKelompokColumn(): bool
    {
        static $hasKelompokColumn = null;

        if ($hasKelompokColumn === null) {
            $hasKelompokColumn = Schema::hasColumn((new static())->getTable(), 'kelompok');
        }

        return $hasKelompokColumn;
    }

    public function getKelompokAttribute($value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        return static::normalizeKelompok($this->attributes['alamat'] ?? null);
    }

    public function getKelompokLabelAttribute(): ?string
    {
        return static::kelompokOptions()[$this->kelompok] ?? null;
    }

    public function getIsBiodataCompleteAttribute(): bool
    {
        return empty($this->missing_biodata_fields);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

    public function scopeInClass($query, $kelasId)
    {
        return $query->where('kelas_id', $kelasId);
    }

    public function scopeAssignedTo($query, $pamongId)
    {
        return $query->whereHas('pamongAssignments', function ($q) use ($pamongId) {
            $q->where('pamong_id', $pamongId);
        });
    }

    public function scopeForUser($query, $user)
    {
        if ($user->hasRole('admin') || $user->isPamongExcluded()) {
            return $query;
        }
        return $query->assignedTo($user->id);
    }

    public function scopeForManualAttendance($query, User $user)
    {
        if ($user->canAccessAllManualAttendanceStudents()) {
            return $query;
        }

        return $query->assignedTo($user->id);
    }

    public function pamongAssignments(): HasMany
    {
        return $this->hasMany(PamongSiswa::class);
    }

    public function tracerKarakter(): HasMany
    {
        return $this->hasMany(TracerKarakter::class);
    }

    public function biometricCredentials(): HasMany
    {
        return $this->hasMany(WebAuthnCredential::class, 'user_id')
            ->where('user_type', 'siswa');
    }

    public function validBiometricCredentials(): HasMany
    {
        $relation = $this->biometricCredentials();

        if (! WebAuthnCredential::supportsCredentialPublicKey()) {
            return $relation->whereRaw('1 = 0');
        }

        return $relation->whereNotNull('credential_public_key');
    }

    public function legacyBiometricCredentials(): HasMany
    {
        $relation = $this->biometricCredentials();

        if (! WebAuthnCredential::supportsCredentialPublicKey()) {
            return $relation;
        }

        return $relation->whereNull('credential_public_key');
    }

    public function ortuBiometricCredentials(): HasMany
    {
        return $this->hasMany(WebAuthnCredential::class, 'user_id')
            ->where('user_type', 'ortu');
    }

    public function setPasswordAttribute($value): void
    {
        if ($value) {
            // Check if value is already hashed
            if (str_starts_with($value, '$2y$') || str_starts_with($value, '$2a$')) {
                $this->attributes['password'] = $value;
            } else {
                $this->attributes['password'] = Hash::make($value);
            }
        }
    }

    /**
     * Generate default password (NIS).
     */
    public function generateDefaultPassword(): void
    {
        $this->password_plain = null;
        $this->attributes['password'] = Hash::make($this->nis);
        $this->save();
    }

    public function getAuthIdentifierName(): string
    {
        return 'nis';
    }

    public function getPamongAttribute()
    {
        return $this->pamongAssignments()
            ->with('pamong')
            ->get()
            ->pluck('pamong');
    }

    // ============ GAMIFICATION RELATIONSHIPS ============

    public function siswaPoint(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SiswaPoint::class);
    }

    public function badges(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('earned_at', 'metadata')
            ->withTimestamps();
    }

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    // ============ ORTU RELATIONSHIPS & METHODS ============

    public function ortuComments(): HasMany
    {
        return $this->hasMany(OrtuComment::class);
    }

    public function setOrtuPasswordAttribute($value): void
    {
        if ($value) {
            if (str_starts_with($value, '$2y$') || str_starts_with($value, '$2a$')) {
                $this->attributes['ortu_password'] = $value;
            } else {
                $this->attributes['ortu_password'] = Hash::make($value);
            }
        }
    }

    public function initOrtuAccount(): void
    {
        if (!$this->ortu_username) {
            $this->ortu_username = $this->nis;
            $this->save();
        }
    }
}
