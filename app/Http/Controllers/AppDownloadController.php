<?php

namespace App\Http\Controllers;

use App\Models\ThemeSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Distribusi APK PKGenerus: halaman /download_app dan berkas /download_app/apk.
 *
 * Catatan penting soal "popup install langsung muncul di HP":
 * Android TIDAK mengizinkan situs web memunculkan dialog install begitu saja.
 * Yang terjadi (dan yang di-optimalkan di sini) adalah:
 *   1. Browser mengunduh berkas .apk sampai selesai.
 *   2. Karena Content-Type = application/vnd.android.package-archive, Android
 *      mengenali berkas sebagai paket aplikasi lalu menampilkan notifikasi /
 *      tombol "Buka" yang membuka Package Installer -> POPUP INSTALL.
 * Syarat mutlak: header Content-Type & Content-Length benar, koneksi HTTPS,
 * dan pengguna sudah mengizinkan "Install unknown apps" untuk browsernya.
 */
class AppDownloadController extends Controller
{
    /** Halaman informasi + tombol unduh. */
    public function page(Request $request)
    {
        $release = $this->latestRelease();
        $isAndroid = $this->isAndroid($request);

        // Di Android, unduhan dimulai otomatis supaya notifikasi "Buka"
        // (pemicu popup install) muncul tanpa langkah tambahan.
        $autoStart = $isAndroid
            && (bool) config('app_download.auto_start', true)
            && $release !== null
            && ! $request->boolean('no_auto');

        return response()
            ->view('public.download-app', [
                'theme' => ThemeSetting::current(),
                'release' => $release,
                'isAndroid' => $isAndroid,
                'autoStart' => $autoStart,
                'minAndroid' => config('app_download.min_android', '7.0'),
                'isSecure' => $request->isSecure(),
            ])
            // Halaman versi tidak boleh di-cache proxy: user harus lihat rilis terbaru.
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    /** Kirim berkas APK dengan header yang membuat Android menawarkan install. */
    public function apk(Request $request): BinaryFileResponse|StreamedResponse|Response
    {
        $release = $this->latestRelease();

        if ($release === null) {
            return response()->view('public.download-app-missing', [
                'theme' => ThemeSetting::current(),
            ], 404);
        }

        $downloadName = str_replace(
            '{version}',
            $release['version_name'].'-'.$release['version_code'],
            basename((string) config('app_download.filename', 'pkgenerus-{version}.apk'))
        );
        // Nama dari konfigurasi masuk ke Content-Disposition. Batasi ke nama
        // ASCII aman agar separator path/control character tidak ikut ke header.
        $downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $downloadName) ?: 'pkgenerus.apk';
        if (! str_ends_with(strtolower($downloadName), '.apk')) {
            $downloadName .= '.apk';
        }

        return response()->download($release['path'], $downloadName, [
            // WAJIB: tanpa MIME ini Android menyimpan berkas sebagai file biasa
            // dan tidak menawarkan install.
            'Content-Type' => 'application/vnd.android.package-archive',
            // WAJIB: tanpa Content-Length, progres unduhan tidak diketahui dan
            // sebagian Download Manager menolak menawarkan install.
            'Content-Length' => (string) $release['size'],
            'Cache-Control' => 'no-store, max-age=0',
            'X-Apk-Sha256' => $release['sha256'],
        ]);
    }

    /**
     * Metadata rilis terbaru, atau null bila belum ada APK yang diunggah.
     *
     * @return array{path:string,file:string,version_name:string,version_code:string,size:int,sha256:string,released_at:Carbon,notes:array<int,string>}|null
     */
    protected function latestRelease(): ?array
    {
        $dir = (string) config('app_download.dir');

        if ($dir === '' || ! is_dir($dir)) {
            return null;
        }

        $realDir = realpath($dir);
        if ($realDir === false) {
            return null;
        }

        $files = array_values(array_filter(
            glob($realDir.DIRECTORY_SEPARATOR.'*.apk') ?: [],
            static function (string $file) use ($realDir): bool {
                $realFile = realpath($file);

                // Jangan layani direktori, file tak terbaca, atau symlink yang
                // lolos dari direktori rilis yang dikonfigurasi.
                return $realFile !== false
                    && is_file($realFile)
                    && is_readable($realFile)
                    && str_starts_with($realFile, $realDir.DIRECTORY_SEPARATOR);
            }
        ));

        if ($files === []) {
            return null;
        }

        // Rilis terbaru = versionCode tertinggi; fallback ke mtime bila nama
        // berkas tidak memuat versi.
        usort($files, function (string $a, string $b) {
            $va = $this->versionFromFilename($a);
            $vb = $this->versionFromFilename($b);

            if ($va['version_code'] !== $vb['version_code']) {
                return (int) $vb['version_code'] <=> (int) $va['version_code'];
            }

            return filemtime($b) <=> filemtime($a);
        });

        $path = $files[0];
        $meta = $this->versionFromFilename($path);
        $notes = $this->notesFor(basename($path), $dir);

        return [
            'path' => $path,
            'file' => basename($path),
            'version_name' => $meta['version_name'],
            'version_code' => $meta['version_code'],
            'size' => (int) filesize($path),
            'sha256' => hash_file('sha256', $path),
            'released_at' => Carbon::createFromTimestamp(filemtime($path)),
            'notes' => $notes,
        ];
    }

    /** Ambil versi dari nama berkas: pkgenerus-1.4.0-14.apk -> 1.4.0 / 14. */
    protected function versionFromFilename(string $path): array
    {
        $base = basename($path, '.apk');

        if (preg_match('/(\d+\.\d+(?:\.\d+)?)(?:[-_](\d+))?$/', $base, $m) === 1) {
            return [
                'version_name' => $m[1],
                'version_code' => $m[2] ?? '0',
            ];
        }

        return ['version_name' => $base, 'version_code' => '0'];
    }

    /**
     * Changelog opsional dari releases.json:
     *   { "pkgenerus-1.4.0-14.apk": ["Perbaikan tombol kembali", "..."] }
     *
     * @return array<int,string>
     */
    protected function notesFor(string $file, string $dir): array
    {
        $json = rtrim($dir, '/\\').DIRECTORY_SEPARATOR.'releases.json';

        if (! is_file($json)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($json), true);

        if (! is_array($data) || ! isset($data[$file]) || ! is_array($data[$file])) {
            return [];
        }

        return array_values(array_map('strval', $data[$file]));
    }

    protected function isAndroid(Request $request): bool
    {
        return str_contains(strtolower((string) $request->userAgent()), 'android');
    }
}
