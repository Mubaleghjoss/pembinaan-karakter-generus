<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareInfo extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'auto_dismiss_seconds',
        'is_active',
        'target',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_dismiss_seconds' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTarget($query, string $target)
    {
        return $query->where(function ($q) use ($target) {
            $q->where('target', 'all')->orWhere('target', $target);
        });
    }
}
