<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MateriRppJournalAssignee extends Model
{
    protected $fillable = [
        'schedule_reminder_id',
        'assignee_type',
        'user_id',
        'siswa_id',
        'assigned_by',
    ];

    public function scheduleReminder(): BelongsTo
    {
        return $this->belongsTo(ScheduleReminder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getLabelAttribute(): string
    {
        return $this->assignee_type === 'siswa'
            ? ($this->siswa?->nama ?? 'Siswa tidak tersedia')
            : ($this->user?->display_name ?? 'Petugas tidak tersedia');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->assignee_type === 'siswa' ? 'Siswa' : 'Admin/Pamong';
    }
}
