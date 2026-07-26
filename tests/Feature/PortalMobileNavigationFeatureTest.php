<?php

namespace Tests\Feature;

use App\Models\PamongPermission;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalMobileNavigationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_dashboard_renders_complete_mobile_navigation(): void
    {
        $siswa = Siswa::factory()->create([
            'nama' => 'Siswa Navigasi',
            'nis' => 'PKG-MOBILE-001',
        ]);

        $response = $this->actingAs($siswa, 'siswa')->get(route('siswa.dashboard'));

        $response->assertOk()
            ->assertSee('Navigasi Portal Siswa')
            ->assertSee('Menu lainnya')
            ->assertSee('Jurnal RPP')
            ->assertSee('Kehadiran')
            ->assertSee('Gamifikasi')
            ->assertSee('RPG Quest')
            ->assertSee('Profil dan Foto')
            ->assertSee('Kartu Siswa')
            ->assertSee(route('siswa.calendar.index'), false)
            ->assertSee(route('siswa.tugas-pkg.index'), false)
            ->assertSee(route('siswa.materi.index'), false)
            ->assertSee(route('siswa.chat.index'), false);
    }

    public function test_ortu_dashboard_renders_complete_mobile_navigation(): void
    {
        $siswa = Siswa::factory()->create([
            'nama' => 'Generus Orang Tua',
            'nis' => 'PKG-MOBILE-ORTU',
        ]);

        $response = $this->actingAs($siswa, 'ortu')->get(route('ortu.dashboard'));

        $response->assertOk()
            ->assertSee('Navigasi Portal Orang Tua')
            ->assertSee('Menu lainnya')
            ->assertSee('Kehadiran PKG')
            ->assertSee('Pengaturan')
            ->assertSee('Biometrik')
            ->assertSee(route('ortu.jadwal'), false)
            ->assertSee(route('ortu.tugas'), false)
            ->assertSee(route('ortu.materi.index'), false)
            ->assertSee(route('ortu.chat'), false);
    }

    public function test_admin_dashboard_renders_mobile_shell_and_editable_favorites(): void
    {
        $admin = $this->operationalUser(User::ROLE_ADMIN, 'Administrator');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Navigasi Portal Admin')
            ->assertSee('Favorit Admin')
            ->assertSee('Atur Favorit')
            ->assertSee(route('profile.mobile-menu-favorites.update'), false)
            ->assertSee(route('tugas-pkg.verification'), false);
    }

    public function test_pamong_dashboard_renders_only_permitted_mobile_menu(): void
    {
        $pamong = $this->operationalUser(User::ROLE_TEACHER, 'Pamong');
        PamongPermission::query()->create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['dashboard', 'materi'],
            'crud_permissions' => ['materi' => ['view']],
        ]);

        $this->actingAs($pamong)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Navigasi Portal Pamong')
            ->assertSee(route('materi.index'), false)
            ->assertDontSee('Favorit Admin')
            ->assertDontSee(route('settings.index'), false)
            ->assertDontSee(route('tugas-pkg.verification'), false);
    }

    private function operationalUser(string $roleName, string $displayName): User
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
            'name' => $displayName.' Mobile',
        ]);
    }
}
