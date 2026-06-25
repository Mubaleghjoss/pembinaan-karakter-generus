<?php

namespace App\Support;

use App\Models\PointTransaction;
use App\Models\SiswaKarakterChecklist;
use App\Services\GamificationService;
use Illuminate\Support\Facades\Log;

class InvalidKarakterChecklistCleaner
{
    public function cleanupAll(?int $deletedBy = null): int
    {
        return $this->cleanupQuery(SiswaKarakterChecklist::query(), $deletedBy);
    }

    public function cleanupForSiswa(int $siswaId, ?int $deletedBy = null): int
    {
        return $this->cleanupQuery(
            SiswaKarakterChecklist::query()->where('siswa_id', $siswaId),
            $deletedBy
        );
    }

    public function cleanupForKarakter(int $karakterId, ?int $deletedBy = null): int
    {
        return $this->cleanupQuery(
            SiswaKarakterChecklist::query()->where('karakter_id', $karakterId),
            $deletedBy
        );
    }

    protected function cleanupQuery($query, ?int $deletedBy = null): int
    {
        $count = 0;

        $query
            ->with(['siswa', 'karakter'])
            ->orderBy('id')
            ->chunkById(100, function ($checklists) use (&$count, $deletedBy) {
                foreach ($checklists as $checklist) {
                    if (! $this->isOutsideTaskPeriod($checklist)) {
                        continue;
                    }

                    $this->deleteInvalidChecklist($checklist, $deletedBy);
                    $count++;
                }
            });

        return $count;
    }

    protected function isOutsideTaskPeriod(SiswaKarakterChecklist $checklist): bool
    {
        if (! $checklist->checked_at || ! $checklist->karakter) {
            return false;
        }

        return ! $checklist->karakter->isAvailableOn($checklist->checked_at);
    }

    protected function deleteInvalidChecklist(SiswaKarakterChecklist $checklist, ?int $deletedBy = null): void
    {
        $this->reverseAwardedPoints($checklist);

        $checklist->update([
            'deleted_by' => $deletedBy,
            'deleted_reason' => $this->deleteReason($checklist),
        ]);

        $checklist->clearStoredEvidenceFiles();
        $checklist->delete();
    }

    protected function reverseAwardedPoints(SiswaKarakterChecklist $checklist): void
    {
        if (! $checklist->siswa || ! $checklist->karakter) {
            return;
        }

        $netAwarded = (int) PointTransaction::query()
            ->where('siswa_id', $checklist->siswa_id)
            ->where('source', 'character')
            ->where('reference_type', SiswaKarakterChecklist::class)
            ->where('reference_id', $checklist->id)
            ->sum('points');

        if ($netAwarded <= 0) {
            return;
        }

        try {
            $siswaPoint = app(GamificationService::class)->getOrCreateSiswaPoint($checklist->siswa);
            $siswaPoint->addPoints(
                -$netAwarded,
                'character',
                'Hapus otomatis data di luar periode tugas: ' . $checklist->karakter->nama . ' (-' . $netAwarded . ' poin)',
                $checklist,
                [
                    'event' => 'invalid_task_period_cleanup',
                    'checklist_id' => $checklist->id,
                    'karakter_id' => $checklist->karakter_id,
                    'checked_at' => $checklist->checked_at?->toDateString(),
                    'period_start' => $checklist->karakter->tanggal_mulai?->toDateString(),
                    'period_end' => $checklist->karakter->tanggal_selesai?->toDateString(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to reverse invalid task period points: ' . $e->getMessage(), [
                'checklist_id' => $checklist->id,
            ]);
        }
    }

    protected function deleteReason(SiswaKarakterChecklist $checklist): string
    {
        $checkedAt = $checklist->checked_at?->toDateString() ?? '-';
        $start = $checklist->karakter?->tanggal_mulai?->toDateString() ?? '-';
        $end = $checklist->karakter?->tanggal_selesai?->toDateString() ?? '-';

        return "Otomatis dihapus karena tanggal pengerjaan {$checkedAt} berada di luar periode tugas {$start} - {$end}.";
    }
}
