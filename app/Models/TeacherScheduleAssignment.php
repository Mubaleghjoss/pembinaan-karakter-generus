<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherScheduleAssignment extends Model
{
    protected $fillable = [
        'session_id', 'teacher_profile_id', 'role', 'source', 'is_locked',
        'confirmation_status', 'confirmation_token_hash', 'confirmation_requested_at',
        'confirmation_token_encrypted',
        'confirmed_at', 'confirmation_note', 'h3_whatsapp_opened_at',
        'h3_whatsapp_sent_at', 'h1_whatsapp_opened_at', 'h1_whatsapp_sent_at',
        'overload_reason', 'assigned_by',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'confirmation_requested_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'h3_whatsapp_opened_at' => 'datetime',
        'h3_whatsapp_sent_at' => 'datetime',
        'h1_whatsapp_opened_at' => 'datetime',
        'h1_whatsapp_sent_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TeacherScheduleSession::class, 'session_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(TeacherScheduleRequest::class, 'assignment_id');
    }
}
