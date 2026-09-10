<?php

namespace Tests\Unit;

use Tests\TestCase;

class GamificationReportingBatchFiveMobileDisclosureRegressionTest extends TestCase
{
    public function test_batch_five_record_views_keep_mobile_secondary_information_collapsed(): void
    {
        $files = [
            'resources/views/admin/gamification/analytics.blade.php',
            'resources/views/admin/gamification/badges.blade.php',
            'resources/views/admin/gamification/levels.blade.php',
            'resources/views/admin/gamification/transactions.blade.php',
            'resources/views/reports/index.blade.php',
            'resources/views/laporan-penyaksian/index.blade.php',
            'resources/views/admin/data-pull.blade.php',
            'resources/views/pamong/activity-log.blade.php',
            'resources/views/materi-rpp-journals/index.blade.php',
        ];

        foreach ($files as $path) {
            $view = file_get_contents(base_path($path));

            $this->assertNotFalse($view, "Unable to read {$path}");
            $this->assertStringContainsString('<details', $view, "{$path} needs a native mobile disclosure");
            $this->assertStringContainsString('md:hidden', $view, "{$path} must scope the disclosure to mobile");
        }
    }

    public function test_mobile_summaries_retain_the_primary_record_information(): void
    {
        $transactions = file_get_contents(base_path('resources/views/admin/gamification/transactions.blade.php'));
        $reports = file_get_contents(base_path('resources/views/reports/index.blade.php'));
        $journals = file_get_contents(base_path('resources/views/materi-rpp-journals/index.blade.php'));

        $this->assertStringContainsString('{{ $t->formatted_points }}', $transactions);
        $this->assertStringContainsString('persentase_kehadiran', $reports);
        $this->assertStringContainsString('{{ $workflowLabel }}', $journals);
    }
}
