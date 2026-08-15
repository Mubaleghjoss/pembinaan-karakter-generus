<?php

namespace Tests\Feature;

use App\Models\PamongSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PamongAssignmentBoardService;
use App\Support\TargetGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PamongAssignmentBoardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_board_shows_all_active_pamong_and_students_but_excludes_alumni(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');
        $pamongA = $this->user(User::ROLE_TEACHER, 'Mas Afif');
        $pamongB = $this->user(User::ROLE_TEACHER, 'Mas Agil');
        $assigned = $this->student('Generus Terbina');
        $unassigned = $this->student('Generus Belum Dibina');
        $alumni = $this->student('Generus Alumni', 'graduated');
        PamongSiswa::query()->create(['pamong_id' => $pamongA->id, 'siswa_id' => $assigned->id]);

        $response = $this->actingAs($admin)->get(route('pamong.assign.form', $pamongB));

        $response->assertOk()
            ->assertSee('Papan Plotting Generus–Pamong')
            ->assertSee('Mas Afif')
            ->assertSee('Mas Agil')
            ->assertSee('Generus Terbina')
            ->assertSee('Generus Belum Dibina')
            ->assertSee('"focused_pamong_id":'.$pamongB->id, false)
            ->assertDontSee($alumni->nama);
    }

    public function test_batch_update_moves_only_the_source_assignment_and_preserves_other_pamong(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');
        $pamongA = $this->user(User::ROLE_TEACHER, 'Pamong A');
        $pamongB = $this->user(User::ROLE_TEACHER, 'Pamong B');
        $pamongC = $this->user(User::ROLE_TEACHER, 'Pamong C');
        $student = $this->student('Generus Multi Pamong');
        PamongSiswa::query()->create(['pamong_id' => $pamongA->id, 'siswa_id' => $student->id]);
        PamongSiswa::query()->create(['pamong_id' => $pamongC->id, 'siswa_id' => $student->id]);
        $version = app(PamongAssignmentBoardService::class)->version();

        $response = $this->actingAs($admin)->putJson(route('pamong.assignments.board'), [
            'version' => $version,
            'students' => [[
                'siswa_id' => $student->id,
                'pamong_ids' => [$pamongB->id, $pamongC->id],
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('affected_students', 1)
            ->assertJsonPath('added', 1)
            ->assertJsonPath('ended', 1);
        $this->assertNotNull(PamongSiswa::query()
            ->where('pamong_id', $pamongA->id)
            ->where('siswa_id', $student->id)
            ->value('ended_at'));
        $this->assertSame($admin->id, PamongSiswa::query()
            ->where('pamong_id', $pamongA->id)
            ->where('siswa_id', $student->id)
            ->value('ended_by'));
        $this->assertDatabaseHas('pamong_siswa', [
            'pamong_id' => $pamongB->id,
            'siswa_id' => $student->id,
            'ended_at' => null,
        ]);
        $this->assertDatabaseHas('pamong_siswa', [
            'pamong_id' => $pamongC->id,
            'siswa_id' => $student->id,
            'ended_at' => null,
        ]);
    }

    public function test_batch_update_can_assign_unassigned_student_and_reactivate_history(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');
        $pamong = $this->user(User::ROLE_TEACHER, 'Pamong Aktif');
        $student = $this->student('Generus Kembali Dibina');
        PamongSiswa::query()->create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $student->id,
            'ended_at' => now()->subDay(),
            'ended_by' => $admin->id,
        ]);
        $version = app(PamongAssignmentBoardService::class)->version();

        $this->actingAs($admin)->putJson(route('pamong.assignments.board'), [
            'version' => $version,
            'students' => [[
                'siswa_id' => $student->id,
                'pamong_ids' => [$pamong->id],
            ]],
        ])->assertOk()->assertJsonPath('added', 1);

        $this->assertDatabaseCount('pamong_siswa', 1);
        $this->assertDatabaseHas('pamong_siswa', [
            'pamong_id' => $pamong->id,
            'siswa_id' => $student->id,
            'ended_at' => null,
            'ended_by' => null,
        ]);
    }

    public function test_stale_board_version_returns_conflict_without_overwriting_assignments(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');
        $pamongA = $this->user(User::ROLE_TEACHER, 'Pamong A');
        $pamongB = $this->user(User::ROLE_TEACHER, 'Pamong B');
        $student = $this->student('Generus Konflik');
        $otherStudent = $this->student('Generus Perubahan Lain');
        PamongSiswa::query()->create(['pamong_id' => $pamongA->id, 'siswa_id' => $student->id]);
        $staleVersion = app(PamongAssignmentBoardService::class)->version();
        PamongSiswa::query()->create(['pamong_id' => $pamongA->id, 'siswa_id' => $otherStudent->id]);

        $this->actingAs($admin)->putJson(route('pamong.assignments.board'), [
            'version' => $staleVersion,
            'students' => [[
                'siswa_id' => $student->id,
                'pamong_ids' => [$pamongB->id],
            ]],
        ])->assertConflict()->assertJsonPath('success', false);

        $this->assertDatabaseHas('pamong_siswa', [
            'pamong_id' => $pamongA->id,
            'siswa_id' => $student->id,
            'ended_at' => null,
        ]);
        $this->assertDatabaseMissing('pamong_siswa', [
            'pamong_id' => $pamongB->id,
            'siswa_id' => $student->id,
        ]);
    }

    public function test_non_admin_cannot_open_or_update_assignment_board(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'Administrator');
        $pamong = $this->user(User::ROLE_TEACHER, 'Pamong Terbatas');
        $student = $this->student('Generus Terbatas');
        $version = app(PamongAssignmentBoardService::class)->version();

        $this->actingAs($pamong)
            ->get(route('pamong.assign.form', $pamong))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($pamong)->putJson(route('pamong.assignments.board'), [
            'version' => $version,
            'students' => [[
                'siswa_id' => $student->id,
                'pamong_ids' => [$pamong->id],
            ]],
        ])->assertForbidden();
        $this->actingAs($admin)->putJson(route('pamong.assignments.board'), [
            'version' => $version,
            'students' => array_fill(0, 101, [
                'siswa_id' => $student->id,
                'pamong_ids' => [$pamong->id],
            ]),
        ])->assertUnprocessable()->assertJsonValidationErrors('students');
    }

    private function user(string $roleName, string $name): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            [
                'display_name' => $name,
                'permissions' => $roleName === User::ROLE_ADMIN ? ['*'] : [],
                'is_active' => true,
            ]
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function student(string $name, string $status = 'active'): Siswa
    {
        return Siswa::factory()->create([
            'nama' => $name,
            'status' => $status,
            'is_active' => true,
            'school_grade' => TargetGrade::SMP_8,
            'kelompok' => Siswa::KELOMPOK_SAWAH_DALAM_1,
        ]);
    }
}
