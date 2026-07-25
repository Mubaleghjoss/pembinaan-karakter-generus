<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BeritaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\MateriRppJournalController;
use App\Http\Controllers\MateriTargetController;
use App\Http\Controllers\PamongController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiswaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Removed insecure public cache-clear route.
/* Route::get('/clear-cache-xyz', function () {
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    \Artisan::call('view:clear');
    \Artisan::call('route:clear');
    return 'Cache cleared! ✅ Sekarang hapus route ini dari web.php';
}); */

// Public routes (no auth required)
Route::get('/', [App\Http\Controllers\PublicController::class, 'index'])->name('public.index');
Route::get('/berita-publik/{slug}', [App\Http\Controllers\PublicController::class, 'berita'])->name('public.berita');
Route::get('/scan-presensi', [App\Http\Controllers\PublicController::class, 'scanner'])->name('public.scanner');
Route::get('/materi', [App\Http\Controllers\PublicController::class, 'materiIndex'])->name('materi.index');
Route::get('/materi-publik/{materi}/pdf/{index}', [App\Http\Controllers\PublicController::class, 'materiPdfView'])
    ->whereNumber('index')
    ->name('public.materi.pdf.view');
Route::get('/materi-publik/{materi}/pdf/{index}/download', [App\Http\Controllers\PublicController::class, 'materiPdfDownload'])
    ->whereNumber('index')
    ->name('public.materi.pdf.download');
Route::get('/materi-publik/{materi}', [App\Http\Controllers\PublicController::class, 'materiShow'])->name('public.materi.show');
Route::get('/kalender', [App\Http\Controllers\CalendarController::class, 'publicIndex'])->name('public.calendar.index');
Route::get('/kalender/events', [App\Http\Controllers\CalendarController::class, 'publicEvents'])->name('public.calendar.events');
// Pendataan guru privat. Sengaja tidak ditampilkan pada navigasi publik.
Route::get('/pendataanguru', [App\Http\Controllers\TeacherAvailabilityController::class, 'index'])
    ->name('public.teacher-availability.index');
Route::post('/pendataanguru/akses', [App\Http\Controllers\TeacherAvailabilityController::class, 'unlock'])
    ->middleware('throttle:5,1')
    ->name('public.teacher-availability.unlock');
Route::post('/pendataanguru', [App\Http\Controllers\TeacherAvailabilityController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('public.teacher-availability.store');
Route::get('/pendataanguru/selesai', [App\Http\Controllers\TeacherAvailabilityController::class, 'success'])
    ->name('public.teacher-availability.success');
Route::get('/pendataanguru/hasil/{teacherProfile}/{downloadToken}/pdf', [App\Http\Controllers\TeacherAvailabilityController::class, 'pdf'])
    ->middleware('throttle:30,1')
    ->name('public.teacher-availability.pdf');
Route::get('/konfirmasi-pengajar/{token}', [App\Http\Controllers\TeacherConfirmationController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('public.teacher-confirmation.show');
Route::post('/konfirmasi-pengajar/{token}', [App\Http\Controllers\TeacherConfirmationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.teacher-confirmation.store');
// Public QR Scan endpoint (no auth required for public scanner)
Route::post('/qr/scan', [PresensiController::class, 'scan'])->name('qr.scan.post');
Route::post('/face-presensi/scan', [App\Http\Controllers\FaceAttendanceController::class, 'scan'])
    ->middleware('throttle:qr-scan')
    ->name('face-presensi.scan');

// Pendaftaran Generus privat. Sengaja tidak ditampilkan pada navigasi publik.
Route::get('/daftarpkg', [App\Http\Controllers\GenerusRegistrationController::class, 'index'])
    ->name('public.generus-registration.short.index');
Route::post('/daftarpkg/akses', [App\Http\Controllers\GenerusRegistrationController::class, 'unlock'])
    ->middleware('throttle:5,1')
    ->name('public.generus-registration.short.unlock');
Route::get('/daftarpkg/cari-generus', [App\Http\Controllers\GenerusRegistrationController::class, 'searchStudents'])
    ->middleware('throttle:30,1')
    ->name('public.generus-registration.short.search');
Route::post('/daftarpkg/verifikasi-akun', [App\Http\Controllers\GenerusRegistrationController::class, 'verifyExisting'])
    ->middleware('throttle:5,1')
    ->name('public.generus-registration.short.verify');
Route::post('/daftarpkg', [App\Http\Controllers\GenerusRegistrationController::class, 'storeShort'])
    ->middleware('throttle:5,1')
    ->name('public.generus-registration.short.store');
Route::get('/daftarpkg/hasil/{registration}/{downloadToken}', [App\Http\Controllers\GenerusRegistrationController::class, 'result'])
    ->name('public.generus-registration.short.result');
Route::get('/daftarpkg/hasil/{registration}/{downloadToken}/pdf', [App\Http\Controllers\GenerusRegistrationController::class, 'pdf'])
    ->name('public.generus-registration.short.pdf');

// Tautan privat lama tetap diterima untuk masa transisi.
Route::get('/pendaftaran-generus/hasil/{registration}/{downloadToken}', [App\Http\Controllers\GenerusRegistrationController::class, 'result'])
    ->name('public.generus-registration.result');
Route::get('/pendaftaran-generus/hasil/{registration}/{downloadToken}/pdf', [App\Http\Controllers\GenerusRegistrationController::class, 'pdf'])
    ->name('public.generus-registration.pdf');
Route::get('/pendaftaran-generus/{token}', [App\Http\Controllers\GenerusRegistrationController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('public.generus-registration.show');
Route::post('/pendaftaran-generus/{token}', [App\Http\Controllers\GenerusRegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('public.generus-registration.store');

// Laporan Penyaksian - Public Form (like Google Form)
Route::get('/lapor-pkg', [App\Http\Controllers\LaporanPenyaksianController::class, 'create'])->name('laporan-penyaksian.create');
Route::post('/lapor-pkg', [App\Http\Controllers\LaporanPenyaksianController::class, 'store'])->name('laporan-penyaksian.store');
Route::get('/lapor-pkg/siswa-list', [App\Http\Controllers\LaporanPenyaksianController::class, 'getSiswaList'])->name('laporan-penyaksian.siswa-list');
Route::get('/lapor-pkg/pamong-list', [App\Http\Controllers\LaporanPenyaksianController::class, 'getPamongList'])->name('laporan-penyaksian.pamong-list');
Route::get('/lapor-pkg/generus-list', [App\Http\Controllers\LaporanPenyaksianController::class, 'getGenerusList'])->name('laporan-penyaksian.generus-list');
Route::get('/game-29-karakter', [App\Http\Controllers\RpgGameController::class, 'publicIndex'])
    ->middleware('throttle:rpg-public')
    ->name('public.rpg.index');
Route::get('/game-29-karakter/{rpgMap}/main', [App\Http\Controllers\RpgGameController::class, 'publicPlay'])
    ->middleware('throttle:rpg-public')
    ->name('public.rpg.play');
Route::post('/game-29-karakter/{rpgMap}/presence', [App\Http\Controllers\RpgGameController::class, 'publicPresence'])
    ->middleware('throttle:rpg-presence')
    ->name('public.rpg.presence');
Route::get('/rpg-admin', function () {
    if (! auth()->check()) {
        return redirect()->route('public.rpg.index');
    }

    return redirect()->route('admin.rpg.index');
});
Route::get('/rpg-admin/play/{rpgMap}', function (App\Models\RpgMap $rpgMap) {
    return redirect()->route('public.rpg.play', $rpgMap);
});
Route::post('/rpg-admin/play/{rpgMap}/presence', [App\Http\Controllers\RpgGameController::class, 'publicPresence'])
    ->middleware('throttle:rpg-presence');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// CSRF Token refresh endpoint
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});

// Shared WebAuthn biometric routes (public - no auth needed for login flow)
Route::prefix('webauthn')->name('webauthn.')->group(function () {
    Route::post('/login-options', [App\Http\Controllers\Auth\WebAuthnController::class, 'loginOptions'])->name('login-options');
    Route::post('/login', [App\Http\Controllers\Auth\WebAuthnController::class, 'login'])->name('login');
    Route::get('/has-credentials', [App\Http\Controllers\Auth\WebAuthnController::class, 'hasCredentials'])->name('has-credentials');
});

// Siswa Authentication routes
Route::prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\SiswaAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\SiswaAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [App\Http\Controllers\Auth\SiswaAuthController::class, 'logout'])->name('logout');

    // WebAuthn biometric routes (public - no auth needed for login flow)
    Route::post('/webauthn/login-options', [App\Http\Controllers\Auth\WebAuthnController::class, 'loginOptions'])->name('webauthn.login-options');
    Route::post('/webauthn/login', [App\Http\Controllers\Auth\WebAuthnController::class, 'login'])->name('webauthn.login');
    Route::get('/webauthn/has-credentials', [App\Http\Controllers\Auth\WebAuthnController::class, 'hasCredentials'])->name('webauthn.has-credentials');
    
    // Protected siswa routes
    Route::middleware('auth.siswa')->group(function () {
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'storeSiswa'])
            ->name('pwa.push-subscriptions.store');
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroySiswa'])
            ->name('pwa.push-subscriptions.destroy');

        // WebAuthn protected routes (registration & management)
        Route::get('/webauthn/register-options', [App\Http\Controllers\Auth\WebAuthnController::class, 'registerOptions'])->name('webauthn.register-options');
        Route::post('/webauthn/register', [App\Http\Controllers\Auth\WebAuthnController::class, 'register'])->name('webauthn.register');
        Route::get('/webauthn/status', [App\Http\Controllers\Auth\WebAuthnController::class, 'status'])->name('webauthn.status');
        Route::delete('/webauthn/{id}', [App\Http\Controllers\Auth\WebAuthnController::class, 'destroy'])->name('webauthn.destroy');
        Route::post('/webauthn/dismiss-prompt', [App\Http\Controllers\Auth\WebAuthnController::class, 'dismissPrompt'])->name('webauthn.dismiss-prompt');
        Route::get('/biometrik', [App\Http\Controllers\Auth\WebAuthnController::class, 'settingsPage'])->name('biometrik');

        Route::get('/dashboard', [App\Http\Controllers\SiswaDashboardController::class, 'index'])->name('dashboard');
        Route::put('/profil-penempatan', [App\Http\Controllers\ProfileAssignmentController::class, 'updateSiswa'])->name('profile-assignment.update');
        Route::get('/materi', [App\Http\Controllers\MateriController::class, 'siswaIndex'])->name('materi.index');
        Route::get('/materi/{materi}', [App\Http\Controllers\MateriController::class, 'siswaShow'])->name('materi.show');
        Route::post('/materi-targets/{target}/toggle', [MateriTargetController::class, 'siswaToggle'])->name('materi-targets.toggle');
        Route::get('/jurnal-rpp', [App\Http\Controllers\SiswaMateriRppJournalController::class, 'index'])->name('materi-rpp-journals.index');
        Route::get('/jurnal-rpp/{scheduleReminder}', [App\Http\Controllers\SiswaMateriRppJournalController::class, 'show'])->name('materi-rpp-journals.show');
        Route::post('/jurnal-rpp/{scheduleReminder}', [App\Http\Controllers\SiswaMateriRppJournalController::class, 'store'])->name('materi-rpp-journals.store');
        Route::get('/pr', function () {
            return redirect()
                ->route('siswa.tugas-pkg.index')
                ->with('success', 'Menu tugas lama siswa sudah diganti. Gunakan halaman Tugas PKG.');
        })->name('pr.index');
        Route::get('/pr/{pr}', function () {
            return redirect()
                ->route('siswa.tugas-pkg.index')
                ->with('success', 'Halaman tugas lama sudah diganti. Gunakan daftar Tugas PKG yang aktif.');
        })->name('pr.show');
        Route::post('/pr/{pr}/submit', function () {
            return redirect()
                ->route('siswa.tugas-pkg.index')
                ->with('success', 'Pengumpulan tugas lama sudah tidak dipakai. Gunakan alur Tugas PKG.');
        })->name('pr.submit');
        Route::get('/kartu', [App\Http\Controllers\SiswaDashboardController::class, 'kartu'])->name('kartu');
        Route::get('/kartu/print', [App\Http\Controllers\SiswaDashboardController::class, 'kartuPrint'])->name('kartu.print');
        Route::get('/face-profile', [App\Http\Controllers\FaceAttendanceController::class, 'profile'])->name('face-profile.show');
        Route::post('/face-profile/enroll', [App\Http\Controllers\FaceAttendanceController::class, 'enroll'])->name('face-profile.enroll');

        // Canonical Tugas PKG routes
        Route::prefix('/tugas-pkg')->name('tugas-pkg.')->group(function () {
            Route::get('/', [App\Http\Controllers\SiswaKarakterController::class, 'index'])->name('index');
            Route::post('/{karakter}/submit', [App\Http\Controllers\SiswaKarakterController::class, 'toggle'])->name('submit');
            Route::get('/riwayat', [App\Http\Controllers\SiswaKarakterController::class, 'history'])->name('history');
            Route::get('/terverifikasi', [App\Http\Controllers\SiswaKarakterController::class, 'verifiedHistory'])->name('verified-history');
        });

        // Tugas PKG compatibility routes
        Route::get('/karakter', [App\Http\Controllers\SiswaKarakterController::class, 'index'])->name('karakter.index');
        Route::post('/karakter/{karakter}/toggle', [App\Http\Controllers\SiswaKarakterController::class, 'toggle'])->name('karakter.toggle');
        Route::get('/karakter/history', [App\Http\Controllers\SiswaKarakterController::class, 'history'])->name('karakter.history');
        Route::get('/karakter/verified-history', [App\Http\Controllers\SiswaKarakterController::class, 'verifiedHistory'])->name('karakter.verified-history');
        
        // Kehadiran PKG
        Route::get('/kehadiran', [App\Http\Controllers\CekKehadiranController::class, 'siswaIndex'])->name('kehadiran.index');
        
        // Profile routes
        Route::get('/profile', [App\Http\Controllers\SiswaDashboardController::class, 'profile'])->name('profile');
        Route::get('/profile/surat-pernyataan', [App\Http\Controllers\GenerusRegistrationController::class, 'siswaPreview'])->name('profile.statement.preview');
        Route::get('/profile/surat-pernyataan/unduh', [App\Http\Controllers\GenerusRegistrationController::class, 'siswaDownload'])->name('profile.statement.download');
        Route::post('/profile/update-photo', [App\Http\Controllers\SiswaDashboardController::class, 'updatePhoto'])->name('profile.update-photo');
        Route::post('/profile/update', [App\Http\Controllers\SiswaDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/update-account', [App\Http\Controllers\SiswaDashboardController::class, 'updateAccount'])->name('profile.update-account');
        
        // Chat routes
        Route::get('/chat', [App\Http\Controllers\SiswaChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/messages', [App\Http\Controllers\SiswaChatController::class, 'getMessages'])->name('chat.messages');
        Route::post('/chat/send', [App\Http\Controllers\SiswaChatController::class, 'sendMessage'])->name('chat.send');
        Route::get('/chat/unread', [App\Http\Controllers\SiswaChatController::class, 'getUnreadCount'])->name('chat.unread');
        Route::get('/chat/unread-counts', [App\Http\Controllers\SiswaChatController::class, 'getUnreadCountPerContact'])->name('chat.unread.counts');
        
        // Group Chat routes for Siswa
        Route::prefix('group-chat')->name('group-chat.')->group(function () {
            Route::get('/', [App\Http\Controllers\UserGroupChatController::class, 'index'])->name('index');
            Route::get('/unread', [App\Http\Controllers\UserGroupChatController::class, 'getUnreadCount'])->name('unread');
            Route::get('/{chatGroup}/messages', [App\Http\Controllers\UserGroupChatController::class, 'getMessages'])->name('messages');
            Route::post('/{chatGroup}/send', [App\Http\Controllers\UserGroupChatController::class, 'sendMessage'])->name('send');
            Route::get('/{chatGroup}/info', [App\Http\Controllers\UserGroupChatController::class, 'getGroupInfo'])->name('info');
        });
        
        // Gamification routes for Siswa
        Route::prefix('gamification')->name('gamification.')->group(function () {
            Route::get('/', [App\Http\Controllers\GamificationController::class, 'dashboard'])->name('dashboard');
            Route::get('/leaderboard', [App\Http\Controllers\GamificationController::class, 'leaderboard'])->name('leaderboard');
            Route::get('/badges', [App\Http\Controllers\GamificationController::class, 'badges'])->name('badges');
            Route::get('/badges/{badge}', [App\Http\Controllers\GamificationController::class, 'badgeDetail'])->name('badge-detail');
            Route::get('/history', [App\Http\Controllers\GamificationController::class, 'pointHistory'])->name('history');
            Route::get('/widget', [App\Http\Controllers\GamificationController::class, 'widgetData'])->name('widget');
            Route::get('/certificate/{level}/download', [App\Http\Controllers\CertificateController::class, 'download'])->name('certificate.download');
        });
        
        // RPG Quest routes for Siswa
        Route::prefix('rpg')->name('rpg.')->group(function () {
            Route::get('/', [App\Http\Controllers\RpgGameController::class, 'index'])->name('index');
            Route::get('/beta-3d', [App\Http\Controllers\RpgGameController::class, 'beta3d'])->name('beta-3d');
            Route::get('/{rpgMap}/play', [App\Http\Controllers\RpgGameController::class, 'play'])->name('play');
            Route::post('/{rpgMap}/move', [App\Http\Controllers\RpgGameController::class, 'move'])->name('move');
            Route::post('/{rpgMap}/answer', [App\Http\Controllers\RpgGameController::class, 'answer'])->name('answer');
            Route::get('/{rpgMap}/state', [App\Http\Controllers\RpgGameController::class, 'getGameState'])->name('state');
            Route::post('/character', [App\Http\Controllers\RpgGameController::class, 'updateCharacter'])->name('character');
            Route::post('/heartbeat', [App\Http\Controllers\RpgGameController::class, 'heartbeat'])->name('heartbeat');
            Route::post('/{rpgMap}/reset', [App\Http\Controllers\RpgGameController::class, 'resetSession'])->name('reset');
        });
        
        // Calendar routes for Siswa
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [App\Http\Controllers\CalendarController::class, 'siswaIndex'])->name('index');
            Route::get('/events', [App\Http\Controllers\CalendarController::class, 'siswaEvents'])->name('events');
        });
    });
});

// Ortu (Parent) Authentication & Portal routes
Route::prefix('ortu')->name('ortu.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\OrtuAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\OrtuAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [App\Http\Controllers\Auth\OrtuAuthController::class, 'logout'])->name('logout');

    // WebAuthn biometric routes (public - no auth needed for login flow)
    Route::post('/webauthn/login-options', [App\Http\Controllers\Auth\WebAuthnController::class, 'loginOptions'])->name('webauthn.login-options');
    Route::post('/webauthn/login', [App\Http\Controllers\Auth\WebAuthnController::class, 'login'])->name('webauthn.login');
    Route::get('/webauthn/has-credentials', [App\Http\Controllers\Auth\WebAuthnController::class, 'hasCredentials'])->name('webauthn.has-credentials');

    // Protected ortu routes
    Route::middleware('auth.ortu')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\OrtuDashboardController::class, 'index'])->name('dashboard');
        Route::get('/materi', [App\Http\Controllers\MateriController::class, 'ortuIndex'])->name('materi.index');
        Route::get('/materi/{materi}', [App\Http\Controllers\MateriController::class, 'ortuShow'])->name('materi.show');

        // WebAuthn biometric routes (ortu)
        Route::get('/webauthn/register-options', [App\Http\Controllers\Auth\WebAuthnController::class, 'registerOptions'])->name('webauthn.register-options');
        Route::post('/webauthn/register', [App\Http\Controllers\Auth\WebAuthnController::class, 'register'])->name('webauthn.register');
        Route::get('/webauthn/status', [App\Http\Controllers\Auth\WebAuthnController::class, 'status'])->name('webauthn.status');
        Route::delete('/webauthn/{id}', [App\Http\Controllers\Auth\WebAuthnController::class, 'destroy'])->name('webauthn.destroy');
        Route::post('/webauthn/dismiss-prompt', [App\Http\Controllers\Auth\WebAuthnController::class, 'dismissPrompt'])->name('webauthn.dismiss-prompt');
        Route::get('/biometrik', [App\Http\Controllers\Auth\WebAuthnController::class, 'settingsPage'])->name('biometrik');

        // Jadwal (view-only)
        Route::get('/jadwal', [App\Http\Controllers\OrtuJadwalController::class, 'index'])->name('jadwal');
        Route::get('/jadwal/events', [App\Http\Controllers\OrtuJadwalController::class, 'getEvents'])->name('jadwal.events');

        // Tugas PKG
        Route::get('/tugas', [App\Http\Controllers\OrtuTugasController::class, 'index'])->name('tugas');
        Route::post('/tugas/{checklist}/comment', [App\Http\Controllers\OrtuTugasController::class, 'addComment'])->name('tugas.comment');

        // Kehadiran PKG
        Route::get('/kehadiran', [App\Http\Controllers\CekKehadiranController::class, 'ortuIndex'])->name('kehadiran');

        // Chat Pamong
        Route::get('/chat', [App\Http\Controllers\OrtuChatController::class, 'index'])->name('chat');
        Route::get('/chat/messages', [App\Http\Controllers\OrtuChatController::class, 'getMessages'])->name('chat.messages');
        Route::post('/chat/send', [App\Http\Controllers\OrtuChatController::class, 'sendMessage'])->name('chat.send');

        // Settings
        Route::get('/settings', [App\Http\Controllers\OrtuDashboardController::class, 'settings'])->name('settings');
        Route::get('/settings/surat-pernyataan', [App\Http\Controllers\GenerusRegistrationController::class, 'ortuPreview'])->name('settings.statement.preview');
        Route::get('/settings/surat-pernyataan/unduh', [App\Http\Controllers\GenerusRegistrationController::class, 'ortuDownload'])->name('settings.statement.download');
        Route::post('/settings/update', [App\Http\Controllers\OrtuDashboardController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/password', [App\Http\Controllers\OrtuDashboardController::class, 'updatePassword'])->name('settings.password');

        // Certificate Download
        Route::get('/certificate/{level}/download', [App\Http\Controllers\CertificateController::class, 'download'])->name('certificate.download');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'storeWeb'])
        ->name('pwa.push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroyWeb'])
        ->name('pwa.push-subscriptions.destroy');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // WebAuthn biometric routes (admin/pamong)
    Route::get('/webauthn/register-options', [App\Http\Controllers\Auth\WebAuthnController::class, 'registerOptions'])->name('webauthn.register-options');
    Route::post('/webauthn/register', [App\Http\Controllers\Auth\WebAuthnController::class, 'register'])->name('webauthn.register');
    Route::get('/webauthn/status', [App\Http\Controllers\Auth\WebAuthnController::class, 'status'])->name('webauthn.status');
    Route::delete('/webauthn/{id}', [App\Http\Controllers\Auth\WebAuthnController::class, 'destroy'])->name('webauthn.destroy');
    Route::post('/webauthn/dismiss-prompt', [App\Http\Controllers\Auth\WebAuthnController::class, 'dismissPrompt'])->name('webauthn.dismiss-prompt');
    Route::get('/biometrik', [App\Http\Controllers\Auth\WebAuthnController::class, 'settingsPage'])->name('biometrik');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/secondary-panels', [DashboardController::class, 'secondaryPanels'])->name('dashboard.secondary-panels');
    Route::put('/profil-penempatan', [App\Http\Controllers\ProfileAssignmentController::class, 'updatePamong'])->name('profile-assignment.update');

    // Profile management
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/id-card', [ProfileController::class, 'idCard'])->name('profile.id-card');
    Route::get('/profile/id-card/print', [ProfileController::class, 'idCardPrint'])->name('profile.id-card.print');
    Route::post('/profile/id-card/refresh-qr', [ProfileController::class, 'refreshIdCardQr'])->name('profile.id-card.refresh-qr');
    Route::get('/face-profile', [App\Http\Controllers\FaceAttendanceController::class, 'profile'])->name('face-profile.show');
    Route::post('/face-profile/enroll', [App\Http\Controllers\FaceAttendanceController::class, 'enroll'])->name('face-profile.enroll');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update.post');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Pendataan dan penjadwalan guru MT/MS.
    Route::get('/pendataan-guru', [App\Http\Controllers\TeacherPlanningController::class, 'index'])->name('teacher-planning.index');
    Route::put('/pendataan-guru/akses', [App\Http\Controllers\TeacherPlanningController::class, 'updateInvite'])->name('teacher-planning.invite.update');
    Route::put('/pendataan-guru/pesan-selesai', [App\Http\Controllers\TeacherPlanningController::class, 'updateSuccessMessage'])->name('teacher-planning.success-message.update');
    Route::put('/pendataan-guru/profil/{teacherProfile}', [App\Http\Controllers\TeacherPlanningController::class, 'updateProfile'])->name('teacher-planning.profiles.update');
    Route::delete('/pendataan-guru/profil/{teacherProfile}', [App\Http\Controllers\TeacherPlanningController::class, 'destroyProfile'])->name('teacher-planning.profiles.destroy');
    Route::get('/pendataan-guru/profil/{teacherProfile}/surat', [App\Http\Controllers\TeacherPlanningController::class, 'statementPreview'])->name('teacher-planning.profiles.statement.preview');
    Route::get('/pendataan-guru/profil/{teacherProfile}/surat/unduh', [App\Http\Controllers\TeacherPlanningController::class, 'statementDownload'])->name('teacher-planning.profiles.statement.download');
    Route::post('/pendataan-guru/template', [App\Http\Controllers\TeacherPlanningController::class, 'storeTemplate'])->name('teacher-planning.templates.store');
    Route::patch('/pendataan-guru/template/{teacherScheduleTemplate}/toggle', [App\Http\Controllers\TeacherPlanningController::class, 'toggleTemplate'])->name('teacher-planning.templates.toggle');
    Route::post('/pendataan-guru/jadwal/generate', [App\Http\Controllers\TeacherPlanningController::class, 'generate'])->name('teacher-planning.generate');
    Route::put('/pendataan-guru/sesi/{teacherScheduleSession}/{role}', [App\Http\Controllers\TeacherPlanningController::class, 'assign'])->name('teacher-planning.sessions.assign');
    Route::patch('/pendataan-guru/sesi/{teacherScheduleSession}/swap', [App\Http\Controllers\TeacherPlanningController::class, 'swap'])->name('teacher-planning.sessions.swap');
    Route::patch('/pendataan-guru/periode/{teacherSchedulePeriod}/publish', [App\Http\Controllers\TeacherPlanningController::class, 'publish'])->name('teacher-planning.periods.publish');
    Route::post('/pendataan-guru/penugasan/{assignment}/whatsapp/{stage}', [App\Http\Controllers\TeacherPlanningController::class, 'whatsapp'])->name('teacher-planning.assignments.whatsapp');
    Route::patch('/pendataan-guru/penugasan/{assignment}/terkirim/{stage}', [App\Http\Controllers\TeacherPlanningController::class, 'markWhatsappSent'])->name('teacher-planning.assignments.sent');
    Route::get('/pendataan-guru/periode/{teacherSchedulePeriod}/excel', [App\Http\Controllers\TeacherPlanningController::class, 'exportExcel'])->name('teacher-planning.export.excel');
    Route::get('/pendataan-guru/periode/{teacherSchedulePeriod}/pdf', [App\Http\Controllers\TeacherPlanningController::class, 'exportPdf'])->name('teacher-planning.export.pdf');
    Route::get('/pendataan-guru/periode/{teacherSchedulePeriod}/gambar', [App\Http\Controllers\TeacherPlanningController::class, 'exportImage'])->name('teacher-planning.export.image');

    // Student management - Import/Export (harus sebelum resource agar tidak bentrok)
    Route::get('/siswa/template-import', [SiswaController::class, 'downloadTemplate'])->name('siswa.import.template');
    Route::get('/siswa/export-accounts', [SiswaController::class, 'exportAccounts'])->name('siswa.export-accounts');
    Route::post('/siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
    
    // Student management - Resource & Card
    Route::get('/siswa/cards/print', [SiswaController::class, 'printCards'])->name('siswa.cards.print');
    Route::resource('siswa', SiswaController::class);
    Route::get('/siswa/{siswa}/card', [SiswaController::class, 'printCard'])->name('siswa.card');
    Route::get('/siswa/{siswa}/card/print', [SiswaController::class, 'printCardOnly'])->name('siswa.card.print');
    Route::get('/siswa/{siswa}/card/download', [SiswaController::class, 'downloadCard'])->name('siswa.card.download');
    Route::get('/siswa/{siswa}/qr-code', [SiswaController::class, 'getQrCode'])->name('siswa.qrcode'); // Web-based QR generation
    
    // Siswa Account Management
    Route::post('/siswa/{siswa}/reset-password', [SiswaController::class, 'resetPassword'])->name('siswa.reset-password');
    Route::post('/siswa/{siswa}/generate-password', [SiswaController::class, 'generatePassword'])->name('siswa.generate-password');
    Route::post('/siswa/{siswa}/set-password', [SiswaController::class, 'setPassword'])->name('siswa.set-password');
    Route::post('/siswa-bulk-reset-password', [SiswaController::class, 'bulkResetPassword'])->name('siswa.bulk-reset-password');

    // Siswa - Ortu Account Management (admin)
    Route::post('/siswa/{siswa}/reset-ortu-password', [SiswaController::class, 'resetOrtuPassword'])->name('siswa.reset-ortu-password');
    Route::post('/siswa/{siswa}/update-ortu-account', [SiswaController::class, 'updateOrtuAccount'])->name('siswa.update-ortu-account');

    // Ortu Management (Dedicated Menu)
    Route::get('/ortu-management', [App\Http\Controllers\OrtuManagementController::class, 'index'])->name('ortu-management.index');
    Route::post('/ortu-management/{siswa}/reset', [App\Http\Controllers\OrtuManagementController::class, 'resetPassword'])->name('ortu-management.reset');
    Route::post('/ortu-management/reset-all', [App\Http\Controllers\OrtuManagementController::class, 'resetAllPasswords'])->name('ortu-management.reset-all');

    // Share Info Management
    Route::post('/share-info', [App\Http\Controllers\ShareInfoController::class, 'store'])->name('share-info.store');
    Route::put('/share-info/{shareInfo}', [App\Http\Controllers\ShareInfoController::class, 'update'])->name('share-info.update');
    Route::patch('/share-info/{shareInfo}/toggle', [App\Http\Controllers\ShareInfoController::class, 'toggle'])->name('share-info.toggle');
    Route::delete('/share-info/{shareInfo}', [App\Http\Controllers\ShareInfoController::class, 'destroy'])->name('share-info.destroy');

    // Class management
    Route::get('/kelas', [KelasController::class, 'index'])->name('kelas.index');
    Route::post('/kelas', [KelasController::class, 'store'])->name('kelas.store');
    Route::put('/kelas/{kela}', [KelasController::class, 'update'])->name('kelas.update');
    Route::delete('/kelas/{kela}', [KelasController::class, 'destroy'])->name('kelas.destroy');
    Route::post('/kelas/{kela}/toggle-status', [KelasController::class, 'toggleStatus'])->name('kelas.toggle-status');

    // QR Code management (admin only)
    Route::get('/qr/generate', [SiswaController::class, 'qrGenerate'])->name('qr.generate');
    Route::post('/qr/generate', [SiswaController::class, 'qrGeneratePost'])->name('qr.generate.post');
    Route::get('/qr/scan', [SiswaController::class, 'qrScan'])->name('qr.scan');
    // Note: POST /qr/scan is now a public route (see above)

    // Attendance management
    Route::redirect('/absen-manual', '/presensi?tab=input#input')->name('manual-attendance.index');
    Route::get('/absen-manual/siswa', [App\Http\Controllers\ManualAttendanceController::class, 'students'])->name('manual-attendance.students');
    Route::post('/absen-manual/siswa', [App\Http\Controllers\ManualAttendanceController::class, 'storeSiswa'])->name('manual-attendance.siswa.store');
    Route::post('/absen-manual/pamong', [App\Http\Controllers\ManualAttendanceController::class, 'storePamong'])->name('manual-attendance.pamong.store');
    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
    Route::get('/presensi/siswa', [PresensiController::class, 'students'])->name('presensi.students');
    Route::get('/presensi/recap', [PresensiController::class, 'recap'])->name('presensi.recap');
    Route::get('/presensi/rekap-generus', [App\Http\Controllers\GenerusRecapController::class, 'index'])->name('presensi.generus-recap');
    Route::get('/presensi/create', [PresensiController::class, 'create'])->name('presensi.create');
    Route::get('/presensi/export', [PresensiController::class, 'export'])->name('presensi.export');
    Route::get('/presensi/template-import', [PresensiController::class, 'downloadTemplate'])->name('presensi.import.template');
    Route::post('/presensi/import', [PresensiController::class, 'import'])->name('presensi.import');
    Route::post('/presensi/bulk', [PresensiController::class, 'bulkStore'])->name('presensi.bulk');
    Route::post('/presensi/bulk-verify', [PresensiController::class, 'bulkVerify'])->name('presensi.bulk-verify');
    Route::post('/presensi', [PresensiController::class, 'store'])->name('presensi.store');
    Route::put('/presensi/{presensi}', [PresensiController::class, 'update'])->name('presensi.update');
    Route::delete('/presensi/{presensi}', [PresensiController::class, 'destroy'])->name('presensi.destroy');
    Route::post('/presensi/{presensi}/verify', [PresensiController::class, 'verify'])->name('presensi.verify');

    // Attendance Schedule (Jadwal Presensi)
    Route::resource('attendance-schedule', App\Http\Controllers\AttendanceScheduleController::class);
    Route::patch('/attendance-schedule/{attendanceSchedule}/activate', [App\Http\Controllers\AttendanceScheduleController::class, 'activate'])->name('attendance-schedule.activate');
    Route::patch('/attendance-schedule/{attendanceSchedule}/deactivate', [App\Http\Controllers\AttendanceScheduleController::class, 'deactivate'])->name('attendance-schedule.deactivate');

    // Presensi Web Endpoints (for AJAX calls with session auth)
    Route::get('/presensi/data', [PresensiController::class, 'getData'])->name('presensi.data');
    Route::get('/presensi/stats', [PresensiController::class, 'getStats'])->name('presensi.stats');

    // Siswa Web Endpoints
    Route::get('/siswa-list', [SiswaController::class, 'getList'])->name('siswa.list');

    // Pamong Web Endpoints
    Route::get('/pamong-list', [PamongController::class, 'getList'])->name('pamong.list');
    Route::post('/pamong/{pamong}/toggle-status', [PamongController::class, 'toggleStatus'])->name('pamong.toggle-status');
    Route::get('/pamong/{pamong}/students', [PamongController::class, 'getAssignedStudents'])->name('pamong.students');
    Route::post('/pamong/bulk-permissions', [PamongController::class, 'bulkUpdatePermissions'])->name('pamong.bulk-permissions');
    Route::post('/pamong/{pamong}/change-password', [PamongController::class, 'changePassword'])->name('pamong.change-password');

    // Kelas Web Endpoints
    Route::get('/kelas-list', [App\Http\Controllers\KelasController::class, 'getList'])->name('kelas.list');

    // Reports (requires permission)
    Route::middleware(['role.permission:view_reports'])->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
        Route::get('/reports/status-chart', [ReportController::class, 'statusChart'])->name('reports.status-chart');
        Route::get('/reports/trend-chart', [ReportController::class, 'trendChart'])->name('reports.trend-chart');
        Route::get('/reports/class-performance', [ReportController::class, 'classPerformance'])->name('reports.class-performance');
        Route::get('/reports/top-students', [ReportController::class, 'topStudents'])->name('reports.top-students');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    // News (Berita)
    Route::resource('berita', BeritaController::class)->parameters([
        'berita' => 'berita',
    ]);
    Route::get('/berita/{berita}/download', [BeritaController::class, 'downloadPdf'])->name('berita.download');

    // Materi Management
    Route::get('/materi-targets', [MateriTargetController::class, 'index'])->name('materi-targets.index');
    Route::post('/materi-targets', [MateriTargetController::class, 'store'])->name('materi-targets.store');
    Route::patch('/materi-targets/{target}', [MateriTargetController::class, 'update'])->name('materi-targets.update');
    Route::delete('/materi-targets/{target}', [MateriTargetController::class, 'destroy'])->name('materi-targets.destroy');
    Route::post('/materi-targets/progress/{siswa}/{target}/toggle', [MateriTargetController::class, 'toggleProgress'])->name('materi-targets.progress.toggle');
    Route::get('/materi-rpp-journals', [MateriRppJournalController::class, 'index'])->name('materi-rpp-journals.index');
    Route::get('/materi-rpp-journals/export', [MateriRppJournalController::class, 'export'])->name('materi-rpp-journals.export');
    Route::get('/materi-rpp-journals/schedule/{scheduleReminder}', [MateriRppJournalController::class, 'forSchedule'])->name('materi-rpp-journals.schedule');
    Route::post('/materi-rpp-journals/schedule/{scheduleReminder}', [MateriRppJournalController::class, 'storeForSchedule'])->name('materi-rpp-journals.schedule.store');
    Route::post('/materi-rpp-journals/schedule/{scheduleReminder}/assignees', [MateriRppJournalController::class, 'addAssignee'])->name('materi-rpp-journals.schedule.assignees.store');
    Route::delete('/materi-rpp-journals/schedule/{scheduleReminder}/assignees/{assignee}', [MateriRppJournalController::class, 'removeAssignee'])->name('materi-rpp-journals.schedule.assignees.destroy');
    Route::patch('/materi-rpp-journals/{journal}/review', [MateriRppJournalController::class, 'review'])->name('materi-rpp-journals.review');
    Route::get('/materi-rpp-journals/{journal}/edit', [MateriRppJournalController::class, 'edit'])->name('materi-rpp-journals.edit');
    Route::patch('/materi-rpp-journals/{journal}', [MateriRppJournalController::class, 'update'])->name('materi-rpp-journals.update');
    Route::post('/materi/folders', [MateriController::class, 'storeFolder'])->name('materi.folders.store');
    Route::patch('/materi/folders/{folder}', [MateriController::class, 'updateFolder'])->name('materi.folders.update');
    Route::post('/materi/rpp-preview', [MateriController::class, 'rppPreview'])->name('materi.rpp-preview');
    Route::patch('/materi/{materi}/publish-rpp', [MateriController::class, 'publishRpp'])->name('materi.publish-rpp');
    Route::resource('materi', MateriController::class)->except(['index']);
    Route::patch('/materi/{materi}/toggle-status', [MateriController::class, 'toggleStatus'])->name('materi.toggle-status');

    // Canonical Tugas PKG routes
    Route::get('/tugas-pkg', [App\Http\Controllers\TugasPkgController::class, 'index'])->name('tugas-pkg.index');
    Route::get('/tugas-pkg/master', [App\Http\Controllers\KarakterController::class, 'index'])->name('tugas-pkg.master');
    Route::post('/tugas-pkg/master', [App\Http\Controllers\KarakterController::class, 'store'])->name('tugas-pkg.store');
    Route::match(['put', 'patch'], '/tugas-pkg/master/{karakter}', [App\Http\Controllers\KarakterController::class, 'update'])->name('tugas-pkg.update');
    Route::patch('/tugas-pkg/master/{karakter}/toggle-status', [App\Http\Controllers\KarakterController::class, 'toggleStatus'])->name('tugas-pkg.toggle-status');
    Route::post('/tugas-pkg/master/bulk-action', [App\Http\Controllers\KarakterController::class, 'bulkAction'])->name('tugas-pkg.bulk-action');
    Route::get('/tugas-pkg/verifikasi', [App\Http\Controllers\TracerKarakterController::class, 'index'])->name('tugas-pkg.verification');
    Route::get('/tugas-pkg/verifikasi/rekap', [App\Http\Controllers\TracerKarakterController::class, 'rekap'])->name('tugas-pkg.rekap');
    Route::get('/tugas-pkg/verifikasi/detail-siswa', [App\Http\Controllers\TracerKarakterController::class, 'detailSiswa'])->name('tugas-pkg.detail-siswa');
    Route::get('/tugas-pkg/verifikasi/export', [App\Http\Controllers\TracerKarakterController::class, 'export'])->name('tugas-pkg.export');
    Route::get('/tugas-pkg/verifikasi/template-import', [App\Http\Controllers\TracerKarakterController::class, 'downloadTemplate'])->name('tugas-pkg.import.template');
    Route::post('/tugas-pkg/verifikasi/import', [App\Http\Controllers\TracerKarakterController::class, 'import'])->name('tugas-pkg.import');
    Route::get('/tugas-pkg/verifikasi/{siswa}/check', [App\Http\Controllers\TracerKarakterController::class, 'checkKarakter'])->name('tugas-pkg.check');
    Route::post('/tugas-pkg/verifikasi/{siswa}/check', [App\Http\Controllers\TracerKarakterController::class, 'storeCheck'])->name('tugas-pkg.store-check');
    Route::get('/tugas-pkg/verifikasi/{siswa}/history', [App\Http\Controllers\TracerKarakterController::class, 'history'])->name('tugas-pkg.history');
    Route::post('/tugas-pkg/verifikasi/bulk-action', [App\Http\Controllers\TracerKarakterController::class, 'bulkAction'])->name('tugas-pkg.verification.bulk-action');
    Route::put('/tugas-pkg/verifikasi/{checklist}/verify', [App\Http\Controllers\SiswaKarakterController::class, 'verify'])->name('tugas-pkg.verification.verify');
    Route::put('/tugas-pkg/verifikasi/{checklist}/unverify', [App\Http\Controllers\SiswaKarakterController::class, 'unverify'])->name('tugas-pkg.verification.unverify');
    Route::delete('/tugas-pkg/verifikasi/{checklist}', [App\Http\Controllers\SiswaKarakterController::class, 'destroy'])->name('tugas-pkg.verification.destroy');
    Route::post('/tugas-pkg/verifikasi/{id}/restore', [App\Http\Controllers\SiswaKarakterController::class, 'restore'])->name('tugas-pkg.verification.restore');

    // Legacy task routes redirected to Tugas PKG
    Route::get('/pr', function () {
        return redirect()->route('tugas-pkg.index', request()->query(), 301);
    })->name('pr.index');
    Route::get('/pr/create', function () {
        return redirect()
            ->route('tugas-pkg.master')
            ->with('success', 'Menu tugas lama sudah diganti. Gunakan master tugas PKG untuk menambah tugas baru.');
    })->name('pr.create');
    Route::post('/pr', function () {
        return redirect()
            ->route('tugas-pkg.master')
            ->with('success', 'Pembuatan tugas lama sudah dinonaktifkan. Gunakan master tugas PKG.');
    })->name('pr.store');
    Route::get('/pr/{pr}', function () {
        return redirect()
            ->route('tugas-pkg.index')
            ->with('success', 'Detail tugas lama sudah diganti. Gunakan daftar tugas PKG aktif.');
    })->name('pr.show');
    Route::get('/pr/{pr}/edit', function () {
        return redirect()
            ->route('tugas-pkg.master')
            ->with('success', 'Menu edit tugas lama sudah diganti. Gunakan master tugas PKG untuk mengubah tugas.');
    })->name('pr.edit');
    Route::match(['put', 'patch'], '/pr/{pr}', function () {
        return redirect()
            ->route('tugas-pkg.master')
            ->with('success', 'Pembaruan tugas lama sudah dinonaktifkan. Gunakan master tugas PKG.');
    })->name('pr.update');
    Route::delete('/pr/{pr}', function () {
        return redirect()
            ->route('tugas-pkg.index')
            ->with('success', 'Data tugas lama sudah tidak dipakai lagi.');
    })->name('pr.destroy');
    Route::patch('/pr/{pr}/toggle-status', function () {
        return redirect()
            ->route('tugas-pkg.index')
            ->with('success', 'Status tugas lama sudah tidak digunakan. Gunakan status tugas PKG di master tugas.');
    })->name('pr.toggle-status');
    Route::get('/pr-submissions', function () {
        return redirect()
            ->route('tugas-pkg.verification', ['tab' => 'verification'])
            ->with('success', 'Verifikasi tugas lama sudah dipusatkan ke verifikasi tugas PKG.');
    })->name('pr.submissions');
    Route::post('/pr-submissions/{submission}/verify', function () {
        return redirect()
            ->route('tugas-pkg.verification', ['tab' => 'verification'])
            ->with('success', 'Verifikasi tugas lama sudah dipusatkan ke verifikasi tugas PKG.');
    })->name('pr.verify');

    // Settings
    Route::middleware('admin.only')->group(function () {
        Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
        Route::put('/settings/general', [App\Http\Controllers\SettingsController::class, 'updateGeneral'])->name('settings.update.general');
        Route::put('/settings/id-card', [App\Http\Controllers\SettingsController::class, 'updateIdCard'])->name('settings.update.id-card');
        Route::put('/settings/theme', [App\Http\Controllers\SettingsController::class, 'updateTheme'])->name('settings.update.theme');
        Route::put('/settings/kelas', [App\Http\Controllers\SettingsController::class, 'updateTingkat'])->name('settings.update.kelas');
        Route::put('/settings/footer', [App\Http\Controllers\SettingsController::class, 'updateFooter'])->name('settings.update.footer');
        Route::put('/settings/permissions', [App\Http\Controllers\SettingsController::class, 'updateDefaultPermissions'])->name('settings.update.permissions');
        Route::put('/settings/popup', [App\Http\Controllers\SettingsController::class, 'updatePopups'])->name('settings.update.popup');
        Route::put('/settings/face-attendance', [App\Http\Controllers\SettingsController::class, 'updateFaceAttendance'])->name('settings.update.face-attendance');
        Route::put('/settings/registration-access', [App\Http\Controllers\SettingsController::class, 'updateRegistrationAccess'])->name('settings.update.registration-access');

        // Backup & Restore
        Route::get('/settings/backup', [App\Http\Controllers\BackupController::class, 'index'])->name('settings.backup');
        Route::post('/settings/backup/database', [App\Http\Controllers\BackupController::class, 'backupDatabase'])->name('settings.backup.database');
        Route::post('/settings/backup/files', [App\Http\Controllers\BackupController::class, 'backupFiles'])->name('settings.backup.files');
        Route::post('/settings/backup/all', [App\Http\Controllers\BackupController::class, 'backupAll'])->name('settings.backup.all');
        Route::get('/settings/backup/download/{filename}', [App\Http\Controllers\BackupController::class, 'download'])->name('settings.backup.download');
        Route::delete('/settings/backup/{filename}', [App\Http\Controllers\BackupController::class, 'delete'])->name('settings.backup.delete');
    });

    // User Management
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::patch('/users/{user}/toggle-status', [App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::get('/users-template', [App\Http\Controllers\UserController::class, 'downloadTemplate'])->name('users.template');
    Route::post('/users-import', [App\Http\Controllers\UserController::class, 'import'])->name('users.import');

    // Karakter Management
    Route::resource('karakter', App\Http\Controllers\KarakterController::class)->except(['create', 'show', 'edit']);
    Route::patch('/karakter/{karakter}/toggle-status', [App\Http\Controllers\KarakterController::class, 'toggleStatus'])->name('karakter.toggle-status');
    Route::post('/karakter/bulk-action', [App\Http\Controllers\KarakterController::class, 'bulkAction'])->name('karakter.bulk-action');

    // Pamong (Teachers) & Assignment
    Route::get('/pamong', [PamongController::class, 'index'])->name('pamong.index');
    Route::post('/pamong/teams', [PamongController::class, 'storeTeam'])->name('pamong.teams.store');
    Route::put('/pamong/teams/{team}', [PamongController::class, 'updateTeam'])->name('pamong.teams.update');
    Route::delete('/pamong/teams/{team}', [PamongController::class, 'destroyTeam'])->name('pamong.teams.destroy');
    Route::post('/pamong/team-members', [PamongController::class, 'saveTeamMember'])->name('pamong.team-members.save');
    Route::get('/pamong/export-accounts', [PamongController::class, 'exportAccounts'])->name('pamong.export-accounts');
    Route::get('/pamong/cards/print', [PamongController::class, 'printCards'])->name('pamong.cards.print');
    Route::get('/pamong/qr-generate', [PamongController::class, 'qrGenerate'])->name('pamong.qr.generate');
    Route::post('/pamong/qr-generate', [PamongController::class, 'qrGeneratePost'])->name('pamong.qr.generate.post');
    Route::get('/pamong/permissions', [PamongController::class, 'permissionsIndex'])->name('pamong.permissions.index');
    Route::post('/pamong/permissions/bulk', [PamongController::class, 'bulkUpdatePermissions'])->name('pamong.permissions.bulk');
    Route::get('/pamong/{pamong}', [PamongController::class, 'show'])->name('pamong.show');
    Route::get('/pamong/{pamong}/assign', [PamongController::class, 'assignForm'])->name('pamong.assign.form');
    Route::post('/pamong/{pamong}/assign', [PamongController::class, 'assignStudents'])->name('pamong.assign');
    Route::get('/pamong/{pamong}/permissions', [PamongController::class, 'permissionForm'])->name('pamong.permissions');
    Route::post('/pamong/{pamong}/permissions', [PamongController::class, 'updatePermissions'])->name('pamong.permissions.update');
    Route::post('/pamong/{pamong}/copy-permissions', [PamongController::class, 'copyPermissions'])->name('pamong.permissions.copy');
    Route::get('/pamong/{pamong}/activity-log', [PamongController::class, 'activityLog'])->name('pamong.activity-log');
    Route::delete('/pamong/{pamong}/siswa/{siswa}', [PamongController::class, 'removeAssignment'])->name('pamong.remove-assignment');
    Route::get('/pamong/students-by-kelas', [PamongController::class, 'getStudentsByKelas'])->name('pamong.students-by-kelas');
    Route::post('/pamong/{pamong}/reset-password', [PamongController::class, 'resetPassword'])->name('pamong.reset-password');
    Route::post('/pamong/{pamong}/reset-password-custom', [PamongController::class, 'resetPasswordCustom'])->name('pamong.reset-password-custom');
    
    // Pamong Chat
    Route::get('/pamong-chat', [App\Http\Controllers\PamongChatController::class, 'index'])->name('pamong.chat.index');
    Route::get('/pamong-chat/messages', [App\Http\Controllers\PamongChatController::class, 'getMessages'])->name('pamong.chat.messages');
    Route::post('/pamong-chat/send', [App\Http\Controllers\PamongChatController::class, 'sendMessage'])->name('pamong.chat.send');
    Route::get('/pamong-chat/unread', [App\Http\Controllers\PamongChatController::class, 'getUnreadCount'])->name('pamong.chat.unread');
    Route::get('/pamong-chat/unread-counts', [App\Http\Controllers\PamongChatController::class, 'getUnreadCountPerContact'])->name('pamong.chat.unread.counts');
    Route::get('/pamong-chat/broadcast', [App\Http\Controllers\PamongChatController::class, 'broadcastForm'])->name('pamong.chat.broadcast');
    Route::post('/pamong-chat/broadcast', [App\Http\Controllers\PamongChatController::class, 'sendBroadcast'])->name('pamong.chat.broadcast.send');
    Route::post('/pamong-chat/broadcast/send', [App\Http\Controllers\PamongChatController::class, 'sendBroadcast'])->name('pamong.chat.broadcast.send-legacy');
    Route::post('/pamong-chat/groups', [App\Http\Controllers\PamongChatController::class, 'storeGroup'])
        ->middleware('pamong.permission:group_chat,create')
        ->name('pamong.chat.groups.store');
    Route::get('/pamong-chat/groups/{chatGroup}', [App\Http\Controllers\PamongChatController::class, 'editGroup'])
        ->middleware('pamong.permission:group_chat,view')
        ->name('pamong.chat.groups.edit');
    Route::put('/pamong-chat/groups/{chatGroup}', [App\Http\Controllers\PamongChatController::class, 'updateGroup'])
        ->middleware('pamong.permission:group_chat,view')
        ->name('pamong.chat.groups.update');
    
    // Admin Broadcast
    Route::get('/admin-broadcast', [App\Http\Controllers\AdminBroadcastController::class, 'index'])->name('admin.broadcast.index');
    Route::post('/admin-broadcast/siswa', [App\Http\Controllers\AdminBroadcastController::class, 'sendToSiswa'])->name('admin.broadcast.siswa');
    Route::post('/admin-broadcast/users', [App\Http\Controllers\AdminBroadcastController::class, 'sendToUsers'])->name('admin.broadcast.users');
    Route::post('/admin-broadcast/pamong-groups', [App\Http\Controllers\AdminBroadcastController::class, 'sendToPamongGroups'])->name('admin.broadcast.pamong-groups');

    // Chat Groups (Admin)
    Route::resource('chat-groups', App\Http\Controllers\ChatGroupController::class);
    Route::post('/chat-groups/{chatGroup}/add-all-pamong', [App\Http\Controllers\ChatGroupController::class, 'addAllPamong'])->name('chat-groups.add-all-pamong');
    Route::post('/chat-groups/{chatGroup}/add-all-siswa', [App\Http\Controllers\ChatGroupController::class, 'addAllSiswa'])->name('chat-groups.add-all-siswa');
    Route::post('/chat-groups/{chatGroup}/add-all-users', [App\Http\Controllers\ChatGroupController::class, 'addAllUsers'])->name('chat-groups.add-all-users');
    Route::get('/chat-groups/{chatGroup}/messages', [App\Http\Controllers\ChatGroupController::class, 'getMessages'])->name('chat-groups.messages');
    Route::post('/chat-groups/{chatGroup}/send', [App\Http\Controllers\ChatGroupController::class, 'sendMessage'])->name('chat-groups.send');
    Route::delete('/chat-groups/{chatGroup}/members/{member}', [App\Http\Controllers\ChatGroupController::class, 'removeMember'])->name('chat-groups.remove-member');
    Route::post('/admin-broadcast/pamong', [App\Http\Controllers\AdminBroadcastController::class, 'sendToPamong'])->name('admin.broadcast.pamong');

    // Group Chat routes for Pamong/Admin
    Route::get('/group-chat', [App\Http\Controllers\UserGroupChatController::class, 'index'])->name('group-chat.index');
    Route::get('/group-chat/unread', [App\Http\Controllers\UserGroupChatController::class, 'getUnreadCount'])->name('group-chat.unread');
    Route::get('/group-chat/{chatGroup}/messages', [App\Http\Controllers\UserGroupChatController::class, 'getMessages'])->name('group-chat.messages');
    Route::post('/group-chat/{chatGroup}/send', [App\Http\Controllers\UserGroupChatController::class, 'sendMessage'])->name('group-chat.send');
    Route::get('/group-chat/{chatGroup}/info', [App\Http\Controllers\UserGroupChatController::class, 'getGroupInfo'])->name('group-chat.info');

    // Karakter Harian (browse page)
    Route::get('/karakter-harian', [App\Http\Controllers\TracerKarakterController::class, 'karakterHarian'])->name('karakter-harian.index');

    // Tracer Karakter
    Route::get('/tracer-karakter', [App\Http\Controllers\TracerKarakterController::class, 'index'])->name('tracer-karakter.index');
    Route::get('/tracer-karakter/rekap', [App\Http\Controllers\TracerKarakterController::class, 'rekap'])->name('tracer-karakter.rekap');
    Route::get('/tracer-karakter/detail-siswa', [App\Http\Controllers\TracerKarakterController::class, 'detailSiswa'])->name('tracer-karakter.detail-siswa');
    Route::get('/tracer-karakter/export', [App\Http\Controllers\TracerKarakterController::class, 'export'])->name('tracer-karakter.export');
    Route::get('/tracer-karakter/template-import', [App\Http\Controllers\TracerKarakterController::class, 'downloadTemplate'])->name('tracer-karakter.import.template');
    Route::post('/tracer-karakter/import', [App\Http\Controllers\TracerKarakterController::class, 'import'])->name('tracer-karakter.import');
    Route::get('/tracer-karakter/{siswa}/check', [App\Http\Controllers\TracerKarakterController::class, 'checkKarakter'])->name('tracer-karakter.check');
    Route::post('/tracer-karakter/{siswa}/check', [App\Http\Controllers\TracerKarakterController::class, 'storeCheck'])->name('tracer-karakter.store-check');
    Route::get('/tracer-karakter/{siswa}/history', [App\Http\Controllers\TracerKarakterController::class, 'history'])->name('tracer-karakter.history');
    Route::post('/tracer-karakter/bulk-action', [App\Http\Controllers\TracerKarakterController::class, 'bulkAction'])->name('tracer-karakter.bulk-action');
    
    // Karakter Verification (Tugas PKG) - integrated in tracer-karakter
    Route::put('/karakter/verification/{checklist}/verify', [App\Http\Controllers\SiswaKarakterController::class, 'verify'])->name('karakter.verification.verify');
    Route::put('/karakter/verification/{checklist}/unverify', [App\Http\Controllers\SiswaKarakterController::class, 'unverify'])->name('karakter.verification.unverify');
    Route::delete('/karakter/verification/{checklist}', [App\Http\Controllers\SiswaKarakterController::class, 'destroy'])->name('karakter.verification.destroy');
    Route::post('/karakter/verification/{id}/restore', [App\Http\Controllers\SiswaKarakterController::class, 'restore'])->name('karakter.verification.restore');

    // Cek Kehadiran PKG
    Route::get('/cek-kehadiran', [App\Http\Controllers\CekKehadiranController::class, 'index'])->name('cek-kehadiran.index');
    Route::delete('/cek-kehadiran/{transaction}', [App\Http\Controllers\CekKehadiranController::class, 'destroy'])->name('cek-kehadiran.destroy');

    // Calendar
    Route::get('/calendar', [App\Http\Controllers\CalendarController::class, 'adminIndex'])->name('calendar.index');
    Route::get('/calendar/events', [App\Http\Controllers\CalendarController::class, 'adminEvents'])->name('calendar.events');
    Route::get('/calendar/date-stats', [App\Http\Controllers\CalendarController::class, 'getDateStats'])->name('calendar.date-stats');
    Route::get('/calendar/share-text', [App\Http\Controllers\CalendarController::class, 'adminShareText'])->name('calendar.share-text');
    Route::get('/calendar/export', [App\Http\Controllers\CalendarController::class, 'adminExport'])->name('calendar.export');

    // Schedule Reminder (Jadwal Pengingat untuk Kalender)
    Route::resource('schedule-reminder', App\Http\Controllers\ScheduleReminderController::class);
    Route::patch('/schedule-reminder/{scheduleReminder}/toggle', [App\Http\Controllers\ScheduleReminderController::class, 'toggle'])->name('schedule-reminder.toggle');
    Route::get('/schedule-reminder-events', [App\Http\Controllers\ScheduleReminderController::class, 'getEvents'])->name('schedule-reminder.events');

    // Laporan Penyaksian (Admin/Pamong Management)
    Route::get('/laporan-penyaksian', [App\Http\Controllers\LaporanPenyaksianController::class, 'index'])->name('laporan-penyaksian.index');
    Route::delete('/laporan-penyaksian/bulk', [App\Http\Controllers\LaporanPenyaksianController::class, 'bulkDestroy'])->name('laporan-penyaksian.bulk-destroy');
    Route::get('/laporan-penyaksian/{laporanPenyaksian}', [App\Http\Controllers\LaporanPenyaksianController::class, 'show'])->name('laporan-penyaksian.show');
    Route::put('/laporan-penyaksian/{laporanPenyaksian}', [App\Http\Controllers\LaporanPenyaksianController::class, 'update'])->name('laporan-penyaksian.update');
    Route::delete('/laporan-penyaksian/{laporanPenyaksian}', [App\Http\Controllers\LaporanPenyaksianController::class, 'destroy'])->name('laporan-penyaksian.destroy');

    // Pamong Presensi (Presensi Pamong/Guru)
    Route::get('/pamong-presensi', [App\Http\Controllers\PamongPresensiController::class, 'index'])->name('pamong-presensi.index');
    Route::get('/pamong-presensi/summary', [App\Http\Controllers\PamongPresensiController::class, 'summary'])->name('pamong-presensi.summary');
    Route::post('/pamong-presensi', [App\Http\Controllers\PamongPresensiController::class, 'store'])->name('pamong-presensi.store');
    Route::put('/pamong-presensi/{pamongPresensi}', [App\Http\Controllers\PamongPresensiController::class, 'update'])->name('pamong-presensi.update');
    Route::delete('/pamong-presensi/{pamongPresensi}', [App\Http\Controllers\PamongPresensiController::class, 'destroy'])->name('pamong-presensi.destroy');
    Route::post('/pamong-presensi/{pamongPresensi}/verify', [App\Http\Controllers\PamongPresensiController::class, 'verify'])->name('pamong-presensi.verify');
    Route::get('/pamong-presensi/data', [App\Http\Controllers\PamongPresensiController::class, 'getData'])->name('pamong-presensi.data');
    Route::get('/pamong-presensi/stats', [App\Http\Controllers\PamongPresensiController::class, 'getStats'])->name('pamong-presensi.stats');
    Route::get('/pamong-presensi/export', [App\Http\Controllers\PamongPresensiController::class, 'export'])->name('pamong-presensi.export');
    Route::get('/pamong-presensi/template-import', [App\Http\Controllers\PamongPresensiController::class, 'downloadTemplate'])->name('pamong-presensi.import.template');
    Route::post('/pamong-presensi/import', [App\Http\Controllers\PamongPresensiController::class, 'import'])->name('pamong-presensi.import');
    Route::get('/pamong-presensi/card/{user}', [App\Http\Controllers\PamongPresensiController::class, 'card'])->name('pamong-presensi.card');
    Route::get('/pamong-presensi/card/{user}/print', [App\Http\Controllers\PamongPresensiController::class, 'cardPrint'])->name('pamong-presensi.card.print');
    Route::post('/pamong-presensi/refresh-qr/{user}', [App\Http\Controllers\PamongPresensiController::class, 'refreshQr'])->name('pamong-presensi.refresh-qr');

    Route::get('/media/sync-proxy', [App\Http\Controllers\RemoteMediaController::class, 'show'])->name('admin.media.sync-proxy');

    // Admin Data Pull (Tarik data dari server online)
    Route::middleware('admin.only')->group(function () {
        Route::get('/data-pull', [App\Http\Controllers\DataPullController::class, 'index'])->name('admin.data-pull.index');
        Route::post('/data-pull/save-settings', [App\Http\Controllers\DataPullController::class, 'saveSettings'])->name('admin.data-pull.save-settings');
        Route::post('/data-pull/save-export-key', [App\Http\Controllers\DataPullController::class, 'saveExportKey'])->name('admin.data-pull.save-export-key');
        Route::post('/data-pull/test', [App\Http\Controllers\DataPullController::class, 'testConnection'])->name('admin.data-pull.test');
        Route::post('/data-pull/pull', [App\Http\Controllers\DataPullController::class, 'pull'])->name('admin.data-pull.pull');
        Route::post('/data-pull/sync-media', [App\Http\Controllers\DataPullController::class, 'syncMediaOnly'])->name('admin.data-pull.sync-media');
        Route::get('/data-pull/unavailable-media-report', [App\Http\Controllers\DataPullController::class, 'downloadUnavailableMediaReport'])->name('admin.data-pull.unavailable-media-report');
    });

    // Admin Gamification Management (tanpa prefix admin/ karena sudah di dalam middleware auth)
    Route::prefix('gamification')->name('admin.gamification.')->group(function () {
        Route::get('/badges', [App\Http\Controllers\GamificationController::class, 'adminBadges'])->name('badges');
        Route::post('/badges', [App\Http\Controllers\GamificationController::class, 'adminCreateBadge'])->name('badges.store');
        Route::put('/badges/{badge}', [App\Http\Controllers\GamificationController::class, 'adminUpdateBadge'])->name('badges.update');
        Route::delete('/badges/{badge}', [App\Http\Controllers\GamificationController::class, 'adminDeleteBadge'])->name('badges.destroy');
        Route::get('/levels', [App\Http\Controllers\GamificationController::class, 'adminLevels'])->name('levels');
        Route::post('/levels', [App\Http\Controllers\GamificationController::class, 'adminCreateLevel'])->name('levels.store');
        Route::put('/levels/{level}', [App\Http\Controllers\GamificationController::class, 'adminUpdateLevel'])->name('levels.update');
        Route::delete('/levels/{level}', [App\Http\Controllers\GamificationController::class, 'adminDeleteLevel'])->name('levels.destroy');
        Route::post('/point-config', [App\Http\Controllers\GamificationController::class, 'adminSavePointConfig'])->name('point-config');
        Route::post('/periods', [App\Http\Controllers\GamificationController::class, 'adminStorePeriod'])->name('periods.store');
        Route::post('/periods/{period}/activate', [App\Http\Controllers\GamificationController::class, 'adminActivatePeriod'])->name('periods.activate');
        Route::post('/periods/{period}/close', [App\Http\Controllers\GamificationController::class, 'adminClosePeriod'])->name('periods.close');
        Route::post('/periods/sync-active', [App\Http\Controllers\GamificationController::class, 'adminSyncActivePeriodTransactions'])->name('periods.sync-active');
        Route::post('/periods/{period}/sync', [App\Http\Controllers\GamificationController::class, 'adminSyncPeriodTransactions'])->name('periods.sync');
        Route::post('/periods/{period}/restore-archived-tasks', [App\Http\Controllers\GamificationController::class, 'adminRestoreArchivedPeriodTasks'])->name('periods.restore-archived-tasks');
        Route::get('/analytics', [App\Http\Controllers\GamificationController::class, 'adminAnalytics'])->name('analytics');
        Route::get('/export-analytics', [App\Http\Controllers\GamificationController::class, 'exportAnalytics'])->name('export-analytics');
        Route::post('/adjust-points', [App\Http\Controllers\GamificationController::class, 'adminAdjustPoints'])->name('adjust-points');
        Route::get('/transactions', [App\Http\Controllers\GamificationController::class, 'adminTransactions'])->name('transactions');
        Route::put('/transactions/{transaction}', [App\Http\Controllers\GamificationController::class, 'adminUpdateTransaction'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [App\Http\Controllers\GamificationController::class, 'adminDeleteTransaction'])->name('transactions.destroy');
        Route::post('/reset-character-points', [App\Http\Controllers\GamificationController::class, 'adminResetCharacterPoints'])->name('reset-character-points');
        Route::post('/reset-badges', [App\Http\Controllers\GamificationController::class, 'adminResetBadges'])->name('reset-badges');
        Route::post('/full-reset', [App\Http\Controllers\GamificationController::class, 'adminFullReset'])->name('full-reset');
    });

    // Admin RPG Quest Management
    Route::get('/admin/rpg', [App\Http\Controllers\RpgGameController::class, 'adminIndex'])
        ->middleware('pamong.permission:game,view')
        ->name('admin.rpg.index');

    Route::prefix('rpg-admin')->name('admin.rpg.')->group(function () {
        Route::post('/maps', [App\Http\Controllers\RpgGameController::class, 'adminStoreMap'])->name('maps.store');
        Route::post('/maps/{rpgMap}/duplicate', [App\Http\Controllers\RpgGameController::class, 'adminDuplicateMap'])->name('maps.duplicate');
        Route::put('/maps/{rpgMap}', [App\Http\Controllers\RpgGameController::class, 'adminUpdateMap'])->name('maps.update');
        Route::delete('/maps/{rpgMap}', [App\Http\Controllers\RpgGameController::class, 'adminDeleteMap'])->name('maps.destroy');
        Route::get('/maps/{rpgMap}/detail', [App\Http\Controllers\RpgGameController::class, 'adminGetMap'])->name('maps.detail');
        Route::post('/npcs', [App\Http\Controllers\RpgGameController::class, 'adminStoreNpc'])->name('npcs.store');
        Route::put('/npcs/{rpgNpc}', [App\Http\Controllers\RpgGameController::class, 'adminUpdateNpc'])->name('npcs.update');
        Route::delete('/npcs/{rpgNpc}', [App\Http\Controllers\RpgGameController::class, 'adminDeleteNpc'])->name('npcs.destroy');
    });

    // Admin Certificate / Reward Template Management
    Route::prefix('certificate')->name('admin.certificate.')->group(function () {
        Route::get('/settings/{level}', [App\Http\Controllers\CertificateController::class, 'settings'])->name('settings');
        Route::post('/upload/{level}/{rewardType}', [App\Http\Controllers\CertificateController::class, 'uploadTemplate'])->name('upload-template');
        Route::put('/settings/{level}/{rewardType}', [App\Http\Controllers\CertificateController::class, 'updateTemplateSettings'])->name('update-template');
        Route::get('/preview/{level}/{rewardType}', [App\Http\Controllers\CertificateController::class, 'preview'])->name('preview');
    });

    // Export Data
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/', [App\Http\Controllers\ExportController::class, 'index'])->name('index');
        Route::get('/presensi', [App\Http\Controllers\ExportController::class, 'presensi'])->name('presensi');
        Route::get('/rekap-presensi', [App\Http\Controllers\ExportController::class, 'rekapPresensi'])->name('rekap-presensi');
        Route::get('/leaderboard', [App\Http\Controllers\ExportController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/period-collection', [App\Http\Controllers\ExportController::class, 'periodCollection'])->name('period-collection');
        Route::get('/siswa', [App\Http\Controllers\ExportController::class, 'siswa'])->name('siswa');
    });

    // PWA Manifest
    Route::get('/manifest.json', function () {
        return response()->json([
            'name' => 'Pembinaan Karakter Generus Panunggangan',
            'short_name' => 'PKG Panunggangan',
            'description' => 'Sistem Pembinaan Karakter Generus Panunggangan',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#f8f6e8',
            'theme_color' => '#10643a',
            'orientation' => 'portrait',
            'icons' => [
                [
                    'src' => '/images/icons/pkg-pwa-2026-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => '/images/icons/pkg-pwa-2026-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ]);
    })->name('manifest');

    // Service Worker
    Route::get('/sw.js', function () {
        $content = "
const CACHE_NAME = 'pkg-presensi-v6';
const urlsToCache = [
    '/',
    '/dashboard',
    '/siswa',
    '/presensi',
    '/reports',
    '/css/app.css'
];

self.addEventListener('install', function(event) {
    console.log('SW: Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('SW: Caching files');
                return cache.addAll(urlsToCache);
            })
            .catch(function(error) {
                console.error('SW: Install failed', error);
            })
    );
    self.skipWaiting();
});

self.addEventListener('fetch', function(event) {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Skip chrome-extension and other non-http requests
    if (!event.request.url.startsWith('http')) {
        return;
    }

    const requestUrl = new URL(event.request.url);
    if (event.request.mode === 'navigate' || requestUrl.pathname.startsWith('/build/')) {
        event.respondWith(fetch(event.request));
        return;
    }

    if (
        requestUrl.pathname.startsWith('/storage/') ||
        requestUrl.pathname.startsWith('/media/sync-proxy')
    ) {
        event.respondWith(fetch(event.request, { cache: 'no-store' }));
        return;
    }
    
    event.respondWith(
        fetch(event.request)
            .then(function(response) {
                if (response && response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(function(cache) {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(function(error) {
                return caches.match(event.request);
            })
    );
});

self.addEventListener('activate', function(event) {
    console.log('SW: Activating...');
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('SW: Deleting old cache', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});
        ";

        return response($content)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', '/');
    })->name('sw');
});

// ============================================
// Backward Compatibility Redirects
// These routes redirect old URLs to new consolidated pages with tabs
// ============================================

// Redirect old attendance-schedule to presensi with jadwal tab
Route::get('/attendance-schedule-redirect', function () {
    return redirect()->route('presensi.index', ['tab' => 'jadwal']);
})->name('attendance-schedule.redirect');

// Redirect old admin-broadcast to pamong chat with broadcast tab
Route::get('/admin-broadcast-redirect', function () {
    return redirect()->route('pamong.chat.index', ['tab' => 'broadcast']);
})->name('admin.broadcast.redirect');

// Redirect old chat-groups to pamong chat with grup tab
Route::get('/chat-groups-redirect', function () {
    return redirect()->route('pamong.chat.index', ['tab' => 'grup']);
})->name('chat-groups.redirect');

// Redirect old group-chat to pamong chat with grup tab
Route::get('/group-chat-redirect', function () {
    return redirect()->route('pamong.chat.index', ['tab' => 'grup']);
})->name('group-chat.redirect');

// Catatan Rapat / Musyawarah (Kanban Board)
Route::middleware('auth')->group(function () {
    Route::get('/catatan-rapat', [App\Http\Controllers\CatatanRapatController::class, 'index'])->name('catatan-rapat.index');
    Route::post('/catatan-rapat', [App\Http\Controllers\CatatanRapatController::class, 'store'])->name('catatan-rapat.store');
    Route::put('/catatan-rapat/{catatanRapat}', [App\Http\Controllers\CatatanRapatController::class, 'update'])->name('catatan-rapat.update');
    Route::delete('/catatan-rapat/{catatanRapat}', [App\Http\Controllers\CatatanRapatController::class, 'destroy'])->name('catatan-rapat.destroy');
    Route::post('/catatan-rapat/move', [App\Http\Controllers\CatatanRapatController::class, 'move'])->name('catatan-rapat.move');
    Route::post('/catatan-rapat/settings', [App\Http\Controllers\CatatanRapatController::class, 'updateSettings'])->name('catatan-rapat.settings');

    // Persiapan Acara
    Route::get('/persiapan-acara', [App\Http\Controllers\PersiapanAcaraController::class, 'index'])->name('persiapan-acara.index');
    Route::post('/persiapan-acara', [App\Http\Controllers\PersiapanAcaraController::class, 'store'])->name('persiapan-acara.store');
    Route::put('/persiapan-acara/{persiapanAcara}', [App\Http\Controllers\PersiapanAcaraController::class, 'update'])->name('persiapan-acara.update');
    Route::delete('/persiapan-acara/{persiapanAcara}', [App\Http\Controllers\PersiapanAcaraController::class, 'destroy'])->name('persiapan-acara.destroy');
});

// Fallback route for SPA-like behavior
Route::fallback(function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});
