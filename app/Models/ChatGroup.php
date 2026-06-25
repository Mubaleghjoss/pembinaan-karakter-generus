<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Group types
    const TYPE_CUSTOM = 'custom';
    const TYPE_ALL_PAMONG = 'all_pamong';
    const TYPE_ALL_SISWA = 'all_siswa';
    const TYPE_ALL_USERS = 'all_users';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatGroupMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatGroupMessage::class);
    }

    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    public function getLastActivityAttribute(): ?string
    {
        $lastMessage = $this->messages()->latest()->first();
        return $lastMessage ? $lastMessage->created_at->diffForHumans() : null;
    }

    public function hasMember($userId = null, $siswaId = null): bool
    {
        $query = $this->members();
        
        if ($userId) {
            return $query->where('user_id', $userId)->exists();
        }
        
        if ($siswaId) {
            return $query->where('siswa_id', $siswaId)->exists();
        }
        
        return false;
    }
}
