<?php

namespace Tests\Feature;

use App\Models\MobileDeviceToken;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileDeviceTokenFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_siswa_can_register_and_revoke_own_device_token(): void
    {
        $siswa = Siswa::factory()->create();
        $token = $siswa->createToken('test', ['siswa'])->plainTextToken;
        $deviceToken = str_repeat('fcm-token-', 5);

        $this->withToken($token)->postJson('/api/v1/mobile/device-token', [
            'token' => $deviceToken,
            'platform' => 'android',
            'app_version' => '1.5.3+20',
        ])->assertOk()
            ->assertJsonPath('data.registered', true);

        $this->assertDatabaseHas('mobile_device_tokens', [
            'owner_type' => Siswa::class,
            'owner_id' => $siswa->id,
            'token_hash' => hash('sha256', $deviceToken),
        ]);

        $this->withToken($token)->deleteJson('/api/v1/mobile/device-token', [
            'token' => $deviceToken,
        ])->assertOk();

        $this->assertNotNull(
            MobileDeviceToken::query()->where('token_hash', hash('sha256', $deviceToken))->first()?->revoked_at,
        );
    }

    public function test_device_token_cannot_be_registered_without_authentication(): void
    {
        $this->postJson('/api/v1/mobile/device-token', [
            'token' => str_repeat('fcm-token-', 5),
            'platform' => 'android',
        ])->assertUnauthorized();
    }

    public function test_invalid_platform_is_rejected(): void
    {
        $siswa = Siswa::factory()->create();
        $token = $siswa->createToken('test', ['siswa'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/mobile/device-token', [
            'token' => str_repeat('fcm-token-', 5),
            'platform' => 'windows',
        ])->assertUnprocessable();
    }
}
