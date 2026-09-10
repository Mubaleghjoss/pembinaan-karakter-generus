<?php

namespace Tests\Unit;

use Tests\TestCase;

class TugasPkgMobileDisclosureRegressionTest extends TestCase
{
    public function test_pkg_mobile_summaries_keep_secondary_content_collapsed(): void
    {
        $views = [
            'resources/views/tugas-pkg/verification/index.blade.php' => 'Lihat binaan dan aksi',
            'resources/views/tugas-pkg/verification/history.blade.php' => 'Lihat catatan dan riwayat',
            'resources/views/tugas-pkg/verification/rekap.blade.php' => 'Lihat rekap dan aksi',
            'resources/views/tugas-pkg/verification/karakter-harian.blade.php' => 'Lihat catatan dan aksi',
            'resources/views/tugas-pkg/verification/partials/mobile-checklist-card.blade.php' => 'Lihat jawaban dan bukti',
        ];

        foreach ($views as $path => $disclosure) {
            $view = file_get_contents(base_path($path));

            $this->assertNotFalse($view, "Unable to read {$path}");
            $this->assertStringContainsString('<details', $view, "{$path} needs a native accessible disclosure");
            $this->assertStringContainsString($disclosure, $view, "{$path} must label secondary mobile content");
        }
    }

    public function test_pkg_desktop_tables_are_preserved_alongside_mobile_cards(): void
    {
        foreach ([
            'resources/views/tugas-pkg/verification/index.blade.php',
            'resources/views/tugas-pkg/verification/history.blade.php',
            'resources/views/tugas-pkg/verification/rekap.blade.php',
            'resources/views/tugas-pkg/verification/karakter-harian.blade.php',
        ] as $path) {
            $view = file_get_contents(base_path($path));

            $this->assertStringContainsString('hidden lg:block', $view, "{$path} must retain its desktop table");
            $this->assertStringContainsString('lg:hidden', $view, "{$path} must provide a compact mobile alternative");
        }
    }
}
