<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatatanRapatLog extends Model
{
    protected $table = 'catatan_rapat_logs';

    protected $fillable = [
        'catatan_rapat_id',
        'card_title',
        'user_id',
        'action',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function catatanRapat(): BelongsTo
    {
        return $this->belongsTo(CatatanRapat::class, 'catatan_rapat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-readable action label
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'created' => 'Membuat catatan',
            'updated' => 'Mengedit catatan',
            'deleted' => 'Menghapus catatan',
            'moved' => 'Memindahkan catatan',
            default => $this->action,
        };
    }

    /**
     * Get action color for UI
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'moved' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Log an activity
     */
    public static function log(CatatanRapat $card, string $action, ?array $details = null): self
    {
        return self::create([
            'catatan_rapat_id' => $action === 'deleted' ? null : $card->id,
            'card_title' => $card->title,
            'user_id' => auth()->id(),
            'action' => $action,
            'details' => $details,
        ]);
    }
}
