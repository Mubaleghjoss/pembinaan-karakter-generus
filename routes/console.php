<?php

use App\Services\QuranReadingScanService;
use App\Services\TaskPwaNotificationService;
use App\Services\TeacherSchedulePwaNotificationService;
use Illuminate\Support\Facades\Schedule;

/**
 * Catatan hosting: proc_open (dan exec/shell_exec) DIMATIKAN di hosting ini.
 * Karena itu Schedule::command() — yang menjalankan artisan lewat sub-proses —
 * selalu gagal ("Process class relies on proc_open") dan membanjiri log.
 *
 * Solusi: pakai Schedule::call() yang menjalankan logika langsung dalam proses
 * PHP yang sama (tanpa sub-proses), sehingga scheduler tetap berjalan.
 */

Schedule::call(function () {
    app(TaskPwaNotificationService::class)->notifyStudentsWithPendingTasks();
})->name('pwa-notify-pending-tasks')
    ->everyFiveMinutes()
    ->between('05:00', '21:00')
    ->withoutOverlapping();

Schedule::call(function () {
    app(TeacherSchedulePwaNotificationService::class)->notifyDue();
})->name('pwa-notify-teacher-schedules')
    ->everyFiveMinutes()
    ->between('05:00', '21:00')
    ->withoutOverlapping();

Schedule::call(function () {
    app(QuranReadingScanService::class)->cleanup();
})->name('quran-scans-cleanup')
    ->hourly()
    ->withoutOverlapping();
