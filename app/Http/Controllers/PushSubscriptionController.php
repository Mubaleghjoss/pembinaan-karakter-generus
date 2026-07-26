<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use App\Services\TaskPwaNotificationService;
use App\Services\TeacherSchedulePwaNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function __construct(
        private readonly TaskPwaNotificationService $taskNotifications,
        private readonly TeacherSchedulePwaNotificationService $teacherNotifications,
    ) {}

    public function storeWeb(Request $request): JsonResponse
    {
        return $this->store($request, Auth::user());
    }

    public function destroyWeb(Request $request): JsonResponse
    {
        return $this->destroy($request, Auth::user());
    }

    public function storeSiswa(Request $request): JsonResponse
    {
        return $this->store($request, Auth::guard('siswa')->user());
    }

    public function destroySiswa(Request $request): JsonResponse
    {
        return $this->destroy($request, Auth::guard('siswa')->user());
    }

    private function store(Request $request, User|Siswa|null $notifiable): JsonResponse
    {
        abort_unless($notifiable, 401);

        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        $notifiable->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['content_encoding'] ?? 'aes128gcm',
        );

        return response()->json([
            'message' => 'Notifikasi PWA berhasil diaktifkan.',
            'badge_count' => $this->badgeCount($notifiable),
        ]);
    }

    private function destroy(Request $request, User|Siswa|null $notifiable): JsonResponse
    {
        abort_unless($notifiable, 401);

        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
        ]);

        $notifiable->deletePushSubscription($validated['endpoint']);

        return response()->json([
            'message' => 'Notifikasi PWA dinonaktifkan pada perangkat ini.',
            'badge_count' => 0,
        ]);
    }

    private function badgeCount(User|Siswa $notifiable): int
    {
        if ($notifiable instanceof Siswa) {
            return $this->taskNotifications->pendingStudentTaskCount($notifiable);
        }

        return $notifiable->teacherProfile
            ? $this->teacherNotifications->upcomingCount($notifiable)
            : $this->taskNotifications->pendingVerificationCount($notifiable);
    }
}
