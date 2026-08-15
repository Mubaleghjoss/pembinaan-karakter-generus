<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\PamongSiswa;
use App\Models\Presensi;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Support\TargetGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolGradeMentorshipFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_binaan_page_groups_active_students_by_each_pamong_without_using_legacy_class(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');
        $pamongA = $this->user(User::ROLE_TEACHER, 'Pamong A');
        $pamongB = $this->user(User::ROLE_TEACHER, 'Pamong B');
        $legacy = Kelas::factory()->create(['nama' => 'MAS AGIL']);
        $siswa = Siswa::factory()->create([
            'nama' => 'Generus Multi Pamong',
            'kelas_id' => $legacy->id,
            'school_grade' => TargetGrade::SMA_10,
        ]);
        PamongSiswa::create(['pamong_id' => $pamongA->id, 'siswa_id' => $siswa->id]);
        PamongSiswa::create(['pamong_id' => $pamongB->id, 'siswa_id' => $siswa->id]);

        $response = $this->actingAs($admin)->get(route('kelas.index'));

        $response->assertOk()
            ->assertSee('Binaan Pamong')
            ->assertSee('Generus Multi Pamong')
            ->assertSee('SMA/SMK Kelas 1')
            ->assertSee('2 Pamong')
            ->assertDontSee('MAS AGIL');
    }

    public function test_new_student_requires_school_grade_and_does_not_write_legacy_class(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');

        $this->actingAs($admin)->postJson(route('siswa.store'), [
            'nis' => 'GRADE-001',
            'nama' => 'Generus Sekolah',
            'jenis_kelamin' => 'L',
        ])->assertUnprocessable()->assertJsonValidationErrors('school_grade');

        $this->actingAs($admin)->postJson(route('siswa.store'), [
            'nis' => 'GRADE-001',
            'nama' => 'Generus Sekolah',
            'jenis_kelamin' => 'L',
            'school_grade' => TargetGrade::SMP_8,
        ])->assertOk();

        $this->assertDatabaseHas('siswa', [
            'nis' => 'GRADE-001',
            'school_grade' => TargetGrade::SMP_8,
            'kelas_id' => null,
        ]);
    }

    public function test_legacy_class_mutations_are_gone_but_read_page_remains_available(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');

        $this->actingAs($admin)->postJson(route('kelas.store'), ['nama' => 'Kelas Baru'])
            ->assertStatus(410);
        $this->actingAs($admin)->get(route('kelas.index'))->assertOk();
        $this->assertDatabaseMissing('kelas', ['nama' => 'Kelas Baru']);
    }

    public function test_reports_group_active_data_by_school_grade_and_pamong(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');
        $pamong = $this->user(User::ROLE_TEACHER, 'Pamong Laporan');
        $siswa = Siswa::factory()->create(['school_grade' => TargetGrade::SMP_9]);
        PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $siswa->id]);
        Presensi::query()->create([
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
        ]);

        $response = $this->actingAs($admin)->getJson(route('reports.class-performance', [
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'school_grade' => TargetGrade::SMP_9,
            'pamong_id' => $pamong->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.id', TargetGrade::SMP_9)
            ->assertJsonPath('data.0.total_siswa', 1)
            ->assertJsonPath('pamong_data.0.nama', 'Pamong Laporan')
            ->assertJsonPath('pamong_data.0.total_siswa', 1);
    }

    public function test_operational_report_and_export_pages_offer_school_grade_and_pamong_filters(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');
        $pamong = $this->user(User::ROLE_TEACHER, 'Pamong Filter');

        $this->actingAs($admin)->get(route('reports.index'))->assertOk()
            ->assertSee('Kelas Sekolah')->assertSee('Pamong Filter');
        $this->actingAs($admin)->get(route('export.index'))->assertOk()
            ->assertSee('Kelas Sekolah')->assertSee('Pamong Filter');
        $this->actingAs($admin)->get(route('presensi.recap'))->assertOk()
            ->assertSee('Kelas Sekolah')->assertSee('Pamong Binaan');
    }

    private function user(string $roleName, string $displayName): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => $displayName, 'permissions' => $roleName === User::ROLE_ADMIN ? ['*'] : [], 'is_active' => true]
        );

        return User::factory()->create(['role_id' => $role->id, 'name' => $displayName]);
    }
}
