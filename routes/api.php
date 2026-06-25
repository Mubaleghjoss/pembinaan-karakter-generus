<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\PresensiController;
use App\Http\Controllers\Api\SiswaController;
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
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-activities', [\App\Http\Controllers\Api\DashboardController::class, 'recentActivities']);
});

// Karakter API (web session auth)
Route::middleware('web')->group(function () {
    Route::get('/karakter', [\App\Http\Controllers\KarakterController::class, 'index']);
});

// Sync/Export API (protected by sync key)
Route::prefix('sync')->middleware([\App\Http\Middleware\ValidateSyncKey::class])->group(function () {
    Route::get('/export', [\App\Http\Controllers\Api\SyncController::class, 'export']);
    Route::get('/media', [\App\Http\Controllers\Api\SyncController::class, 'media']);
    Route::get('/media-status', [\App\Http\Controllers\Api\SyncController::class, 'mediaStatus']);
    Route::get('/ping', [\App\Http\Controllers\Api\SyncController::class, 'ping']);
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
