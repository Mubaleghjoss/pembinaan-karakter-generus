<?php

namespace App\Observers;

use App\Models\SiswaKarakterChecklist;
use App\Services\MobileFcmNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiswaKarakterChecklistUpdatedObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(SiswaKarakterChecklist $checklist): void
    {
        if (! $checklist->wasChanged('verified_at')
            || $checklist->getOriginal('verified_at') !== null
            || $checklist->verified_at === null) {
            return;
        }

        $checklist->loadMissing(['siswa', 'karakter']);

        try {
            app(MobileFcmNotificationService::class)->sendToOwner(
                $checklist->siswa,
                'task',
                '/tugas',
                'Tugas PKG diverifikasi',
                'Tugas PKG "'.self::boundedName($checklist->karakter?->nama).'" sudah diverifikasi.',
                $checklist->getKey(),
                'task-verified-'.$checklist->getKey(),
                ['task_id' => (string) $checklist->getKey()],
            );
        } catch (Throwable $exception) {
            Log::warning('Task verification notification failed.', [
                'checklist_id' => $checklist->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }

    private static function boundedName(?string $name): string
    {
        $name = trim((string) $name);

        return mb_strlen($name) > 100 ? mb_substr($name, 0, 97).'...' : $name;
    }
}
