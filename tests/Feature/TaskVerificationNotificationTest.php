<?php

namespace Tests\Feature;

use App\Models\SiswaKarakterChecklist;
use App\Models\Siswa;
use App\Models\Karakter;
use App\Services\MobileFcmNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Mockery;
use Tests\TestCase;

class TaskVerificationNotificationTest extends TestCase
{
    public function test_verified_checklist_notifies_the_student_owner(): void
    {
        $checklist = Mockery::mock(SiswaKarakterChecklist::class)->makePartial();
        $checklist->shouldReceive('wasChanged')->once()->with('verified_at')->andReturnTrue();
        $checklist->shouldReceive('getOriginal')->once()->with('verified_at')->andReturnNull();
        $checklist->shouldReceive('getKey')->andReturn(42);
        $checklist->shouldReceive('getAttribute')->with('verified_at')->andReturn(now());
        $checklist->shouldReceive('getAttributeValue')->with('verified_at')->andReturn(now());
        $checklist->shouldReceive('getAttribute')->with('karakter')->andReturn(new Karakter(['nama' => 'Hafalan Surat']));
        $checklist->shouldReceive('getAttribute')->with('siswa')->andReturn(new Siswa(['id' => 7]));
        $checklist->shouldReceive('loadMissing')->once()->withAnyArgs()->andReturnSelf();
        $checklist->setRelation('karakter', new Karakter(['nama' => 'Hafalan Surat']));
        $checklist->setRelation('siswa', new Siswa(['id' => 7]));

        $called = false;
        $payload = [];
        $service = Mockery::mock(MobileFcmNotificationService::class);
        $service->shouldReceive('sendToOwner')
            ->once()
            ->withArgs(function (...$args) use (&$called, &$payload): bool {
                $called = true;
                $payload = $args;
                return true;
            })
            ->andReturn(1);
        $this->app->instance(MobileFcmNotificationService::class, $service);
        \Illuminate\Support\Facades\Log::spy();

        app(\App\Observers\SiswaKarakterChecklistUpdatedObserver::class)->updated($checklist);
        self::assertTrue($called);
        self::assertSame('task', $payload[1]);
        self::assertSame('/tugas', $payload[2]);
        self::assertSame(42, $payload[5]);
        self::assertSame('task-verified-42', $payload[6]);
        self::assertSame('42', $payload[7]['task_id']);
    }

    public function test_verification_observer_handles_events_after_commit(): void
    {
        self::assertInstanceOf(
            ShouldHandleEventsAfterCommit::class,
            new \App\Observers\SiswaKarakterChecklistUpdatedObserver(),
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
