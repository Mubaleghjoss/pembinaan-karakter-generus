<?php

namespace Tests\Feature;

use App\Models\SiswaKarakterChecklist;
use App\Services\TaskPwaNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Mockery;
use Tests\TestCase;

class TaskSubmissionNotificationTest extends TestCase
{
    public function test_checklist_creation_notifies_reviewers_after_commit(): void
    {
        $checklist = Mockery::mock(SiswaKarakterChecklist::class);
        $service = Mockery::mock(TaskPwaNotificationService::class);
        $called = false;
        $service->shouldReceive('notifyPamongAboutSubmission')
            ->once()
            ->with($checklist)
            ->andReturnUsing(function () use (&$called): int {
                $called = true;
                return 1;
            });
        $this->app->instance(TaskPwaNotificationService::class, $service);

        app(\App\Observers\SiswaKarakterChecklistCreatedObserver::class)->created($checklist);

        self::assertTrue($called);
    }

    public function test_checklist_observer_handles_events_after_commit(): void
    {
        self::assertInstanceOf(
            ShouldHandleEventsAfterCommit::class,
            new \App\Observers\SiswaKarakterChecklistCreatedObserver(),
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
