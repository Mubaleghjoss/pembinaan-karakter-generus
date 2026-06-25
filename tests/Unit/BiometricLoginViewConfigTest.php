<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BiometricLoginViewConfigTest extends TestCase
{
    #[Test]
    public function siswa_login_view_uses_siswa_specific_webauthn_routes(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/auth/siswa-login.blade.php');

        $this->assertStringContainsString("route('siswa.webauthn.login-options')", $view);
        $this->assertStringContainsString("route('siswa.webauthn.login')", $view);
    }

    #[Test]
    public function ortu_login_view_uses_ortu_specific_webauthn_routes(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/auth/ortu-login.blade.php');

        $this->assertStringContainsString("route('ortu.webauthn.login-options')", $view);
        $this->assertStringContainsString("route('ortu.webauthn.login')", $view);
    }
}
