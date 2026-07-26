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
        $this->assertStringContainsString('data-auth-login-form', $view);
        $this->assertStringNotContainsString('function refreshCsrfToken()', $view);
    }

    #[Test]
    public function ortu_login_view_uses_ortu_specific_webauthn_routes(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/auth/ortu-login.blade.php');

        $this->assertStringContainsString("route('ortu.webauthn.login-options')", $view);
        $this->assertStringContainsString("route('ortu.webauthn.login')", $view);
        $this->assertStringContainsString('data-auth-login-form', $view);
    }

    #[Test]
    public function shared_login_view_uses_the_central_auth_session_recovery(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/auth/login.blade.php');
        $script = file_get_contents(dirname(__DIR__, 2) . '/resources/js/biometric.js');
        $serviceWorker = file_get_contents(dirname(__DIR__, 2) . '/public/sw.js');

        $this->assertStringContainsString('data-auth-login-form', $view);
        $this->assertStringNotContainsString('function refreshCsrfToken()', $view);
        $this->assertStringContainsString("cache: 'no-store'", $script);
        $this->assertStringContainsString('response.status !== 419', $script);
        $this->assertStringContainsString("window.addEventListener('pageshow'", $script);
        $this->assertStringContainsString("document.addEventListener('visibilitychange'", $script);
        $this->assertStringContainsString('activeBiometricLoginButtons', $script);
        $this->assertStringContainsString('isCredentialManagerUnknownError', $script);
        $this->assertStringContainsString("credentialRequest.mediation = 'required'", $script);
        $this->assertStringContainsString('credentialManagerRetryCount < 1', $script);
        $this->assertStringContainsString("requestUrl.pathname === '/csrf-token'", $serviceWorker);
        $this->assertStringContainsString("fetch(event.request, { cache: 'no-store' })", $serviceWorker);
    }
}
