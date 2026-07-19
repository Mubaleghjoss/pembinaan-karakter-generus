<?php

namespace Tests\Feature;

use App\Models\GenerusRegistrationInvite;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerusRegistrationSettingsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_registration_access_settings(): void
    {
        $invite = $this->invite('B9UY7BLS');

        $this->actingAs($this->admin())
            ->get(route('settings.index', ['tab' => 'registration']))
            ->assertOk()
            ->assertSee('Akses Pendaftaran Generus PKG')
            ->assertSee((string) $invite->max_uses)
            ->assertDontSee('B9UY7BLS');
    }

    public function test_admin_can_change_access_code_without_resetting_usage(): void
    {
        $invite = $this->invite('B9UY7BLS', ['used_count' => 3]);

        $this->actingAs($this->admin())
            ->put(route('settings.update.registration-access'), [
                'label' => 'Pendaftaran Generus PKG',
                'access_code' => 'PKGBARU9',
                'valid_days' => 90,
                'max_uses' => 75,
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.index', ['tab' => 'registration']))
            ->assertSessionHas('registration_access_code', 'PKGBARU9');

        $invite->refresh();
        $this->assertSame(hash('sha256', 'PKGBARU9'), $invite->token_hash);
        $this->assertSame(3, $invite->used_count);
        $this->assertSame(75, $invite->max_uses);
        $this->assertTrue($invite->is_active);
        $this->assertTrue($invite->expires_at->between(now()->addDays(89), now()->addDays(91)));

        $this->post(route('public.generus-registration.short.unlock'), ['access_code' => 'B9UY7BLS'])
            ->assertSessionHasErrors('access_code');
        $this->post(route('public.generus-registration.short.unlock'), ['access_code' => 'pkgbaru9'])
            ->assertRedirect(route('public.generus-registration.short.index'));
    }

    public function test_admin_can_update_limits_without_changing_code(): void
    {
        $invite = $this->invite('B9UY7BLS', ['used_count' => 4]);
        $originalHash = $invite->token_hash;

        $this->actingAs($this->admin())
            ->put(route('settings.update.registration-access'), [
                'label' => 'Pendaftaran Terbatas',
                'access_code' => '',
                'valid_days' => 180,
                'max_uses' => 50,
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $invite->refresh();
        $this->assertSame($originalHash, $invite->token_hash);
        $this->assertSame(4, $invite->used_count);
        $this->assertSame('Pendaftaran Terbatas', $invite->label);
    }

    public function test_maximum_uses_cannot_be_lower_than_current_usage(): void
    {
        $invite = $this->invite('B9UY7BLS', ['used_count' => 6]);

        $this->actingAs($this->admin())
            ->from(route('settings.index', ['tab' => 'registration']))
            ->put(route('settings.update.registration-access'), [
                'label' => $invite->label,
                'valid_days' => 180,
                'max_uses' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.index', ['tab' => 'registration']))
            ->assertSessionHasErrors('max_uses');
    }

    private function invite(string $code, array $overrides = []): GenerusRegistrationInvite
    {
        return GenerusRegistrationInvite::query()->create(array_merge([
            'label' => 'Pendaftaran Generus PKG',
            'token_hash' => hash('sha256', $code),
            'max_uses' => 50,
            'used_count' => 0,
            'expires_at' => now()->addDays(180),
            'is_active' => true,
        ], $overrides));
    }

    private function admin(): User
    {
        $role = Role::query()->updateOrCreate(
            ['name' => User::ROLE_ADMIN],
            [
                'display_name' => 'Administrator',
                'permissions' => ['*'],
                'is_active' => true,
            ]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }
}
