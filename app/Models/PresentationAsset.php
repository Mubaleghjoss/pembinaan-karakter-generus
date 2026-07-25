<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresentationAsset extends Model
{
    protected $fillable = [
        'presentation_id',
        'uploaded_by',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
