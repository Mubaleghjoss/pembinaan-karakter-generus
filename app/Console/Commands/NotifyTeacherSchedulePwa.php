<?php

namespace App\Console\Commands;

use App\Services\TeacherSchedulePwaNotificationService;
use Illuminate\Console\Command;

class NotifyTeacherSchedulePwa extends Command
{
    protected $signature = 'pwa:notify-teacher-schedules {--date= : Tanggal acuan dalam format Y-m-d}';

    protected $description = 'Kirim Web Push jadwal Guru yang jatuh pada H-3 dan H-1';

    public function handle(TeacherSchedulePwaNotificationService $notifications): int
    {
        $sent = $notifications->notifyDue($this->option('date'));
        $this->info("Notifikasi jadwal Guru terkirim: {$sent}");

        return self::SUCCESS;
    }
}
