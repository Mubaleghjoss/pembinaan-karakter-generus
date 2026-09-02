<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kontrak /download_app: halaman rilis + berkas APK dengan header yang membuat
 * Android menawarkan dialog install setelah unduhan selesai.
 */
class AppDownloadFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/app-releases-'.uniqid());
        File::ensureDirectoryExists($this->dir);
        config()->set('app_download.dir', $this->dir);
        config()->set('app_download.filename', 'pkgenerus-{version}.apk');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    private function putApk(string $name, string $body = 'PK-dummy-apk'): string
    {
        $path = $this->dir.DIRECTORY_SEPARATOR.$name;
        File::put($path, $body);

        return $path;
    }

    public function test_halaman_download_app_menampilkan_versi_dan_ukuran(): void
    {
        $this->putApk('pkgenerus-1.4.0-14.apk', str_repeat('A', 2048));

        $this->get('/download_app')
            ->assertOk()
            ->assertSee('Unduh Aplikasi PKGenerus')
            ->assertSee('1.4.0')
            ->assertSee('build 14')
            ->assertSee(route('public.download-app.apk'));
    }

    public function test_halaman_memberi_tahu_bila_apk_belum_diunggah(): void
    {
        $this->get('/download_app')
            ->assertOk()
            ->assertSee('Berkas aplikasi belum tersedia');
    }

    public function test_unduhan_apk_memakai_header_yang_memicu_dialog_install_android(): void
    {
        $body = str_repeat('B', 4096);
        $this->putApk('pkgenerus-1.4.0-14.apk', $body);

        $res = $this->get('/download_app/apk')->assertOk();

        $this->assertSame(
            'application/vnd.android.package-archive',
            $res->headers->get('Content-Type'),
            'Tanpa MIME ini Android tidak menawarkan install.'
        );
        $this->assertSame((string) strlen($body), $res->headers->get('Content-Length'));
        $disposition = (string) $res->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment;', $disposition);
        $this->assertStringContainsString('pkgenerus-1.4.0-14.apk', $disposition);
        $this->assertSame(hash('sha256', $body), $res->headers->get('X-Apk-Sha256'));
    }

    public function test_rilis_dengan_version_code_tertinggi_yang_dipilih(): void
    {
        $this->putApk('pkgenerus-1.3.0-9.apk');
        $this->putApk('pkgenerus-1.10.0-21.apk');
        $this->putApk('pkgenerus-1.4.0-14.apk');

        $this->get('/download_app')->assertOk()->assertSee('build 21');

        $this->assertStringContainsString(
            'pkgenerus-1.10.0-21.apk',
            (string) $this->get('/download_app/apk')->headers->get('Content-Disposition')
        );
    }

    public function test_changelog_dari_releases_json_ditampilkan(): void
    {
        $this->putApk('pkgenerus-1.4.0-14.apk');
        File::put($this->dir.DIRECTORY_SEPARATOR.'releases.json', json_encode([
            'pkgenerus-1.4.0-14.apk' => ['Tombol kembali tidak lagi menutup aplikasi'],
        ]));

        $this->get('/download_app')
            ->assertOk()
            ->assertSee('Tombol kembali tidak lagi menutup aplikasi');
    }

    public function test_unduhan_mulai_otomatis_hanya_untuk_android(): void
    {
        $this->putApk('pkgenerus-1.4.0-14.apk');

        $android = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36';
        $this->withHeader('User-Agent', $android)
            ->get('/download_app')
            ->assertOk()
            ->assertSee('Unduhan dimulai otomatis');

        $desktop = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36';
        $this->withHeader('User-Agent', $desktop)
            ->get('/download_app')
            ->assertOk()
            ->assertDontSee('Unduhan dimulai otomatis');
    }

    public function test_permintaan_apk_saat_belum_ada_rilis_mengembalikan_404(): void
    {
        $this->get('/download_app/apk')
            ->assertNotFound()
            ->assertSee('Berkas aplikasi belum tersedia');
    }
}
