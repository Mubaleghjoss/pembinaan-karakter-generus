<?php

namespace Tests\Feature;

use App\Models\Karakter;
use App\Models\PamongSiswa;
use App\Models\Presensi;
use App\Models\Role;
use App\Models\ScheduleReminder;
use App\Models\Siswa;
use App\Models\TracerKarakter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CalendarApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_requires_authentication(): void
    {
        $this->getJson('/api/v1/calendar/events?start=2026-09-01&end=2026-09-30')
            ->assertUnauthorized();
    }

    public function test_staff_receives_standard_calendar_envelope(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/calendar/events?start=2026-09-01&end=2026-09-30')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.actor', 'staff')
            ->assertJsonPath('meta.scope', 'semua')
            ->assertJsonPath('meta.start', '2026-09-01')
            ->assertJsonPath('meta.end', '2026-09-30')
            ->assertJsonStructure(['data']);
    }

    public function test_student_and_parent_tokens_receive_their_own_actor_scope(): void
    {
        $siswa = Siswa::factory()->create();

        Sanctum::actingAs($siswa, ['siswa']);
        $this->getJson('/api/v1/calendar/events?start=2026-09-01&end=2026-09-30')
            ->assertOk()
            ->assertJsonPath('meta.actor', 'siswa')
            ->assertJsonPath('meta.scope', 'sendiri');

        Sanctum::actingAs($siswa, ['ortu']);
        $this->getJson('/api/v1/calendar/events?start=2026-09-01&end=2026-09-30')
            ->assertOk()
            ->assertJsonPath('meta.actor', 'ortu')
            ->assertJsonPath('meta.scope', 'anak');
    }

    public function test_student_and_parent_receive_only_the_child_attendance_event(): void
    {
        $child = Siswa::factory()->create(['nama' => 'Anak Sendiri']);
        $other = Siswa::factory()->create(['nama' => 'Anak Lain']);
        Presensi::factory()->create([
            'siswa_id' => $child->id,
            'tanggal' => '2026-09-10',
            'status' => 'terlambat',
        ]);
        Presensi::factory()->create([
            'siswa_id' => $other->id,
            'tanggal' => '2026-09-10',
            'status' => 'alpha',
        ]);

        foreach (['siswa', 'ortu'] as $ability) {
            Sanctum::actingAs($child, [$ability]);
            $response = $this->getJson('/api/v1/calendar/events?start=2026-09-01&end=2026-09-30')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.type', 'presensi')
                ->assertJsonPath('data.0.title', 'Presensi: Terlambat')
                ->assertJsonMissing(['title' => 'Presensi: Alpha']);

            $this->assertArrayNotHasKey('url', $response->json('data.0.details'));
        }
    }

    public function test_teacher_attendance_summary_is_limited_to_active_assignments(): void
    {
        $teacher = $this->teacher();
        $assigned = Siswa::factory()->create();
        $outside = Siswa::factory()->create();
        $ended = Siswa::factory()->create();
        PamongSiswa::query()->create(['pamong_id' => $teacher->id, 'siswa_id' => $assigned->id]);
        PamongSiswa::query()->create([
            'pamong_id' => $teacher->id,
            'siswa_id' => $ended->id,
            'ended_at' => now()->subDay(),
        ]);
        foreach ([$assigned, $outside, $ended] as $siswa) {
            Presensi::factory()->create([
                'siswa_id' => $siswa->id,
                'tanggal' => '2026-09-11',
                'status' => 'hadir',
            ]);
        }

        Sanctum::actingAs($teacher);
        $this->getJson('/api/v1/calendar/events?start=2026-09-01&end=2026-09-30')
            ->assertOk()
            ->assertJsonPath('meta.scope', 'binaan')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'presensi_summary')
            ->assertJsonPath('data.0.details.total', 1)
            ->assertJsonPath('data.0.details.hadir', 1);
    }

    public function test_student_calendar_includes_tasks_and_only_student_audience_schedules(): void
    {
        $siswa = Siswa::factory()->create();
        Karakter::query()->create([
            'nama' => 'Jujur Setiap Hari',
            'kategori' => 'harian',
            'tanggal_mulai' => '2026-09-02',
            'tanggal_selesai' => '2026-09-20',
            'poin' => 10,
            'is_active' => true,
        ]);
        foreach ([
            ['title' => 'Kajian siswa', 'target_audience' => 'siswa'],
            ['title' => 'Agenda semua', 'target_audience' => 'all'],
            ['title' => 'Rapat pamong', 'target_audience' => 'pamong'],
        ] as $schedule) {
            ScheduleReminder::query()->create($schedule + [
                'start_date' => '2026-09-12',
                'is_recurring' => false,
                'is_active' => true,
            ]);
        }

        Sanctum::actingAs($siswa, ['siswa']);
        $response = $this->getJson('/api/v1/calendar/events?start=2026-09-01&end=2026-09-30')
            ->assertOk();

        $types = collect($response->json('data'))->pluck('type');
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($types->contains('pkg_task'));
        $this->assertTrue($types->contains('schedule'));
        $this->assertTrue($titles->contains('Tugas PKG: Jujur Setiap Hari'));
        $this->assertTrue($titles->contains('Kajian siswa'));
        $this->assertTrue($titles->contains('Agenda semua'));
        $this->assertFalse($titles->contains('Rapat pamong'));
        $this->assertFalse(collect($response->json('data'))->contains(
            fn (array $event) => array_key_exists('url', $event['details'] ?? []),
        ));
    }

    public function test_character_events_are_scoped_to_child_or_active_teacher_assignments(): void
    {
        $teacher = $this->teacher();
        $assigned = Siswa::factory()->create();
        $outside = Siswa::factory()->create();
        $character = Karakter::query()->create([
            'nama' => 'Amanah',
            'kategori' => 'harian',
            'poin' => 5,
            'is_active' => true,
        ]);
        PamongSiswa::query()->create(['pamong_id' => $teacher->id, 'siswa_id' => $assigned->id]);
        foreach ([$assigned, $outside] as $siswa) {
            TracerKarakter::query()->create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $character->id,
                'pamong_id' => $teacher->id,
                'checked_at' => '2026-09-15 08:00:00',
            ]);
        }

        Sanctum::actingAs($assigned, ['ortu']);
        $this->getJson('/api/v1/calendar/events?start=2026-09-01&end=2026-09-30')
            ->assertOk()
            ->assertJsonFragment([
                'type' => 'karakter',
                'title' => 'Karakter: 1',
            ]);

        Sanctum::actingAs($teacher);
        $this->getJson('/api/v1/calendar/events?start=2026-09-01&end=2026-09-30')
            ->assertOk()
            ->assertJsonFragment([
                'type' => 'karakter_summary',
                'siswa_count' => 1,
                'total_checks' => 1,
            ]);
    }

    public function test_calendar_rejects_invalid_or_excessive_ranges(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/calendar/events?start=not-a-date&end=2026-09-30')
            ->assertUnprocessable();
        $this->getJson('/api/v1/calendar/events?start=2026-10-01&end=2026-09-30')
            ->assertUnprocessable();
        $this->getJson('/api/v1/calendar/events?start=2026-01-01&end=2026-06-01')
            ->assertUnprocessable();
    }

    private function teacher(): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => User::ROLE_TEACHER],
            [
                'display_name' => 'Pamong',
                'permissions' => ['view_students'],
                'is_active' => true,
            ],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    private function admin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            [
                'display_name' => 'Administrator',
                'permissions' => ['*'],
                'is_active' => true,
            ],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }
}
