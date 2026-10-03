<?php

namespace Tests\Unit;

use App\Observers\PointTransactionCreatedObserver;
use App\Observers\PresensiCreatedObserver;
use App\Observers\QuranReadingEntryCreatedObserver;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use PHPUnit\Framework\TestCase;

class MobileNotificationAfterCommitTest extends TestCase
{
    public function test_business_notification_observers_handle_events_after_commit(): void
    {
        foreach ([
            PresensiCreatedObserver::class,
            QuranReadingEntryCreatedObserver::class,
            PointTransactionCreatedObserver::class,
        ] as $observer) {
            self::assertInstanceOf(ShouldHandleEventsAfterCommit::class, new $observer());
        }
    }
}
