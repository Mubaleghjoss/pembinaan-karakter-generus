<?php

namespace Tests\Feature;

use App\Models\Siswa;
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
}
