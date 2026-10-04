<?php

namespace App\Observers;

use App\Models\SiswaKarakterChecklist;
use App\Services\TaskPwaNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiswaKarakterChecklistCreatedObserver implements ShouldHandleEventsAfterCommit
{
    public function created(SiswaKarakterChecklist $checklist): void
    {
        try {
            app(TaskPwaNotificationService::class)->notifyPamongAboutSubmission($checklist);
        } catch (Throwable $exception) {
            Log::warning('Task submission notification failed.', [
                'checklist_id' => $checklist->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }
}
