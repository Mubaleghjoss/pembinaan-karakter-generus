<?php

namespace App\Console\Commands;

use App\Models\SiswaPoint;
use App\Models\PointTransaction;
use App\Models\SiswaKarakterChecklist;
use Illuminate\Console\Command;

class RecalculatePoints extends Command
{
    protected $signature = 'points:recalculate {--siswa= : Recalculate for specific siswa_id} {--fix : Actually fix the data, otherwise dry-run}';
    protected $description = 'Recalculate siswa points based on actual verified data (not stale transactions)';

    public function handle()
    {
        $siswaId = $this->option('siswa');
        $fix = $this->option('fix');

        $query = SiswaPoint::with('siswa');
        if ($siswaId) {
            $query->where('siswa_id', $siswaId);
        }

        $siswaPoints = $query->get();

        if ($siswaPoints->isEmpty()) {
            $this->error('No siswa points found.');
            return;
        }

        $this->info($fix ? '🔧 FIXING points...' : '👀 DRY RUN (use --fix to apply changes)');
        $this->newLine();

        foreach ($siswaPoints as $sp) {
            $siswaName = $sp->siswa->nama ?? "siswa #{$sp->siswa_id}";
            $oldTotal = $sp->total_points;
            $currentCycleQuery = SiswaPoint::currentCycleTransactionsQuery($sp->siswa_id);

            // Calculate what points SHOULD be based on actual data
            // 1. Attendance points: from attendance-sourced transactions (these are accurate)
            $attendancePoints = (clone $currentCycleQuery)
                ->where('source', 'attendance')
                ->sum('points');

            // 2. Character points: only count transactions for currently-verified checklists
            $verifiedChecklistIds = SiswaKarakterChecklist::where('siswa_id', $sp->siswa_id)
                ->verified()
                ->pluck('id')
                ->toArray();

            // Sum character points only for verified checklists
            $characterPoints = 0;
            if (!empty($verifiedChecklistIds)) {
                $characterPoints = (clone $currentCycleQuery)
                    ->where('source', 'character')
                    ->where('type', 'earned')
                    ->where('reference_type', SiswaKarakterChecklist::class)
                    ->whereIn('reference_id', $verifiedChecklistIds)
                    ->sum('points');
            }

            // 3. Bonus points: keep as-is
            $bonusPoints = (clone $currentCycleQuery)
                ->whereNotIn('source', ['attendance', 'character'])
                ->where('type', 'earned')
                ->sum('points');

            $correctTotal = max(0, $attendancePoints + $characterPoints + $bonusPoints);

            $diff = $oldTotal - $correctTotal;

            if ($diff != 0) {
                $this->warn("⚠️ {$siswaName}: {$oldTotal} → {$correctTotal} (selisih: {$diff})");

                if ($fix) {
                    // Create a correction transaction
                    PointTransaction::create([
                        'siswa_id' => $sp->siswa_id,
                        'type' => 'spent',
                        'source' => 'character',
                        'points' => -$diff,
                        'description' => 'Koreksi otomatis: sinkronisasi poin dengan data verifikasi aktual (-' . $diff . ' poin)',
                    ]);

                    $sp->character_points = max(0, $characterPoints);
                    $sp->attendance_points = max(0, $attendancePoints);
                    $sp->bonus_points = max(0, $bonusPoints);
                    $sp->total_points = $correctTotal;
                    $sp->save();

                    $this->info("  ✅ Fixed! New total: {$correctTotal}");
                }
            } else {
                $this->info("✅ {$siswaName}: {$oldTotal} poin (correct)");
            }
        }

        $this->newLine();
        $this->info('Done!');
    }
}
