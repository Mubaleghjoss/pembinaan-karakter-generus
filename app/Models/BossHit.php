<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BossHit extends Model
{
    protected $fillable = [
        'boss_battle_id', 'siswa_id', 'damage', 'correct_count', 'points_awarded',
    ];

    protected $casts = [
        'damage' => 'integer',
        'correct_count' => 'integer',
        'points_awarded' => 'boolean',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function bossBattle()
    {
        return $this->belongsTo(BossBattle::class);
    }
}
