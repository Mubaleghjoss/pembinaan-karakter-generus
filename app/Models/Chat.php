<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chat extends Model
{
    protected $fillable = [
        'sender_siswa_id',
        'sender_user_id',
        'receiver_siswa_id',
        'receiver_user_id',
        'message',
        'message_type',
        'attachment_path',
        'caption',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Message types
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_LINK = 'link';

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? asset('storage/' . $this->attachment_path) : null;
    }

    public function senderSiswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'sender_siswa_id');
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function receiverSiswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'receiver_siswa_id');
    }

    public function receiverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function getSenderNameAttribute(): string
    {
        return $this->senderSiswa?->nama ?? $this->senderUser?->username ?? 'Unknown';
    }

    public function getReceiverNameAttribute(): string
    {
        return $this->receiverSiswa?->nama ?? $this->receiverUser?->username ?? 'Unknown';
    }
}
