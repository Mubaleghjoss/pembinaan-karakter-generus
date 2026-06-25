<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrtuComment extends Model
{
    protected $fillable = [
        'siswa_karakter_checklist_id',
        'siswa_id',
        'comment',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(SiswaKarakterChecklist::class, 'siswa_karakter_checklist_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
