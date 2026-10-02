<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MobileFcmNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Local-only FCM smoke-test endpoint. It must never be enabled in production.
 */
class MobileNotificationTestController extends Controller
{
    public function __construct(private readonly MobileFcmNotificationService $mobileFcm) {}

    public function send(Request $request): JsonResponse
    {
        if (! (bool) config('app.debug') || ! in_array(config('app.env'), ['local', 'testing'], true)) {
            throw new AccessDeniedHttpException('Endpoint pengujian lokal dinonaktifkan.');
        }

        $type = (string) $request->validate([
            'type' => ['required', 'in:chat,task,attendance,calendar,quran,gamification,announcement'],
        ])['type'];

        $catalog = [
            'chat' => [
                'route' => '/chat',
                'title' => 'Pesan baru',
                'body' => 'Ada pesan chat baru. Ketuk untuk membuka chat.',
            ],
            'task' => [
                'route' => '/tugas',
                'title' => 'Tugas PKG baru',
                'body' => 'Tugas PKG baru tersedia. Ketuk untuk membuka tugas.',
            ],
            'attendance' => [
                'route' => '/presensi',
                'title' => 'Presensi diperbarui',
                'body' => 'Status presensi hari ini diperbarui. Ketuk untuk melihat presensi.',
            ],
            'calendar' => [
                'route' => '/kalender',
                'title' => 'Agenda baru',
                'body' => 'Ada agenda baru pada kalender. Ketuk untuk melihat kalender.',
            ],
            'quran' => [
                'route' => '/quran',
                'title' => 'Bacaan Quran diperbarui',
                'body' => 'Target atau status bacaan Quran diperbarui. Ketuk untuk melihat Quran.',
            ],
            'gamification' => [
                'route' => '/poin',
                'title' => 'Pencapaian baru',
                'body' => 'Poin atau pencapaian Gamifikasi kamu bertambah. Ketuk untuk melihat poin.',
            ],
            'announcement' => [
                'route' => '/',
                'title' => 'Informasi PKGenerus',
                'body' => 'Ada informasi baru dari PKGenerus. Ketuk untuk membuka aplikasi.',
            ],
        ][$type];

        $actor = $request->user();
        $sent = $this->mobileFcm->sendToOwner(
            $actor,
            $type,
            $catalog['route'],
            $catalog['title'],
            $catalog['body'],
            1,
            'local-test-'.$type.'-'.now()->format('YmdHisv'),
        );

        return response()->json([
            'success' => true,
            'type' => $type,
            'sent' => $sent,
            'message' => 'Notifikasi pengujian lokal diproses.',
        ]);
    }
}
