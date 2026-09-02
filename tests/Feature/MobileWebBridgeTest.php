<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\MobileWebBridgeController;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Jembatan token Sanctum -> sesi web sekali pakai.
 *
 * Fitur server yang hanya punya halaman web (chat, biometrik, jurnal RPP,
 * lembar Quran lanjutan) dibuka aplikasi lewat WebView; tanpa jembatan ini
 * WebView hanya melihat halaman login.
 */
class MobileWebBridgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_can_exchange_sanctum_token_for_one_time_web_session(): void
    {
        $siswa = Siswa::factory()->create();
        Sanctum::actingAs($siswa, ['siswa']);

        $issue = $this->postJson('/api/v1/mobile/web-bridge', ['target' => '/siswa/chat'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.target', '/siswa/chat')
            ->assertJsonPath('data.expires_in', MobileWebBridgeController::TTL_DETIK)
            ->assertJsonStructure(['data' => ['path', 'url', 'target', 'expires_in']]);

        $path = $issue->json('data.path');
        $this->assertMatchesRegularExpression('#^/mobile-bridge/[A-Za-z0-9]{64}$#', $path);

        // Token mentah tidak boleh tersimpan apa adanya di cache.
        $raw = basename($path);
        $this->assertNull(cache()->get('mobile-web-bridge:'.$raw));
        $this->assertIsArray(cache()->get('mobile-web-bridge:'.hash('sha256', $raw)));

        $this->get($path)->assertRedirect('/siswa/chat');
        $this->assertTrue(Auth::guard('siswa')->check());
        // Guard siswa memakai `nis` sebagai auth identifier, jadi bandingkan model.
        $this->assertSame($siswa->id, Auth::guard('siswa')->user()->id);
    }

    public function test_bridge_token_is_single_use(): void
    {
        $siswa = Siswa::factory()->create();
        Sanctum::actingAs($siswa, ['siswa']);

        $path = $this->postJson('/api/v1/mobile/web-bridge', ['target' => '/siswa/chat'])
            ->assertOk()
            ->json('data.path');

        $this->get($path)->assertRedirect('/siswa/chat');

        $this->flushSession();
        $this->get($path)->assertStatus(410);
    }

    public function test_bridge_rejects_target_outside_feature_allowlist(): void
    {
        $siswa = Siswa::factory()->create();
        Sanctum::actingAs($siswa, ['siswa']);

        // Halaman staff tidak boleh diminta oleh akun siswa.
        $this->postJson('/api/v1/mobile/web-bridge', ['target' => '/pamong-chat'])
            ->assertForbidden()
            ->assertJsonPath('code', 'TARGET_NOT_ALLOWED');

        // Path acak juga ditolak walau formatnya sah.
        $this->postJson('/api/v1/mobile/web-bridge', ['target' => '/admin/users'])
            ->assertForbidden();
    }

    public function test_bridge_rejects_absolute_url_to_block_open_redirect(): void
    {
        $siswa = Siswa::factory()->create();
        Sanctum::actingAs($siswa, ['siswa']);

        $this->postJson('/api/v1/mobile/web-bridge', ['target' => 'https://jahat.example/phish'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'TARGET_INVALID');

        $this->postJson('/api/v1/mobile/web-bridge', ['target' => '//jahat.example'])
            ->assertStatus(422);
    }

    public function test_bridge_uses_ortu_guard_for_ortu_ability_token(): void
    {
        $siswa = Siswa::factory()->create();
        Sanctum::actingAs($siswa, ['ortu']);

        $path = $this->postJson('/api/v1/mobile/web-bridge', ['target' => '/ortu/chat'])
            ->assertOk()
            ->json('data.path');

        $this->get($path)->assertRedirect('/ortu/chat');
        $this->assertTrue(Auth::guard('ortu')->check());
        $this->assertFalse(Auth::guard('siswa')->check());
    }

    public function test_bridge_uses_web_guard_for_staff_token(): void
    {
        $role = Role::query()->firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]
        );
        $admin = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);
        Sanctum::actingAs($admin);

        $path = $this->postJson('/api/v1/mobile/web-bridge', ['target' => '/pamong-chat'])
            ->assertOk()
            ->json('data.path');

        $this->get($path)->assertRedirect('/pamong-chat');
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertSame($admin->id, Auth::guard('web')->id());
    }

    public function test_bridge_requires_authentication(): void
    {
        $this->postJson('/api/v1/mobile/web-bridge', ['target' => '/siswa/chat'])
            ->assertUnauthorized();
    }

    public function test_unknown_bridge_token_is_gone(): void
    {
        $this->get('/mobile-bridge/'.str_repeat('z', 64))->assertStatus(410);
    }
}
