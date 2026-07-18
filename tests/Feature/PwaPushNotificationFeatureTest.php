<?php

namespace Tests\Feature;

use App\Models\Karakter;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Models\User;
use App\Notifications\TaskBadgeWebPushNotification;
use App\Services\TaskPwaNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PwaPushNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pamong_can_subscribe_and_unsubscribe_current_device(): void
    {
        $user = User::factory()->admin()->create();
        $payload = $this->subscriptionPayload('https://push.example.test/pamong-device');

        $this->actingAs($user)
            ->postJson(route('pwa.push-subscriptions.store'), $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Notifikasi PWA berhasil diaktifkan.');

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $user->id,
            'endpoint' => $payload['endpoint'],
        ]);

        $this->actingAs($user)
            ->deleteJson(route('pwa.push-subscriptions.destroy'), ['endpoint' => $payload['endpoint']])
            ->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $payload['endpoint']]);
    }

    public function test_siswa_subscription_uses_siswa_guard_and_pending_badge_count(): void
    {
        $siswa = Siswa::factory()->create();
        Karakter::query()->create([
            'nama' => 'Tugas PWA Hari Ini',
            'kategori' => 'harian',
            'poin' => 10,
            'is_active' => true,
        ]);
        $payload = $this->subscriptionPayload('https://push.example.test/siswa-device');

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.pwa.push-subscriptions.store'), $payload)
            ->assertOk()
            ->assertJsonPath('badge_count', 1);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => Siswa::class,
            'subscribable_id' => $siswa->id,
            'endpoint' => $payload['endpoint'],
        ]);
    }

    public function test_pending_student_count_uses_same_completion_rule_as_sidebar_badge(): void
    {
        $siswa = Siswa::factory()->create();
        $task = Karakter::query()->create([
            'nama' => 'Tugas Hitungan Badge',
            'kategori' => 'harian',
            'poin' => 10,
            'is_active' => true,
        ]);
        $service = app(TaskPwaNotificationService::class);

        $this->assertSame(1, $service->pendingStudentTaskCount($siswa));

        SiswaKarakterChecklist::query()->create([
            'siswa_id' => $siswa->id,
            'karakter_id' => $task->id,
            'checked_at' => now(),
        ]);

        $this->assertSame(0, $service->pendingStudentTaskCount($siswa));
    }

    public function test_new_submission_notifies_subscribed_pamong_only_once(): void
    {
        Notification::fake();

        $adminRole = Role::query()->create([
            'name' => User::ROLE_ADMIN,
            'display_name' => 'Administrator',
            'permissions' => ['*'],
            'is_active' => true,
        ]);
        $pamong = User::factory()->create([
            'name' => 'Pak Ahmad',
            'role_id' => $adminRole->id,
        ]);
        $pamong->updatePushSubscription(
            'https://push.example.test/pamong-notification',
            str_repeat('p', 65),
            str_repeat('a', 22),
            'aes128gcm',
        );
        $siswa = Siswa::factory()->create(['nama' => 'Rafi']);
        $task = Karakter::query()->create([
            'nama' => 'Tugas untuk Pamong',
            'kategori' => 'harian',
            'poin' => 10,
            'is_active' => true,
        ]);
        $checklist = SiswaKarakterChecklist::query()->create([
            'siswa_id' => $siswa->id,
            'karakter_id' => $task->id,
            'checked_at' => now(),
        ]);
        $service = app(TaskPwaNotificationService::class);

        $this->assertTrue($pamong->fresh()->isAdmin());
        $this->assertTrue($pamong->pushSubscriptions()->exists());
        $this->assertTrue(User::query()->whereHas('pushSubscriptions')->whereKey($pamong->id)->exists());
        $this->assertSame(1, $service->notifyPamongAboutSubmission($checklist));
        $this->assertSame(0, $service->notifyPamongAboutSubmission($checklist));

        Notification::assertSentToTimes($pamong, TaskBadgeWebPushNotification::class, 1);
        Notification::assertSentTo(
            $pamong,
            TaskBadgeWebPushNotification::class,
            function (TaskBadgeWebPushNotification $notification) use ($pamong): bool {
                $payload = $notification->toWebPush($pamong, $notification)->toArray();

                return $payload['title'] === 'Hai, Pak Ahmad'
                    && $payload['body'] === 'Ada tugas PKG dari Rafi yang perlu diverifikasi: Tugas untuk Pamong.';
            }
        );
        $this->assertDatabaseCount('pwa_notification_deliveries', 1);
    }

    public function test_pending_task_reminder_greets_student_by_name(): void
    {
        Notification::fake();

        $siswa = Siswa::factory()->create(['nama' => 'Nabila']);
        $siswa->updatePushSubscription(
            'https://push.example.test/student-notification',
            str_repeat('p', 65),
            str_repeat('a', 22),
            'aes128gcm',
        );
        Karakter::query()->create([
            'nama' => 'Tugas Harian Nabila',
            'kategori' => 'harian',
            'poin' => 10,
            'is_active' => true,
        ]);

        $this->assertSame(1, app(TaskPwaNotificationService::class)->notifyStudentsWithPendingTasks());

        Notification::assertSentTo(
            $siswa,
            TaskBadgeWebPushNotification::class,
            function (TaskBadgeWebPushNotification $notification) use ($siswa): bool {
                $payload = $notification->toWebPush($siswa, $notification)->toArray();

                return $payload['title'] === 'Hai, Nabila'
                    && $payload['body'] === 'Ada 1 tugas PKG hari ini yang belum dikerjakan.';
            }
        );
    }

    private function subscriptionPayload(string $endpoint): array
    {
        return [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => str_repeat('p', 65),
                'auth' => str_repeat('a', 22),
            ],
            'content_encoding' => 'aes128gcm',
        ];
    }
}
