<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroupMember extends Model
{
    protected $fillable = [
        'chat_group_id',
        'user_id',
        'siswa_id',
        'role',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    // Roles
    const ROLE_ADMIN = 'admin';
    const ROLE_MEMBER = 'member';

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function getMemberNameAttribute(): string
    {
        return $this->user?->username ?? $this->siswa?->nama ?? 'Unknown';
    }

    public function getMemberTypeAttribute(): string
    {
        return $this->user_id ? 'user' : 'siswa';
    }
}
