<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RpgMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'deskripsi',
        'grid_size',
        'background_theme',
        'obstacles',
        'enemies',
        'difficulty',
        'shield_duration_seconds',
        'ammo_per_pickup',
        'shield_pickups_count',
        'ammo_pickups_count',
        'is_active',
    ];

    protected $casts = [
        'obstacles' => 'array',
        'enemies' => 'array',
        'is_active' => 'boolean',
        'grid_size' => 'integer',
        'shield_duration_seconds' => 'integer',
        'ammo_per_pickup' => 'integer',
        'shield_pickups_count' => 'integer',
        'ammo_pickups_count' => 'integer',
    ];

    public function npcs(): HasMany
    {
        return $this->hasMany(RpgNpc::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(RpgGameSession::class);
    }

    public function activeNpcs(): HasMany
    {
        return $this->hasMany(RpgNpc::class)->where('is_active', true);
    }
}
