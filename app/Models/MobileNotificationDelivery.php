<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileNotificationDelivery extends Model
{
    protected $fillable = [
        'notification_id',
        'device_id',
        'claimed_at',
        'sent_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(MobileDeviceToken::class, 'device_id');
    }
}
