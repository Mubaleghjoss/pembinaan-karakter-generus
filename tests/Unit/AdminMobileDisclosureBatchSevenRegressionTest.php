<?php

namespace Tests\Unit;

use Tests\TestCase;

class AdminMobileDisclosureBatchSevenRegressionTest extends TestCase
{
    public function test_batch_seven_collapses_only_secondary_mobile_controls(): void
    {
        $expectations = [
            'resources/views/admin/gamification/badges.blade.php' => 'Cara kerja pin penghargaan',
            'resources/views/settings/backup.blade.php' => 'Cara pulihkan full backup',
            'resources/views/qr/generate.blade.php' => 'Opsi massal',
            'resources/views/tugas-pkg/master/index.blade.php' => 'Aksi massal',
        ];

        foreach ($expectations as $path => $control) {
            $view = file_get_contents(base_path($path));

            $this->assertNotFalse($view, "Unable to read {$path}");
            $this->assertStringContainsString($control, $view);
            $this->assertStringContainsString('md:hidden', $view, "{$path} must scope its disclosure to mobile");
        }
    }

    public function test_registration_templates_start_closed_on_mobile_but_open_on_desktop_or_errors(): void
    {
        $view = file_get_contents(base_path('resources/views/admin/generus-registration/index.blade.php'));

        $this->assertStringContainsString("window.innerWidth >= 768", $view);
        $this->assertStringContainsString("\$errors->any() ? 'true'", $view);
        $this->assertStringContainsString('x-show="open" x-cloak', $view);
    }

    public function test_secondary_forms_are_not_wrapped_by_native_details(): void
    {
        $qr = file_get_contents(base_path('resources/views/qr/generate.blade.php'));
        $master = file_get_contents(base_path('resources/views/tugas-pkg/master/index.blade.php'));

        $this->assertStringNotContainsString('<details', $qr);
        $this->assertStringNotContainsString('<details', $master);
    }
}
