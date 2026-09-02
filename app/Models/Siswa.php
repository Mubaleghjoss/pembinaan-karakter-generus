<?php

namespace App\Models;

use Carbon\Carbon;
use App\Support\ParticipantProfileOptions;
use App\Support\TargetGrade;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class Siswa extends Authenticatable
{
    // HasApiTokens ditambahkan agar aplikasi mobile bisa login sebagai
    // siswa/orang tua lewat Sanctum bearer token (web tetap memakai
    // guard sesi 'siswa'/'ortu' — tidak ada perubahan pada alur web).
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable;

    public const KELOMPOK_SAWAH_DALAM_1 = ParticipantProfileOptions::SAWAH_DALAM_1;
    public const KELOMPOK_SAWAH_DALAM_2 = ParticipantProfileOptions::SAWAH_DALAM_2;
    public const KELOMPOK_SAWAH_DALAM = self::KELOMPOK_SAWAH_DALAM_1;
    public const KELOMPOK_PANUNGGANGAN_UTARA = ParticipantProfileOptions::PANUNGGANGAN_UTARA;
    public const KELOMPOK_PAKULONAN = ParticipantProfileOptions::PAKULONAN;

    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'password',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'kelompok',
        'phone',
        'kelas_id',
        'school_grade',
        'target_grade_override',
        'profile_assignment_confirmed_at',
        'foto_path',
        'status',
        'graduated_at',
        'alumni_can_submit',
        'alumni_reviewer_id',
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
        'profile_assignment_confirmed_at' => 'datetime',
        'graduated_at' => 'datetime',
        'alumni_can_submit' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($siswa) {
            if (empty($siswa->status)) {
                $siswa->status = 'active';
            }
            if ($siswa->is_active === null) {
                $siswa->is_active = true;
            }
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

    public function generusRegistration(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(GenerusRegistration::class);
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

    public function isGraduated(): bool
    {
        return $this->status === 'graduated' && $this->is_active;
    }

    public function canLogin(): bool
    {
        return $this->is_active && in_array($this->status, ['active', 'graduated'], true);
    }

    public function canSubmitAsAlumni(): bool
    {
        return ! $this->isGraduated() || $this->alumni_can_submit;
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

    public function getSchoolGradeLabelAttribute(): ?string
    {
        return TargetGrade::schoolClassLabel($this->school_grade);
    }

    public function getSchoolGradeSuggestionAttribute(): ?string
    {
        return TargetGrade::fromBirthDate($this->tanggal_lahir);
    }

    public function getMissingBiodataFieldsAttribute(): array
    {
        $missingFields = [];
        $kelompokValue = $this->kelompok;
        
        if (empty($this->nama)) $missingFields[] = 'Nama Lengkap';
        if (empty($kelompokValue)) $missingFields[] = 'Kelompok';
        if (empty($this->tanggal_lahir)) $missingFields[] = 'Tanggal Lahir';
        if (empty($this->school_grade)) $missingFields[] = 'Kelas Sekolah';
        if (empty($this->phone)) $missingFields[] = 'No. HP';
        if (empty($this->phone_wali)) $missingFields[] = 'No. HP Wali';
        if (empty($this->foto_path)) $missingFields[] = 'Foto Profil';
        
        return $missingFields;
    }

    public static function kelompokOptions(): array
    {
        return ParticipantProfileOptions::groups();
    }

    public static function normalizeKelompok(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return ParticipantProfileOptions::normalizeGroup($value);
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

    public function needsProfileAssignmentConfirmation(): bool
    {
        return ! $this->profile_assignment_confirmed_at
            || ! static::normalizeKelompok($this->kelompok)
            || ! in_array($this->school_grade, TargetGrade::values(), true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

    public function scopeInClass($query, $kelasId)
    {
        return $query->where('kelas_id', $kelasId);
    }

    public function scopeBySchoolGrade($query, ?string $schoolGrade)
    {
        return $query->when($schoolGrade, fn ($builder, $grade) => $builder->where('school_grade', $grade));
    }

    public function scopeByPamong($query, $pamongId)
    {
        return $query->when($pamongId, fn ($builder, $id) => $builder->whereHas(
            'pamongAssignments',
            fn ($assignment) => $assignment->where('pamong_id', $id)
        ));
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
        return $this->hasMany(PamongSiswa::class)->whereNull('ended_at');
    }

    public function pamongAssignmentHistory(): HasMany
    {
        return $this->hasMany(PamongSiswa::class);
    }

    public function alumniReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alumni_reviewer_id');
    }

    public function tracerKarakter(): HasMany
    {
        return $this->hasMany(TracerKarakter::class);
    }

    public function quranReadingEntries(): HasMany
    {
        return $this->hasMany(QuranReadingEntry::class);
    }

    public function quranReadingSheets(): HasMany
    {
        return $this->hasMany(QuranReadingSheet::class);
    }

    public function quranReadingCycles(): HasMany
    {
        return $this->hasMany(QuranReadingCycle::class);
    }

    public function quranProgressSubmissions(): HasMany
    {
        return $this->hasMany(QuranProgressSubmission::class);
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
