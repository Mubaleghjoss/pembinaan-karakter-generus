<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeacherScheduleSession extends Model
{
    protected $fillable = [
        'period_id', 'template_id', 'session_date', 'rombel', 'start_time',
        'end_time', 'location', 'status', 'notes',
    ];

    protected $casts = ['session_date' => 'date'];

    public function period(): BelongsTo
    {
        return $this->belongsTo(TeacherSchedulePeriod::class, 'period_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherScheduleAssignment::class, 'session_id');
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(
            TeacherMaterial::class,
            'teacher_material_session',
            'teacher_schedule_session_id',
            'teacher_material_id'
        )->withTimestamps();
    }

    public function mainAssignment(): ?TeacherScheduleAssignment
    {
        return $this->assignments->firstWhere('role', 'main');
    }

    public function backupAssignment(): ?TeacherScheduleAssignment
    {
        return $this->assignments->firstWhere('role', 'backup');
    }
}
