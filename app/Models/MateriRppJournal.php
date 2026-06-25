<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MateriRppJournal extends Model
{
    public const STATUS_TERLAKSANA = 'terlaksana';
    public const STATUS_SEBAGIAN = 'sebagian';
    public const STATUS_TIDAK_TERLAKSANA = 'tidak_terlaksana';

    public const WORKFLOW_PENDING_REVIEW = 'pending_review';
    public const WORKFLOW_NEEDS_REVISION = 'needs_revision';
    public const WORKFLOW_APPROVED = 'approved';

    protected $fillable = [
        'schedule_reminder_id',
        'materi_id',
        'journal_date',
        'session_number',
        'session_type',
        'materi_title',
        'target_page_range',
        'target_page_start',
        'target_page_end',
        'actual_page_start',
        'actual_page_end',
        'start_time',
        'end_time',
        'teacher_name',
        'teacher_user_id',
        'realization_status',
        'workflow_status',
        'notes',
        'obstacles',
        'follow_up',
        'created_by',
        'updated_by',
        'submitted_by_siswa_id',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'target_page_start' => 'integer',
        'target_page_end' => 'integer',
        'actual_page_start' => 'integer',
        'actual_page_end' => 'integer',
        'teacher_user_id' => 'integer',
        'session_number' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_TERLAKSANA => 'Terlaksana',
            self::STATUS_SEBAGIAN => 'Sebagian',
            self::STATUS_TIDAK_TERLAKSANA => 'Tidak Terlaksana',
        ];
    }

    public static function workflowOptions(): array
    {
        return [
            self::WORKFLOW_PENDING_REVIEW => 'Menunggu Konfirmasi',
            self::WORKFLOW_NEEDS_REVISION => 'Perlu Perbaikan',
            self::WORKFLOW_APPROVED => 'Disahkan',
        ];
    }

    public function getWorkflowLabelAttribute(): string
    {
        return self::workflowOptions()[$this->workflow_status] ?? $this->workflow_status;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->realization_status] ?? $this->realization_status;
    }

    public function getActualPageRangeAttribute(): string
    {
        if ($this->actual_page_start && $this->actual_page_end) {
            return 'Halaman ' . $this->actual_page_start . '-' . $this->actual_page_end;
        }

        if ($this->actual_page_start) {
            return 'Halaman ' . $this->actual_page_start;
        }

        return '-';
    }

    public function scheduleReminder(): BelongsTo
    {
        return $this->belongsTo(ScheduleReminder::class);
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submittedBySiswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'submitted_by_siswa_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
