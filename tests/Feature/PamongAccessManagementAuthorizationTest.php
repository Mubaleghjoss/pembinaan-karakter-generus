<?php

namespace Tests\Feature;

use App\Models\PamongPermission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PamongAccessManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_staff_cannot_open_global_team_permission_management(): void
    {
        $staff = $this->operationalStaff();

        $this->actingAs($staff)
            ->get(route('pamong.permissions.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Hanya admin yang dapat mengakses fitur ini.');
    }

    public function test_operational_staff_cannot_change_another_team_members_permissions(): void
    {
        $staff = $this->operationalStaff();
        $target = $this->operationalStaff();

        $this->actingAs($staff)
            ->post(route('pamong.permissions.update', $target), [
                'menu_permissions' => ['dashboard', 'materi'],
                'crud_permissions' => ['materi' => ['view']],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('pamong_permissions', [
            'user_id' => $target->id,
        ]);
    }

    public function test_admin_can_turn_off_bypass_and_restore_checked_permissions(): void
    {
        $admin = $this->adminUser();
        $target = $this->operationalStaff();

        PamongPermission::query()->create([
            'user_id' => $target->id,
            'is_excluded' => true,
            'menu_permissions' => ['dashboard'],
            'crud_permissions' => [],
        ]);

        $this->actingAs($admin)
            ->post(route('pamong.permissions.update', $target), [
                'menu_permissions' => ['dashboard', 'materi'],
                'crud_permissions' => ['materi' => ['view']],
            ])
            ->assertRedirect(route('pamong.permissions', $target));

        $this->assertDatabaseHas('pamong_permissions', [
            'user_id' => $target->id,
            'is_excluded' => false,
        ]);

        $permission = $target->fresh(['pamongPermission'])->pamongPermission;
        $this->assertSame(['dashboard', 'materi'], $permission->menu_permissions);
        $this->assertSame(['view'], $permission->crud_permissions['materi']);
    }

    private function operationalStaff(): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => User::ROLE_TEACHER],
            ['display_name' => 'Pamong', 'permissions' => [], 'is_active' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function adminUser(): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Admin', 'permissions' => [], 'is_active' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }
}
