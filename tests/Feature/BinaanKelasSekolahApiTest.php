<?php

namespace Tests\Feature;

use App\Models\PamongSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Support\TargetGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API v1 sumber data AKTIF: Binaan Pamong + Kelas Sekolah.
 *
 * Menggantikan `/kelas` yang ditandai deprecated. Yang dikunci di sini:
 * batas akses per pamong, penugasan berakhir tidak ikut terhitung, dan
 * pengelompokan kelas sekolah yang tetap berguna walau `school_grade` kosong.
 */
class BinaanKelasSekolahApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pamong_hanya_melihat_binaan_sendiri(): void
    {
        $pamongA = $this->pamong('Pamong A');
        $pamongB = $this->pamong('Pamong B');
        $siswaA = $this->siswa('Generus A');
        $siswaB = $this->siswa('Generus B');
        PamongSiswa::query()->create(['pamong_id' => $pamongA->id, 'siswa_id' => $siswaA->id]);
        PamongSiswa::query()->create(['pamong_id' => $pamongB->id, 'siswa_id' => $siswaB->id]);

        Sanctum::actingAs($pamongA);
        $response = $this->getJson('/api/v1/binaan-pamong');

        $response->assertOk()
            ->assertJsonPath('meta.scope', 'sendiri')
            ->assertJsonPath('meta.total_pamong', 1)
            ->assertJsonPath('data.0.pamong_id', $pamongA->id)
            ->assertJsonPath('data.0.jumlah_binaan', 1);
    }

    public function test_pamong_ditolak_membuka_binaan_pamong_lain(): void
    {
        $pamongA = $this->pamong('Pamong A');
        $pamongB = $this->pamong('Pamong B');

        Sanctum::actingAs($pamongA);

        $this->getJson("/api/v1/binaan-pamong/{$pamongB->id}/siswa")
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_admin_melihat_seluruh_pamong(): void
    {
        $admin = $this->admin();
        $pamongA = $this->pamong('Pamong A');
        $pamongB = $this->pamong('Pamong B');
        PamongSiswa::query()->create(['pamong_id' => $pamongA->id, 'siswa_id' => $this->siswa('S1')->id]);
        PamongSiswa::query()->create(['pamong_id' => $pamongB->id, 'siswa_id' => $this->siswa('S2')->id]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/binaan-pamong')
            ->assertOk()
            ->assertJsonPath('meta.scope', 'semua')
            ->assertJsonPath('meta.total_pamong', 2)
            ->assertJsonPath('meta.total_binaan', 2);
    }

    public function test_penugasan_yang_sudah_diakhiri_tidak_dihitung(): void
    {
        $admin = $this->admin();
        $pamong = $this->pamong('Pamong A');
        $aktif = $this->siswa('Masih Dibina');
        $selesai = $this->siswa('Sudah Lepas');
        PamongSiswa::query()->create(['pamong_id' => $pamong->id, 'siswa_id' => $aktif->id]);
        PamongSiswa::query()->create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $selesai->id,
            'ended_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/binaan-pamong')
            ->assertOk()
            ->assertJsonPath('data.0.jumlah_binaan', 1);

        $this->getJson("/api/v1/binaan-pamong/{$pamong->id}/siswa")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nama', 'Masih Dibina');
    }

    public function test_pencarian_pamong_juga_menyempitkan_total_binaan(): void
    {
        $admin = $this->admin();
        $pamongA = $this->pamong('Pamong A');
        $pamongB = $this->pamong('Pamong B');
        PamongSiswa::query()->create(['pamong_id' => $pamongA->id, 'siswa_id' => $this->siswa('S1')->id]);
        PamongSiswa::query()->create(['pamong_id' => $pamongB->id, 'siswa_id' => $this->siswa('S2')->id]);

        Sanctum::actingAs($admin);

        // Kartu ringkasan harus ikut menyempit, bukan tetap memakai total global.
        $this->getJson('/api/v1/binaan-pamong?search=Pamong A')
            ->assertOk()
            ->assertJsonPath('meta.total_pamong', 1)
            ->assertJsonPath('meta.total_binaan', 1);

        // Pencarian tanpa hasil: keduanya nol, tidak boleh 0 pamong tapi 2 binaan.
        $this->getJson('/api/v1/binaan-pamong?search=zzz')
            ->assertOk()
            ->assertJsonPath('meta.total_pamong', 0)
            ->assertJsonPath('meta.total_binaan', 0);
    }

    public function test_pencarian_siswa_binaan_menyaring_hasil(): void
    {
        $admin = $this->admin();
        $pamong = $this->pamong('Pamong A');
        foreach (['Ahmad Rizki', 'Siti Nurhaliza'] as $nama) {
            PamongSiswa::query()->create([
                'pamong_id' => $pamong->id,
                'siswa_id' => $this->siswa($nama)->id,
            ]);
        }

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/binaan-pamong/{$pamong->id}/siswa?search=Siti")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nama', 'Siti Nurhaliza');
    }

    public function test_kelas_sekolah_mengembalikan_seluruh_opsi_dengan_jumlah(): void
    {
        $admin = $this->admin();
        $this->siswa('Generus SMP', TargetGrade::SMP_8);
        $this->siswa('Generus SMA', TargetGrade::SMA_11);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/v1/kelas-sekolah');

        $response->assertOk();
        $data = collect($response->json('data'))->keyBy('kode');

        // Seluruh opsi TargetGrade hadir, termasuk yang kosong.
        $this->assertSame(count(TargetGrade::schoolClassOptions()), $data->count());
        $this->assertSame(1, $data[TargetGrade::SMP_8]['jumlah_siswa']);
        $this->assertSame(1, $data[TargetGrade::SMA_11]['jumlah_siswa']);
        $this->assertSame(0, $data[TargetGrade::SMP_7]['jumlah_siswa']);
    }

    public function test_only_used_menyaring_kelas_kosong(): void
    {
        $admin = $this->admin();
        $this->siswa('Generus SMA', TargetGrade::SMA_11);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/v1/kelas-sekolah?only_used=1');

        $response->assertOk();
        $kode = collect($response->json('data'))->pluck('kode')->all();
        $this->assertSame([TargetGrade::SMA_11], $kode);
    }

    public function test_school_grade_kosong_dilaporkan_lewat_belum_diisi(): void
    {
        $admin = $this->admin();
        // Tanggal lahir dikosongkan supaya tidak ada taksiran level efektif.
        Siswa::factory()->create([
            'nama' => 'Biodata Belum Lengkap',
            'status' => 'active',
            'is_active' => true,
            'school_grade' => null,
            'tanggal_lahir' => null,
            'kelompok' => Siswa::KELOMPOK_SAWAH_DALAM_1,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/kelas-sekolah')
            ->assertOk()
            ->assertJsonPath('meta.belum_diisi', 1)
            ->assertJsonPath('meta.total_efektif', 0);
    }

    public function test_kelas_sekolah_kosong_terisi_lewat_level_efektif(): void
    {
        $admin = $this->admin();
        // school_grade NULL tapi override terisi → level efektif tetap terbaca.
        Siswa::factory()->create([
            'nama' => 'Override SMA 12',
            'status' => 'active',
            'is_active' => true,
            'school_grade' => null,
            'target_grade_override' => TargetGrade::SMA_12,
            'kelompok' => Siswa::KELOMPOK_SAWAH_DALAM_1,
        ]);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/v1/kelas-sekolah?only_used=1');

        $response->assertOk();
        $baris = collect($response->json('data'))->firstWhere('kode', TargetGrade::SMA_12);
        $this->assertNotNull($baris, 'Kelas dengan level efektif harus lolos only_used');
        $this->assertSame(0, $baris['jumlah_siswa']);
        $this->assertSame(1, $baris['jumlah_efektif']);

        $this->getJson('/api/v1/kelas-sekolah/'.TargetGrade::SMA_12.'/siswa?effective=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nama', 'Override SMA 12');
    }

    public function test_kode_kelas_tulisan_manusia_dinormalkan(): void
    {
        $admin = $this->admin();
        $this->siswa('Generus SMA', TargetGrade::SMA_11);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/kelas-sekolah/kelas 11/siswa')
            ->assertOk()
            ->assertJsonPath('meta.kode', TargetGrade::SMA_11)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_kode_kelas_tidak_dikenal_ditolak_422(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/kelas-sekolah/xyz/siswa')
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_SCHOOL_GRADE');
    }

    public function test_pamong_pada_kelas_sekolah_hanya_melihat_binaannya(): void
    {
        $pamong = $this->pamong('Pamong A');
        $milikSendiri = $this->siswa('Binaan Sendiri', TargetGrade::SMA_11);
        $this->siswa('Bukan Binaan', TargetGrade::SMA_11);
        PamongSiswa::query()->create(['pamong_id' => $pamong->id, 'siswa_id' => $milikSendiri->id]);

        Sanctum::actingAs($pamong);

        $this->getJson('/api/v1/kelas-sekolah/'.TargetGrade::SMA_11.'/siswa')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nama', 'Binaan Sendiri');
    }

    public function test_tanpa_token_ditolak(): void
    {
        $this->getJson('/api/v1/binaan-pamong')->assertUnauthorized();
        $this->getJson('/api/v1/kelas-sekolah')->assertUnauthorized();
    }

    private function admin(): User
    {
        return $this->userWithRole(User::ROLE_ADMIN, 'Administrator', ['*']);
    }

    private function pamong(string $nama): User
    {
        return $this->userWithRole(
            User::ROLE_TEACHER,
            $nama,
            ['view_students', 'manage_students'],
        );
    }

    private function userWithRole(string $roleName, string $nama, array $permissions): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            [
                'display_name' => ucfirst($roleName),
                'permissions' => $permissions,
                'is_active' => true,
            ]
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'name' => $nama,
            'status' => 'active',
        ]);
    }

    private function siswa(string $nama, ?string $schoolGrade = null): Siswa
    {
        return Siswa::factory()->create([
            'nama' => $nama,
            'status' => 'active',
            'is_active' => true,
            'school_grade' => $schoolGrade,
            'kelompok' => Siswa::KELOMPOK_SAWAH_DALAM_1,
        ]);
    }
}
