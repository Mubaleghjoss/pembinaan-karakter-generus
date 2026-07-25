<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherScheduleTemplate extends Model
{
    protected $fillable = ['weekday', 'rombel', 'start_time', 'end_time', 'location', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
