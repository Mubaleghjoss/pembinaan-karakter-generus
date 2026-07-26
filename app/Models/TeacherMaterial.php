<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeacherMaterial extends Model
{
    protected $fillable = [
        'title',
        'description',
        'google_drive_url',
        'rombels',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'rombels' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(
            TeacherScheduleSession::class,
            'teacher_material_session',
            'teacher_material_id',
            'teacher_schedule_session_id'
        )->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function rombelLabels(): array
    {
        return collect($this->rombels ?? [])
            ->map(fn (string $rombel) => TeacherProfile::ROMBELS[$rombel] ?? strtoupper($rombel))
            ->values()
            ->all();
    }
}
