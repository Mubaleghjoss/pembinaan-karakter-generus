<?php

namespace App\Services;

use App\Models\PwaNotificationDelivery;
use App\Models\TeacherProfile;
use App\Models\TeacherScheduleAssignment;
use App\Models\TeacherSchedulePeriod;
use App\Models\User;
use App\Notifications\TaskBadgeWebPushNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class TeacherSchedulePwaNotificationService
{
    public function upcomingCount(User $user): int
    {
        $profileId = $user->teacherProfile?->id;
        if (! $profileId) {
            return 0;
        }

        return TeacherScheduleAssignment::query()
            ->where('teacher_profile_id', $profileId)
            ->whereHas('session', fn ($query) => $query->whereDate('session_date', '>=', today()))
            ->whereHas('session.period', fn ($query) => $query->where('status', 'published'))
            ->count();
    }

    public function notifyPublished(TeacherSchedulePeriod $period): int
    {
        $period->load(['sessions.materials', 'sessions.assignments.teacher.user.pushSubscriptions']);
        $sent = 0;

        foreach ($period->sessions->flatMap->assignments as $assignment) {
            $assignment->setRelation('session', $period->sessions->firstWhere('id', $assignment->session_id));
            $fingerprint = $this->assignmentFingerprint($assignment);
            $sent += $this->send(
                $assignment,
                "teacher-schedule-published:{$assignment->id}:{$fingerprint}",
                'Jadwal mengajar telah diterbitkan',
                'published'
            );
        }

        return $sent;
    }

    public function notifyDue(CarbonInterface|string|null $date = null): int
    {
        $today = $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse($date ?? today());
        $sent = 0;

        foreach (['h3' => 3, 'h1' => 1] as $stage => $days) {
            $assignments = TeacherScheduleAssignment::query()
                ->whereHas('session', fn ($query) => $query->whereDate('session_date', $today->copy()->addDays($days)))
                ->whereHas('session.period', fn ($query) => $query->where('status', 'published'))
                ->with(['session.materials', 'session.period', 'teacher.user.pushSubscriptions'])
                ->get();

            foreach ($assignments as $assignment) {
                $sent += $this->send(
                    $assignment,
                    "teacher-schedule-{$stage}:{$assignment->id}:{$assignment->session->session_date->toDateString()}",
                    $stage === 'h3' ? 'Konfirmasi jadwal H-3' : 'Pengingat jadwal besok',
                    $stage
                );
            }
        }

        return $sent;
    }

    private function send(
        TeacherScheduleAssignment $assignment,
        string $deliveryKey,
        string $heading,
        string $stage
    ): int {
        $assignment->loadMissing(['session.materials', 'teacher.user.pushSubscriptions']);
        $user = $assignment->teacher?->user;
        if (! $user || $user->pushSubscriptions->isEmpty() || ! $this->claimDelivery($user, $deliveryKey)) {
            return 0;
        }

        try {
            $session = $assignment->session;
            $name = trim((string) $user->name) ?: trim((string) $assignment->teacher?->name) ?: 'Guru';
            $role = $assignment->role === 'main' ? 'utama' : 'cadangan';
            $rombel = strtoupper((string) $session->rombel);
            $date = $session->session_date->translatedFormat('l, d F Y');
            $time = substr((string) $session->start_time, 0, 5).'-'.substr((string) $session->end_time, 0, 5).' WIB';
            $material = $session->materials->where('is_active', true)->isNotEmpty()
                ? 'Materi sudah tersedia.'
                : 'Materi belum tersedia.';

            Notification::sendNow($user, new TaskBadgeWebPushNotification(
                "Hai, {$name} - {$heading}",
                "Anda bertugas sebagai pengajar {$role} rombel {$rombel}, {$date}, {$time}. {$material}",
                route('guru.schedule.show', $assignment, false),
                "teacher-schedule-{$stage}-{$assignment->id}",
                $this->upcomingCount($user),
            ));
            $this->markDeliverySent($user, $deliveryKey);

            return 1;
        } catch (Throwable $exception) {
            $this->releaseDelivery($user, $deliveryKey);
            Log::warning('Web Push jadwal Guru gagal.', [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'stage' => $stage,
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    private function assignmentFingerprint(TeacherScheduleAssignment $assignment): string
    {
        $session = $assignment->session;
        $payload = [
            $assignment->teacher_profile_id,
            $assignment->role,
            $session?->session_date?->toDateString(),
            $session?->start_time,
            $session?->end_time,
            $session?->rombel,
            $session?->location,
            $session?->materials?->pluck('id')->sort()->values()->all(),
        ];

        return substr(hash('sha256', json_encode($payload)), 0, 20);
    }

    private function claimDelivery(User $user, string $key): bool
    {
        try {
            return PwaNotificationDelivery::query()->firstOrCreate([
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->getKey(),
                'notification_key' => $key,
            ])->wasRecentlyCreated;
        } catch (QueryException) {
            return false;
        }
    }

    private function markDeliverySent(User $user, string $key): void
    {
        PwaNotificationDelivery::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('notification_key', $key)
            ->update(['sent_at' => now()]);
    }

    private function releaseDelivery(User $user, string $key): void
    {
        PwaNotificationDelivery::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('notification_key', $key)
            ->delete();
    }
}
