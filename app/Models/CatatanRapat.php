<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatatanRapat extends Model
{
    protected $table = 'catatan_rapat';

    protected $fillable = [
        'title',
        'description',
        'tanggal_rapat',
        'column_id',
        'created_by',
        'assigned_to',
        'priority',
        'due_date',
        'order',
    ];

    protected $casts = [
        'tanggal_rapat' => 'date',
        'due_date' => 'date',
    ];

    public function column(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class, 'column_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }
}
