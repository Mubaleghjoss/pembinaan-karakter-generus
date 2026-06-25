<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'badge_id',
        'earned_at',
        'metadata'
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'metadata' => 'array'
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('earned_at', '>=', now()->subDays($days));
    }
}
