<?php

namespace Tests\Feature;

use App\Models\Presensi;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiCorrectionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_presensi_sections_are_collapsed_by_default_and_edit_modal_is_available(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('presensi.index', ['tab' => 'rekap']))
            ->assertOk()
            ->assertSee('data-collapsible-section', false)
            ->assertSee('Koreksi Presensi')
            ->assertSee('presensiUpdate', false);

        $component = file_get_contents(resource_path('views/components/collapsible-section.blade.php'));
        $this->assertStringContainsString("'open' => false", $component);
    }

    public function test_admin_can_correct_manual_attendance_status(): void
    {
        $admin = $this->admin();
        $siswa = Siswa::factory()->create();
        $presensi = Presensi::query()->create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-19',
            'status' => 'hadir',
            'jam_masuk' => '2026-07-19 07:00:00',
            'is_verified' => true,
        ]);

        $this->actingAs($admin)
            ->putJson(route('presensi.update', $presensi), [
                'tanggal' => '2026-07-19',
                'status' => 'izin',
                'jam_masuk' => null,
                'jam_keluar' => null,
                'keterangan' => 'Koreksi input manual',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'izin');

        $presensi->refresh();
        $this->assertSame('izin', $presensi->status);
        $this->assertSame('Koreksi input manual', $presensi->keterangan);
    }

    private function admin(): User
    {
        $role = Role::query()->create([
            'name' => User::ROLE_ADMIN,
            'display_name' => 'Administrator',
            'permissions' => ['*'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
