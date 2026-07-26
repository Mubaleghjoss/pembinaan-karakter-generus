<?php

namespace Tests\Feature;

use App\Models\PwaNotificationDelivery;
use App\Models\Role;
use App\Models\Setting;
use App\Models\TeacherMaterial;
use App\Models\TeacherProfile;
use App\Models\TeacherSchedulePeriod;
use App\Models\TeacherScheduleRequest;
use App\Models\TeacherScheduleSession;
use App\Models\User;
use App\Notifications\TaskBadgeWebPushNotification;
use App\Services\TeacherSchedulePlanner;
use App\Services\TeacherSchedulePwaNotificationService;
use App\Support\ParticipantProfileOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherPortalFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_admin_creates_guru_account_with_one_time_password_and_first_login_change(): void
    {
        $admin = $this->admin();
        $profile = $this->profile();

        $response = $this->actingAs($admin)
            ->post(route('teacher-planning.profiles.account.store', $profile))
            ->assertRedirect()
            ->assertSessionHas('teacher_credentials');

        $credentials = $response->getSession()->get('teacher_credentials');
        $profile->refresh();
        $guru = $profile->user()->with('role')->firstOrFail();

        $this->assertSame(User::ROLE_GURU, $guru->role->name);
        $this->assertStringStartsWith('guru.ahmad.fulan', $guru->username);
        $this->assertSame(12, strlen($credentials['password']));
        $this->assertTrue(Hash::check($credentials['password'], $guru->password));
        $this->assertTrue($guru->must_change_password);
        $this->assertNull($guru->plain_password);

        auth()->logout();
        $this->post(route('login.post'), [
            'login' => $guru->username,
            'password' => $credentials['password'],
        ])->assertRedirect(route('guru.password.initial'));

        $this->put(route('guru.password.initial.update'), [
            'password' => 'Password-Baru-2026',
            'password_confirmation' => 'Password-Baru-2026',
        ])->assertRedirect(route('guru.dashboard'));

        $this->assertFalse($guru->fresh()->must_change_password);
        $this->assertNotNull($guru->fresh()->password_changed_at);
    }

    public function test_guru_is_isolated_from_operational_routes_and_only_sees_own_published_schedule(): void
    {
        [$guru, $profile] = $this->guru();
        [, $otherProfile] = $this->guru('Siti Guru', '6281234567891');
        $admin = $this->admin();
        $published = $this->period($admin, 'published');
        $draft = $this->period($admin, 'draft', now()->addMonth()->startOfMonth()->toDateString());
        $own = $this->assignment($published, $profile, 'main', now()->addDays(4));
        $this->assignment($published, $otherProfile, 'main', now()->addDays(5), 'sma');
        $this->assignment($draft, $profile, 'backup', now()->addMonth()->addDays(2), 'pranikah');

        $this->actingAs($guru)
            ->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee('Jadwal terdekat');

        $this->actingAs($guru)
            ->get(route('guru.schedule'))
            ->assertOk()
            ->assertSee($own->session->session_date->translatedFormat('l, d F Y'))
            ->assertDontSee('PRANIKAH');

        $this->actingAs($guru)
            ->get(route('guru.schedule.show', $own))
            ->assertOk()
            ->assertSee('Bahan ajar sesi');

        $this->actingAs($guru)
            ->get(route('guru.profile'))
            ->assertOk()
            ->assertSee('Kesediaan Mengajar');

        $this->actingAs($guru)
            ->get(route('guru.schedule.show', $this->assignment($published, $otherProfile, 'backup', now()->addDays(6), 'sma')))
            ->assertNotFound();

        $this->actingAs($guru)
            ->get(route('dashboard'))
            ->assertRedirect(route('guru.dashboard'));

        $this->actingAs($guru)
            ->post(route('teacher-materials.store'), [])
            ->assertForbidden();
    }

    public function test_material_library_validates_google_domain_and_portal_filters_by_rombel_or_session(): void
    {
        $admin = $this->admin();
        [$guru, $profile] = $this->guru();
        $period = $this->period($admin, 'published');
        $assignment = $this->assignment($period, $profile, 'main', now()->addDays(3));

        $this->actingAs($admin)
            ->from(route('teacher-materials.index'))
            ->post(route('teacher-materials.store'), [
                'title' => 'Tautan Tidak Aman',
                'google_drive_url' => 'https://example.com/file.pdf',
                'is_active' => '1',
            ])
            ->assertRedirect(route('teacher-materials.index'))
            ->assertSessionHasErrors('google_drive_url');

        $smp = TeacherMaterial::create([
            'title' => 'Materi SMP',
            'google_drive_url' => 'https://drive.google.com/file/d/smp/view',
            'rombels' => ['smp'],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $sma = TeacherMaterial::create([
            'title' => 'Materi SMA Khusus Sesi',
            'google_drive_url' => 'https://docs.google.com/presentation/d/sma/edit',
            'rombels' => ['sma'],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $hidden = TeacherMaterial::create([
            'title' => 'Materi Pranikah',
            'google_drive_url' => 'https://drive.google.com/file/d/pranikah/view',
            'rombels' => ['pranikah'],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $assignment->session->materials()->sync([$sma->id]);

        $this->actingAs($guru)
            ->get(route('guru.materials'))
            ->assertOk()
            ->assertSee($smp->title)
            ->assertSee($sma->title)
            ->assertDontSee($hidden->title);
    }

    public function test_availability_update_does_not_change_existing_assignment(): void
    {
        $admin = $this->admin();
        [$guru, $profile] = $this->guru();
        $assignment = $this->assignment($this->period($admin, 'published'), $profile, 'main', now()->addDays(4));

        $this->actingAs($guru)
            ->put(route('guru.availability.update'), [
                'participation_role' => TeacherProfile::ROLE_BACKUP,
                'rombels' => ['sma'],
                'available_nights' => ['friday'],
                'night_priorities' => ['friday' => 1],
                'monthly_limit' => 1,
                'competencies' => ['practice'],
                'material_readiness' => 'ready',
                'backup_contact_preference' => 'one_day_notice',
                'constraints' => 'Tidak dapat dua malam berturut-turut.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('main', $assignment->fresh()->role);
        $this->assertSame($profile->id, $assignment->fresh()->teacher_profile_id);
        $this->assertSame(TeacherProfile::ROLE_BACKUP, $profile->fresh()->participation_role);
        $this->assertSame(['sma'], $profile->fresh()->rombels);
    }

    public function test_publish_h3_and_h1_push_are_personal_and_deduplicated(): void
    {
        Notification::fake();
        $admin = $this->admin();
        [$guru, $profile] = $this->guru();
        $guru->updatePushSubscription('https://push.example.test/guru-1', 'public-key', 'auth-token', 'aes128gcm');
        $period = $this->period($admin, 'draft');
        $assignment = $this->assignment($period, $profile, 'main', now()->addDays(3));
        $material = TeacherMaterial::create([
            'title' => 'Materi Sesi',
            'google_drive_url' => 'https://drive.google.com/file/d/materi/view',
            'rombels' => ['smp'],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $assignment->session->materials()->attach($material);

        $this->actingAs($admin)
            ->patch(route('teacher-planning.periods.publish', $period), [
                'warning_acknowledgement' => 'Cadangan sedang dilengkapi.',
            ])
            ->assertRedirect();

        $notifications = app(TeacherSchedulePwaNotificationService::class);
        $this->assertSame(1, $notifications->notifyDue(today()));
        $this->assertSame(0, $notifications->notifyDue(today()));

        Notification::assertSentTo($guru, TaskBadgeWebPushNotification::class, 2);
        $this->assertSame(2, PwaNotificationDelivery::query()->where('notifiable_id', $guru->id)->count());

        $assignment->session->update(['session_date' => now()->addDay()->toDateString()]);
        $this->assertSame(1, $notifications->notifyDue(today()));
        Notification::assertSentTo($guru, TaskBadgeWebPushNotification::class, 3);
    }

    public function test_guru_can_confirm_and_submit_schedule_request_to_configured_admin_whatsapp(): void
    {
        $admin = $this->admin();
        [$guru, $profile] = $this->guru();
        [, $otherProfile] = $this->guru('Guru Lain', '6281234567891');
        $period = $this->period($admin, 'published');
        $assignment = $this->assignment($period, $profile, 'main', now()->addDays(4));
        $otherAssignment = $this->assignment($period, $otherProfile, 'backup', now()->addDays(5), 'sma');

        $this->actingAs($admin)
            ->put(route('teacher-planning.admin-contact.update'), [
                'admin_whatsapp' => '0812-9999-8877',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('6281299998877', Setting::get(Setting::TEACHER_ADMIN_WHATSAPP_KEY));

        $this->actingAs($guru)
            ->patch(route('guru.schedule.confirm', $assignment), [
                'status' => 'confirmed',
                'note' => 'Insyaallah hadir.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('teacher_schedule_assignments', [
            'id' => $assignment->id,
            'confirmation_status' => 'confirmed',
            'confirmation_note' => 'Insyaallah hadir.',
        ]);

        $response = $this->actingAs($guru)
            ->post(route('guru.schedule.request', $assignment), [
                'request_type' => TeacherScheduleRequest::TYPE_RESCHEDULE,
                'reason' => 'Mohon digeser ke sesi Jumat karena ada kegiatan keluarga.',
            ])
            ->assertRedirect();

        $this->assertStringStartsWith(
            'https://wa.me/6281299998877?text=',
            (string) $response->headers->get('Location')
        );
        $scheduleRequest = TeacherScheduleRequest::query()->firstOrFail();
        $this->assertSame($assignment->id, $scheduleRequest->assignment_id);
        $this->assertSame(TeacherScheduleRequest::STATUS_PENDING, $scheduleRequest->status);

        $this->actingAs($admin)
            ->patch(route('teacher-planning.requests.status', $scheduleRequest), [
                'status' => TeacherScheduleRequest::STATUS_APPROVED,
                'admin_note' => 'Admin akan menukar jadwal.',
            ])
            ->assertRedirect();
        $this->assertSame(TeacherScheduleRequest::STATUS_APPROVED, $scheduleRequest->fresh()->status);

        $this->actingAs($guru)
            ->patch(route('guru.schedule.confirm', $otherAssignment), ['status' => 'confirmed'])
            ->assertNotFound();
    }

    private function admin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]
        );

        return User::factory()->create([
            'name' => 'Admin PKG',
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    private function guru(string $name = 'Ahmad Fulan', string $phone = '6281234567890'): array
    {
        $role = Role::query()->firstOrCreate(
            ['name' => User::ROLE_GURU],
            ['display_name' => 'Guru', 'permissions' => [], 'is_active' => true]
        );
        $user = User::factory()->create([
            'name' => $name,
            'phone' => $phone,
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $profile = $this->profile($name, $phone, $user->id);

        return [$user, $profile];
    }

    private function profile(
        string $name = 'Ahmad Fulan',
        string $phone = '6281234567890',
        ?int $userId = null
    ): TeacherProfile {
        return TeacherProfile::create([
            'user_id' => $userId,
            'name' => $name,
            'public_name' => explode(' ', $name)[0],
            'kelompok' => ParticipantProfileOptions::SAWAH_DALAM_1,
            'whatsapp' => $phone,
            'whatsapp_normalized' => $phone,
            'participation_role' => TeacherProfile::ROLE_BOTH,
            'rombels' => ['smp'],
            'available_nights' => ['monday'],
            'night_priorities' => ['monday' => 1],
            'monthly_limit' => 2,
            'competencies' => ['quran'],
            'material_readiness' => 'ready',
            'backup_contact_preference' => 'ready',
            'consent_version' => 'v1',
            'consented_at' => now(),
            'submitted_at' => now(),
            'is_active' => true,
        ]);
    }

    private function period(User $admin, string $status, ?string $month = null): TeacherSchedulePeriod
    {
        return TeacherSchedulePeriod::create([
            'month' => $month ?: now()->startOfMonth()->toDateString(),
            'status' => $status,
            'created_by' => $admin->id,
            'published_by' => $status === 'published' ? $admin->id : null,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }

    private function assignment(
        TeacherSchedulePeriod $period,
        TeacherProfile $profile,
        string $role,
        $date,
        string $rombel = 'smp'
    ) {
        $session = TeacherScheduleSession::create([
            'period_id' => $period->id,
            'session_date' => $date->toDateString(),
            'rombel' => $rombel,
            'start_time' => '20:00',
            'end_time' => '21:30',
            'location' => 'Masjid PKG',
            'status' => 'scheduled',
        ]);

        return app(TeacherSchedulePlanner::class)
            ->createAssignment($session, $profile, $role, 'manual', true, $period->created_by);
    }
}
