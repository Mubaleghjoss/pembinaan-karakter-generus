<?php

namespace App\Services;

use App\Models\QuranProgressSubmission;
use App\Models\QuranReadingCycle;
use App\Models\QuranSurahProgress;
use App\Models\Siswa;
use App\Support\QuranCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuranKhatamService
{
    public function summaryForStudent(Siswa $siswa): array
    {
        $cycle = QuranReadingCycle::where('siswa_id', $siswa->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->latest('cycle_number')
            ->first();

        if (! $cycle) {
            return [
                'cycle' => null, 'progress' => collect(), 'completed_surahs' => [],
                'completed_count' => 0, 'percentage' => 0, 'active_surah' => null, 'active_ayah' => null,
            ];
        }

        return $this->summary($cycle);
    }

    public function activeCycle(Siswa $siswa, ?int $createdBy = null): QuranReadingCycle
    {
        return DB::transaction(function () use ($siswa, $createdBy) {
            $active = QuranReadingCycle::where('siswa_id', $siswa->id)
                ->where('status', QuranReadingCycle::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($active) {
                return $active;
            }

            $number = ((int) QuranReadingCycle::where('siswa_id', $siswa->id)->max('cycle_number')) + 1;

            return QuranReadingCycle::create([
                'siswa_id' => $siswa->id,
                'cycle_number' => $number,
                'status' => QuranReadingCycle::STATUS_ACTIVE,
                'started_at' => now()->toDateString(),
                'created_by' => $createdBy,
            ]);
        });
    }

    public function summary(QuranReadingCycle $cycle): array
    {
        $progress = $cycle->progress()->orderBy('surah_number')->get()->keyBy('surah_number');
        $completed = $progress->filter(fn ($row) => $row->completed_at !== null)->keys()->map(fn ($value) => (int) $value)->values()->all();
        $active = $progress->filter(fn ($row) => ! $row->completed_at && $row->last_ayah > 0)->sortByDesc('updated_at')->first();

        return [
            'cycle' => $cycle,
            'progress' => $progress,
            'completed_surahs' => $completed,
            'completed_count' => count($completed),
            'percentage' => round(count($completed) / 114 * 100),
            'active_surah' => $active?->surah_number,
            'active_ayah' => $active?->last_ayah,
        ];
    }

    public function applySubmission(QuranProgressSubmission $submission, int $reviewerId): void
    {
        DB::transaction(function () use ($submission, $reviewerId) {
            $submission = QuranProgressSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($submission->status === QuranProgressSubmission::STATUS_VERIFIED) {
                return;
            }
            if ($submission->status === QuranProgressSubmission::STATUS_REJECTED) {
                throw ValidationException::withMessages(['submission' => 'Pengajuan yang ditolak tidak dapat diverifikasi.']);
            }

            $cycle = QuranReadingCycle::whereKey($submission->cycle_id)->lockForUpdate()->firstOrFail();
            $completed = collect($submission->completed_surahs ?? [])->map(fn ($n) => (int) $n)->filter(fn ($n) => $n >= 1 && $n <= 114)->unique();
            foreach ($completed as $surahNumber) {
                QuranSurahProgress::updateOrCreate(
                    ['cycle_id' => $cycle->id, 'surah_number' => $surahNumber],
                    ['last_ayah' => QuranCatalog::ayahCount($surahNumber), 'completed_at' => now(), 'source' => 'scan', 'updated_by' => $reviewerId],
                );
            }

            if ($submission->active_surah) {
                $surahNumber = (int) $submission->active_surah;
                $ayah = min((int) $submission->active_ayah, QuranCatalog::ayahCount($surahNumber));
                $existing = QuranSurahProgress::firstOrNew(['cycle_id' => $cycle->id, 'surah_number' => $surahNumber]);
                if (! $existing->completed_at && $ayah > (int) $existing->last_ayah) {
                    $existing->fill(['last_ayah' => $ayah, 'source' => 'scan', 'updated_by' => $reviewerId])->save();
                }
            }

            $submission->update(['status' => QuranProgressSubmission::STATUS_VERIFIED, 'reviewed_by' => $reviewerId, 'reviewed_at' => now()]);

            if ($cycle->progress()->whereNotNull('completed_at')->count() === 114) {
                $cycle->update(['status' => QuranReadingCycle::STATUS_COMPLETED, 'completed_at' => now()->toDateString()]);
            }
        });
    }
}
