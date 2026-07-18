<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use App\Models\PamongActivityLog;
use App\Support\ParticipantProfileOptions;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_PKG_MANAGER = 'pkg_manager';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'kelompok',
        'profile_assignment_confirmed_at',
        'password',
        'role_id',
        'organizational_team_id',
        'organizational_title',
        'organizational_sort_order',
        'status',
        'avatar_path',
        'qr_token',
        'qr_token_generated_at',
        'theme_preference',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['avatar_url', 'display_name'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'plain_password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'qr_token_generated_at' => 'datetime',
        'profile_assignment_confirmed_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the role that owns the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function organizationalTeam(): BelongsTo
    {
        return $this->belongsTo(OrganizationalTeam::class, 'organizational_team_id');
    }

    /**
     * Get the pamong associated with the user.
     */
    public function pamong(): HasOne
    {
        return $this->hasOne(Pamong::class);
    }

    /**
     * Get the berita for the user.
     */
    public function berita(): HasMany
    {
        return $this->hasMany(Berita::class, 'author_id');
    }

    /**
     * Get the students assigned to this pamong.
     */
    public function assignedStudents(): HasMany
    {
        return $this->hasMany(PamongSiswa::class, 'pamong_id');
    }

    /**
     * Get siswa assigned to this pamong (through pivot).
     */
    public function siswa()
    {
        return $this->hasManyThrough(
            Siswa::class,
            PamongSiswa::class,
            'pamong_id',
            'id',
            'id',
            'siswa_id'
        );
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is locked
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Lock user account
     */
    public function lockAccount(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => Carbon::now()->addMinutes($minutes),
            'failed_login_attempts' => $this->failed_login_attempts + 1,
        ]);
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(): void
    {
        $this->update([
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Record successful login
     */
    public function recordLogin(string $ipAddress, ?string $userAgent = null): void
    {
        $this->update([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $ipAddress,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        // Log login activity for pamong (wrapped in try-catch so login doesn't fail if table doesn't exist yet)
        try {
            if ($this->usesPamongPermissionSystem()) {
                PamongActivityLog::log(
                    userId: $this->id,
                    action: 'login',
                    description: 'Login ke sistem',
                    module: 'auth',
                    ipAddress: $ipAddress
                );
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to log pamong activity: ' . $e->getMessage());
        }
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role &&
               $this->role->permissions &&
               (in_array('*', $this->role->permissions, true) ||
                in_array($permission, $this->role->permissions, true));
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function hasAnyRole(array $roleNames): bool
    {
        return $this->role && in_array($this->role->name, $roleNames, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isTeacher(): bool
    {
        return $this->hasRole(self::ROLE_TEACHER);
    }

    public function isPengurusPkg(): bool
    {
        return $this->hasRole(self::ROLE_PKG_MANAGER);
    }

    public function operationalRoleLabel(): string
    {
        $roleName = $this->role?->name;

        if ($roleName === self::ROLE_TEACHER) {
            return 'Pamong';
        }

        if ($this->role?->display_name) {
            return $this->role->display_name;
        }

        return match ($roleName) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_PKG_MANAGER => 'Pengurus PKG',
            default => 'User',
        };
    }

    public function organizationalLabel(): ?string
    {
        if (! $this->organizationalTeam && ! $this->organizational_title) {
            return null;
        }

        if ($this->organizationalTeam && $this->organizational_title) {
            return $this->organizationalTeam->name . ' - ' . $this->organizational_title;
        }

        return $this->organizational_title ?: $this->organizationalTeam?->name;
    }

    public static function operationalRoleNames(): array
    {
        return [
            self::ROLE_TEACHER,
            self::ROLE_PKG_MANAGER,
        ];
    }

    public static function attendanceRoleNames(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_TEACHER,
            self::ROLE_PKG_MANAGER,
        ];
    }

    public function usesPamongPermissionSystem(): bool
    {
        return $this->hasAnyRole(self::operationalRoleNames());
    }

    public static function kelompokOptions(): array
    {
        return ParticipantProfileOptions::groups();
    }

    public static function normalizeKelompok(?string $value): ?string
    {
        return ParticipantProfileOptions::normalizeGroup($value);
    }

    public function getKelompokLabelAttribute(): ?string
    {
        return static::kelompokOptions()[$this->kelompok] ?? null;
    }

    public function needsProfileAssignmentConfirmation(): bool
    {
        return $this->usesPamongPermissionSystem()
            && (! $this->profile_assignment_confirmed_at || ! static::normalizeKelompok($this->kelompok));
    }

    public function canAccessGamificationAdmin(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->usesPamongPermissionSystem()) {
            return false;
        }

        return $this->hasPamongMenuAccess('gamification')
            || $this->hasPamongMenuAccess('game');
    }

    /**
     * Get the avatar URL.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
            return asset('storage/' . $this->avatar_path);
        }

        return null;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name
            ?: $this->username
            ?: $this->email
            ?: 'User #' . $this->id;
    }

    /**
     * Check if this pamong is assigned to a specific siswa.
     */
    public function isAssignedTo(Siswa $siswa): bool
    {
        return $this->assignedStudents()->where('siswa_id', $siswa->id)->exists();
    }

    public function canAccessAllManualAttendanceStudents(): bool
    {
        if ($this->isAdmin() || $this->isPamongExcluded()) {
            return true;
        }

        if (! $this->usesPamongPermissionSystem()) {
            return false;
        }

        return $this->hasAnyPamongCrudPermission('manual_attendance', [
            'all_students',
            'all_siswa',
            'semua_siswa',
            'semua_murid',
        ]);
    }

    public function canCreateManualAttendance(): bool
    {
        if ($this->isAdmin() || $this->isPamongExcluded()) {
            return true;
        }

        if (! $this->usesPamongPermissionSystem()) {
            return false;
        }

        return $this->hasPamongMenuAccess('manual_attendance')
            && $this->hasPamongCrudPermission('manual_attendance', 'create');
    }

    public function canRecordManualAttendanceFor(Siswa $siswa): bool
    {
        return $this->canCreateManualAttendance()
            && ($this->canAccessAllManualAttendanceStudents()
                || $this->isAssignedTo($siswa));
    }

    /**
     * Get assigned siswa IDs for this pamong.
     */
    public function getAssignedSiswaIds(): array
    {
        return $this->assignedStudents()->pluck('siswa_id')->toArray();
    }

    /**
     * Filter siswa query to only show assigned students (for pamong role).
     */
    public function filterSiswaByAssignment($query)
    {
        if ($this->isTeacher()) {
            $assignedIds = $this->getAssignedSiswaIds();
            return $query->whereIn('id', $assignedIds);
        }
        return $query;
    }

    /**
     * Get pamong attendance records.
     */
    public function pamongPresensi(): HasMany
    {
        return $this->hasMany(PamongPresensi::class);
    }

    /**
     * Get pamong permission settings.
     */
    public function pamongPermission(): HasOne
    {
        return $this->hasOne(PamongPermission::class);
    }

    public function biometricCredentials(): HasMany
    {
        return $this->hasMany(WebAuthnCredential::class, 'user_id')
            ->where('user_type', 'admin');
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

    /**
     * Check if pamong has menu access.
     */
    public function hasPamongMenuAccess(string $menu): bool
    {
        // Admin always has full access
        if ($this->isAdmin()) {
            return true;
        }
        
        // Roles outside scoped operational access don't use this permission system
        if (! $this->usesPamongPermissionSystem()) {
            return true;
        }
        
        $permission = $this->pamongPermission;
        
        // Fall back to configured defaults for older accounts that do not have a permission record yet.
        if (!$permission) {
            return in_array($menu, PamongPermission::getDefaultMenuPermissions(), true);
        }
        
        return $permission->hasMenuAccess($menu);
    }

    /**
     * Check if pamong has CRUD permission.
     */
    public function hasPamongCrudPermission(string $module, string $operation): bool
    {
        // Admin always has full access
        if ($this->isAdmin()) {
            return true;
        }

        // Roles outside scoped operational access don't use this permission system
        if (! $this->usesPamongPermissionSystem()) {
            return true;
        }
        
        $permission = $this->pamongPermission;
        
        // Fall back to configured defaults for older accounts that do not have a permission record yet.
        if (!$permission) {
            $defaultCrud = PamongPermission::getDefaultCrudPermissions();

            return in_array($operation, $defaultCrud[$module] ?? [], true);
        }
        
        return $permission->hasCrudPermission($module, $operation);
    }

    public function hasAnyPamongCrudPermission(string $module, array $operations): bool
    {
        foreach ($operations as $operation) {
            if ($this->hasPamongCrudPermission($module, $operation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if pamong is excluded from restrictions.
     */
    public function isPamongExcluded(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        $permission = $this->pamongPermission;
        return $permission && $permission->is_excluded;
    }

    /**
     * Get schedule reminders created by this user.
     */
    public function scheduleReminders(): HasMany
    {
        return $this->hasMany(ScheduleReminder::class, 'created_by');
    }
}
