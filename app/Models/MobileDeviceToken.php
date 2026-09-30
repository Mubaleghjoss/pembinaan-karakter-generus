<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MobileDeviceToken extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'token_hash',
        'token',
        'platform',
        'app_version',
        'last_seen_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token',
        'token_hash',
    ];

    protected $casts = [
        'token' => 'encrypted',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
