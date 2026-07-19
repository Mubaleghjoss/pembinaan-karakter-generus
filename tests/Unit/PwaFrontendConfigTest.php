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
        $styles = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

        $this->assertStringContainsString('pkg-auth-panel-content', $layout);
        $this->assertStringContainsString('rel="manifest"', $layout);
        $this->assertStringNotContainsString('authQuickStats', $layout);
        $this->assertStringNotContainsString('auth-theme-toggle', $layout);
        $this->assertStringNotContainsString("@yield('auth_footer')", $layout);
        $this->assertStringContainsString('height: 100svh', $styles);
        $this->assertStringContainsString('max-width: 28rem', $styles);

        foreach (['login.blade.php', 'siswa-login.blade.php', 'ortu-login.blade.php'] as $view) {
            $login = file_get_contents(dirname(__DIR__, 2).'/resources/views/auth/'.$view);

            $this->assertStringNotContainsString("@section('auth_footer')", $login);
            $this->assertStringNotContainsString("@section('auth_mark')", $login);
        }
    }

    public function test_public_mobile_navigation_uses_accessible_off_canvas_panel(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 2).'/resources/views/layouts/public.blade.php');
        $styles = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

        $this->assertStringContainsString('id="mobile-menu-overlay"', $layout);
        $this->assertStringContainsString('id="mobile-menu-close"', $layout);
        $this->assertStringContainsString("menu.classList.toggle('is-open', open)", $layout);
        $this->assertStringContainsString('menu.inert = !open', $layout);
        $this->assertStringContainsString('.pkg-mobile-menu-shell.is-open', $styles);
        $this->assertStringContainsString('cubic-bezier(0.785, 0.135, 0.15, 0.86)', $styles);
    }
}
