<?php

namespace App\Observers;

use App\Models\Presensi;
use App\Services\MobileFcmNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Throwable;

class PresensiCreatedObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Presensi $presensi): void
    {
        $siswa = $presensi->siswa;
        if (! $siswa || ! $siswa->is_active || $siswa->status !== 'active') {
            return;
        }

        try {
            app(MobileFcmNotificationService::class)->sendToOwner(
                $siswa,
                'attendance',
                '/presensi',
                'Presensi tercatat',
                sprintf(
                    'Presensi %s: %s. Ketuk untuk melihat presensi.',
                    $presensi->tanggal?->format('d/m/Y') ?? '-',
                    $presensi->status,
                ),
                $presensi->getKey(),
                'attendance-'.$presensi->getKey().'-'.($presensi->created_at?->timestamp ?? time()),
            );
        } catch (Throwable $exception) {
            Log::warning('Mobile attendance notification failed.', [
                'presensi_id' => $presensi->getKey(),
                'siswa_id' => $presensi->siswa_id,
                'exception' => $exception::class,
            ]);
        }
    }
}
