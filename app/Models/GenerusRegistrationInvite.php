<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GenerusRegistrationInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'token_hash',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(GenerusRegistration::class, 'invite_id');
    }

    public function isAvailable(): bool
    {
        return $this->is_active
            && (! $this->expires_at || $this->expires_at->isFuture())
            && $this->used_count < $this->max_uses;
    }
}
