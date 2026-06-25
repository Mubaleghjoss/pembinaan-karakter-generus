<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MateriTarget;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SiswaMateriTargetProgress;
use App\Models\User;
use App\Support\KmgtSilabusTargets;
use App\Support\TargetGrade;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MateriTargetFeatureTest extends TestCase
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

    public function test_admin_can_create_update_and_delete_materi_target(): void
    {
        $admin = $this->adminUser();

        $create = $this->actingAs($admin)->post(route('materi-targets.store'), [
            'category' => MateriTarget::CATEGORY_MAKNA_AL_QURAN,
            'target_grade' => TargetGrade::SMP_7,
            'semester' => 2,
            'title' => 'Makna QS Al Fatihah',
            'description' => 'Target awal.',
            'sort_order' => 2,
            'is_active' => '1',
        ]);

        $create->assertRedirect();

        $target = MateriTarget::firstOrFail();

        $this->assertSame('Makna QS Al Fatihah', $target->title);
        $this->assertSame(2, $target->semester);
        $this->assertTrue($target->is_active);

        $update = $this->actingAs($admin)->patch(route('materi-targets.update', $target), [
            'category' => MateriTarget::CATEGORY_MAKNA_AL_QURAN,
            'target_grade' => TargetGrade::SMP_7,
            'semester' => 1,
            'title' => 'Makna QS Al Fatihah Revisi',
            'description' => null,
            'sort_order' => 1,
        ]);

        $update->assertRedirect();

        $target->refresh();

        $this->assertSame('Makna QS Al Fatihah Revisi', $target->title);
        $this->assertSame(1, $target->semester);
        $this->assertFalse($target->is_active);
        $this->assertSame(1, $target->sort_order);

        $delete = $this->actingAs($admin)->delete(route('materi-targets.destroy', $target));

        $delete->assertRedirect();
        $this->assertSame(0, MateriTarget::count());
    }

    public function test_siswa_materi_page_shows_matching_targets_and_can_toggle_progress(): void
    {
        $siswa = $this->siswaForGrade(TargetGrade::SMP_7);
        $matchingTarget = MateriTarget::create([
            'category' => MateriTarget::CATEGORY_BACAAN_MAKNA_AL_QURAN,
            'target_grade' => TargetGrade::SMP_7,
            'semester' => 2,
            'title' => 'Makna QS Al Fatihah',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        MateriTarget::create([
            'category' => MateriTarget::CATEGORY_BACAAN_MAKNA_AL_QURAN,
            'target_grade' => TargetGrade::SMP_8,
            'semester' => 2,
            'title' => 'Target Kelas Lain',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        MateriTarget::create([
            'category' => MateriTarget::CATEGORY_BACAAN_MAKNA_AL_QURAN,
            'target_grade' => TargetGrade::SMP_7,
            'semester' => 1,
            'title' => 'Target Semester Lain',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $page = $this->actingAs($siswa, 'siswa')->get(route('siswa.materi.index'));

        $page->assertOk()
            ->assertSee('Target Materi Saya')
            ->assertSee('Makna QS Al Fatihah')
            ->assertDontSee('Target Kelas Lain')
            ->assertDontSee('Target Semester Lain');

        $complete = $this->actingAs($siswa, 'siswa')->post(route('siswa.materi-targets.toggle', $matchingTarget), [
            'completed' => '1',
        ]);

        $complete->assertRedirect();

        $this->assertDatabaseHas('siswa_materi_target_progress', [
            'siswa_id' => $siswa->id,
            'materi_target_id' => $matchingTarget->id,
            'is_completed' => 1,
            'actor_type' => 'siswa',
            'actor_id' => $siswa->id,
        ]);

        $undo = $this->actingAs($siswa, 'siswa')->post(route('siswa.materi-targets.toggle', $matchingTarget), [
            'completed' => '0',
        ]);

        $undo->assertRedirect();

        $progress = SiswaMateriTargetProgress::firstOrFail();

        $this->assertFalse($progress->is_completed);
        $this->assertNull($progress->completed_at);
    }

    public function test_siswa_cannot_toggle_target_for_another_grade(): void
    {
        $siswa = $this->siswaForGrade(TargetGrade::SMP_7);
        $target = MateriTarget::create([
            'category' => MateriTarget::CATEGORY_HAFALAN,
            'target_grade' => TargetGrade::SMP_8,
            'title' => 'Hafalan Kelas Lain',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($siswa, 'siswa')->post(route('siswa.materi-targets.toggle', $target), [
            'completed' => '1',
        ]);

        $response->assertForbidden();
        $this->assertSame(0, SiswaMateriTargetProgress::count());
    }

    public function test_admin_can_correct_student_progress(): void
    {
        $admin = $this->adminUser();
        $siswa = $this->siswaForGrade(TargetGrade::SMP_7);
        $target = MateriTarget::create([
            'category' => MateriTarget::CATEGORY_HAFALAN,
            'target_grade' => TargetGrade::SMP_7,
            'title' => 'Hafalan Doa',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('materi-targets.progress.toggle', [$siswa, $target]), [
            'completed' => '1',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('siswa_materi_target_progress', [
            'siswa_id' => $siswa->id,
            'materi_target_id' => $target->id,
            'is_completed' => 1,
            'actor_type' => 'user',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_admin_target_page_filters_by_grade_semester_and_category(): void
    {
        $admin = $this->adminUser();

        MateriTarget::create([
            'category' => MateriTarget::CATEGORY_BACAAN_MAKNA_AL_QURAN,
            'target_grade' => TargetGrade::SMP_7,
            'semester' => 2,
            'title' => 'Target Terpilih',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MateriTarget::create([
            'category' => MateriTarget::CATEGORY_BACAAN_MAKNA_AL_QURAN,
            'target_grade' => TargetGrade::SMP_7,
            'semester' => 1,
            'title' => 'Target Semester Lain',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MateriTarget::create([
            'category' => MateriTarget::CATEGORY_MAKNA_AL_HADITS,
            'target_grade' => TargetGrade::SMP_7,
            'semester' => 2,
            'title' => 'Target Kategori Lain',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('materi-targets.index', [
            'grade' => TargetGrade::SMP_7,
            'semester' => 2,
            'category' => MateriTarget::CATEGORY_BACAAN_MAKNA_AL_QURAN,
        ]));

        $response->assertOk()
            ->assertSee('Target Terpilih')
            ->assertDontSee('Target Semester Lain')
            ->assertDontSee('Target Kategori Lain');
    }

    public function test_kmgt_silabus_command_imports_targets_idempotently(): void
    {
        $expectedCount = count(KmgtSilabusTargets::records());

        Artisan::call('materi-targets:seed-kmgt-silabus');
        $this->assertSame($expectedCount, MateriTarget::count());

        Artisan::call('materi-targets:seed-kmgt-silabus');
        $this->assertSame($expectedCount, MateriTarget::count());

        $this->assertDatabaseHas('materi_targets', [
            'source_key' => 'kmgt_c_1_bacaan_makna_al_quran',
            'target_grade' => TargetGrade::SMP_7,
            'semester' => 1,
            'category' => MateriTarget::CATEGORY_BACAAN_MAKNA_AL_QURAN,
            'title' => 'Al-Quran Juz 12-14 dan Makna Juz 21-22',
        ]);

        $this->assertDatabaseHas('materi_targets', [
            'source_key' => 'kmgt_d_5_bacaan_makna_al_quran',
            'target_grade' => TargetGrade::SMA_12,
            'semester' => 1,
            'category' => MateriTarget::CATEGORY_BACAAN_MAKNA_AL_QURAN,
            'title' => 'Bacaan sebelum makna Al-Quran dan Makna Juz 17-18',
        ]);

        $this->assertDatabaseHas('materi_targets', [
            'source_key' => 'kmgt_d_6_makna_al_hadits',
            'target_grade' => TargetGrade::SMA_12,
            'semester' => 2,
            'category' => MateriTarget::CATEGORY_MAKNA_AL_HADITS,
            'title' => 'Makna Hadits Kitabul Haji',
        ]);
    }

    public function test_siswa_profile_can_store_manual_target_grade_override(): void
    {
        $siswa = $this->siswaForGrade(TargetGrade::SMP_7);

        $response = $this->actingAs($siswa, 'siswa')->post(route('siswa.profile.update'), [
            'nama' => $siswa->nama,
            'jenis_kelamin' => $siswa->jenis_kelamin,
            'tanggal_lahir' => '2014-06-30',
            'kelompok' => $siswa->kelompok,
            'target_grade_override' => TargetGrade::SMA_10,
            'phone' => '081234567890',
            'nama_wali' => 'Wali Test',
            'phone_wali' => '081111111111',
        ]);

        $response->assertRedirect(route('siswa.profile'));

        $siswa->refresh();

        $this->assertSame(TargetGrade::SMA_10, $siswa->target_grade_override);
        $this->assertSame(TargetGrade::SMA_10, $siswa->target_grade);
    }

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]
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
}
