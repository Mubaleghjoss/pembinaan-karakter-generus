<?php

namespace App\Observers;

use App\Models\PointTransaction;
use App\Services\MobileFcmNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Throwable;

class PointTransactionCreatedObserver implements ShouldHandleEventsAfterCommit
{
    public function created(PointTransaction $transaction): void
    {
        if ($transaction->points <= 0) {
            return;
        }

        $siswa = $transaction->siswa;
        if (! $siswa || ! $siswa->is_active || $siswa->status !== 'active') {
            return;
        }

        try {
            app(MobileFcmNotificationService::class)->sendToOwner(
                $siswa,
                'gamification',
                '/poin',
                'Poin bertambah',
                sprintf(
                    'Kamu mendapat +%d poin dari %s. Ketuk untuk melihat poin.',
                    $transaction->points,
                    $transaction->description,
                ),
                $transaction->getKey(),
                'gamification-'.$transaction->getKey(),
                ['source' => (string) $transaction->source],
            );
        } catch (Throwable $exception) {
            Log::warning('Mobile gamification notification failed.', [
                'transaction_id' => $transaction->getKey(),
                'siswa_id' => $transaction->siswa_id,
                'exception' => $exception::class,
            ]);
        }
    }
}
