<?php

namespace App\Observers;

use App\Models\Karakter;
use App\Services\MobileFcmNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class KarakterCreatedObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Karakter $karakter): void
    {
        if (! $karakter->is_active) {
            return;
        }

        try {
            app(MobileFcmNotificationService::class)->sendToActiveMobileUsers(
                'task',
                '/tugas',
                'Tugas PKG baru',
                Str::limit('Tugas PKG baru: '.($karakter->nama ?: 'Tugas baru').'. Ketuk untuk melihat tugas.', 160, '...'),
                $karakter->getKey(),
                'task-created-'.$karakter->getKey(),
                ['task_id' => (string) $karakter->getKey()],
                'siswa',
            );
        } catch (Throwable $exception) {
            Log::warning('Mobile task notification failed.', [
                'karakter_id' => $karakter->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }
}
