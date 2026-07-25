<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherSchedulePeriod extends Model
{
    protected $fillable = [
        'month', 'status', 'created_by', 'published_by', 'published_at',
        'publish_warning_acknowledgement',
    ];

    protected $casts = ['month' => 'date', 'published_at' => 'datetime'];

    public function sessions(): HasMany
    {
        return $this->hasMany(TeacherScheduleSession::class, 'period_id');
    }
}
