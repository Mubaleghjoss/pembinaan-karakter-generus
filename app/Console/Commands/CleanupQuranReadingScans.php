<?php

namespace App\Console\Commands;

use App\Services\QuranReadingScanService;
use Illuminate\Console\Command;

class CleanupQuranReadingScans extends Command
{
    protected $signature = 'quran-scans:cleanup';

    protected $description = 'Hapus file gambar scan bacaan yang selesai atau kedaluwarsa';

    public function handle(QuranReadingScanService $scans): int
    {
        $result = $scans->cleanup();
        $this->info("Selesai: {$result['completed']}; kedaluwarsa: {$result['expired']}; gagal: {$result['failed']}.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
