<?php

namespace Tests\Feature;

use App\Models\Karakter;
use App\Models\Kelas;
use App\Models\MateriTarget;
use App\Models\PamongPermission;
use App\Models\PamongSiswa;
use App\Models\Presensi;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Models\SiswaMateriTargetProgress;
use App\Models\User;
use App\Support\TargetGrade;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerusRecapFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-20 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_sees_combined_recap_for_each_kelompok(): void
    {
        $admin = $this->adminUser();
        $northStudent = $this->student(Siswa::KELOMPOK_PANUNGGANGAN_UTARA, 'Generus Utara');
        $southStudent = $this->student(Siswa::KELOMPOK_SAWAH_DALAM, 'Generus Selatan');
        $inactiveStudent = $this->student(Siswa::KELOMPOK_PAKULONAN, 'Generus Nonaktif');
        $inactiveStudent->update(['status' => 'inactive', 'is_active' => false]);

        $task = Karakter::create([
            'nama' => 'Tugas Periode',
            'kategori' => 'harian',
            'poin' => 5,
            'is_active' => true,
        ]);

        SiswaKarakterChecklist::create([
            'siswa_id' => $northStudent->id,
            'karakter_id' => $task->id,
            'checked_at' => '2026-06-10 07:00:00',
            'verified_by' => $admin->id,
            'verified_at' => '2026-06-10 08:00:00',
        ]);
        SiswaKarakterChecklist::create([
            'siswa_id' => $southStudent->id,
            'karakter_id' => $task->id,
            'checked_at' => '2026-06-11 07:00:00',
        ]);
        SiswaKarakterChecklist::create([
            'siswa_id' => $northStudent->id,
            'karakter_id' => $task->id,
            'checked_at' => '2026-05-30 07:00:00',
            'verified_by' => $admin->id,
            'verified_at' => '2026-05-30 08:00:00',
        ]);

        Presensi::create([
            'siswa_id' => $northStudent->id,
            'tanggal' => '2026-06-08',
            'status' => 'hadir',
            'is_verified' => true,
        ]);
        Presensi::create([
            'siswa_id' => $southStudent->id,
            'tanggal' => '2026-06-08',
            'status' => 'sakit',
            'is_verified' => true,
        ]);

        $firstTarget = $this->target('Target Hadits');
        $this->target('Target Hafalan');
        SiswaMateriTargetProgress::create([
            'siswa_id' => $northStudent->id,
            'materi_target_id' => $firstTarget->id,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('presensi.panel.generus', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'semester' => 2,
        ]));

        $response->assertOk()
            ->assertSee('Cakupan data: semua Generus aktif')
            ->assertSee('Panunggangan Utara')
            ->assertSee('Sawah Dalam')
            ->assertSee('Tugas PKG');

        $response->assertViewHas('rows', function ($rows) {
            $north = $rows->firstWhere('key', Siswa::KELOMPOK_PANUNGGANGAN_UTARA);
            $south = $rows->firstWhere('key', Siswa::KELOMPOK_SAWAH_DALAM);
            $empty = $rows->firstWhere('key', Siswa::KELOMPOK_PAKULONAN);

            return $north['total_students'] === 1
                && $north['task']['verified'] === 1
                && $north['task']['submitted'] === 1
                && $north['attendance']['present'] === 1
                && $north['attendance']['records'] === 1
                && $north['rpp']['completed'] === 1
                && $north['rpp']['expected'] === 2
                && $south['total_students'] === 1
                && $south['task']['pending'] === 1
                && $south['attendance']['absent'] === 1
                && $south['rpp']['completed'] === 0
                && $empty['total_students'] === 0;
        });

        $response->assertViewHas('totals', fn (array $totals) => $totals['total_students'] === 2
            && $totals['task']['verified'] === 1
            && $totals['task']['submitted'] === 2
            && $totals['attendance']['present'] === 1
            && $totals['attendance']['records'] === 2
            && $totals['rpp']['completed'] === 1
            && $totals['rpp']['expected'] === 4
        );
    }

    public function test_pamong_only_sees_assigned_generus(): void
    {
        $pamong = $this->pamongUserWithPresensiAccess();
        $assignedStudent = $this->student(Siswa::KELOMPOK_PANUNGGANGAN_UTARA, 'Generus Binaan');
        $outsideStudent = $this->student(Siswa::KELOMPOK_SAWAH_DALAM, 'Generus Luar');

        PamongSiswa::create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $assignedStudent->id,
        ]);

        $response = $this->actingAs($pamong)->get(route('presensi.panel.generus'));

        $response->assertOk()
            ->assertSee('Cakupan data: Generus binaan')
            ->assertViewHas('totals', fn (array $totals) => $totals['total_students'] === 1)
            ->assertViewHas('rows', function ($rows) use ($assignedStudent, $outsideStudent) {
                $north = $rows->firstWhere('key', $assignedStudent->kelompok);
                $south = $rows->firstWhere('key', $outsideStudent->kelompok);

                return $north['total_students'] === 1 && $south['total_students'] === 0;
            });
    }

    public function test_user_without_presensi_menu_access_is_forbidden(): void
    {
        $pamong = $this->pamongUserWithPresensiAccess();
        $pamong->pamongPermission()->update(['menu_permissions' => ['materi']]);

        $this->actingAs($pamong)
            ->get(route('presensi.panel.generus'))
            ->assertForbidden();
    }

    public function test_legacy_url_redirects_to_the_single_presensi_page(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('presensi.generus-recap', ['semester' => 2]))
            ->assertRedirect(route('presensi.index', [
                'semester' => 2,
                'tab' => 'rekap',
                'panel' => 'rekap-generus',
            ]).'#rekap-generus');

        $this->actingAs($admin)
            ->get(route('presensi.index', ['tab' => 'rekap']))
            ->assertOk()
            ->assertSee('Rekap Generus Tugas/RPP')
            ->assertSee('generusPanel:', false);
    }

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function pamongUserWithPresensiAccess(): User
    {
        $role = Role::firstOrCreate(
            ['name' => User::ROLE_TEACHER],
            ['display_name' => 'Pamong', 'permissions' => ['view_students'], 'is_active' => true]
        );
        $pamong = User::factory()->create(['role_id' => $role->id]);

        PamongPermission::create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['presensi'],
            'crud_permissions' => ['presensi' => ['view']],
            'is_excluded' => false,
        ]);

        return $pamong;
    }

    private function student(string $kelompok, string $name): Siswa
    {
        return Siswa::factory()->create([
            'nama' => $name,
            'tanggal_lahir' => '2014-06-30',
            'target_grade_override' => TargetGrade::SMP_7,
            'kelas_id' => Kelas::factory(),
            'kelompok' => $kelompok,
        ]);
    }

    private function target(string $title): MateriTarget
    {
        return MateriTarget::create([
            'category' => MateriTarget::CATEGORY_MAKNA_AL_HADITS,
            'target_grade' => TargetGrade::SMP_7,
            'semester' => 2,
            'title' => $title,
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }
}
