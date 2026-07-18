<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PwaNotificationDelivery extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'notification_key',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
