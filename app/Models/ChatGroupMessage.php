<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroupMessage extends Model
{
    protected $fillable = [
        'chat_group_id',
        'sender_user_id',
        'sender_siswa_id',
        'message',
        'attachment_path',
        'is_read_by',
    ];

    protected $casts = [
        'is_read_by' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id');
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function senderSiswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'sender_siswa_id');
    }

    public function getSenderNameAttribute(): string
    {
        return $this->senderUser?->username ?? $this->senderSiswa?->nama ?? 'Unknown';
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? asset('storage/' . $this->attachment_path) : null;
    }

    public function isReadBy($userId = null, $siswaId = null): bool
    {
        $readBy = $this->is_read_by ?? [];
        
        if ($userId) {
            return in_array("user_{$userId}", $readBy);
        }
        
        if ($siswaId) {
            return in_array("siswa_{$siswaId}", $readBy);
        }
        
        return false;
    }

    public function markAsReadBy($userId = null, $siswaId = null): void
    {
        $readBy = $this->is_read_by ?? [];
        
        $key = $userId ? "user_{$userId}" : "siswa_{$siswaId}";
        
        if (!in_array($key, $readBy)) {
            $readBy[] = $key;
            $this->update(['is_read_by' => $readBy]);
        }
    }
}
