<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MateriFolder extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class, 'materi_folder_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getDisplayNameAttribute($value = null): string
    {
        if ($value) {
            return $value;
        }

        if ($this->relationLoaded('parent') && $this->parent) {
            return $this->parent->display_name . ' / ' . $this->name;
        }

        return $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where($this->getTable() . '.is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull($this->getTable() . '.parent_id');
    }
}
