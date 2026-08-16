<?php

namespace Tests\Feature;

use App\Models\Presensi;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnifiedAttendanceSummaryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_quick_status_and_share_all_groups_including_unassigned(): void
    {
        $admin = $this->admin();
        $present = Siswa::factory()->create(['nama' => 'Generus Hadir', 'kelompok' => Siswa::KELOMPOK_PAKULONAN]);
        $unassigned = Siswa::factory()->create(['nama' => 'Generus Tanpa Kelompok']);
        DB::table('siswa')->where('id', $unassigned->id)->update(['kelompok' => null, 'alamat' => null]);

        $this->actingAs($admin)->putJson(route('presensi.quick-status'), [
            'siswa_id' => $present->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('presensi', [
            'siswa_id' => $present->id,
            'status' => 'hadir',
        ]);

        $this->actingAs($admin)->getJson(route('presensi.share-summary', [
            'tanggal' => now()->toDateString(),
        ]))->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['student_count' => 2])
            ->assertSee('Generus Hadir')
            ->assertSee('Generus Tanpa Kelompok')
            ->assertSee('Belum Ada Data Kelompok');
    }

    public function test_quick_status_does_not_replace_scan_attendance(): void
    {
        $admin = $this->admin();
        $student = Siswa::factory()->create();
        Presensi::create([
            'siswa_id' => $student->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'qr_code_used' => true,
        ]);

        $this->actingAs($admin)->putJson(route('presensi.quick-status'), [
            'siswa_id' => $student->id,
            'tanggal' => now()->toDateString(),
            'status' => 'izin',
        ])->assertStatus(409)->assertJsonPath('success', false);

        $this->assertDatabaseHas('presensi', ['siswa_id' => $student->id, 'status' => 'hadir']);
    }

    public function test_legacy_period_recap_redirects_and_panel_loads_inside_presensi(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('presensi.recap', ['audience' => 'siswa']))
            ->assertRedirect(route('presensi.index', [
                'audience' => 'siswa',
                'tab' => 'rekap',
                'panel' => 'laporan-periode',
            ]).'#laporan-periode');

        $this->actingAs($admin)->get(route('presensi.panel.period', ['audience' => 'siswa']))
            ->assertOk()
            ->assertSee('Tanggal Mulai')
            ->assertSee('Jenis Data');
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => User::ROLE_ADMIN], [
            'display_name' => 'Administrator',
            'permissions' => ['*'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
