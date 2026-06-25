<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RpgGameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'rpg_map_id',
        'pos_x',
        'pos_y',
        'answered_npcs',
        'total_score',
        'completed_at',
    ];

    protected $casts = [
        'answered_npcs' => 'array',
        'total_score' => 'integer',
        'pos_x' => 'integer',
        'pos_y' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(RpgMap::class, 'rpg_map_id');
    }

    public function hasAnsweredNpc(int $npcId): bool
    {
        return in_array($npcId, $this->answered_npcs ?? []);
    }

    public function markNpcAnswered(int $npcId): void
    {
        $answered = $this->answered_npcs ?? [];
        if (!in_array($npcId, $answered)) {
            $answered[] = $npcId;
            $this->answered_npcs = $answered;
        }
    }
}
