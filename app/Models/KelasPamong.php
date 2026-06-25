<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasPamong extends Model
{
    protected $table = 'kelas_pamong';

    protected $fillable = [
        'kelas_id',
        'pamong_id',
        'role',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pamong(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pamong_id');
    }
}
