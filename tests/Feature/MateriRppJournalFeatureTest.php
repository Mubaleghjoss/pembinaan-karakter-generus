<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\MateriRppJournal;
use App\Models\MateriRppJournalAssignee;
use App\Models\Role;
use App\Models\ScheduleReminder;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MateriRppJournalFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-20 22:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_create_and_update_journal_for_rpp_calendar_event(): void
    {
        $admin = $this->adminUser();
        [$materi, $schedule] = $this->rppSchedule($admin);

        $form = $this->actingAs($admin)->get(route('materi-rpp-journals.schedule', $schedule));

        $form->assertOk()
            ->assertSee('Isi Jurnal RPP')
            ->assertSee($materi->judul)
            ->assertSee('Halaman 1-2');

        $create = $this->actingAs($admin)->post(route('materi-rpp-journals.schedule.store', $schedule), [
            'realization_status' => MateriRppJournal::STATUS_SEBAGIAN,
            'actual_page_start' => 1,
            'actual_page_end' => 1,
            'notes' => 'Peserta perlu penguatan makna.',
            'obstacles' => 'Waktu diskusi kurang.',
            'follow_up' => 'Ulangi halaman 2 pekan depan.',
        ]);

        $journal = MateriRppJournal::firstOrFail();

        $create->assertRedirect(route('materi-rpp-journals.edit', $journal));
        $this->assertSame(1, MateriRppJournal::count());
        $this->assertSame($schedule->id, $journal->schedule_reminder_id);
        $this->assertSame($materi->id, $journal->materi_id);
        $this->assertSame('Halaman 1-2', $journal->target_page_range);
        $this->assertSame(MateriRppJournal::STATUS_SEBAGIAN, $journal->realization_status);

        $update = $this->actingAs($admin)->post(route('materi-rpp-journals.schedule.store', $schedule), [
            'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
            'actual_page_start' => 1,
            'actual_page_end' => 2,
            'notes' => 'Materi selesai.',
        ]);

        $update->assertRedirect(route('materi-rpp-journals.edit', $journal));
        $this->assertSame(1, MateriRppJournal::count());
        $this->assertSame(MateriRppJournal::STATUS_TERLAKSANA, $journal->refresh()->realization_status);
        $this->assertSame(2, $journal->actual_page_end);
    }

    public function test_pamong_can_create_journal_without_full_materi_access(): void
    {
        $pamong = $this->pamongUser();
        [, $schedule] = $this->rppSchedule($pamong);

        $response = $this->actingAs($pamong)->post(route('materi-rpp-journals.schedule.store', $schedule), [
            'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
            'notes' => 'Jurnal diisi pamong.',
        ]);

        $journal = MateriRppJournal::firstOrFail();

        $response->assertRedirect(route('materi-rpp-journals.edit', $journal));
        $this->assertSame($pamong->id, $journal->created_by);
        $this->assertSame('Jurnal diisi pamong.', $journal->notes);
    }

    public function test_calendar_event_contains_journal_link_and_detail_materi_shows_journal(): void
    {
        $admin = $this->adminUser();
        [$materi, $schedule] = $this->rppSchedule($admin);
        $journal = MateriRppJournal::create([
            'schedule_reminder_id' => $schedule->id,
            'materi_id' => $materi->id,
            'journal_date' => $schedule->start_date,
            'materi_title' => $materi->judul,
            'session_number' => 1,
            'target_page_range' => 'Halaman 1-2',
            'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
            'notes' => 'Catatan jurnal tampil di detail materi.',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $events = $this->actingAs($admin)->getJson(route('calendar.events', [
            'start' => '2026-06-01',
            'end' => '2026-06-30',
        ]));

        $events->assertOk()
            ->assertJsonFragment([
                'journal_id' => $journal->id,
                'journal_button_label' => 'Lihat Jurnal',
                'journal_url' => route('materi-rpp-journals.schedule', $schedule),
            ]);

        $detail = $this->actingAs($admin)->get(route('materi.show', $materi));

        $detail->assertOk()
            ->assertSee('Jurnal RPP')
            ->assertSee('Catatan jurnal tampil di detail materi.')
            ->assertSee(route('materi-rpp-journals.edit', $journal), false);
    }

    public function test_siswa_cannot_access_journal_form(): void
    {
        $admin = $this->adminUser();
        [, $schedule] = $this->rppSchedule($admin);
        $siswa = Siswa::factory()->create();

        $response = $this->actingAs($siswa, 'siswa')->get(route('materi-rpp-journals.schedule', $schedule));

        $response->assertForbidden();
    }

    public function test_assigned_siswa_submits_draft_and_teacher_approves_it(): void
    {
        $teacher = $this->pamongUser();
        [, $schedule] = $this->rppSchedule($teacher);
        $siswa = Siswa::factory()->create();
        $otherSiswa = Siswa::factory()->create();

        $schedule->journalAssignees()->create([
            'assignee_type' => 'siswa',
            'siswa_id' => $siswa->id,
            'assigned_by' => $teacher->id,
        ]);

        $forbidden = $this->actingAs($otherSiswa, 'siswa')->post(
            route('siswa.materi-rpp-journals.store', $schedule),
            [
                'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
                'notes' => 'Bukan tugas saya.',
            ]
        );
        $forbidden->assertForbidden();

        $submit = $this->actingAs($siswa, 'siswa')->post(
            route('siswa.materi-rpp-journals.store', $schedule),
            [
                'realization_status' => MateriRppJournal::STATUS_SEBAGIAN,
                'actual_page_start' => 1,
                'actual_page_end' => 1,
                'notes' => 'Jurnal draf dari siswa.',
            ]
        );

        $submit->assertRedirect(route('siswa.materi-rpp-journals.index'));
        $journal = MateriRppJournal::firstOrFail();
        $this->assertSame(MateriRppJournal::WORKFLOW_PENDING_REVIEW, $journal->workflow_status);
        $this->assertSame($siswa->id, $journal->submitted_by_siswa_id);

        $approve = $this->actingAs($teacher)->patch(route('materi-rpp-journals.review', $journal), [
            'review_action' => 'approve',
        ]);

        $approve->assertRedirect(route('materi-rpp-journals.edit', $journal));
        $this->assertSame(MateriRppJournal::WORKFLOW_APPROVED, $journal->refresh()->workflow_status);

        $locked = $this->actingAs($siswa, 'siswa')->post(
            route('siswa.materi-rpp-journals.store', $schedule),
            [
                'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
                'notes' => 'Tidak boleh diubah lagi.',
            ]
        );
        $locked->assertForbidden();
    }

    public function test_journal_task_is_not_actionable_before_event_ends(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-20 20:30:00'));
        $pamong = $this->pamongUser();
        [, $schedule] = $this->rppSchedule($pamong);

        $response = $this->actingAs($pamong)->post(route('materi-rpp-journals.schedule.store', $schedule), [
            'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
            'notes' => 'Terlalu awal.',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('materi_rpp_journals', 0);
    }

    public function test_assigned_siswa_sees_journal_menu_and_dashboard_task(): void
    {
        $admin = $this->adminUser();
        [$materi, $schedule] = $this->rppSchedule($admin);
        $siswa = Siswa::factory()->create();
        $schedule->journalAssignees()->create([
            'assignee_type' => 'siswa',
            'siswa_id' => $siswa->id,
            'assigned_by' => $admin->id,
        ]);

        $dashboard = $this->actingAs($siswa, 'siswa')->get(route('siswa.dashboard'));
        $dashboard->assertOk()
            ->assertSee('Jurnal RPP')
            ->assertSee('Tugas Jurnal RPP')
            ->assertSee($materi->judul);

        $index = $this->actingAs($siswa, 'siswa')->get(route('siswa.materi-rpp-journals.index'));
        $index->assertOk()
            ->assertSee($materi->judul)
            ->assertSee('Belum Diisi');
    }

    public function test_admin_can_add_multiple_students_beside_main_teacher_until_journal_is_submitted(): void
    {
        $admin = $this->adminUser();
        [, $schedule] = $this->rppSchedule($admin);
        $firstSiswa = Siswa::factory()->create();
        $secondSiswa = Siswa::factory()->create();

        foreach ([$firstSiswa, $secondSiswa] as $siswa) {
            $assign = $this->actingAs($admin)->post(
                route('materi-rpp-journals.schedule.assignees.store', $schedule),
                ['assignee_type' => 'siswa', 'assignee_id' => $siswa->id]
            );

            $assign->assertRedirect(route('materi-rpp-journals.schedule', $schedule));
        }

        $this->assertDatabaseHas('materi_rpp_journal_assignees', [
            'schedule_reminder_id' => $schedule->id,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('materi_rpp_journal_assignees', [
            'schedule_reminder_id' => $schedule->id,
            'siswa_id' => $firstSiswa->id,
        ]);
        $this->assertDatabaseHas('materi_rpp_journal_assignees', [
            'schedule_reminder_id' => $schedule->id,
            'siswa_id' => $secondSiswa->id,
        ]);

        $this->actingAs($firstSiswa, 'siswa')
            ->get(route('siswa.materi-rpp-journals.show', $schedule))
            ->assertOk();
        $this->actingAs($secondSiswa, 'siswa')
            ->get(route('siswa.materi-rpp-journals.show', $schedule))
            ->assertOk();

        $journal = MateriRppJournal::create([
            'schedule_reminder_id' => $schedule->id,
            'materi_id' => $schedule->source_id,
            'journal_date' => $schedule->start_date,
            'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
            'workflow_status' => MateriRppJournal::WORKFLOW_PENDING_REVIEW,
            'submitted_by_siswa_id' => $firstSiswa->id,
        ]);

        $secondAssignment = MateriRppJournalAssignee::query()
            ->where('schedule_reminder_id', $schedule->id)
            ->where('siswa_id', $secondSiswa->id)
            ->firstOrFail();

        $remove = $this->actingAs($admin)
            ->from(route('materi-rpp-journals.schedule', $schedule))
            ->delete(route('materi-rpp-journals.schedule.assignees.destroy', [$schedule, $secondAssignment]));

        $remove->assertRedirect(route('materi-rpp-journals.schedule', $schedule));
        $remove->assertSessionHasErrors('assignee_id');
        $this->assertDatabaseHas('materi_rpp_journal_assignees', ['id' => $secondAssignment->id]);
        $this->assertSame(MateriRppJournal::WORKFLOW_PENDING_REVIEW, $journal->refresh()->workflow_status);
    }

    public function test_admin_can_export_journal_excel_using_page_filters(): void
    {
        $admin = $this->adminUser();
        [, $schedule] = $this->rppSchedule($admin);
        MateriRppJournal::create([
            'schedule_reminder_id' => $schedule->id,
            'materi_id' => $schedule->source_id,
            'journal_date' => $schedule->start_date,
            'materi_title' => $schedule->title,
            'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
            'workflow_status' => MateriRppJournal::WORKFLOW_APPROVED,
            'notes' => 'Data untuk ekspor.',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('materi-rpp-journals.export', [
            'month' => '2026-06',
            'materi_id' => $schedule->source_id,
            'workflow_status' => MateriRppJournal::WORKFLOW_APPROVED,
        ]));

        $response->assertOk()
            ->assertDownload('jurnal-rpp-2026-06.xlsx');
        $this->assertSame('PK', substr($response->streamedContent(), 0, 2));
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

    private function rppSchedule(User $creator): array
    {
        $materi = Materi::create([
            'judul' => 'Makna Al Quran Juz 1',
            'deskripsi' => 'Materi RPP test.',
            'bulan' => '2026-06-01',
            'is_active' => true,
            'rpp_is_enabled' => true,
            'rpp_status' => 'published',
            'created_by' => $creator->id,
        ]);

        $schedule = ScheduleReminder::create([
            'title' => 'RPP: Makna Al Quran Juz 1',
            'description' => 'Pertemuan 1; Halaman 1-2',
            'start_date' => '2026-06-20',
            'start_time' => '20:00:00',
            'end_time' => '21:00:00',
            'target_audience' => 'all',
            'is_recurring' => false,
            'color' => '#14B8A6',
            'is_active' => true,
            'created_by' => $creator->id,
            'source_type' => ScheduleReminder::SOURCE_MATERI_RPP,
            'source_id' => $materi->id,
            'source_payload' => [
                'number' => 1,
                'type' => 'regular',
                'materi_title' => $materi->judul,
                'page_range' => 'Halaman 1-2',
                'page_start' => 1,
                'page_end' => 2,
                'teacher_name' => 'MAS AFIF',
                'teacher_user_id' => $creator->id,
            ],
            'journal_assignee_type' => 'user',
            'journal_assignee_user_id' => $creator->id,
        ]);

        $schedule->journalAssignees()->create([
            'assignee_type' => 'user',
            'user_id' => $creator->id,
            'assigned_by' => $creator->id,
        ]);

        return [$materi, $schedule];
    }
}
