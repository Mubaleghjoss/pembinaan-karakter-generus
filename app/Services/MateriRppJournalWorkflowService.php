<?php

namespace App\Services;

use App\Models\MateriRppJournal;
use App\Models\MateriRppJournalAssignee;
use App\Models\ScheduleReminder;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MateriRppJournalWorkflowService
{
    public const ASSIGNEE_USER = 'user';
    public const ASSIGNEE_SISWA = 'siswa';

    public function schemaReady(): bool
    {
        return Cache::remember('materi-rpp-journal-schema-ready-v1', now()->addMinutes(5), fn () =>
            Schema::hasTable('materi_rpp_journal_assignees')
            && Schema::hasColumns('schedule_reminders', [
                'journal_assignee_type',
                'journal_assignee_user_id',
                'journal_assignee_siswa_id',
            ])
            && Schema::hasColumns('materi_rpp_journals', [
                'workflow_status',
                'submitted_by_siswa_id',
            ])
        );
    }

    public function canManageAll(User $user): bool
    {
        return $user->isAdmin()
            || ($user->isPengurusPkg()
                && $user->hasPamongMenuAccess('rpp_journals')
                && $user->hasPamongCrudPermission('rpp_journals', 'manage'));
    }

    public function canUseStaffJournal(User $user): bool
    {
        return $user->isAdmin()
            || ($user->usesPamongPermissionSystem()
                && $user->hasPamongMenuAccess('rpp_journals')
                && $user->hasPamongCrudPermission('rpp_journals', 'view'));
    }

    public function canViewStaffSchedule(User $user, ScheduleReminder $schedule): bool
    {
        if (! $this->isRppSchedule($schedule) || ! $this->canUseStaffJournal($user)) {
            return false;
        }

        if ($this->canManageAll($user)) {
            return true;
        }

        return $this->hasUserAssignee($schedule, $user->id)
            || $this->teacherUserId($schedule) === $user->id;
    }

    public function canSubmitAsStaff(User $user, ScheduleReminder $schedule): bool
    {
        if (! $this->canViewStaffSchedule($user, $schedule) || ! $schedule->isJournalAvailable()) {
            return false;
        }

        return $this->canManageAll($user) || $this->hasUserAssignee($schedule, $user->id);
    }

    public function canReview(User $user, MateriRppJournal $journal): bool
    {
        $schedule = $journal->scheduleReminder;

        if (! $schedule || ! $journal->submitted_by_siswa_id) {
            return false;
        }

        return $this->canManageAll($user) || $this->teacherUserId($schedule) === $user->id;
    }

    public function canViewAsStudent(Siswa $siswa, ScheduleReminder $schedule): bool
    {
        return $this->isRppSchedule($schedule)
            && $this->hasStudentAssignee($schedule, $siswa->id);
    }

    public function canSubmitAsStudent(Siswa $siswa, ScheduleReminder $schedule, ?MateriRppJournal $journal = null): bool
    {
        if (! $this->canViewAsStudent($siswa, $schedule) || ! $schedule->isJournalAvailable()) {
            return false;
        }

        return ! $journal || $journal->workflow_status === MateriRppJournal::WORKFLOW_NEEDS_REVISION;
    }

    public function visibleStaffSchedules(User $user): Builder
    {
        $query = $this->endedRppSchedules();

        if ($this->canManageAll($user)) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($user) {
            $scope->whereHas('journalAssignees', fn (Builder $assignee) => $assignee->where('user_id', $user->id))
                ->orWhere('source_payload->teacher_user_id', $user->id);
        });
    }

    public function visibleStudentSchedules(Siswa $siswa): Builder
    {
        return $this->endedRppSchedules()
            ->whereHas('journalAssignees', fn (Builder $assignee) => $assignee->where('siswa_id', $siswa->id));
    }

    public function actionableStaffSchedules(User $user): Builder
    {
        $query = $this->visibleStaffSchedules($user);

        if ($this->canManageAll($user)) {
            return $query->where(function (Builder $status) {
                $status->whereDoesntHave('rppJournal')
                    ->orWhereHas('rppJournal', fn (Builder $journal) =>
                        $journal->where('workflow_status', MateriRppJournal::WORKFLOW_PENDING_REVIEW)
                    );
            });
        }

        return $query->where(function (Builder $status) use ($user) {
            $status->where(function (Builder $assigned) use ($user) {
                $assigned->where(function (Builder $scope) use ($user) {
                    $scope->whereHas('journalAssignees', fn (Builder $assignee) => $assignee->where('user_id', $user->id));
                })
                    ->whereDoesntHave('rppJournal');
            })->orWhere(function (Builder $review) use ($user) {
                $review->where('source_payload->teacher_user_id', $user->id)
                    ->whereHas('rppJournal', fn (Builder $journal) =>
                        $journal->where('workflow_status', MateriRppJournal::WORKFLOW_PENDING_REVIEW)
                    );
            });
        });
    }

    public function actionableStudentSchedules(Siswa $siswa): Builder
    {
        return $this->visibleStudentSchedules($siswa)
            ->where(function (Builder $status) {
                $status->whereDoesntHave('rppJournal')
                    ->orWhereHas('rppJournal', fn (Builder $journal) =>
                        $journal->where('workflow_status', MateriRppJournal::WORKFLOW_NEEDS_REVISION)
                    );
            });
    }

    public function staffTasks(User $user, int $limit = 5): Collection
    {
        if (! $this->schemaReady() || ! $this->canUseStaffJournal($user)) {
            return collect();
        }

        return $this->actionableStaffSchedules($user)
            ->with(['rppJournal', 'sourceMateri', 'journalAssignees.user', 'journalAssignees.siswa'])
            ->orderBy('start_date')
            ->orderBy('end_time')
            ->limit($limit)
            ->get();
    }

    public function studentTasks(Siswa $siswa, int $limit = 5): Collection
    {
        if (! $this->schemaReady()) {
            return collect();
        }

        return $this->actionableStudentSchedules($siswa)
            ->with(['rppJournal', 'sourceMateri'])
            ->orderBy('start_date')
            ->orderBy('end_time')
            ->limit($limit)
            ->get();
    }

    public function pendingStaffCount(User $user): int
    {
        if (! $this->schemaReady() || ! $this->canUseStaffJournal($user)) {
            return 0;
        }

        return Cache::remember(
            "materi-rpp-journal:staff:{$user->id}:{$this->cacheVersion()}",
            now()->addSeconds(60),
            fn () => $this->actionableStaffSchedules($user)->count()
        );
    }

    public function pendingStudentCount(Siswa $siswa): int
    {
        if (! $this->schemaReady()) {
            return 0;
        }

        return Cache::remember(
            "materi-rpp-journal:siswa:{$siswa->id}:{$this->cacheVersion()}",
            now()->addSeconds(60),
            fn () => $this->actionableStudentSchedules($siswa)->count()
        );
    }

    public function addAssignee(ScheduleReminder $schedule, string $type, int $assigneeId, ?User $assignedBy = null): MateriRppJournalAssignee
    {
        if ($schedule->rppJournal()->exists()) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Petugas tidak dapat diubah setelah jurnal pertama dikirim.',
            ]);
        }

        if ($type === self::ASSIGNEE_USER) {
            $assignee = User::query()
                ->where('status', 'active')
                ->whereHas('role', fn (Builder $role) => $role->whereIn('name', [
                    User::ROLE_ADMIN,
                    User::ROLE_TEACHER,
                    User::ROLE_PKG_MANAGER,
                ]))
                ->findOrFail($assigneeId);

            $row = MateriRppJournalAssignee::firstOrCreate([
                'schedule_reminder_id' => $schedule->id,
                'user_id' => $assignee->id,
            ], [
                'assignee_type' => self::ASSIGNEE_USER,
                'siswa_id' => null,
                'assigned_by' => $assignedBy?->id,
            ]);
        } elseif ($type === self::ASSIGNEE_SISWA) {
            $assignee = Siswa::active()->findOrFail($assigneeId);

            $row = MateriRppJournalAssignee::firstOrCreate([
                'schedule_reminder_id' => $schedule->id,
                'siswa_id' => $assignee->id,
            ], [
                'assignee_type' => self::ASSIGNEE_SISWA,
                'user_id' => null,
                'assigned_by' => $assignedBy?->id,
            ]);
        } else {
            throw ValidationException::withMessages([
                'assignee_type' => 'Jenis petugas jurnal tidak valid.',
            ]);
        }

        $this->touchCache();

        return $row;
    }

    public function removeAssignee(ScheduleReminder $schedule, MateriRppJournalAssignee $assignee): void
    {
        if ($schedule->rppJournal()->exists()) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Petugas tidak dapat diubah setelah jurnal pertama dikirim.',
            ]);
        }

        abort_unless((int) $assignee->schedule_reminder_id === $schedule->id, 404);

        if ($assignee->user_id && (int) $assignee->user_id === $this->teacherUserId($schedule)) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Pamong pengajar utama tetap menjadi petugas jurnal.',
            ]);
        }

        $assignee->delete();
        $this->touchCache();
    }

    public function snapshotFromSchedule(ScheduleReminder $schedule): array
    {
        $payload = $schedule->source_payload ?? [];
        $rawStartTime = $schedule->getAttributes()['start_time'] ?? null;
        $rawEndTime = $schedule->getAttributes()['end_time'] ?? null;

        return [
            'schedule_reminder_id' => $schedule->id,
            'materi_id' => $schedule->source_id,
            'journal_date' => $schedule->start_date,
            'session_number' => $payload['number'] ?? null,
            'session_type' => $payload['type'] ?? 'regular',
            'materi_title' => $payload['materi_title'] ?? $schedule->title,
            'target_page_range' => $payload['page_range'] ?? null,
            'target_page_start' => $payload['page_start'] ?? null,
            'target_page_end' => $payload['page_end'] ?? null,
            'start_time' => $rawStartTime ? substr($rawStartTime, 0, 8) : null,
            'end_time' => $rawEndTime ? substr($rawEndTime, 0, 8) : null,
            'teacher_name' => $payload['teacher_name'] ?? null,
            'teacher_user_id' => $payload['teacher_user_id'] ?? null,
            'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
        ];
    }

    public function workflowState(ScheduleReminder $schedule): string
    {
        return $schedule->rppJournal?->workflow_status ?? 'pending';
    }

    public function workflowLabel(ScheduleReminder $schedule): string
    {
        return match ($this->workflowState($schedule)) {
            MateriRppJournal::WORKFLOW_PENDING_REVIEW => 'Menunggu Konfirmasi',
            MateriRppJournal::WORKFLOW_NEEDS_REVISION => 'Perlu Perbaikan',
            MateriRppJournal::WORKFLOW_APPROVED => 'Disahkan',
            default => 'Belum Diisi',
        };
    }

    public function touchCache(): void
    {
        Cache::put(
            'materi-rpp-journal-workflow-version',
            $this->cacheVersion() + 1,
            now()->addDays(30)
        );
    }

    private function endedRppSchedules(): Builder
    {
        $today = today()->toDateString();
        $time = now()->format('H:i:s');

        return ScheduleReminder::query()
            ->where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where(function (Builder $ended) use ($today, $time) {
                $ended->whereDate('start_date', '<', $today)
                    ->orWhere(function (Builder $sameDay) use ($today, $time) {
                        $sameDay->whereDate('start_date', $today)
                            ->whereNotNull('end_time')
                            ->whereTime('end_time', '<=', $time);
                    });
            });
    }

    private function isRppSchedule(ScheduleReminder $schedule): bool
    {
        return $schedule->source_type === ScheduleReminder::SOURCE_MATERI_RPP;
    }

    private function teacherUserId(ScheduleReminder $schedule): int
    {
        return (int) (($schedule->source_payload ?? [])['teacher_user_id'] ?? 0);
    }

    private function hasUserAssignee(ScheduleReminder $schedule, int $userId): bool
    {
        if ($schedule->relationLoaded('journalAssignees')) {
            return $schedule->journalAssignees->contains(fn (MateriRppJournalAssignee $assignee) => (int) $assignee->user_id === $userId);
        }

        return $schedule->journalAssignees()->where('user_id', $userId)->exists();
    }

    private function hasStudentAssignee(ScheduleReminder $schedule, int $siswaId): bool
    {
        if ($schedule->relationLoaded('journalAssignees')) {
            return $schedule->journalAssignees->contains(fn (MateriRppJournalAssignee $assignee) => (int) $assignee->siswa_id === $siswaId);
        }

        return $schedule->journalAssignees()->where('siswa_id', $siswaId)->exists();
    }

    private function cacheVersion(): int
    {
        return (int) Cache::get('materi-rpp-journal-workflow-version', 1);
    }
}
