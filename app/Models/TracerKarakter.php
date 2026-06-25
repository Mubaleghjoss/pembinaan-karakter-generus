<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TracerKarakter extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tracer_karakter';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
        'karakter_id',
        'pamong_id',
        'checked_at',
        'catatan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checked_at' => 'datetime',
    ];

    /**
     * Get the siswa that owns this tracer record.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Get the karakter that was checked.
     */
    public function karakter(): BelongsTo
    {
        return $this->belongsTo(Karakter::class);
    }

    /**
     * Get the pamong who recorded this check.
     */
    public function pamong(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pamong_id');
    }
}
