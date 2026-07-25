<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presentation extends Model
{
    protected $fillable = [
        'materi_id',
        'created_by',
        'title',
        'slug',
        'description',
        'background_color',
        'path_mode',
        'canvas_data',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'canvas_data' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(PresentationAsset::class);
    }
}
