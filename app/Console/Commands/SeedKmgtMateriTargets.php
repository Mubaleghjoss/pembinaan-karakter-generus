<?php

namespace App\Console\Commands;

use App\Models\MateriTarget;
use App\Support\KmgtSilabusTargets;
use Illuminate\Console\Command;

class SeedKmgtMateriTargets extends Command
{
    protected $signature = 'materi-targets:seed-kmgt-silabus';

    protected $description = 'Import target materi dari Silabus KMGT pra remaja dan remaja secara idempotent.';

    public function handle(): int
    {
        $created = 0;
        $updated = 0;

        foreach (KmgtSilabusTargets::records() as $record) {
            $target = MateriTarget::updateOrCreate(
                ['source_key' => $record['source_key']],
                $record
            );

            $target->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->info("Import Silabus KMGT selesai. Dibuat: {$created}. Diperbarui: {$updated}.");

        return self::SUCCESS;
    }
}
