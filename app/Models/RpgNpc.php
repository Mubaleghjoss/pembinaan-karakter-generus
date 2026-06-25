<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RpgNpc extends Model
{
    use HasFactory;

    protected $fillable = [
        'rpg_map_id',
        'nama',
        'avatar',
        'pos_x',
        'pos_y',
        'pertanyaan',
        'pilihan_jawaban',
        'jawaban_benar',
        'poin',
        'is_active',
    ];

    protected $casts = [
        'pilihan_jawaban' => 'array',
        'jawaban_benar' => 'integer',
        'poin' => 'integer',
        'pos_x' => 'integer',
        'pos_y' => 'integer',
        'is_active' => 'boolean',
    ];

    public function map(): BelongsTo
    {
        return $this->belongsTo(RpgMap::class, 'rpg_map_id');
    }
}
