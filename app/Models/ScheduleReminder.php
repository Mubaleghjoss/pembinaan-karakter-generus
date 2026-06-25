<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduleReminder extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'target_audience',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_days',
        'location',
        'color',
        'is_active',
        'created_by',
        'source_type',
        'source_id',
        'source_payload',
        'journal_assignee_type',
        'journal_assignee_user_id',
        'journal_assignee_siswa_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'is_recurring' => 'boolean',
        'recurrence_days' => 'array',
        'is_active' => 'boolean',
        'source_payload' => 'array',
    ];

    public const SOURCE_MATERI_RPP = 'materi_rpp';

    /**
     * Get the user who created this schedule.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rppJournal(): HasOne
    {
        return $this->hasOne(MateriRppJournal::class);
    }

    public function journalAssignees(): HasMany
    {
        return $this->hasMany(MateriRppJournalAssignee::class);
    }

    public function journalAssigneeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'journal_assignee_user_id');
    }

    public function journalAssigneeSiswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'journal_assignee_siswa_id');
    }

    public function sourceMateri(): BelongsTo
    {
        return $this->belongsTo(Materi::class, 'source_id');
    }

    public function journalAvailableAt(): Carbon
    {
        $date = $this->start_date->copy()->startOfDay();
        $rawEndTime = $this->getAttributes()['end_time'] ?? null;

        if (! $rawEndTime) {
            return $date->endOfDay();
        }

        return $date->setTimeFromTimeString(substr($rawEndTime, 0, 8));
    }

    public function isJournalAvailable(): bool
    {
        return now()->greaterThanOrEqualTo($this->journalAvailableAt());
    }

    public function getJournalAssigneeLabelAttribute(): string
    {
        if ($this->relationLoaded('journalAssignees') && $this->journalAssignees->isNotEmpty()) {
            return $this->journalAssignees
                ->map(fn (MateriRppJournalAssignee $assignee) => $assignee->label)
                ->filter()
                ->unique()
                ->implode(', ');
        }

        if ($this->journal_assignee_type === 'siswa') {
            return $this->journalAssigneeSiswa?->nama ?? 'Siswa tidak tersedia';
        }

        if ($this->journal_assignee_type === 'user') {
            return $this->journalAssigneeUser?->display_name ?? 'Petugas tidak tersedia';
        }

        return 'Belum ditugaskan';
    }

    /**
     * Scope for active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for schedules targeting specific audience.
     */
    public function scopeForAudience($query, string $audience)
    {
        if ($audience === 'all') {
            return $query;
        }

        return $query->where(function ($q) use ($audience) {
            $q->where('target_audience', $audience)
              ->orWhere('target_audience', 'all');
        });
    }

    /**
     * Scope for schedules within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            // Non-recurring: check if schedule overlaps with range
            $q->where(function ($subQ) use ($startDate, $endDate) {
                $subQ->where('is_recurring', false)
                     ->where(function ($dateQ) use ($startDate) {
                         $dateQ->where(function ($singleDate) use ($startDate) {
                             $singleDate->whereNull('end_date')
                                 ->whereDate('start_date', '>=', $startDate);
                         })->orWhere(function ($dateRange) use ($startDate) {
                             $dateRange->whereNotNull('end_date')
                                 ->whereDate('end_date', '>=', $startDate);
                         });
                     })
                     ->where('start_date', '<=', $endDate);
            })
            // Recurring: check if start_date is before end of range
            ->orWhere(function ($subQ) use ($endDate) {
                $subQ->where('is_recurring', true)
                     ->where('start_date', '<=', $endDate);
            });
        });
    }

    /**
     * Expand recurring schedule into individual events for a date range.
     */
    public function expandToEvents(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $events = [];

        if (!$this->is_recurring) {
            $events[] = $this->toCalendarEvent($this->start_date, $this->end_date);
            return $events;
        }

        // Recurring event - expand based on pattern
        $currentDate = $this->start_date->copy();
        $endDate = $this->end_date ?? $rangeEnd;

        // Start from range start if schedule started before
        if ($currentDate->lt($rangeStart)) {
            $currentDate = $rangeStart->copy();
        }

        while ($currentDate->lte($rangeEnd) && $currentDate->lte($endDate)) {
            $shouldInclude = false;

            switch ($this->recurrence_pattern) {
                case 'daily':
                    $shouldInclude = true;
                    break;

                case 'weekly':
                    $dayName = strtolower($currentDate->format('l'));
                    $shouldInclude = empty($this->recurrence_days) || in_array($dayName, $this->recurrence_days);
                    break;

                case 'monthly':
                    // Same day of month as start_date
                    $shouldInclude = $currentDate->day === $this->start_date->day;
                    break;
            }

            if ($shouldInclude && $currentDate->gte($this->start_date)) {
                $events[] = $this->toCalendarEvent($currentDate->copy());
            }

            $currentDate->addDay();
        }

        return $events;
    }

    /**
     * Convert to calendar event format.
     */
    public function toCalendarEvent(Carbon $date, ?Carbon $eventEndDate = null): array
    {
        if ($this->source_type === self::SOURCE_MATERI_RPP) {
            return $this->toMateriRppCalendarEvent($date);
        }

        $startDate = $date->copy();
        $endDate = $eventEndDate ? $eventEndDate->copy() : $date->copy();
        $startDateTime = $startDate->format('Y-m-d');

        // Get raw time values from attributes (before casting)
        $rawStartTime = $this->getAttributes()['start_time'] ?? null;
        $rawEndTime = $this->getAttributes()['end_time'] ?? null;

        // Handle overnight events (end_time < start_time means next day)
        if ($rawStartTime && $rawEndTime) {
            $startTimeStr = substr($rawStartTime, 0, 5); // Get HH:mm
            $endTimeStr = substr($rawEndTime, 0, 5);
            
            // If end time is before start time on the same date, it's an overnight event
            if (! $eventEndDate && $endTimeStr < $startTimeStr) {
                $endDate->addDay();
            }
        }

        $endDateTime = $rawStartTime ? null : $endDate->copy()->addDay()->format('Y-m-d');

        if ($rawStartTime) {
            $startDateTime .= 'T' . $rawStartTime;
        }

        if ($rawEndTime) {
            $endDateTime = $endDate->format('Y-m-d');
            $endDateTime .= 'T' . $rawEndTime;
        }

        // Default color jika tidak diset - orange untuk jadwal admin
        $color = $this->color ?: '#F97316';

        // Format time for display
        $displayStartTime = $rawStartTime ? substr($rawStartTime, 0, 5) : null;
        $displayEndTime = $rawEndTime ? substr($rawEndTime, 0, 5) : null;

        return [
            'id' => 'schedule-' . $this->id . '-' . $date->format('Y-m-d'),
            'title' => $this->title,
            'start' => $startDateTime,
            'end' => $endDateTime,
            'allDay' => ! $rawStartTime,
            'color' => $color,
            'type' => 'schedule-reminder',
            'extendedProps' => [
                'type' => 'schedule-reminder',
                'schedule_id' => $this->id,
                'title' => $this->title,
                'description' => $this->description,
                'location' => $this->location,
                'start_time' => $displayStartTime,
                'end_time' => $displayEndTime,
                'is_recurring' => $this->is_recurring,
                'target_audience' => $this->target_audience,
                'created_by' => $this->creator?->name ?? $this->creator?->username ?? 'Admin',
            ],
        ];
    }

    protected function toMateriRppCalendarEvent(Carbon $date): array
    {
        $payload = $this->source_payload ?? [];
        $pageRange = $payload['page_range'] ?? null;
        $rawStartTime = $this->getAttributes()['start_time'] ?? null;
        $rawEndTime = $this->getAttributes()['end_time'] ?? null;
        $start = $date->format('Y-m-d');
        $end = null;

        if ($rawStartTime) {
            $start .= 'T' . $rawStartTime;
        }

        if ($rawEndTime) {
            $end = $date->format('Y-m-d') . 'T' . $rawEndTime;
        }

        return [
            'id' => 'materi-rpp-' . $this->source_id . '-' . ($payload['number'] ?? $this->id),
            'title' => $payload['materi_title'] ?? $this->title,
            'start' => $start,
            'end' => $end,
            'allDay' => ! $rawStartTime,
            'color' => $this->color ?: '#14B8A6',
            'type' => self::SOURCE_MATERI_RPP,
            'extendedProps' => [
                'type' => self::SOURCE_MATERI_RPP,
                'schedule_id' => $this->id,
                'materi_id' => $this->source_id,
                'title' => $payload['materi_title'] ?? $this->title,
                'materi_title' => $payload['materi_title'] ?? $this->title,
                'description' => $this->description,
                'page_range' => $pageRange,
                'page_start' => $payload['page_start'] ?? null,
                'page_end' => $payload['page_end'] ?? null,
                'pages' => $payload['pages'] ?? null,
                'start_time' => $rawStartTime ? substr($rawStartTime, 0, 5) : null,
                'end_time' => $rawEndTime ? substr($rawEndTime, 0, 5) : null,
                'session_number' => $payload['number'] ?? null,
                'session_type' => $payload['type'] ?? 'regular',
                'weekday' => $payload['weekday'] ?? null,
                'weekday_label' => $payload['weekday_label'] ?? null,
                'range_start_date' => $payload['range_start_date'] ?? null,
                'range_end_date' => $payload['range_end_date'] ?? null,
                'teacher_name' => $payload['teacher_name'] ?? null,
                'teacher_user_id' => $payload['teacher_user_id'] ?? null,
                'teacher_is_manual' => $payload['teacher_is_manual'] ?? null,
                'teacher_is_override' => $payload['teacher_is_override'] ?? null,
                'target_audience' => $this->target_audience,
                'url' => $this->source_id ? route('public.materi.show', $this->source_id) : null,
            ],
        ];
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): ?string
    {
        $rawStartTime = $this->getAttributes()['start_time'] ?? null;
        $rawEndTime = $this->getAttributes()['end_time'] ?? null;

        if (!$rawStartTime) {
            return null;
        }

        $start = substr($rawStartTime, 0, 5);
        
        if ($rawEndTime) {
            $end = substr($rawEndTime, 0, 5);
            return "{$start} - {$end}";
        }

        return $start;
    }
}
