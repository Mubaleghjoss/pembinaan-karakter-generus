<?php

namespace Tests\Feature;

use App\Models\PamongPermission;
use App\Models\Role;
use App\Models\User;
use App\Support\OperationalMobileNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OperationalMobileNavigationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_receives_fixed_bottom_navigation_and_default_favorites(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN, 'Administrator');
        $navigation = app(OperationalMobileNavigation::class)->build(
            $admin,
            $this->routeRequest('dashboard')
        );

        $this->assertNotNull($navigation);
        $this->assertSame('Portal Admin', $navigation['portal_label']);
        $this->assertSame(
            ['Beranda', 'Data', 'Presensi', 'Tugas', 'Konten'],
            array_column($navigation['bottom_items'], 'label')
        );
        $this->assertSame(
            OperationalMobileNavigation::DEFAULT_ADMIN_FAVORITES,
            $navigation['favorites']['selected_keys']
        );
        $this->assertContains('Pendataan & Jadwal Guru', array_column($navigation['favorites']['selected_items'], 'label'));
    }

    public function test_pamong_bottom_navigation_uses_only_allowed_items_and_replaces_missing_slots(): void
    {
        $pamong = $this->userWithRole(User::ROLE_TEACHER, 'Pamong');
        PamongPermission::query()->create([
            'user_id' => $pamong->id,
            'menu_permissions' => [
                'dashboard',
                'calendar',
                'manual_attendance',
                'laporan_penyaksian',
                'materi',
            ],
            'crud_permissions' => [],
        ]);

        $navigation = app(OperationalMobileNavigation::class)->build(
            $pamong->fresh('role'),
            $this->routeRequest('dashboard')
        );

        $labels = array_column($navigation['bottom_items'], 'label');
        $allSheetLabels = collect($navigation['sheet_sections'])->pluck('items')->flatten(1)->pluck('label');

        $this->assertSame('Portal Pamong', $navigation['portal_label']);
        $this->assertSame(['Beranda', 'Kalender', 'Input Manual', 'Info Lapor PKG', 'Materi'], $labels);
        $this->assertTrue($allSheetLabels->contains('Materi'));
        $this->assertFalse($allSheetLabels->contains('Siswa'));
        $this->assertFalse($allSheetLabels->contains('Chat'));
        $this->assertFalse($allSheetLabels->contains('Pengaturan'));
        $this->assertNull($navigation['favorites']);
    }

    public function test_pamong_with_very_limited_permission_has_no_locked_or_unauthorized_bottom_item(): void
    {
        $pamong = $this->userWithRole(User::ROLE_TEACHER, 'Pamong');
        PamongPermission::query()->create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['dashboard', 'materi'],
            'crud_permissions' => [],
        ]);

        $navigation = app(OperationalMobileNavigation::class)->build(
            $pamong->fresh('role'),
            $this->routeRequest('dashboard')
        );

        $this->assertSame(['Beranda', 'Materi'], array_column($navigation['bottom_items'], 'label'));
    }

    public function test_jurnal_rpp_only_appears_when_its_menu_permission_is_selected(): void
    {
        $pamong = $this->userWithRole(User::ROLE_TEACHER, 'Pamong');
        $permission = PamongPermission::query()->create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['dashboard'],
            'crud_permissions' => [],
        ]);

        $navigation = app(OperationalMobileNavigation::class)->build(
            $pamong->fresh('role'),
            $this->routeRequest('dashboard')
        );
        $labels = collect($navigation['sheet_sections'])->pluck('items')->flatten(1)->pluck('label');
        $this->assertFalse($labels->contains('Jurnal RPP'));

        $permission->update([
            'menu_permissions' => ['dashboard', 'rpp_journals'],
            'crud_permissions' => ['rpp_journals' => ['view']],
        ]);

        $navigation = app(OperationalMobileNavigation::class)->build(
            $pamong->fresh(['role', 'pamongPermission']),
            $this->routeRequest('dashboard')
        );
        $labels = collect($navigation['sheet_sections'])->pluck('items')->flatten(1)->pluck('label');
        $this->assertTrue($labels->contains('Jurnal RPP'));
    }

    public function test_guru_does_not_receive_operational_navigation(): void
    {
        $guru = $this->userWithRole(User::ROLE_GURU, 'Guru');

        $this->assertNull(app(OperationalMobileNavigation::class)->build(
            $guru,
            $this->routeRequest('dashboard')
        ));
    }

    public function test_admin_can_save_order_and_reset_favorites_but_pamong_cannot(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN, 'Administrator');
        $pamong = $this->userWithRole(User::ROLE_TEACHER, 'Pamong');

        $this->actingAs($admin)
            ->from(route('dashboard'))
            ->put(route('profile.mobile-menu-favorites.update'), [
                'favorites_present' => '1',
                'favorites' => ['materials', 'dashboard', 'task_verification'],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertSame(
            ['materials', 'dashboard', 'task_verification'],
            $admin->fresh()->mobile_menu_favorites
        );

        $this->actingAs($admin)
            ->from(route('dashboard'))
            ->put(route('profile.mobile-menu-favorites.update'), [
                'favorites_present' => '1',
                'favorites' => [
                    'dashboard',
                    'students',
                    'attendance',
                    'task_verification',
                    'materials',
                    'teacher_planning',
                    'settings',
                ],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('favorites');

        $this->actingAs($pamong)
            ->put(route('profile.mobile-menu-favorites.update'), [
                'favorites_present' => '1',
                'favorites' => ['dashboard'],
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->from(route('dashboard'))
            ->put(route('profile.mobile-menu-favorites.update'), ['reset' => '1'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertNull($admin->fresh()->mobile_menu_favorites);
    }

    private function userWithRole(string $roleName, string $displayName): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            [
                'display_name' => $displayName,
                'permissions' => $roleName === User::ROLE_ADMIN ? ['*'] : [],
                'is_active' => true,
            ]
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'name' => $displayName.' Test',
        ])->load('role');
    }

    private function routeRequest(string $routeName): Request
    {
        $request = Request::create(route($routeName), 'GET');
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(static fn () => $route);

        return $request;
    }
}
