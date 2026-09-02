<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BinaanPamongController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GamifikasiController;
use App\Http\Controllers\Api\KarakterLuhurController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\KelasSekolahController;
use App\Http\Controllers\Api\MateriController;
use App\Http\Controllers\Api\MobileServerFeaturesController;
use App\Http\Controllers\Api\MobileWebBridgeController;
use App\Http\Controllers\Api\OrtuMonitoringController;
use App\Http\Controllers\Api\PamongVerifikasiController;
use App\Http\Controllers\Api\PresensiController;
use App\Http\Controllers\Api\QuranReadingController;
use App\Http\Controllers\Api\SiswaAuthController;
use App\Http\Controllers\Api\SiswaController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TugasPkgController;
use App\Http\Controllers\KarakterController;
use App\Http\Middleware\ValidateSyncKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes with rate limiting
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(['throttle:auth']);

    // QR code scanning (public for mobile app) with rate limiting
    Route::post('/presensi/scan-qr', [PresensiController::class, 'scanQr'])
        ->middleware(['throttle:qr-scan']);

    // Public read-only Class endpoints to avoid 401 for listing/detail/statistics
    Route::get('/kelas', [KelasController::class, 'index']);
    Route::get('/kelas/stats', [KelasController::class, 'stats']);
    Route::get('/kelas/statistics', [KelasController::class, 'statistics']);
    Route::get('/kelas/tingkat-options', [KelasController::class, 'tingkatOptions']);
    Route::get('/kelas/{kelas}', [KelasController::class, 'show']);

    // Login siswa & orang tua (token Sanctum untuk aplikasi mobile).
    Route::post('/siswa/login', [SiswaAuthController::class, 'loginSiswa'])
        ->middleware(['throttle:auth']);
    Route::post('/ortu/login', [SiswaAuthController::class, 'loginOrtu'])
        ->middleware(['throttle:auth']);

    // 29 Karakter Luhur — materi bacaan publik, tidak memuat data pribadi.
    Route::get('/karakter-luhur', [KarakterLuhurController::class, 'index']);
    Route::get('/karakter-luhur/{slug}', [KarakterLuhurController::class, 'show']);
});

// Protected routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Student management (requires admin or teacher permissions)
    Route::middleware(['role.permission:manage_students,view_students'])->group(function () {
        Route::get('/siswa', [SiswaController::class, 'index']);
        Route::get('/siswa/statistics', [SiswaController::class, 'statistics']);
        Route::get('/siswa/{siswa}', [SiswaController::class, 'show']);
        Route::get('/siswa/{siswa}/qr-code', [SiswaController::class, 'qrCode']);
    });

    Route::middleware(['role.permission:manage_students'])->group(function () {
        Route::post('/siswa', [SiswaController::class, 'store']);
        Route::put('/siswa/{siswa}', [SiswaController::class, 'update']);
        Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy']);
        Route::post('/siswa/{siswa}/generate-qr', [SiswaController::class, 'generateQr'])
            ->middleware(['throttle:qr-generate']);
    });

    // Class management (requires admin or teacher permissions)
    // Only write operations remain protected; read-only routes moved to public group.
    Route::middleware(['role.permission:manage_students'])->group(function () {
        Route::post('/kelas', [KelasController::class, 'store']);
        Route::put('/kelas/{kelas}', [KelasController::class, 'update']);
        Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy']);
    });

    // ── Sumber data AKTIF pengganti /kelas yang deprecated ───────────────
    // Binaan Pamong (pivot pamong_siswa) dan Kelas Sekolah (siswa.school_grade).
    // Batas akses per pamong dicek di controller lewat Siswa::scopeForUser,
    // sama seperti /pamong/verifikasi.
    Route::middleware(['role.permission:manage_students,view_students'])->group(function () {
        Route::get('/binaan-pamong', [BinaanPamongController::class, 'index']);
        Route::get('/binaan-pamong/{pamong}/siswa', [BinaanPamongController::class, 'siswa']);
        Route::get('/kelas-sekolah', [KelasSekolahController::class, 'index']);
        Route::get('/kelas-sekolah/{kode}/siswa', [KelasSekolahController::class, 'siswa']);
    });

    // Attendance management
    Route::middleware(['role.permission:manage_attendance,view_attendance'])->group(function () {
        Route::get('/presensi', [PresensiController::class, 'index']);
        Route::get('/presensi/statistics', [PresensiController::class, 'statistics']);
    });

    Route::middleware(['role.permission:manage_attendance'])->group(function () {
        Route::post('/presensi', [PresensiController::class, 'store']);
        Route::put('/presensi/{presensi}', [PresensiController::class, 'update']);
        Route::post('/presensi/{presensi}/verify', [PresensiController::class, 'verify']);
    });

    // Dashboard Stats
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-activities', [DashboardController::class, 'recentActivities']);

    // Kalender read-only lintas peran. Cakupan data selalu ditentukan server.
    Route::get('/calendar/events', [CalendarController::class, 'events']);

    // ── Akun siswa / orang tua (token Sanctum milik model Siswa) ──────────
    Route::get('/siswa-account/me', [SiswaAuthController::class, 'me']);
    Route::post('/siswa-account/logout', [SiswaAuthController::class, 'logout']);

    // Materi (read-only untuk mobile; pembuatan materi tetap di web admin)
    Route::get('/materi', [MateriController::class, 'index']);
    Route::get('/materi/folders', [MateriController::class, 'folders']);
    Route::get('/materi/{materi}', [MateriController::class, 'show']);

    // Tugas PKG (siswa mengerjakan, ortu berkomentar)
    Route::get('/tugas-pkg', [TugasPkgController::class, 'index']);
    Route::get('/tugas-pkg/summary', [TugasPkgController::class, 'summary']);
    Route::get('/tugas-pkg/history', [TugasPkgController::class, 'history']);
    Route::post('/tugas-pkg/{karakter}/submit', [TugasPkgController::class, 'submit']);
    Route::post('/tugas-pkg/checklist/{checklist}/comment', [TugasPkgController::class, 'comment']);

    // Verifikasi tugas PKG oleh pamong/admin (token model User).
    // Batas akses siswa binaan dicek di controller, bukan middleware role,
    // karena pamong perlu lolos permission umum lalu difilter per siswa.
    Route::get('/pamong/verifikasi', [PamongVerifikasiController::class, 'index']);
    Route::post('/pamong/verifikasi/bulk', [PamongVerifikasiController::class, 'bulkVerify']);
    Route::post('/pamong/verifikasi/{checklist}', [PamongVerifikasiController::class, 'verify']);
    Route::delete('/pamong/verifikasi/{checklist}', [PamongVerifikasiController::class, 'unverify']);

    // Monitoring orang tua (token model Siswa: ability 'ortu' atau 'siswa').
    Route::get('/ortu/ringkasan', [OrtuMonitoringController::class, 'ringkasan']);
    Route::get('/ortu/presensi', [OrtuMonitoringController::class, 'presensi']);
    Route::get('/ortu/tugas', [OrtuMonitoringController::class, 'tugas']);
    Route::get('/ortu/quran', [OrtuMonitoringController::class, 'quran']);

    // Tracer bacaan Quran (entri manual; alur lembar + scan tetap di web)
    Route::get('/quran/entries', [QuranReadingController::class, 'index']);
    Route::get('/quran/progress', [QuranReadingController::class, 'progress']);
    Route::get('/quran/surahs', [QuranReadingController::class, 'surahs']);
    Route::post('/quran/entries', [QuranReadingController::class, 'store']);
    Route::delete('/quran/entries/{entry}', [QuranReadingController::class, 'destroy']);
    Route::post('/quran/barcode/identify', [QuranReadingController::class, 'identifyBarcode']);
    Route::post('/quran/barcode/store', [QuranReadingController::class, 'storeBarcode']);

    // Gamifikasi (token model Siswa: siswa bermain, ortu memantau).
    // Poin TIDAK dihitung di sini — endpoint ini membaca point_transactions
    // yang sudah ditulis alur tugas PKG (source 'character') dan presensi
    // (source 'attendance'), plus game (source 'game').
    Route::get('/gamifikasi/ringkasan', [GamifikasiController::class, 'ringkasan']);
    Route::get('/gamifikasi/leaderboard', [GamifikasiController::class, 'leaderboard']);
    Route::get('/gamifikasi/history', [GamifikasiController::class, 'history']);
    Route::get('/gamifikasi/badges', [GamifikasiController::class, 'badges']);

    // Game karakter (kunci jawaban di cache server, bukan dikirim ke klien).
    Route::get('/game/info', [GameController::class, 'info']);
    Route::post('/game/solo/mulai', [GameController::class, 'mulaiSolo']);
    Route::post('/game/solo/submit', [GameController::class, 'submitSolo']);
    Route::get('/game/arcade/kata', [GameController::class, 'arcadeKata']);
    Route::post('/game/arcade/skor', [GameController::class, 'simpanSkorArcade']);
    Route::get('/game/arcade/leaderboard', [GameController::class, 'arcadeLeaderboard']);

    // Dashboard mobile untuk 10 fitur server tambahan. Semua angka/item dibaca
    // dari database Laravel, bukan data statis di aplikasi.
    Route::get('/mobile/fitur-server', [MobileServerFeaturesController::class, 'index']);

    // Jembatan sesi web sekali pakai: menukar token Sanctum dengan URL
    // berumur pendek supaya halaman server ber-sesi (chat, biometrik, jurnal
    // RPP, lembar Quran lanjutan) bisa dibuka WebView aplikasi. Target
    // dibatasi allowlist MobileServerFeaturesController::webTargets().
    Route::post('/mobile/web-bridge', [MobileWebBridgeController::class, 'issue'])
        ->middleware(['throttle:auth']);
});

// Karakter API (web session auth)
Route::middleware('web')->group(function () {
    Route::get('/karakter', [KarakterController::class, 'index']);
});

// Sync/Export API (protected by sync key)
Route::prefix('sync')->middleware([ValidateSyncKey::class])->group(function () {
    Route::get('/export', [SyncController::class, 'export']);
    Route::get('/media', [SyncController::class, 'media']);
    Route::get('/media-status', [SyncController::class, 'mediaStatus']);
    Route::get('/ping', [SyncController::class, 'ping']);
});

/*
|--------------------------------------------------------------------------
| Rate Limiting Configuration
|--------------------------------------------------------------------------
|
| Rate limiters are configured in bootstrap/app.php:
| - 'api': 60 requests/minute (default)
| - 'qr-scan': configurable via config/qrcode.php (default: 30/min)
| - 'qr-generate': configurable via config/qrcode.php (default: 10/min)
| - 'auth': 5 requests/minute for login attempts
|
*/
