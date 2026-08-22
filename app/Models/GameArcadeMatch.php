<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameArcadeMatch extends Model
{
    protected $fillable = [
        'code', 'seed', 'status',
        'p1_type', 'p1_id', 'p1_name', 'p1_score',
        'p2_type', 'p2_id', 'p2_name', 'p2_score',
        'winner', 'last_activity_at',
    ];

    protected $casts = [
        'p1_score' => 'integer',
        'p2_score' => 'integer',
        'last_activity_at' => 'datetime',
    ];

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }
}
