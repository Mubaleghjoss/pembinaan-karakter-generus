<?php

namespace App\Services;

use App\Models\MobileDeviceToken;
use App\Models\MobileNotificationDelivery;
use App\Models\Siswa;
use App\Models\User;
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

    public function sendToActiveMobileUsers(
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

        MobileDeviceToken::query()
            ->whereNull('revoked_at')
            ->whereHasMorph('owner', [Siswa::class, User::class], function ($query, string $ownerType): void {
                if ($ownerType === Siswa::class) {
                    $query->where('status', 'active')->where('is_active', true);
                } else {
                    $query->where('status', 'active');
                }
            })
            ->chunkById(100, function ($devices) use ($title, $body, $data, $type, $notificationId, &$sent): void {
                foreach ($devices as $device) {
                    try {
                        if ($this->deliverOnce($device, $notificationId, $title, $body, $data)) {
                            $sent++;
                        }
                    } catch (Throwable $exception) {
                        Log::warning('Mobile FCM broadcast failed.', [
                            'device_id' => $device->id,
                            'type' => $type,
                            'exception' => $exception::class,
                        ]);
                    }
                }
            });

        return $sent;
    }

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
                if ($this->deliverOnce($device, $notificationId, $title, $body, $data)) {
                    $sent++;
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

    private function deliverOnce(MobileDeviceToken $device, string $notificationId, string $title, string $body, array $data): bool
    {
        $delivery = MobileNotificationDelivery::firstOrCreate([
            'notification_id' => $notificationId,
            'device_id' => $device->getKey(),
        ]);

        if ($delivery->sent_at !== null) {
            return false;
        }

        $claimExpiredAt = now()->subMinutes(5);
        $claimed = MobileNotificationDelivery::query()
            ->whereKey($delivery->getKey())
            ->whereNull('sent_at')
            ->where(function ($query) use ($claimExpiredAt): void {
                $query->whereNull('claimed_at')->orWhere('claimed_at', '<=', $claimExpiredAt);
            })
            ->update(['claimed_at' => now(), 'updated_at' => now()]);

        if ($claimed !== 1) {
            return false;
        }

        try {
            $delivered = $this->fcm->send($device->token, $title, $body, $data);
        } catch (Throwable $exception) {
            $delivery->forceFill(['claimed_at' => null])->save();
            throw $exception;
        }

        if (! $delivered) {
            $device->forceFill(['revoked_at' => now()])->save();
            $delivery->delete();
            return false;
        }

        $delivery->forceFill(['sent_at' => now()])->save();
        return true;
    }
}
