<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MateriTarget;
use App\Models\PamongSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SiswaMateriTargetProgress;
use App\Models\User;
use App\Support\TargetGrade;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MateriAnalyticsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-19 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_sees_target_progress_for_all_active_students(): void
    {
        $admin = $this->adminUser();
        $firstStudent = $this->siswaForGrade(TargetGrade::SMP_7);
        $this->siswaForGrade(TargetGrade::SMP_7);
        $unleveledStudent = Siswa::factory()->create([
            'nama' => 'Tanpa Level Admin',
            'nis' => 'NL-ADM',
            'tanggal_lahir' => '2020-01-01',
            'kelas_id' => Kelas::factory(),
            'kelompok' => Siswa::KELOMPOK_PANUNGGANGAN_UTARA,
        ]);

        $haditsTarget = $this->targetForGrade(TargetGrade::SMP_7, MateriTarget::CATEGORY_MAKNA_AL_HADITS);
        $this->targetForGrade(TargetGrade::SMP_7, MateriTarget::CATEGORY_HAFALAN);

        SiswaMateriTargetProgress::create([
            'siswa_id' => $firstStudent->id,
            'materi_target_id' => $haditsTarget->id,
            'is_completed' => true,
            'completed_at' => now(),
            'actor_type' => 'siswa',
            'actor_id' => $firstStudent->id,
        ]);

        $response = $this->actingAs($admin)->get(route('materi.index'));

        $response->assertOk()
            ->assertSee('Analitik Target Materi')
            ->assertSee('Scope: semua siswa aktif')
            ->assertSee('dari 3 siswa aktif')
            ->assertSee('1 siswa belum punya level kelas PKG')
            ->assertSee('Lihat daftar')
            ->assertSee('Tanpa Level Admin')
            ->assertSee('NL-ADM')
            ->assertSee(route('siswa.edit', $unleveledStudent), false)
            ->assertSee('1 / 4')
            ->assertSee('25%')
            ->assertSee('Lihat siapa')
            ->assertSee('analytics_detail=completed', false)
            ->assertSee('Makna Al Hadits')
            ->assertSee('Hafalan');
    }

    public function test_pamong_target_progress_only_counts_assigned_students(): void
    {
        $pamong = $this->pamongUser();
        $assignedStudent = $this->siswaForGrade(TargetGrade::SMP_7);
        $otherStudent = $this->siswaForGrade(TargetGrade::SMP_7);
        $assignedStudent->update(['nama' => 'Siswa Binaan Selesai']);
        $otherStudent->update(['nama' => 'Siswa Luar Selesai']);
        $assignedUnleveledStudent = Siswa::factory()->create([
            'nama' => 'Tanpa Level Binaan',
            'nis' => 'NL-BIN',
            'tanggal_lahir' => '2020-01-01',
            'kelas_id' => Kelas::factory(),
            'kelompok' => Siswa::KELOMPOK_PANUNGGANGAN_UTARA,
        ]);
        $outsideUnleveledStudent = Siswa::factory()->create([
            'nama' => 'Tanpa Level Luar',
            'nis' => 'NL-LUAR',
            'tanggal_lahir' => '2020-01-01',
            'kelas_id' => Kelas::factory(),
            'kelompok' => Siswa::KELOMPOK_PANUNGGANGAN_UTARA,
        ]);

        PamongSiswa::create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $assignedStudent->id,
        ]);
        PamongSiswa::create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $assignedUnleveledStudent->id,
        ]);

        $haditsTarget = $this->targetForGrade(TargetGrade::SMP_7, MateriTarget::CATEGORY_MAKNA_AL_HADITS);
        $hafalanTarget = $this->targetForGrade(TargetGrade::SMP_7, MateriTarget::CATEGORY_HAFALAN);

        SiswaMateriTargetProgress::create([
            'siswa_id' => $assignedStudent->id,
            'materi_target_id' => $haditsTarget->id,
            'is_completed' => true,
            'completed_at' => now(),
            'actor_type' => 'siswa',
            'actor_id' => $assignedStudent->id,
        ]);

        SiswaMateriTargetProgress::create([
            'siswa_id' => $otherStudent->id,
            'materi_target_id' => $hafalanTarget->id,
            'is_completed' => true,
            'completed_at' => now(),
            'actor_type' => 'siswa',
            'actor_id' => $otherStudent->id,
        ]);

        $response = $this->actingAs($pamong)->get(route('materi.index', [
            'analytics_detail' => 'completed',
        ]));

        $response->assertOk()
            ->assertSee('Scope: siswa binaan')
            ->assertSee('dari 2 siswa aktif')
            ->assertSee('Tanpa Level Binaan')
            ->assertSee('NL-BIN')
            ->assertDontSee('Tanpa Level Luar')
            ->assertDontSee('NL-LUAR')
            ->assertDontSee(route('siswa.edit', $assignedUnleveledStudent), false)
            ->assertDontSee(route('siswa.edit', $outsideUnleveledStudent), false)
            ->assertSee('1 / 2')
            ->assertSee('50%')
            ->assertSee('Siswa Binaan Selesai')
            ->assertDontSee('Siswa Luar Selesai')
            ->assertDontSee('2 / 4');
    }

    public function test_target_progress_filter_by_grade_and_semester_changes_denominator(): void
    {
        $admin = $this->adminUser();
        $smpSevenStudent = $this->siswaForGrade(TargetGrade::SMP_7);
        $this->siswaForGrade(TargetGrade::SMP_8);

        $selectedTarget = $this->targetForGrade(TargetGrade::SMP_7, MateriTarget::CATEGORY_MAKNA_AL_HADITS, 2);
        $this->targetForGrade(TargetGrade::SMP_7, MateriTarget::CATEGORY_HAFALAN, 1);
        $this->targetForGrade(TargetGrade::SMP_8, MateriTarget::CATEGORY_HAFALAN, 2);

        SiswaMateriTargetProgress::create([
            'siswa_id' => $smpSevenStudent->id,
            'materi_target_id' => $selectedTarget->id,
            'is_completed' => true,
            'completed_at' => now(),
            'actor_type' => 'siswa',
            'actor_id' => $smpSevenStudent->id,
        ]);

        $response = $this->actingAs($admin)->get(route('materi.index', [
            'analytics_grade' => TargetGrade::SMP_7,
            'analytics_semester' => 2,
        ]));

        $response->assertOk()
            ->assertSee('SMP 7')
            ->assertSee('Semester 2')
            ->assertSee('dari 2 siswa aktif')
            ->assertSee('1 / 1')
            ->assertSee('100%')
            ->assertSee('Makna Al Hadits');
    }

    public function test_completed_detail_groups_checklists_by_student_and_respects_filters(): void
    {
        $admin = $this->adminUser();
        $completedStudent = $this->siswaForGrade(TargetGrade::SMP_7);
        $completedStudent->update(['nama' => 'Siswa Detail Selesai', 'nis' => 'DTL-001']);
        $outsideStudent = $this->siswaForGrade(TargetGrade::SMP_8);
        $outsideStudent->update(['nama' => 'Siswa Di Luar Filter', 'nis' => 'DTL-002']);

        $haditsTarget = $this->targetForGrade(TargetGrade::SMP_7, MateriTarget::CATEGORY_MAKNA_AL_HADITS, 2);
        $hafalanTarget = $this->targetForGrade(TargetGrade::SMP_7, MateriTarget::CATEGORY_HAFALAN, 2);
        $outsideTarget = $this->targetForGrade(TargetGrade::SMP_8, MateriTarget::CATEGORY_HAFALAN, 2);

        foreach ([$haditsTarget, $hafalanTarget] as $target) {
            SiswaMateriTargetProgress::create([
                'siswa_id' => $completedStudent->id,
                'materi_target_id' => $target->id,
                'is_completed' => true,
                'completed_at' => now(),
                'actor_type' => 'siswa',
                'actor_id' => $completedStudent->id,
            ]);
        }

        SiswaMateriTargetProgress::create([
            'siswa_id' => $outsideStudent->id,
            'materi_target_id' => $outsideTarget->id,
            'is_completed' => true,
            'completed_at' => now(),
            'actor_type' => 'siswa',
            'actor_id' => $outsideStudent->id,
        ]);

        $response = $this->actingAs($admin)->get(route('materi.index', [
            'analytics_grade' => TargetGrade::SMP_7,
            'analytics_semester' => 2,
            'analytics_detail' => 'completed',
        ]));

        $response->assertOk()
            ->assertSee('Siswa yang Menyelesaikan Target')
            ->assertSee('1 siswa menyelesaikan 2 ceklis')
            ->assertSee('Siswa Detail Selesai')
            ->assertSee('DTL-001')
            ->assertSee('2 ceklis')
            ->assertSee($haditsTarget->title)
            ->assertSee($hafalanTarget->title)
            ->assertSee('Tutup detail')
            ->assertDontSee('Siswa Di Luar Filter')
            ->assertDontSee($outsideTarget->title);
    }

    public function test_completed_card_has_no_link_when_total_is_zero_and_detail_is_empty(): void
    {
        $admin = $this->adminUser();
        $this->siswaForGrade(TargetGrade::SMP_7);
        $this->targetForGrade(TargetGrade::SMP_7, MateriTarget::CATEGORY_MAKNA_AL_HADITS, 2);

        $page = $this->actingAs($admin)->get(route('materi.index', [
            'analytics_grade' => TargetGrade::SMP_7,
            'analytics_semester' => 2,
        ]));

        $page->assertOk()->assertDontSee('Lihat siapa');

        $detail = $this->actingAs($admin)->get(route('materi.index', [
            'analytics_grade' => TargetGrade::SMP_7,
            'analytics_semester' => 2,
            'analytics_detail' => 'completed',
        ]));

        $detail->assertOk()
            ->assertSee('0 siswa menyelesaikan 0 ceklis')
            ->assertSee('Belum ada ceklis selesai');
    }

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function pamongUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => User::ROLE_TEACHER],
            ['display_name' => 'Pamong', 'permissions' => ['view_students'], 'is_active' => true]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function siswaForGrade(string $grade): Siswa
    {
        $birthDate = match ($grade) {
            TargetGrade::SMP_7 => '2014-06-30',
            TargetGrade::SMP_8 => '2013-06-30',
            TargetGrade::SMP_9 => '2012-06-30',
            TargetGrade::SMA_10 => '2011-06-30',
            TargetGrade::SMA_11 => '2010-06-30',
            TargetGrade::SMA_12 => '2009-06-30',
            default => '2014-06-30',
        };

        return Siswa::factory()->create([
            'tanggal_lahir' => $birthDate,
            'kelas_id' => Kelas::factory(),
            'kelompok' => Siswa::KELOMPOK_PANUNGGANGAN_UTARA,
        ]);
    }

    private function targetForGrade(string $grade, string $category, int $semester = 2): MateriTarget
    {
        return MateriTarget::create([
            'category' => $category,
            'target_grade' => $grade,
            'semester' => $semester,
            'title' => 'Target '.TargetGrade::label($grade).' '.$category.' '.$semester,
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }
}
