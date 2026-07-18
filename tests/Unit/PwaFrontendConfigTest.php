<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PwaFrontendConfigTest extends TestCase
{
    public function test_service_worker_handles_push_and_notification_clicks(): void
    {
        $worker = file_get_contents(dirname(__DIR__, 2).'/public/sw.js');

        $this->assertStringContainsString("addEventListener('push'", $worker);
        $this->assertStringContainsString('showNotification', $worker);
        $this->assertStringContainsString("addEventListener('notificationclick'", $worker);
        $this->assertStringContainsString('openWindow', $worker);
    }

    public function test_auth_layout_prioritizes_compact_mobile_login(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 2).'/resources/views/layouts/auth.blade.php');

        $this->assertStringContainsString('hidden space-y-6 lg:block', $layout);
        $this->assertStringContainsString('lg:hidden', $layout);
        $this->assertStringContainsString('rel="manifest"', $layout);
    }
}
