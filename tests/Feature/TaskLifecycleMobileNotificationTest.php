<?php

namespace Tests\Feature;

use App\Models\Karakter;
use App\Services\MobileFcmNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TaskLifecycleMobileNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_active_task_notifies_all_active_student_accounts(): void
    {
        config()->set('fcm.enabled', true);
        $service = Mockery::mock(MobileFcmNotificationService::class);
        $service->shouldReceive('sendToActiveMobileUsers')
            ->once()
            ->with(
                'task',
                '/tugas',
                'Tugas PKG baru',
                Mockery::pattern('/Tugas PKG baru:/'),
                Mockery::type('int'),
                Mockery::pattern('/^task-created-/'),
                Mockery::on(fn (array $extra): bool => ($extra['task_id'] ?? null) !== null),
                'siswa',
            )
            ->andReturn(2);
        $this->app->instance(MobileFcmNotificationService::class, $service);

        Karakter::create([
            'is_active' => true,
            'nama' => 'Hafalan Surat',
        ]);
    }

    public function test_task_observer_handles_events_after_commit(): void
    {
        self::assertInstanceOf(
            ShouldHandleEventsAfterCommit::class,
            new \App\Observers\KarakterCreatedObserver(),
        );
    }
}
