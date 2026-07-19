<?php

namespace Tests\Feature;

use App\Models\LaporanPenyaksian;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanPenyaksianDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_delete_form_uses_native_submission_and_returns_to_the_list(): void
    {
        $admin = $this->adminUser();
        $laporan = $this->laporan();

        $this->actingAs($admin)
            ->get(route('laporan-penyaksian.show', $laporan))
            ->assertOk()
            ->assertSee('data-no-csrf-handler', false)
            ->assertSee(route('laporan-penyaksian.destroy', $laporan), false);

        $this->delete(route('laporan-penyaksian.destroy', $laporan))
            ->assertRedirect(route('laporan-penyaksian.index'))
            ->assertSessionHas('success', 'Laporan berhasil dihapus.');

        $this->assertDatabaseMissing('laporan_penyaksian', ['id' => $laporan->id]);
    }

    public function test_non_admin_cannot_delete_a_report(): void
    {
        $user = User::factory()->create();
        $laporan = $this->laporan();

        $this->actingAs($user)
            ->delete(route('laporan-penyaksian.destroy', $laporan))
            ->assertForbidden();

        $this->assertDatabaseHas('laporan_penyaksian', ['id' => $laporan->id]);
    }

    private function laporan(): LaporanPenyaksian
    {
        return LaporanPenyaksian::create([
            'nama_pelapor' => 'Pelapor Uji',
            'nama_generus' => 'Generus Uji',
            'karakter_belum_optimal' => 'Kedisiplinan',
            'tanggal_kejadian' => '2026-07-20',
            'status' => 'pending',
        ]);
    }

    private function adminUser(): User
    {
        $role = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'permissions' => ['*'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
