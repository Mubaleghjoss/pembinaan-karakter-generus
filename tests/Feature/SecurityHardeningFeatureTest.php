<?php

namespace Tests\Feature;

use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_web_route_returns_real_404_with_branded_page(): void
    {
        $this->get('/alamat-yang-tidak-ada-untuk-pengujian')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');
    }

    public function test_security_headers_include_legacy_policy_and_strict_report_only_policy(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');

        $this->assertStringContainsString("'unsafe-inline'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString("'unsafe-inline'", (string) $response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertStringContainsString('/security/csp-report', (string) $response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_student_login_returns_generic_error_and_is_rate_limited(): void
    {
        Siswa::factory()->create(['nis' => 'SECURITY-001']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->from(route('siswa.login'))->post(route('siswa.login.post'), [
                'nis' => 'SECURITY-001',
                'password' => 'salah-total',
            ])->assertSessionHasErrors('nis');
        }

        $response = $this->from(route('siswa.login'))->post(route('siswa.login.post'), [
            'nis' => 'SECURITY-001',
            'password' => 'salah-total',
        ]);

        $response->assertSessionHasErrors('nis');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('nis'));
    }

    public function test_operational_and_parent_logins_use_the_same_generic_throttle(): void
    {
        $realms = [
            [route('login.post'), 'login', 'akun.tidak.ada'],
            [route('ortu.login.post'), 'username', 'ortu.tidak.ada'],
        ];

        foreach ($realms as [$endpoint, $field, $identity]) {
            for ($attempt = 1; $attempt <= 5; $attempt++) {
                $this->post($endpoint, [
                    $field => $identity,
                    'password' => 'salah-total',
                ])->assertSessionHasErrors($field);
            }

            $this->post($endpoint, [
                $field => $identity,
                'password' => 'salah-total',
            ])->assertSessionHasErrors($field);

            $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first($field));
        }
    }

    public function test_csp_report_endpoint_is_bounded_and_accepts_browser_report_format(): void
    {
        $this->postJson(route('security.csp-report'), [
            'csp-report' => [
                'document-uri' => 'https://pkgenerus.my.id/login',
                'blocked-uri' => 'inline',
                'effective-directive' => 'script-src-elem',
            ],
        ])->assertNoContent();
    }
}
