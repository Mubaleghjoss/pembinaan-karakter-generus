<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_password_login_pages_show_one_safe_error_without_query_message(): void
    {
        $siswa = Siswa::factory()->create([
            'nis' => 'LOGIN-001',
            'password' => Hash::make('password-benar'),
            'ortu_username' => 'ortu.login.001',
            'ortu_password' => Hash::make('password-orang-tua'),
        ]);
        $user = User::factory()->create([
            'username' => 'admin.login.001',
            'password' => Hash::make('password-admin'),
        ]);

        $cases = [
            [route('siswa.login.post'), route('siswa.login'), ['nis' => $siswa->nis, 'password' => 'salah'], 'NIS atau password salah.'],
            [route('ortu.login.post'), route('ortu.login'), ['username' => $siswa->ortu_username, 'password' => 'salah'], 'Username atau password salah.'],
            [route('login.post'), route('login'), ['login' => $user->username, 'password' => 'salah'], 'Username, nomor HP, email, atau password salah.'],
        ];

        foreach ($cases as [$endpoint, $loginPage, $credentials, $message]) {
            $response = $this->post($endpoint, $credentials);
            $response->assertRedirect($loginPage);
            $this->assertStringNotContainsString('error=', (string) $response->headers->get('Location'));

            $page = $this->get($loginPage);
            $page->assertOk()->assertSee($message);
            $this->assertSame(1, substr_count($page->getContent(), $message));
        }

        $this->get(route('siswa.login', ['error' => 'Pesan palsu dari URL']))
            ->assertOk()
            ->assertDontSee('Pesan palsu dari URL');
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

    public function test_cpanel_front_controller_and_rewrite_enforce_server_level_hardening(): void
    {
        $deployScript = file_get_contents(base_path('deploy/cpanel/deploy_ssh.sh'));
        $frontController = file_get_contents(base_path('deploy/cpanel/public_html_index.pembinaan-karakter-generus.php.example'));

        $this->assertStringContainsString('RewriteCond %{HTTPS} !=on', $deployScript);
        $this->assertStringNotContainsString('HTTP:X-Forwarded-Proto', $deployScript);
        $this->assertStringContainsString('expose_php=Off', $deployScript);
        $this->assertStringContainsString("header_remove('X-Powered-By')", $frontController);
    }
}
