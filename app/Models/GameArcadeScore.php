<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameArcadeScore extends Model
{
    protected $fillable = [
        'game', 'player_type', 'player_id', 'player_name', 'score', 'best_combo',
    ];

    protected $casts = [
        'score' => 'integer',
        'best_combo' => 'integer',
    ];
}
