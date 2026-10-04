<?php

namespace Tests\Feature;

use App\Models\MobileDeviceToken;
use App\Models\Siswa;
use App\Services\MobileFcmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MobileDeviceActorRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_device_registration_persists_ortu_actor_role(): void
    {
        $siswa = Siswa::factory()->create();
        $token = $siswa->createToken('ortu-mobile', ['ortu'])->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/mobile/device-token', [
            'token' => str_repeat('f', 32),
            'platform' => 'android',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('mobile_device_tokens', [
            'owner_type' => Siswa::class,
            'owner_id' => $siswa->getKey(),
            'actor_role' => 'ortu',
        ]);
    }

    public function test_parent_delivery_can_be_scoped_to_ortu_devices(): void
    {
        Config::set('fcm.enabled', true);
        $siswa = Siswa::factory()->create();
        MobileDeviceToken::create([
            'owner_type' => Siswa::class,
            'owner_id' => $siswa->getKey(),
            'actor_role' => 'siswa',
            'token_hash' => hash('sha256', 'student-device-token'),
            'token' => 'student-device-token',
            'platform' => 'android',
        ]);
        MobileDeviceToken::create([
            'owner_type' => Siswa::class,
            'owner_id' => $siswa->getKey(),
            'actor_role' => 'ortu',
            'token_hash' => hash('sha256', 'parent-device-token'),
            'token' => 'parent-device-token',
            'platform' => 'android',
        ]);

        $service = app(MobileFcmNotificationService::class);
        $service->sendToOwner(
            $siswa,
            'task',
            '/tugas',
            'Tugas baru',
            'Tugas untuk anak Anda.',
            7,
            'task-parent-7',
            [],
            'ortu',
        );

        $this->assertDatabaseCount('mobile_notification_deliveries', 1);
        $this->assertDatabaseHas('mobile_notification_deliveries', [
            'notification_id' => 'task-parent-7',
        ]);
    }
}
