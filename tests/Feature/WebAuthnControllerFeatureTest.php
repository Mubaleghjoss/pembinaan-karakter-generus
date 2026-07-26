<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Models\WebAuthnCredential;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebAuthnControllerFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('logging.channels.auth', [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
        ]);
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->setUpMinimalSchema();
    }

    #[Test]
    public function siswa_status_endpoint_reports_valid_and_legacy_credentials(): void
    {
        $siswa = $this->createSiswa();

        WebAuthnCredential::create([
            'user_id' => $siswa->id,
            'user_type' => 'siswa',
            'credential_id' => 'valid-credential',
            'credential_public_key' => 'public-key',
            'signature_counter' => 1,
            'device_name' => 'Android',
        ]);

        WebAuthnCredential::create([
            'user_id' => $siswa->id,
            'user_type' => 'siswa',
            'credential_id' => 'legacy-credential',
            'device_name' => 'Legacy Android',
        ]);

        $response = $this->actingAs($siswa, 'siswa')
            ->getJson(route('siswa.webauthn.status'));

        $response->assertOk()
            ->assertJson([
                'has_biometric' => true,
                'legacy_credential_count' => 1,
            ]);

        $this->assertCount(2, $response->json('credentials'));
    }

    #[Test]
    public function has_credentials_endpoint_ignores_legacy_credentials(): void
    {
        $siswa = $this->createSiswa();

        WebAuthnCredential::create([
            'user_id' => $siswa->id,
            'user_type' => 'siswa',
            'credential_id' => 'legacy-only',
            'device_name' => 'Legacy Device',
        ]);

        $response = $this->actingAs($siswa, 'siswa')
            ->getJson(route('siswa.webauthn.has-credentials'));

        $response->assertOk()
            ->assertJson([
                'has_credentials' => false,
            ]);
    }

    #[Test]
    public function ortu_login_with_legacy_credential_requires_re_registration(): void
    {
        $ortu = $this->createSiswa([
            'ortu_username' => 'ortu.test',
            'ortu_password' => bcrypt('secret123'),
        ]);

        WebAuthnCredential::create([
            'user_id' => $ortu->id,
            'user_type' => 'ortu',
            'credential_id' => 'legacy-ortu',
            'device_name' => 'Old iPhone',
        ]);

        $response = $this->postJson(route('ortu.webauthn.login'), [
            'credential_id' => 'legacy-ortu',
            'response' => [
                'clientDataJSON' => 'Zm9v',
                'authenticatorData' => 'YmFy',
                'signature' => 'YmF6',
                'userHandle' => null,
            ],
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Perangkat biometrik ini memakai format lama. Silakan login biasa lalu daftarkan ulang biometrik.',
            ]);
    }

    #[Test]
    public function login_without_an_active_challenge_returns_a_recoverable_session_message(): void
    {
        $siswa = $this->createSiswa();

        WebAuthnCredential::create([
            'user_id' => $siswa->id,
            'user_type' => 'siswa',
            'credential_id' => 'credential-without-challenge',
            'credential_public_key' => 'public-key',
            'signature_counter' => 1,
            'device_name' => 'Android',
        ]);

        $response = $this->postJson(route('siswa.webauthn.login'), [
            'credential_id' => 'credential-without-challenge',
            'response' => [
                'clientDataJSON' => 'Zm9v',
                'authenticatorData' => 'YmFy',
                'signature' => 'YmF6',
                'userHandle' => null,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Sesi biometrik sudah kedaluwarsa. Coba lagi.',
            ]);
    }

    #[Test]
    public function authentication_session_endpoints_are_not_cacheable(): void
    {
        $csrfResponse = $this->getJson('/csrf-token');

        $csrfResponse->assertOk()
            ->assertJsonStructure(['token']);
        $this->assertStringContainsString('no-store', (string) $csrfResponse->headers->get('Cache-Control'));

        foreach ([route('login'), route('siswa.login'), route('ortu.login')] as $loginUrl) {
            $loginResponse = $this->get($loginUrl);

            $loginResponse->assertOk();
            $this->assertStringContainsString('no-store', (string) $loginResponse->headers->get('Cache-Control'));
        }
    }

    #[Test]
    public function logout_redirects_each_portal_to_a_unique_uncached_login_url(): void
    {
        $admin = User::create([
            'name' => 'Admin Logout',
            'username' => 'admin-logout',
            'email' => 'admin-logout@example.test',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);
        $siswa = $this->createSiswa([
            'ortu_username' => 'ortu.logout',
            'ortu_password' => bcrypt('secret123'),
        ]);

        $responses = [
            $this->actingAs($admin)->post(route('logout')),
            $this->actingAs($siswa, 'siswa')->post(route('siswa.logout')),
            $this->actingAs($siswa, 'ortu')->post(route('ortu.logout')),
        ];

        foreach ($responses as $response) {
            $response->assertRedirect();
            $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

            $query = [];
            parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

            $this->assertArrayHasKey('fresh', $query);
            $this->assertSame(12, strlen((string) $query['fresh']));
        }
    }

    #[Test]
    public function settings_page_provides_environment_warning_metadata(): void
    {
        config()->set('app.url', 'https://pkg.example.com');

        $admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'admin-uji',
            'email' => 'admin@example.test',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->withServerVariables([
                'HTTP_HOST' => 'staging.local',
                'HTTPS' => 'off',
            ])
            ->get('/biometrik');

        $response->assertOk()
            ->assertViewHas('webauthnEnvironment', function (array $environment): bool {
                return count($environment['warnings'] ?? []) >= 1
                    && ($environment['app_url_host'] ?? null) === 'pkg.example.com';
            });
    }

    private function setUpMinimalSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['webauthn_credentials', 'siswa', 'kelas', 'users', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode_kelas')->nullable();
            $table->string('tingkat')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->unique();
            $table->string('nama');
            $table->string('password')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->text('alamat')->nullable();
            $table->string('ortu_username')->nullable();
            $table->string('ortu_password')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->string('qr_secret_salt')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('ortu_last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('webauthn_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->string('credential_id')->unique();
            $table->longText('credential_public_key')->nullable();
            $table->unsignedBigInteger('signature_counter')->nullable();
            $table->string('attestation_format')->nullable();
            $table->string('aaguid')->nullable();
            $table->text('transports')->nullable();
            $table->string('user_handle')->nullable();
            $table->boolean('user_verified')->nullable();
            $table->boolean('backup_eligible')->nullable();
            $table->boolean('backed_up')->nullable();
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    private function createSiswa(array $overrides = []): Siswa
    {
        $kelas = Kelas::create([
            'nama' => 'Kelas Uji',
            'kode_kelas' => 'KU1',
            'tingkat' => '1',
            'is_active' => true,
        ]);

        return Siswa::create(array_merge([
            'nis' => 'NIS' . random_int(1000, 9999),
            'nama' => 'Siswa Uji',
            'password' => bcrypt('secret123'),
            'kelas_id' => $kelas->id,
            'jenis_kelamin' => 'L',
            'is_active' => true,
        ], $overrides));
    }
}
