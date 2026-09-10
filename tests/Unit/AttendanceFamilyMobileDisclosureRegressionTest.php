<?php

namespace Tests\Unit;

use Tests\TestCase;

class AttendanceFamilyMobileDisclosureRegressionTest extends TestCase
{
    public function test_batch_three_attendance_reports_keep_mobile_rows_minimized(): void
    {
        $files = [
            'resources/views/presensi/partials/period-report.blade.php',
            'resources/views/presensi/partials/generus-report.blade.php',
            'resources/views/presensi/generus-recap.blade.php',
            'resources/views/cek-kehadiran/index.blade.php',
        ];

        foreach ($files as $path) {
            $view = file_get_contents(base_path($path));

            $this->assertNotFalse($view, "Unable to read {$path}");
            $this->assertStringContainsString('<details', $view, "{$path} needs a per-record disclosure");
            $this->assertStringContainsString('max-md:!hidden', $view, "{$path} must hide secondary cells on mobile");
            $this->assertStringContainsString('md:hidden', $view, "{$path} needs a compact mobile summary");
        }
    }

    public function test_attendance_point_actions_remain_available_after_mobile_disclosure(): void
    {
        $view = file_get_contents(base_path('resources/views/cek-kehadiran/index.blade.php'));

        $this->assertStringContainsString('Lihat detail dan aksi', $view);
        $this->assertStringContainsString('Hapus poin', $view);
        $this->assertStringContainsString('@csrf', $view);
    }
}
