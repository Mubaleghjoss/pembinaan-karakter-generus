<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MateriFolder extends Model
{
    protected $fillable = [
        'name',
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

    public function scopeActive($query)
    {
        return $query->where($this->getTable() . '.is_active', true);
    }
}
