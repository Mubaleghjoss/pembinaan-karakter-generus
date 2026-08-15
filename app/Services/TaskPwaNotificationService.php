<?php

namespace App\Services;

use App\Models\Karakter;
use App\Models\PwaNotificationDelivery;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Models\User;
use App\Notifications\TaskBadgeWebPushNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class TaskPwaNotificationService
{
    public function pendingStudentTaskCount(Siswa $siswa, CarbonInterface|string|null $date = null): int
    {
        if (! $siswa->canSubmitAsAlumni()) {
            return 0;
        }

        $targetDate = $date instanceof CarbonInterface
            ? $date->toDateString()
            : Carbon::parse($date ?? now())->toDateString();

        return (int) $this->pendingStudentTasksQuery($siswa, $targetDate)->count();
    }

    private function pendingStudentTasksQuery(Siswa $siswa, string $targetDate): Builder
    {
        return Karakter::query()
            ->where('is_active', true)
            ->where(function ($query) use ($targetDate) {
                $query->whereNull('tanggal_mulai')
                    ->orWhereDate('tanggal_mulai', '<=', $targetDate);
            })
            ->where(function ($query) use ($targetDate) {
                $query->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $targetDate);
            })
            ->whereDoesntHave('checklists', function ($query) use ($siswa, $targetDate) {
                $query->where('siswa_id', $siswa->id)
                    ->whereDate('checked_at', $targetDate);
            });
    }

    public function pendingVerificationCount(User $user): int
    {
        if (! $user->hasPamongMenuAccess('tracer_karakter')) {
            return 0;
        }

        $query = SiswaKarakterChecklist::query()->whereNull('verified_at');

        if ($user->isTeacher()) {
            $assignedIds = $user->getAssignedSiswaIds();
            $query->whereIn('siswa_id', $assignedIds ?: [0]);
        }

        return (int) $query->count();
    }

    public function notifyPamongAboutSubmission(SiswaKarakterChecklist $checklist): int
    {
        $checklist->loadMissing(['siswa.alumniReviewer.role', 'karakter']);
        $siswa = $checklist->siswa;

        if (! $siswa) {
            return 0;
        }

        $recipients = User::query()
            ->where('status', 'active')
            ->whereHas('pushSubscriptions')
            ->with(['role', 'pamongPermission'])
            ->get()
            ->filter(function (User $user) use ($siswa) {
                if ($siswa->isGraduated()) {
                    if (! $user->isAdmin()) {
                        return false;
                    }

                    $reviewer = $siswa->alumniReviewer;
                    $hasAvailableReviewer = $reviewer?->isActive() && $reviewer->isAdmin();

                    return ! $hasAvailableReviewer || (int) $reviewer->id === (int) $user->id;
                }

                if (! ($user->isAdmin() || $user->usesPamongPermissionSystem())) {
                    return false;
                }

                if (! $user->hasPamongMenuAccess('tracer_karakter')) {
                    return false;
                }

                return ! $user->isTeacher() || $user->isAssignedTo($siswa);
            });

        $sent = 0;

        foreach ($recipients as $recipient) {
            $count = $this->pendingVerificationCount($recipient);
            $deliveryKey = "pkg-verification:{$checklist->id}";

            if (! $this->claimDelivery($recipient, $deliveryKey)) {
                continue;
            }

            try {
                $pamongName = trim((string) $recipient->display_name) ?: ($siswa->isGraduated() ? 'Admin' : 'Pamong');
                $studentName = trim((string) $siswa->nama) ?: 'Siswa';
                $taskName = $checklist->karakter?->nama ?? 'Tugas PKG';

                Notification::sendNow($recipient, new TaskBadgeWebPushNotification(
                    "Hai, {$pamongName}",
                    "Silakan verifikasi tugas anak Generus {$studentName}: {$taskName}.",
                    '/tugas-pkg/verifikasi?tab=verification',
                    'pkg-verification',
                    $count,
                ));
                $this->markDeliverySent($recipient, $deliveryKey);
                $sent++;
            } catch (Throwable $exception) {
                $this->releaseDelivery($recipient, $deliveryKey);
                Log::warning('Web Push verifikasi Tugas PKG gagal.', [
                    'user_id' => $recipient->id,
                    'checklist_id' => $checklist->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    public function notifyStudentsWithPendingTasks(CarbonInterface|string|null $date = null): int
    {
        $targetDate = $date instanceof CarbonInterface
            ? $date->toDateString()
            : Carbon::parse($date ?? now())->toDateString();
        $sent = 0;

        Siswa::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere(fn ($alumni) => $alumni
                        ->where('status', 'graduated')
                        ->where('alumni_can_submit', true));
            })
            ->whereHas('pushSubscriptions')
            ->with('pushSubscriptions')
            ->chunkById(100, function ($students) use ($targetDate, &$sent) {
                foreach ($students as $siswa) {
                    $deliveryKey = "student-pending-tasks:{$targetDate}";

                    if ($this->deliveryExists($siswa, $deliveryKey)) {
                        continue;
                    }

                    $pendingTasks = $this->pendingStudentTasksQuery($siswa, $targetDate)
                        ->orderBy('nama')
                        ->get(['id', 'nama']);
                    $count = $pendingTasks->count();

                    if ($count < 1 || ! $this->claimDelivery($siswa, $deliveryKey)) {
                        continue;
                    }

                    try {
                        $studentName = trim((string) $siswa->nama) ?: 'Siswa';

                        Notification::sendNow($siswa, new TaskBadgeWebPushNotification(
                            "Hai, {$studentName}",
                            $this->studentTaskReminderBody($pendingTasks),
                            '/siswa/tugas-pkg',
                            'student-pkg-tasks',
                            $count,
                        ));
                        $this->markDeliverySent($siswa, $deliveryKey);
                        $sent++;
                    } catch (Throwable $exception) {
                        $this->releaseDelivery($siswa, $deliveryKey);
                        Log::warning('Web Push pengingat Tugas PKG siswa gagal.', [
                            'siswa_id' => $siswa->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return $sent;
    }

    private function studentTaskReminderBody(Collection $pendingTasks): string
    {
        $count = $pendingTasks->count();
        $taskNames = $pendingTasks
            ->pluck('nama')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->take(2)
            ->values();

        if ($taskNames->isEmpty()) {
            return "Silakan kerjakan {$count} tugas PKG hari ini.";
        }

        if ($count === 1) {
            return "Silakan kerjakan tugas PKG hari ini: {$taskNames->first()}.";
        }

        $listedTasks = $taskNames->count() === 2
            ? $taskNames->join(' dan ')
            : (string) $taskNames->first();
        $remaining = $count - $taskNames->count();

        if ($remaining > 0) {
            $listedTasks .= ", dan {$remaining} tugas lainnya";
        }

        return "Silakan kerjakan {$count} tugas PKG hari ini: {$listedTasks}.";
    }

    private function claimDelivery(object $notifiable, string $key): bool
    {
        try {
            return PwaNotificationDelivery::query()->firstOrCreate([
                'notifiable_type' => $notifiable->getMorphClass(),
                'notifiable_id' => $notifiable->getKey(),
                'notification_key' => $key,
            ])->wasRecentlyCreated;
        } catch (QueryException) {
            return false;
        }
    }

    private function deliveryExists(object $notifiable, string $key): bool
    {
        return PwaNotificationDelivery::query()
            ->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey())
            ->where('notification_key', $key)
            ->exists();
    }

    private function markDeliverySent(object $notifiable, string $key): void
    {
        PwaNotificationDelivery::query()
            ->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey())
            ->where('notification_key', $key)
            ->update(['sent_at' => now()]);
    }

    private function releaseDelivery(object $notifiable, string $key): void
    {
        PwaNotificationDelivery::query()
            ->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey())
            ->where('notification_key', $key)
            ->delete();
    }
}
