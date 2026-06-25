<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'type',
        'source',
        'points',
        'description',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'metadata' => 'array',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    public function scopeSpent($query)
    {
        return $query->where('type', 'spent');
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getFormattedPointsAttribute(): string
    {
        $sign = $this->points > 0 ? '+' : '';

        return $sign . number_format($this->points);
    }

    public function isPeriodResetArchive(): bool
    {
        return data_get($this->metadata, 'event') === 'period_reset';
    }

    public function getIconAttribute(): string
    {
        if ($this->isPeriodResetArchive()) {
            return 'ARS';
        }

        $icons = [
            'attendance' => 'ATT',
            'character' => 'CHR',
            'badge' => 'BDG',
            'manual' => 'MAN',
            'streak' => 'STR',
            'perfect_month' => 'PRF',
        ];

        return $icons[$this->source] ?? 'PTS';
    }

    public function getColorAttribute(): string
    {
        return match ($this->type) {
            'earned', 'bonus' => 'text-green-600',
            'spent', 'penalty' => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    public function getSourceLabelAttribute(): string
    {
        if ($this->isPeriodResetArchive()) {
            return 'arsip periode';
        }

        return $this->source;
    }
}
