<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAvailabilityInvite extends Model
{
    protected $fillable = ['label', 'token_hash', 'max_uses', 'used_count', 'expires_at', 'is_active'];

    protected $casts = [
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isAvailable(): bool
    {
        return $this->is_active
            && (! $this->expires_at || $this->expires_at->isFuture())
            && $this->used_count < $this->max_uses;
    }
}
