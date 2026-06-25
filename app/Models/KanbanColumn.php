<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KanbanColumn extends Model
{
    protected $fillable = ['name', 'color', 'order'];

    public function cards(): HasMany
    {
        return $this->hasMany(CatatanRapat::class, 'column_id')->orderBy('order');
    }
}
