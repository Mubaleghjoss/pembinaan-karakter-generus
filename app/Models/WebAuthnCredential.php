<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class WebAuthnCredential extends Model
{
    protected $table = 'webauthn_credentials';

    protected static array $columnPresenceCache = [];

    protected $fillable = [
        'user_id',
        'user_type',
        'credential_id',
        'credential_public_key',
        'signature_counter',
        'attestation_format',
        'aaguid',
        'transports',
        'user_handle',
        'user_verified',
        'backup_eligible',
        'backed_up',
        'device_name',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'signature_counter' => 'integer',
        'transports' => 'array',
        'user_verified' => 'boolean',
        'backup_eligible' => 'boolean',
        'backed_up' => 'boolean',
    ];

    /**
     * Get the user (siswa, admin/pamong, or ortu) for this credential.
     */
    public function getUser()
    {
        return match($this->user_type) {
            'siswa' => Siswa::find($this->user_id),
            'admin' => \App\Models\User::find($this->user_id),
            'ortu'  => Siswa::find($this->user_id), // Ortu auth uses Siswa model with ortu credentials
            default => null,
        };
    }

    /**
     * Get the guard name for this credential's user type.
     */
    public function getGuardName(): string
    {
        return match($this->user_type) {
            'siswa' => 'siswa',
            'admin' => 'web',
            'ortu'  => 'ortu',
            default => 'web',
        };
    }

    /**
     * Get the dashboard route for this user type.
     */
    public function getDashboardRoute(): string
    {
        if ($this->user_type === 'admin') {
            $user = $this->getUser();
            if ($user?->isGuru()) {
                return $user->must_change_password ? 'guru.password.initial' : 'guru.dashboard';
            }
        }

        return match($this->user_type) {
            'siswa' => 'siswa.dashboard',
            'admin' => 'dashboard',
            'ortu'  => 'ortu.dashboard',
            default => 'dashboard',
        };
    }

    public static function hasSchemaColumn(string $column): bool
    {
        $table = (new static())->getTable();
        $cacheKey = "{$table}.{$column}";

        if (! array_key_exists($cacheKey, static::$columnPresenceCache)) {
            static::$columnPresenceCache[$cacheKey] = Schema::hasTable($table)
                && Schema::hasColumn($table, $column);
        }

        return static::$columnPresenceCache[$cacheKey];
    }

    public static function supportsCredentialPublicKey(): bool
    {
        return static::hasSchemaColumn('credential_public_key');
    }

    public static function supportsServerVerification(): bool
    {
        foreach (['credential_public_key', 'signature_counter', 'user_handle'] as $column) {
            if (! static::hasSchemaColumn($column)) {
                return false;
            }
        }

        return true;
    }
}
