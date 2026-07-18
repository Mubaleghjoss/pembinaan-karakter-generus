<?php

namespace App\Console\Commands;

use App\Services\TaskPwaNotificationService;
use Illuminate\Console\Command;

class NotifyPendingTaskPwa extends Command
{
    protected $signature = 'pwa:notify-pending-tasks {--date= : Tanggal target dalam format Y-m-d}';

    protected $description = 'Kirim satu pengingat PWA per hari kepada siswa yang masih memiliki Tugas PKG aktif';

    public function handle(TaskPwaNotificationService $notifications): int
    {
        $sent = $notifications->notifyStudentsWithPendingTasks($this->option('date'));
        $this->info("Pengingat PWA terkirim ke {$sent} siswa.");

        return self::SUCCESS;
    }
}
