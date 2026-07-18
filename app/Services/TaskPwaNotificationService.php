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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class TaskPwaNotificationService
{
    public function pendingStudentTaskCount(Siswa $siswa, CarbonInterface|string|null $date = null): int
    {
        $targetDate = $date instanceof CarbonInterface
            ? $date->toDateString()
            : Carbon::parse($date ?? now())->toDateString();

        return (int) Karakter::query()
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
            })
            ->count();
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
        $checklist->loadMissing(['siswa', 'karakter']);
        $siswa = $checklist->siswa;

        if (! $siswa) {
            return 0;
        }

        $recipients = User::query()
            ->whereHas('pushSubscriptions')
            ->with(['role', 'pamongPermission'])
            ->get()
            ->filter(function (User $user) use ($siswa) {
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
                Notification::sendNow($recipient, new TaskBadgeWebPushNotification(
                    'Tugas PKG menunggu verifikasi',
                    $siswa->nama.' mengirim tugas '.($checklist->karakter?->nama ?? 'PKG').'.',
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
            ->active()
            ->whereHas('pushSubscriptions')
            ->with('pushSubscriptions')
            ->chunkById(100, function ($students) use ($targetDate, &$sent) {
                foreach ($students as $siswa) {
                    $deliveryKey = "student-pending-tasks:{$targetDate}";

                    if ($this->deliveryExists($siswa, $deliveryKey)) {
                        continue;
                    }

                    $count = $this->pendingStudentTaskCount($siswa, $targetDate);

                    if ($count < 1 || ! $this->claimDelivery($siswa, $deliveryKey)) {
                        continue;
                    }

                    try {
                        Notification::sendNow($siswa, new TaskBadgeWebPushNotification(
                            'Tugas PKG hari ini',
                            "Masih ada {$count} tugas aktif yang perlu dikerjakan.",
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
