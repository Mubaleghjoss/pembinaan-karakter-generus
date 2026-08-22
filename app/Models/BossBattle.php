<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BossBattle extends Model
{
    protected $fillable = [
        'nama', 'deskripsi', 'mode', 'max_hp', 'current_hp', 'status', 'created_by', 'ends_at',
    ];

    protected $casts = [
        'max_hp' => 'integer',
        'current_hp' => 'integer',
        'ends_at' => 'datetime',
    ];

    public function hits()
    {
        return $this->hasMany(BossHit::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->current_hp > 0;
    }

    public function hpPercent(): int
    {
        if ($this->max_hp <= 0) {
            return 0;
        }
        return (int) round(max(0, $this->current_hp) / $this->max_hp * 100);
    }
}
