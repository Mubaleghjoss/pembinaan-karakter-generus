<?php

namespace App\Observers;

use App\Models\QuranReadingEntry;
use App\Services\MobileFcmNotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class QuranReadingEntryCreatedObserver
{
    public function created(QuranReadingEntry $entry): void
    {
        $siswa = $entry->siswa()->with(['pamongAssignments.pamong'])->first();
        if (! $siswa || ! $siswa->is_active || $siswa->status !== 'active') {
            return;
        }

        $pamongUsers = $siswa->pamongAssignments
            ->pluck('pamong')
            ->filter(fn ($pamong) => $pamong && $pamong->status === 'active')
            ->unique('id');

        foreach ($pamongUsers as $pamong) {
            try {
                app(MobileFcmNotificationService::class)->sendToOwner(
                    $pamong,
                    'quran',
                    '/quran',
                    'Catatan Quran baru',
                    sprintf(
                        'Catatan bacaan %s dari %s menunggu verifikasi. Ketuk untuk membuka Quran.',
                        $entry->reading_date?->format('d/m/Y') ?? '-',
                        $siswa->nama,
                    ),
                    $entry->getKey(),
                    'quran-'.$entry->getKey().'-'.$pamong->getKey(),
                );
            } catch (Throwable $exception) {
                Log::warning('Mobile Quran notification failed.', [
                    'entry_id' => $entry->getKey(),
                    'siswa_id' => $entry->siswa_id,
                    'pamong_id' => $pamong->getKey(),
                    'exception' => $exception::class,
                ]);
            }
        }
    }
}
