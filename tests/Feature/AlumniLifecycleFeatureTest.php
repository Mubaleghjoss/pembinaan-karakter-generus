<?php

namespace Tests\Feature;

use App\Models\Karakter;
use App\Models\PamongPermission;
use App\Models\PamongSiswa;
use App\Models\QuranReadingEntry;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AlumniLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_graduates_student_and_ends_active_pamong_assignments_without_deleting_history(): void
    {
        $admin = $this->admin();
        $reviewer = $this->admin();
        $pamong = $this->pamong();
        $siswa = Siswa::factory()->create();
        $assignment = PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $siswa->id]);

        $this->actingAs($admin)->patchJson(route('siswa.alumni.update', $siswa), [
            'action' => 'graduate',
            'alumni_can_submit' => true,
            'alumni_reviewer_id' => $reviewer->id,
        ])->assertOk()->assertJsonPath('data.is_alumni', true);

        $siswa->refresh();
        $assignment->refresh();
        $this->assertTrue($siswa->isGraduated());
        $this->assertTrue($siswa->canLogin());
        $this->assertNotNull($siswa->graduated_at);
        $this->assertNotNull($assignment->ended_at);
        $this->assertSame($admin->id, $assignment->ended_by);
        $this->assertSame(0, $pamong->assignedStudents()->count());
        $this->assertSame(1, $pamong->studentAssignmentHistory()->count());
    }

    public function test_only_admin_can_manage_alumni_and_reactivation_does_not_restore_old_pamong(): void
    {
        $admin = $this->admin();
        $pamong = $this->pamong();
        $siswa = Siswa::factory()->create();
        PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $siswa->id]);

        $this->actingAs($pamong)->patchJson(route('siswa.alumni.update', $siswa), ['action' => 'graduate'])->assertForbidden();
        $this->actingAs($admin)->patchJson(route('siswa.alumni.update', $siswa), ['action' => 'graduate'])->assertOk();
        $this->actingAs($admin)->patchJson(route('siswa.alumni.update', $siswa->fresh()), ['action' => 'reactivate'])->assertOk();

        $this->assertTrue($siswa->fresh()->isActive());
        $this->assertSame(0, $pamong->assignedStudents()->count());
        $this->assertSame(1, $pamong->studentAssignmentHistory()->count());
    }

    public function test_alumni_and_parent_can_login_but_inactive_accounts_are_rejected(): void
    {
        $siswa = Siswa::factory()->create([
            'status' => 'graduated', 'is_active' => true,
            'password' => Hash::make('rahasia-siswa'),
            'ortu_username' => 'ortu.alumni',
            'ortu_password' => Hash::make('rahasia-ortu'),
        ]);

        $this->post(route('siswa.login.post'), ['nis' => $siswa->nis, 'password' => 'rahasia-siswa'])
            ->assertRedirect(route('siswa.dashboard'));
        auth('siswa')->logout();
        $this->post(route('ortu.login.post'), ['username' => 'ortu.alumni', 'password' => 'rahasia-ortu'])
            ->assertRedirect(route('ortu.dashboard'));
        auth('ortu')->logout();

        $siswa->update(['is_active' => false]);
        $this->from(route('siswa.login'))->post(route('siswa.login.post'), ['nis' => $siswa->nis, 'password' => 'rahasia-siswa'])
            ->assertSessionHasErrors('nis');
        $this->from(route('ortu.login'))->post(route('ortu.login.post'), ['username' => 'ortu.alumni', 'password' => 'rahasia-ortu'])
            ->assertSessionHasErrors('username');
    }

    public function test_alumni_submissions_wait_for_admin_and_are_hidden_from_former_pamong(): void
    {
        $admin = $this->admin();
        $pamong = $this->pamong();
        $siswa = Siswa::factory()->create([
            'status' => 'graduated', 'is_active' => true,
            'alumni_can_submit' => true, 'alumni_reviewer_id' => $admin->id,
        ]);
        PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $siswa->id, 'ended_at' => now(), 'ended_by' => $admin->id]);
        $karakter = Karakter::create(['nama' => 'Tugas Alumni', 'kategori' => 'harian', 'poin' => 10, 'is_active' => true]);

        $this->actingAs($siswa, 'siswa')->post(route('siswa.tugas-pkg.submit', $karakter), ['student_note' => 'Sudah dikerjakan.'])->assertRedirect();
        $this->assertDatabaseHas('siswa_karakter_checklist', ['siswa_id' => $siswa->id, 'karakter_id' => $karakter->id, 'verified_by' => null]);

        $this->actingAs($siswa, 'siswa')->post(route('siswa.quran.store'), $this->entryPayload())
            ->assertRedirect()->assertSessionHas('success', 'Catatan bacaan dikirim dan menunggu verifikasi Admin.');
        $this->assertDatabaseHas('quran_reading_entries', ['siswa_id' => $siswa->id, 'status' => QuranReadingEntry::STATUS_PENDING]);

        $this->actingAs($pamong)->get(route('quran.index'))->assertOk()->assertDontSee($siswa->nama);
        $this->actingAs($admin)->get(route('quran.index'))->assertOk()->assertSee($siswa->nama)->assertSee('Antrean Admin');
    }

    public function test_admin_can_disable_alumni_task_and_quran_submissions_without_hiding_history(): void
    {
        $siswa = Siswa::factory()->create(['status' => 'graduated', 'is_active' => true, 'alumni_can_submit' => false]);
        $karakter = Karakter::create(['nama' => 'Tugas Terkunci', 'kategori' => 'harian', 'poin' => 10, 'is_active' => true]);

        $this->actingAs($siswa, 'siswa')->post(route('siswa.tugas-pkg.submit', $karakter))->assertForbidden();
        $this->actingAs($siswa, 'siswa')->post(route('siswa.quran.store'), $this->entryPayload())->assertForbidden();
        $this->actingAs($siswa, 'siswa')->get(route('siswa.quran.index'))
            ->assertOk()->assertSee('Pengiriman dinonaktifkan')->assertDontSee('Catat Bacaan');
    }

    private function entryPayload(): array
    {
        return ['reading_date' => now()->toDateString(), 'page_start' => 1, 'page_end' => 2, 'surah_start' => 1, 'ayah_start' => 1, 'surah_end' => 1, 'ayah_end' => 7];
    }

    private function admin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => User::ROLE_ADMIN], ['display_name' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function pamong(): User
    {
        $role = Role::query()->firstOrCreate(['name' => User::ROLE_TEACHER], ['display_name' => 'Pamong', 'permissions' => [], 'is_active' => true]);
        $pamong = User::factory()->create(['role_id' => $role->id]);
        PamongPermission::create(['user_id' => $pamong->id, 'menu_permissions' => ['dashboard', 'tracer_bacaan_quran'], 'crud_permissions' => ['tracer_bacaan_quran' => ['view', 'create', 'verify', 'export']]]);
        return $pamong;
    }
}
