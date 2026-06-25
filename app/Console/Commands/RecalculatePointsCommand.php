<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use App\Models\PointTransaction;
use App\Models\SiswaPoint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculatePointsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamification:recalculate-points {--dry-run : Only show what would happen without actually updating} {--clean : Remove orphan transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate total points for all students based on their transaction history';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting point recalculation...');
        
        if ($this->option('clean')) {
            $this->info('Cleaning up orphan transactions...');
            $orphans = PointTransaction::where('source', 'character')
                ->where('reference_type', 'App\Models\SiswaKarakterChecklist')
                ->get();
            
            $deletedCount = 0;
            foreach ($orphans as $transaction) {
                if ($transaction->reference_id && !\App\Models\SiswaKarakterChecklist::find($transaction->reference_id)) {
                    if ($this->option('dry-run')) {
                        $this->info("[DRY RUN] Would delete orphan transaction ID {$transaction->id} (Points: {$transaction->points})");
                    } else {
                        $transaction->delete();
                        $deletedCount++;
                    }
                }
            }
            
            if (!$this->option('dry-run')) {
                $this->info("Deleted {$deletedCount} orphan transactions.");
            }
        }
        
        $siswas = Siswa::where('is_active', true)->get();
        $bar = $this->output->createProgressBar(count($siswas));
        $bar->start();

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($siswas as $siswa) {
            try {
                $currentCycleTotals = SiswaPoint::calculateCurrentCycleTotals($siswa->id);
                $correctTotal = $currentCycleTotals['total_points'];
                
                // Get current stored total
                $currentPoint = SiswaPoint::firstOrNew(['siswa_id' => $siswa->id]);
                $storedTotal = $currentPoint->total_points ?? 0;
                
                if ($storedTotal != $correctTotal) {
                    if ($this->option('dry-run')) {
                        $this->info("\n[DRY RUN] Siswa {$siswa->nama}: Current={$storedTotal}, Calculated={$correctTotal}");
                    } else {
                        $currentPoint->syncCurrentCycleTotals();
                        $updatedCount++;
                    }
                }

                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nError processing siswa ID {$siswa->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $bar->finish();
        $this->newLine();
        
        if ($this->option('dry-run')) {
            $this->info("Dry run completed. {$updatedCount} students would be updated.");
        } else {
            $this->info("Recalculation completed. {$updatedCount} students updated. {$errorCount} errors.");
        }
    }
}
