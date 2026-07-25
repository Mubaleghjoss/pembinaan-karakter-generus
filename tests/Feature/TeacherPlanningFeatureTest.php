<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TeacherAvailabilityInvite;
use App\Models\TeacherProfile;
use App\Models\TeacherSchedulePeriod;
use App\Models\TeacherScheduleSession;
use App\Models\TeacherScheduleTemplate;
use App\Models\User;
use App\Services\TeacherSchedulePlanner;
use App\Support\ParticipantProfileOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherPlanningFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_private_teacher_form_requires_code_and_stores_normalized_profile(): void
    {
        TeacherAvailabilityInvite::create([
            'label' => 'Pendataan Guru',
            'token_hash' => hash('sha256', 'GURU2026'),
            'max_uses' => 100,
            'used_count' => 0,
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $this->get('/pendataanguru')
            ->assertOk()
            ->assertSee('Kode Akses')
            ->assertDontSee('Nama lengkap');

        $this->post('/pendataanguru/akses', ['access_code' => 'guru2026'])
            ->assertRedirect('/pendataanguru');

        $this->get('/pendataanguru')
            ->assertOk()
            ->assertSee('Formulir Kesediaan MT/MS')
            ->assertSee('Pastikan tidak typo');

        $this->post('/pendataanguru', $this->profilePayload())
            ->assertRedirect(route('public.teacher-availability.success'));

        $teacherProfile = TeacherProfile::query()->firstOrFail();
        $this->assertDatabaseHas('teacher_profiles', [
            'name' => 'Ahmad Fulan',
            'whatsapp_normalized' => '6281234567890',
            'participation_role' => TeacherProfile::ROLE_BOTH,
            'monthly_limit' => 2,
        ]);
        $this->assertNotNull($teacherProfile->signature_path);
        $this->assertNotNull($teacherProfile->document_token_hash);
        Storage::disk('local')->assertExists($teacherProfile->signature_path);
        $this->assertSame(1, TeacherAvailabilityInvite::firstOrFail()->used_count);

        $downloadToken = session('teacher_availability.download_token');
        $this->assertIsString($downloadToken);

        $this->get(route('public.teacher-availability.success'))
            ->assertOk()
            ->assertSee('Surat pernyataan kesediaan sudah dibuat.')
            ->assertSee('Unduh PDF Surat Pernyataan');

        $this->get(route('public.teacher-availability.pdf', [$teacherProfile, 'token-yang-salah']))
            ->assertNotFound();

        $this->get(route('public.teacher-availability.pdf', [$teacherProfile, $downloadToken]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="surat-kesediaan-guru-ahmad-fulan.pdf"');

        $this->actingAs($this->admin())
            ->get(route('teacher-planning.profiles.statement.preview', $teacherProfile))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="surat-kesediaan-guru-ahmad-fulan.pdf"');
    }

    public function test_duplicate_whatsapp_cannot_create_second_profile(): void
    {
        $invite = TeacherAvailabilityInvite::create([
            'label' => 'Pendataan Guru',
            'token_hash' => hash('sha256', 'GURU2026'),
            'max_uses' => 100,
            'used_count' => 0,
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);
        TeacherProfile::create($this->profileAttributes($invite->id));

        $this->post('/pendataanguru/akses', ['access_code' => 'GURU2026']);
        $this->post('/pendataanguru', $this->profilePayload())
            ->assertSessionHasErrors('whatsapp');

        $this->assertSame(1, TeacherProfile::count());
    }

    public function test_monthly_generator_balances_main_and_backup_within_limits(): void
    {
        $admin = $this->admin();
        $profiles = collect(range(1, 5))->map(fn (int $index) => TeacherProfile::create([
            ...$this->profileAttributes(),
            'name' => "Guru {$index}",
            'public_name' => "Guru {$index}",
            'whatsapp' => "08123456789{$index}",
            'whatsapp_normalized' => "628123456789{$index}",
        ]));
        TeacherScheduleTemplate::create([
            'weekday' => 'monday',
            'rombel' => 'smp',
            'start_time' => '20:00',
            'end_time' => '21:30',
            'is_active' => true,
        ]);
        $period = TeacherSchedulePeriod::create([
            'month' => '2026-08-01',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $period = app(TeacherSchedulePlanner::class)->generate($period, $admin->id);

        $this->assertCount(5, $period->sessions);
        $this->assertSame(10, $period->sessions->flatMap->assignments->count());
        foreach ($period->sessions as $session) {
            $this->assertCount(2, $session->assignments);
            $this->assertNotSame(
                $session->assignments->firstWhere('role', 'main')->teacher_profile_id,
                $session->assignments->firstWhere('role', 'backup')->teacher_profile_id
            );
        }
        foreach ($profiles as $profile) {
            $this->assertLessThanOrEqual(2, app(TeacherSchedulePlanner::class)->monthlyLoad($profile, $period));
        }
    }

    public function test_generate_without_active_template_returns_to_management_page_with_clear_error(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('teacher-planning.index', ['month' => '2026-08']))
            ->post(route('teacher-planning.generate'), ['month' => '2026-08'])
            ->assertRedirect(route('teacher-planning.index', ['month' => '2026-08']))
            ->assertSessionHasErrors('templates');

        $this->assertDatabaseCount('teacher_schedule_periods', 0);

        $this->actingAs($admin)
            ->get(route('teacher-planning.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('Jadwal belum bisa dibuat karena belum ada Template Slot Mingguan aktif.')
            ->assertSee('Buat Jadwal Bulanan');
    }

    public function test_confirmation_and_public_calendar_use_private_token_and_public_name(): void
    {
        $admin = $this->admin();
        $profile = TeacherProfile::create([
            ...$this->profileAttributes(),
            'name' => 'Ahmad Fulan Panunggangan',
            'public_name' => 'Ahmad',
        ]);
        $period = TeacherSchedulePeriod::create([
            'month' => now()->startOfMonth()->toDateString(),
            'status' => 'published',
            'created_by' => $admin->id,
            'published_by' => $admin->id,
            'published_at' => now(),
        ]);
        $session = TeacherScheduleSession::create([
            'period_id' => $period->id,
            'session_date' => now()->addDays(5)->toDateString(),
            'rombel' => 'smp',
            'start_time' => '20:00',
            'end_time' => '21:30',
            'status' => 'scheduled',
        ]);
        $assignment = app(TeacherSchedulePlanner::class)
            ->createAssignment($session, $profile, 'main', 'manual', true, $admin->id);
        $token = Crypt::decryptString($assignment->confirmation_token_encrypted);

        $this->get(route('public.teacher-confirmation.show', $token))
            ->assertOk()
            ->assertSee('Ahmad Fulan Panunggangan')
            ->assertSee('Pengajar Utama');

        $this->post(route('public.teacher-confirmation.store', $token), [
            'status' => 'confirmed',
            'note' => 'Insyaallah hadir.',
        ])->assertRedirect();

        $this->assertDatabaseHas('teacher_schedule_assignments', [
            'id' => $assignment->id,
            'confirmation_status' => 'confirmed',
            'confirmation_note' => 'Insyaallah hadir.',
        ]);

        $response = $this->getJson(route('public.calendar.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
        ]))->assertOk();
        $event = collect($response->json())->firstWhere('type', 'teacher_schedule');

        $this->assertNotNull($event);
        $this->assertStringContainsString('Ahmad', $event['title']);
        $this->assertStringNotContainsString('Fulan Panunggangan', $event['title']);
        $this->assertArrayNotHasKey('whatsapp', $event['extendedProps']);
    }

    public function test_admin_can_open_management_page_and_form_link_is_not_in_public_navigation(): void
    {
        $admin = $this->admin();
        $legacyProfile = TeacherProfile::create($this->profileAttributes());

        $this->actingAs($admin)
            ->get(route('teacher-planning.index'))
            ->assertOk()
            ->assertSee('Pendataan &amp; Jadwal Guru', false)
            ->assertSee('Kode Akses Formulir')
            ->assertSee('Data lama ini belum memiliki tanda tangan.')
            ->assertSee('Lihat PDF');

        $this->actingAs($admin)
            ->get(route('teacher-planning.profiles.statement.preview', $legacyProfile))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $navigation = file_get_contents(resource_path('views/layouts/public.blade.php'));
        $this->assertStringNotContainsString('pendataanguru', $navigation);
    }

    public function test_admin_can_customize_teacher_form_success_message(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('teacher-planning.success-message.update'), [
                'success_title' => 'Data Anda Berhasil Dikirim',
                'success_message' => "Terima kasih sudah mengisi.\nPengurus akan segera menghubungi Anda.",
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('public.teacher-availability.success'))
            ->assertOk()
            ->assertSee('Data Anda Berhasil Dikirim')
            ->assertSee('Terima kasih sudah mengisi.')
            ->assertSee('Pengurus akan segera menghubungi Anda.');

        $this->actingAs($admin)
            ->get(route('teacher-planning.index'))
            ->assertOk()
            ->assertSee('Pesan Setelah Formulir Terkirim')
            ->assertSee('Data Anda Berhasil Dikirim');
    }

    public function test_admin_can_delete_unassigned_teacher_profile_and_its_private_signature(): void
    {
        $admin = $this->admin();
        $invite = TeacherAvailabilityInvite::create([
            'label' => 'Pendataan Guru',
            'token_hash' => hash('sha256', 'DELETE01'),
            'max_uses' => 10,
            'used_count' => 1,
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);
        $signaturePath = 'teacher-statements/delete-test/tanda-tangan.png';
        Storage::disk('local')->put($signaturePath, 'signature');
        $teacherProfile = TeacherProfile::create([
            ...$this->profileAttributes($invite->id),
            'signature_path' => $signaturePath,
        ]);

        $this->actingAs($admin)
            ->delete(route('teacher-planning.profiles.destroy', $teacherProfile))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('teacher_profiles', ['id' => $teacherProfile->id]);
        $this->assertSame(0, $invite->fresh()->used_count);
        Storage::disk('local')->assertMissing($signaturePath);
    }

    public function test_admin_cannot_delete_teacher_profile_that_is_still_assigned(): void
    {
        $admin = $this->admin();
        $teacherProfile = TeacherProfile::create($this->profileAttributes());
        $period = TeacherSchedulePeriod::create([
            'month' => now()->startOfMonth()->toDateString(),
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $session = TeacherScheduleSession::create([
            'period_id' => $period->id,
            'session_date' => now()->addDays(5)->toDateString(),
            'rombel' => 'smp',
            'start_time' => '20:00',
            'end_time' => '21:30',
            'status' => 'scheduled',
        ]);
        app(TeacherSchedulePlanner::class)
            ->createAssignment($session, $teacherProfile, 'main', 'manual', true, $admin->id);

        $this->actingAs($admin)
            ->from(route('teacher-planning.index'))
            ->delete(route('teacher-planning.profiles.destroy', $teacherProfile))
            ->assertRedirect(route('teacher-planning.index'))
            ->assertSessionHasErrors('teacher_profile');

        $this->assertDatabaseHas('teacher_profiles', ['id' => $teacherProfile->id]);
    }

    public function test_admin_can_publish_incomplete_schedule_with_acknowledgement_and_export_it(): void
    {
        $admin = $this->admin();
        $period = TeacherSchedulePeriod::create([
            'month' => '2026-09-01',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        TeacherScheduleSession::create([
            'period_id' => $period->id,
            'session_date' => '2026-09-07',
            'rombel' => 'smp',
            'start_time' => '20:00',
            'end_time' => '21:30',
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin)
            ->patch(route('teacher-planning.periods.publish', $period))
            ->assertSessionHasErrors('warning_acknowledgement');

        $this->actingAs($admin)
            ->patch(route('teacher-planning.periods.publish', $period), [
                'warning_acknowledgement' => 'Tetap diterbitkan sambil mencari pengajar.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('teacher_schedule_periods', [
            'id' => $period->id,
            'status' => 'published',
            'publish_warning_acknowledgement' => 'Tetap diterbitkan sambil mencari pengajar.',
        ]);

        $this->actingAs($admin)
            ->get(route('teacher-planning.export.pdf', $period))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('teacher-planning.export.image', $period))
            ->assertOk()
            ->assertSee('scheduleCanvas', false);
    }

    private function profilePayload(): array
    {
        return [
            'name' => 'Ahmad Fulan',
            'kelompok' => ParticipantProfileOptions::SAWAH_DALAM_1,
            'whatsapp' => '0812-3456-7890',
            'participation_role' => TeacherProfile::ROLE_BOTH,
            'rombels' => ['smp', 'sma'],
            'available_nights' => ['monday', 'friday'],
            'night_priorities' => ['monday' => '1', 'friday' => '2'],
            'monthly_limit' => '2',
            'competencies' => ['quran', 'class_support'],
            'material_readiness' => 'ready',
            'backup_contact_preference' => 'ready',
            'constraints' => 'Tidak bisa tanggal merah.',
            'signature' => $this->signature(),
            'consent' => '1',
        ];
    }

    private function profileAttributes(?int $inviteId = null): array
    {
        return [
            'invite_id' => $inviteId,
            'name' => 'Ahmad Fulan',
            'public_name' => 'Ahmad',
            'kelompok' => ParticipantProfileOptions::SAWAH_DALAM_1,
            'whatsapp' => '081234567890',
            'whatsapp_normalized' => '6281234567890',
            'participation_role' => TeacherProfile::ROLE_BOTH,
            'rombels' => ['smp'],
            'available_nights' => ['monday'],
            'night_priorities' => ['monday' => 1],
            'monthly_limit' => 2,
            'competencies' => ['quran'],
            'material_readiness' => 'ready',
            'backup_contact_preference' => 'ready',
            'constraints' => null,
            'consent_version' => 'v1',
            'consented_at' => now(),
            'submitted_at' => now(),
            'is_active' => true,
        ];
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'description' => 'Administrator']
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
    }

    private function signature(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }
}
