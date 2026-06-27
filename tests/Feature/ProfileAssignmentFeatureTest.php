<?php

namespace Tests\Feature;

use App\Models\MateriTarget;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Support\ParticipantProfileOptions;
use App\Support\PopupManager;
use App\Support\TargetGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProfileAssignmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_siswa_must_confirm_group_and_school_grade_and_sees_qr(): void
    {
        $siswa = Siswa::factory()->create([
            'kelompok' => 'sawah dalam',
            'target_grade_override' => null,
            'profile_assignment_confirmed_at' => null,
        ]);

        $dashboard = $this->actingAs($siswa, 'siswa')->get(route('siswa.dashboard'));

        $dashboard->assertOk()
            ->assertSee('data-dashboard-qr', false)
            ->assertSee('Perbarui Data Penempatan')
            ->assertSee('Sawah Dalam 1')
            ->assertSee('Sawah Dalam 2')
            ->assertSee('Pranikah (Selesai SMA/K)');

        $response = $this->from(route('siswa.dashboard'))
            ->actingAs($siswa, 'siswa')
            ->put(route('siswa.profile-assignment.update'), [
                'kelompok' => ParticipantProfileOptions::SAWAH_DALAM_2,
                'target_grade_override' => TargetGrade::PRANIKAH,
            ]);

        $response->assertRedirect(route('siswa.dashboard'));

        $siswa->refresh();
        $this->assertSame(ParticipantProfileOptions::SAWAH_DALAM_2, $siswa->kelompok);
        $this->assertSame(TargetGrade::PRANIKAH, $siswa->target_grade);
        $this->assertNotNull($siswa->profile_assignment_confirmed_at);

        $this->actingAs($siswa, 'siswa')
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertDontSee('Perbarui Data Penempatan');
    }

    public function test_pamong_must_confirm_group_and_sees_qr_and_group_summary(): void
    {
        $pamong = $this->pamongUser();

        $dashboard = $this->actingAs($pamong)->get(route('dashboard'));

        $dashboard->assertOk()
            ->assertSee('data-dashboard-qr', false)
            ->assertSee('Perbarui Data Penempatan')
            ->assertSee('Sawah Dalam 1')
            ->assertSee('Pakulonan');

        $this->from(route('dashboard'))
            ->actingAs($pamong)
            ->put(route('profile-assignment.update'), [
                'kelompok' => ParticipantProfileOptions::PANUNGGANGAN_UTARA,
            ])
            ->assertRedirect(route('dashboard'));

        $pamong->refresh();
        $this->assertSame(ParticipantProfileOptions::PANUNGGANGAN_UTARA, $pamong->kelompok);
        $this->assertNotNull($pamong->profile_assignment_confirmed_at);

        $this->actingAs($pamong)
            ->get(route('pamong-presensi.index'))
            ->assertOk()
            ->assertSee('Ringkasan Kelompok Pamong')
            ->assertSee('Sawah Dalam 1')
            ->assertSee('Sawah Dalam 2')
            ->assertSee('Panunggangan Utara')
            ->assertSee('Pakulonan');
    }

    public function test_student_recap_uses_four_current_groups(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->getJson(route('presensi.data', [
            'tanggal' => now()->toDateString(),
        ]));

        $response->assertOk();
        $labels = collect($response->json('group_summary'))->pluck('label')->all();

        $this->assertSame([
            'Sawah Dalam 1',
            'Sawah Dalam 2',
            'Panunggangan Utara',
            'Pakulonan',
        ], $labels);

        $this->assertCount(4, Siswa::kelompokOptions());
    }

    public function test_settings_page_lists_required_profile_assignment_popup(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('settings.index', ['tab' => 'popup']))
            ->assertOk()
            ->assertSee('Perbarui kelompok dan kelas sekolah')
            ->assertSee('popups[profile_assignment_prompt][enabled]', false)
            ->assertSee('popups[profile_assignment_prompt][required]', false);

        $this->assertTrue(PopupManager::enabled('profile_assignment_prompt'));
        $this->assertTrue(PopupManager::required('profile_assignment_prompt'));
        $this->assertFalse(PopupManager::enabled('biometric_prompt'));
    }

    public function test_pranikah_has_seeded_rpp_targets(): void
    {
        $this->assertContains(TargetGrade::PRANIKAH, TargetGrade::values());
        $this->artisan('materi-targets:seed-kmgt-silabus')->assertExitCode(0);
        $this->assertGreaterThan(
            0,
            MateriTarget::query()->where('target_grade', TargetGrade::PRANIKAH)->count()
        );
    }

    private function pamongUser(): User
    {
        $role = Role::create([
            'name' => User::ROLE_TEACHER,
            'display_name' => 'Pamong',
            'permissions' => ['view_students'],
            'is_active' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'kelompok' => null,
            'profile_assignment_confirmed_at' => null,
        ]);
    }

    private function adminUser(): User
    {
        $role = Role::create([
            'name' => User::ROLE_ADMIN,
            'display_name' => 'Administrator',
            'permissions' => ['*'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
