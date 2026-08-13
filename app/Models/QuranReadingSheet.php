<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranReadingSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id', 'public_id', 'token_hash', 'status', 'row_count', 'template_version', 'last_position',
        'generated_by', 'revoked_at',
    ];

    protected $casts = [
        'last_position' => 'array',
        'template_version' => 'integer',
        'revoked_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(QuranReadingEntry::class, 'sheet_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(QuranReadingScan::class, 'sheet_id');
    }

    public function verifyToken(string $token): bool
    {
        return $this->status === 'active' && hash_equals($this->token_hash, hash('sha256', $token));
    }
}
