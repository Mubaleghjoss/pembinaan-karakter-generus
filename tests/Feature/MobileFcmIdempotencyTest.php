<?php

namespace Tests\Feature;

use App\Models\MobileDeviceToken;
use App\Models\Siswa;
use App\Services\FcmService;
use App\Services\MobileFcmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MobileFcmIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeating_the_same_owner_notification_does_not_send_twice(): void
    {
        config()->set('fcm.enabled', true);
        $siswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        MobileDeviceToken::create([
            'owner_type' => $siswa->getMorphClass(),
            'owner_id' => $siswa->id,
            'token_hash' => hash('sha256', 'idempotent-token'),
            'token' => 'idempotent-token',
            'platform' => 'android',
        ]);

        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldReceive('send')->once()->andReturnTrue();
        $service = new MobileFcmNotificationService($fcm);

        $service->sendToOwner($siswa, 'attendance', '/presensi', 'Presensi', 'Hadir', 77, 'attendance-77-v1');
        $service->sendToOwner($siswa, 'attendance', '/presensi', 'Presensi', 'Hadir', 77, 'attendance-77-v1');
    }

    public function test_stale_claim_can_be_retried(): void
    {
        config()->set('fcm.enabled', true);
        $siswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        $device = MobileDeviceToken::create([
            'owner_type' => $siswa->getMorphClass(),
            'owner_id' => $siswa->id,
            'token_hash' => hash('sha256', 'stale-token'),
            'token' => 'stale-token',
            'platform' => 'android',
        ]);
        \App\Models\MobileNotificationDelivery::create([
            'notification_id' => 'attendance-79-v1',
            'device_id' => $device->id,
            'claimed_at' => now()->subMinutes(10),
        ]);

        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldReceive('send')->once()->andReturnTrue();
        $service = new MobileFcmNotificationService($fcm);

        $service->sendToOwner($siswa, 'attendance', '/presensi', 'Presensi', 'Hadir', 79, 'attendance-79-v1');
    }

    public function test_failed_provider_attempt_can_be_retried(): void
    {
        config()->set('fcm.enabled', true);
        $siswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        MobileDeviceToken::create([
            'owner_type' => $siswa->getMorphClass(),
            'owner_id' => $siswa->id,
            'token_hash' => hash('sha256', 'retry-token'),
            'token' => 'retry-token',
            'platform' => 'android',
        ]);

        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldReceive('send')->twice()->andThrow(new \RuntimeException('temporary'));
        $service = new MobileFcmNotificationService($fcm);

        $service->sendToOwner($siswa, 'attendance', '/presensi', 'Presensi', 'Hadir', 78, 'attendance-78-v1');
        $service->sendToOwner($siswa, 'attendance', '/presensi', 'Presensi', 'Hadir', 78, 'attendance-78-v1');
    }
}
