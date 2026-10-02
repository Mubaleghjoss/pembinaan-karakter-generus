<?php

namespace App\Services;

use App\Models\MobileDeviceToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Typed FCM delivery for authenticated mobile owners.
 *
 * This service deliberately keeps recipient resolution outside the payload:
 * callers pass an already-authorized owner model, while the device token table
 * remains the only source of delivery targets.
 */
class MobileFcmNotificationService
{
    public const TYPES = [
        'chat',
        'task',
        'attendance',
        'calendar',
        'quran',
        'gamification',
        'announcement',
    ];

    private const ROUTES = [
        'chat' => ['/chat'],
        'task' => ['/tugas'],
        'attendance' => ['/presensi'],
        'calendar' => ['/kalender'],
        'quran' => ['/quran'],
        'gamification' => ['/poin', '/badge'],
        'announcement' => ['/'],
    ];

    public function __construct(private readonly FcmService $fcm) {}

    public function sendToOwner(
        Model $owner,
        string $type,
        string $route,
        string $title,
        string $body,
        int|string $entityId,
        string $notificationId,
        array $extra = [],
    ): int {
        if (! config('fcm.enabled') || ! in_array($type, self::TYPES, true)) {
            return 0;
        }
        if (! in_array($route, self::ROUTES[$type] ?? [], true)) {
            throw new \InvalidArgumentException('Route FCM tidak sesuai dengan tipe notifikasi.');
        }

        $data = array_merge($extra, [
            'type' => $type,
            'route' => $route,
            'entity_id' => (string) $entityId,
            'notification_id' => $notificationId,
        ]);

        $sent = 0;
        $ownerTypes = array_values(array_unique([
            $owner->getMorphClass(),
            $owner::class,
        ]));

        $devices = MobileDeviceToken::query()
            ->whereIn('owner_type', $ownerTypes)
            ->where('owner_id', $owner->getKey())
            ->whereNull('revoked_at')
            ->get();

        foreach ($devices as $device) {
            try {
                if ($this->fcm->send($device->token, $title, $body, $data)) {
                    $sent++;
                } else {
                    $device->forceFill(['revoked_at' => now()])->save();
                }
            } catch (Throwable $exception) {
                Log::warning('Mobile FCM delivery failed.', [
                    'device_id' => $device->id,
                    'owner_type' => $owner->getMorphClass(),
                    'owner_id' => $owner->getKey(),
                    'type' => $type,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $sent;
    }
}
