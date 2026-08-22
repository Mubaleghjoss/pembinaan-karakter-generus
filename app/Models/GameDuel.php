<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameDuel extends Model
{
    protected $fillable = [
        'mode', 'opponent_type', 'status', 'total_rounds',
        'p1_siswa_id', 'p2_siswa_id', 'ai_difficulty',
        'p1_score', 'p2_score', 'questions', 'p1_answers', 'p2_answers',
        'winner', 'join_code', 'last_activity_at',
    ];

    protected $casts = [
        'questions' => 'array',
        'p1_answers' => 'array',
        'p2_answers' => 'array',
        'last_activity_at' => 'datetime',
        'total_rounds' => 'integer',
        'p1_score' => 'integer',
        'p2_score' => 'integer',
    ];

    public function p1()
    {
        return $this->belongsTo(Siswa::class, 'p1_siswa_id');
    }

    public function p2()
    {
        return $this->belongsTo(Siswa::class, 'p2_siswa_id');
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function touchActivity(): void
    {
        $this->last_activity_at = now();
    }
}
