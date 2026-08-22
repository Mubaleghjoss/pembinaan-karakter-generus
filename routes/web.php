<?php

use App\Http\Controllers\AdminBroadcastController;
use App\Http\Controllers\AttendanceScheduleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OrtuAuthController;
use App\Http\Controllers\Auth\SiswaAuthController;
use App\Http\Controllers\Auth\WebAuthnController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BeritaController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CatatanRapatController;
use App\Http\Controllers\CekKehadiranController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ChatGroupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataPullController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FaceAttendanceController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\BossBattleController;
use App\Http\Controllers\KarakterGameController;
use App\Http\Controllers\KarakterLuhurController;
use App\Http\Controllers\GenerusRecapController;
use App\Http\Controllers\GenerusRegistrationController;
use App\Http\Controllers\KarakterController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LaporanPenyaksianController;
use App\Http\Controllers\ManualAttendanceController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\MateriRppJournalController;
use App\Http\Controllers\MateriTargetController;
use App\Http\Controllers\OrtuChatController;
use App\Http\Controllers\OrtuDashboardController;
use App\Http\Controllers\OrtuJadwalController;
use App\Http\Controllers\OrtuManagementController;
use App\Http\Controllers\OrtuTugasController;
use App\Http\Controllers\PamongChatController;
use App\Http\Controllers\PamongController;
use App\Http\Controllers\PamongPresensiController;
use App\Http\Controllers\PersiapanAcaraController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\ProfileAssignmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\QuranReadingController;
use App\Http\Controllers\RemoteMediaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RpgGameController;
use App\Http\Controllers\PublicGameController;
use App\Http\Controllers\PublicKarakterController;
use App\Http\Controllers\ScheduleReminderController;
use App\Http\Controllers\Security\CspReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShareInfoController;
use App\Http\Controllers\SiswaChatController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SiswaDashboardController;
use App\Http\Controllers\SiswaKarakterController;
use App\Http\Controllers\SiswaMateriRppJournalController;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\TeacherConfirmationController;
use App\Http\Controllers\TeacherMaterialController;
use App\Http\Controllers\TeacherPlanningController;
use App\Http\Controllers\TeacherPortalController;
use App\Http\Controllers\TracerKarakterController;
use App\Http\Controllers\TugasPkgController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupChatController;
use App\Models\RpgMap;
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
Route::get('/', [PublicController::class, 'index'])->name('public.index');
Route::get('/berita-publik/{slug}', [PublicController::class, 'legacyBerita'])
    ->name('public.berita.legacy');
Route::get('/berita/{slug}', [PublicController::class, 'berita'])
    ->where('slug', '(?!create$)[A-Za-z0-9-]+')
    ->name('public.berita');
Route::get('/sq/{code}', [QuranReadingController::class, 'openPublicScan'])
    ->where('code', '[A-Za-z0-9_-]{44}')
    ->middleware('throttle:quran-public-open')
    ->name('public.quran.scan.open');
Route::get('/scan-presensi', [PublicController::class, 'scanner'])->name('public.scanner');
Route::post('/scan-presensi/bacaan-quran', [QuranReadingController::class, 'publicScanUpload'])
    ->middleware('throttle:quran-public-scan')
    ->name('public.quran.scan.upload');
Route::post('/scan-presensi/bacaan-quran/barcode/identify', [QuranReadingController::class, 'publicBarcodeIdentify'])
    ->middleware('throttle:quran-barcode')
    ->name('public.quran.barcode.identify');
Route::post('/scan-presensi/bacaan-quran/barcode/store', [QuranReadingController::class, 'publicBarcodeStore'])
    ->middleware('throttle:quran-barcode')
    ->name('public.quran.barcode.store');
Route::get('/scan-presensi/bacaan-quran/{scan}/konfirmasi', [QuranReadingController::class, 'publicScanConfirmForm'])
    ->name('public.quran.scan.confirm');
Route::post('/scan-presensi/bacaan-quran/{scan}/konfirmasi', [QuranReadingController::class, 'publicScanConfirm'])
    ->middleware('throttle:quran-public-scan')
    ->name('public.quran.scan.confirm.store');
Route::get('/scan-presensi/bacaan-quran/{scan}/gambar', [QuranReadingController::class, 'publicScanImage'])
    ->name('public.quran.scan.image');
Route::get('/materi', [PublicController::class, 'materiIndex'])->name('materi.index');
// Referensi publik 29 Karakter Luhur (data dari Bank 29 Karakter)
Route::get('/29-karakter', [PublicKarakterController::class, 'index'])->name('public.karakter.index');
Route::get('/29-karakter/{slug}', [PublicKarakterController::class, 'show'])->name('public.karakter.show');
Route::get('/materi-publik/{materi}/pdf/{index}', [PublicController::class, 'materiPdfView'])
    ->whereNumber('index')
    ->name('public.materi.pdf.view');
Route::get('/materi-publik/{materi}/pdf/{index}/download', [PublicController::class, 'materiPdfDownload'])
    ->whereNumber('index')
    ->name('public.materi.pdf.download');
Route::get('/materi-publik/{materi}', [PublicController::class, 'materiShow'])->name('public.materi.show');
Route::get('/presentasi-publik/{presentation:slug}', [PresentationController::class, 'publicShow'])
    ->name('public.presentations.show');
Route::get('/kalender', [CalendarController::class, 'publicIndex'])->name('public.calendar.index');
Route::get('/kalender/events', [CalendarController::class, 'publicEvents'])->name('public.calendar.events');
// Pendataan guru privat. Sengaja tidak ditampilkan pada navigasi publik.
Route::get('/pendataanguru', [TeacherAvailabilityController::class, 'index'])
    ->name('public.teacher-availability.index');
Route::post('/pendataanguru/akses', [TeacherAvailabilityController::class, 'unlock'])
    ->middleware('throttle:5,1')
    ->name('public.teacher-availability.unlock');
Route::post('/pendataanguru', [TeacherAvailabilityController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('public.teacher-availability.store');
Route::get('/pendataanguru/selesai', [TeacherAvailabilityController::class, 'success'])
    ->name('public.teacher-availability.success');
Route::get('/pendataanguru/hasil/{teacherProfile}/{downloadToken}/pdf', [TeacherAvailabilityController::class, 'pdf'])
    ->middleware('throttle:30,1')
    ->name('public.teacher-availability.pdf');
Route::get('/konfirmasi-pengajar/{token}', [TeacherConfirmationController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('public.teacher-confirmation.show');
Route::post('/konfirmasi-pengajar/{token}', [TeacherConfirmationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.teacher-confirmation.store');
// Public QR Scan endpoint (no auth required for public scanner)
Route::post('/qr/scan', [PresensiController::class, 'scan'])->name('qr.scan.post');
Route::post('/face-presensi/scan', [FaceAttendanceController::class, 'scan'])
    ->middleware('throttle:qr-scan')
    ->name('face-presensi.scan');

// Pendaftaran Generus privat. Sengaja tidak ditampilkan pada navigasi publik.
Route::get('/daftarpkg', [GenerusRegistrationController::class, 'index'])
    ->name('public.generus-registration.short.index');
Route::post('/daftarpkg/akses', [GenerusRegistrationController::class, 'unlock'])
    ->middleware('throttle:5,1')
    ->name('public.generus-registration.short.unlock');
Route::get('/daftarpkg/cari-generus', [GenerusRegistrationController::class, 'searchStudents'])
    ->middleware('throttle:30,1')
    ->name('public.generus-registration.short.search');
Route::post('/daftarpkg/verifikasi-akun', [GenerusRegistrationController::class, 'verifyExisting'])
    ->middleware('throttle:5,1')
    ->name('public.generus-registration.short.verify');
Route::post('/daftarpkg', [GenerusRegistrationController::class, 'storeShort'])
    ->middleware('throttle:5,1')
    ->name('public.generus-registration.short.store');
// Tautan langsung per-Generus (akun sudah ada). Dibagikan ke Orang Tua tanpa kode akses.
Route::get('/daftarpkg/ulang/{token}', [GenerusRegistrationController::class, 'directExisting'])
    ->middleware('throttle:30,1')
    ->name('public.generus-registration.direct');
Route::get('/daftarpkg/ulang-formulir', [GenerusRegistrationController::class, 'directForm'])
    ->name('public.generus-registration.direct.form');
Route::get('/daftarpkg/hasil/{registration}/{downloadToken}', [GenerusRegistrationController::class, 'result'])
    ->name('public.generus-registration.short.result');
Route::get('/daftarpkg/hasil/{registration}/{downloadToken}/pdf', [GenerusRegistrationController::class, 'pdf'])
    ->name('public.generus-registration.short.pdf');

// Tautan privat lama tetap diterima untuk masa transisi.
Route::get('/pendaftaran-generus/hasil/{registration}/{downloadToken}', [GenerusRegistrationController::class, 'result'])
    ->name('public.generus-registration.result');
Route::get('/pendaftaran-generus/hasil/{registration}/{downloadToken}/pdf', [GenerusRegistrationController::class, 'pdf'])
    ->name('public.generus-registration.pdf');
Route::get('/pendaftaran-generus/{token}', [GenerusRegistrationController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('public.generus-registration.show');
Route::post('/pendaftaran-generus/{token}', [GenerusRegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('public.generus-registration.store');

// Laporan Penyaksian - Public Form (like Google Form)
Route::get('/lapor-pkg', [LaporanPenyaksianController::class, 'create'])->name('laporan-penyaksian.create');
Route::post('/lapor-pkg', [LaporanPenyaksianController::class, 'store'])->name('laporan-penyaksian.store');
Route::get('/lapor-pkg/siswa-list', [LaporanPenyaksianController::class, 'getSiswaList'])->name('laporan-penyaksian.siswa-list');
Route::get('/lapor-pkg/pamong-list', [LaporanPenyaksianController::class, 'getPamongList'])->name('laporan-penyaksian.pamong-list');
Route::get('/lapor-pkg/generus-list', [LaporanPenyaksianController::class, 'getGenerusList'])->name('laporan-penyaksian.generus-list');
Route::get('/game-29-karakter', [RpgGameController::class, 'publicIndex'])
    ->middleware('throttle:rpg-public')
    ->name('public.rpg.index');
// Coba game edukatif (Rangkai Kata & Tebak Karakter) tanpa login.
// /coba-game disatukan ke halaman /game-29-karakter.
Route::get('/coba-game', fn () => redirect()->route('public.rpg.index'));
Route::get('/coba-game/{mode}', [PublicGameController::class, 'play'])
    ->middleware('throttle:rpg-public')
    ->name('public.game.play');
Route::get('/game-29-karakter/{rpgMap}/main', [RpgGameController::class, 'publicPlay'])
    ->middleware('throttle:rpg-public')
    ->name('public.rpg.play');
Route::post('/game-29-karakter/{rpgMap}/presence', [RpgGameController::class, 'publicPresence'])
    ->middleware('throttle:rpg-presence')
    ->name('public.rpg.presence');
Route::get('/rpg-admin', function () {
    if (! auth()->check()) {
        return redirect()->route('public.rpg.index');
    }

    return redirect()->route('admin.rpg.index');
});
Route::get('/rpg-admin/play/{rpgMap}', function (RpgMap $rpgMap) {
    return redirect()->route('public.rpg.play', $rpgMap);
});
Route::post('/rpg-admin/play/{rpgMap}/presence', [RpgGameController::class, 'publicPresence'])
    ->middleware('throttle:rpg-presence');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// CSRF Token refresh endpoint
Route::get('/csrf-token', function () {
    return response()
        ->json(['token' => csrf_token()])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
});

Route::post('/security/csp-report', CspReportController::class)
    ->middleware('throttle:csp-report')
    ->name('security.csp-report');

// Shared WebAuthn biometric routes (public - no auth needed for login flow)
Route::prefix('webauthn')->name('webauthn.')->group(function () {
    Route::post('/login-options', [WebAuthnController::class, 'loginOptions'])->middleware('throttle:biometric')->name('login-options');
    Route::post('/login', [WebAuthnController::class, 'login'])->middleware('throttle:biometric')->name('login');
    Route::get('/has-credentials', [WebAuthnController::class, 'hasCredentials'])->middleware('throttle:biometric')->name('has-credentials');
});

// Siswa Authentication routes
Route::prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/login', [SiswaAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [SiswaAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [SiswaAuthController::class, 'logout'])->name('logout');

    // WebAuthn biometric routes (public - no auth needed for login flow)
    Route::post('/webauthn/login-options', [WebAuthnController::class, 'loginOptions'])->middleware('throttle:biometric')->name('webauthn.login-options');
    Route::post('/webauthn/login', [WebAuthnController::class, 'login'])->middleware('throttle:biometric')->name('webauthn.login');
    Route::get('/webauthn/has-credentials', [WebAuthnController::class, 'hasCredentials'])->middleware('throttle:biometric')->name('webauthn.has-credentials');

    // Protected siswa routes
    Route::middleware('auth.siswa')->group(function () {
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'storeSiswa'])
            ->name('pwa.push-subscriptions.store');
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroySiswa'])
            ->name('pwa.push-subscriptions.destroy');

        // WebAuthn protected routes (registration & management)
        Route::get('/webauthn/register-options', [WebAuthnController::class, 'registerOptions'])->name('webauthn.register-options');
        Route::post('/webauthn/register', [WebAuthnController::class, 'register'])->name('webauthn.register');
        Route::get('/webauthn/status', [WebAuthnController::class, 'status'])->name('webauthn.status');
        Route::delete('/webauthn/{id}', [WebAuthnController::class, 'destroy'])->name('webauthn.destroy');
        Route::post('/webauthn/dismiss-prompt', [WebAuthnController::class, 'dismissPrompt'])->name('webauthn.dismiss-prompt');
        Route::get('/biometrik', [WebAuthnController::class, 'settingsPage'])->name('biometrik');

        Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');
        Route::put('/profil-penempatan', [ProfileAssignmentController::class, 'updateSiswa'])->name('profile-assignment.update');
        Route::get('/materi', [MateriController::class, 'siswaIndex'])->name('materi.index');
        Route::get('/materi/{materi}', [MateriController::class, 'siswaShow'])->name('materi.show');
        Route::post('/materi-targets/{target}/toggle', [MateriTargetController::class, 'siswaToggle'])->name('materi-targets.toggle');
        Route::get('/jurnal-rpp', [SiswaMateriRppJournalController::class, 'index'])->name('materi-rpp-journals.index');
        Route::get('/jurnal-rpp/{scheduleReminder}', [SiswaMateriRppJournalController::class, 'show'])->name('materi-rpp-journals.show');
        Route::post('/jurnal-rpp/{scheduleReminder}', [SiswaMateriRppJournalController::class, 'store'])->name('materi-rpp-journals.store');
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
        Route::get('/kartu', [SiswaDashboardController::class, 'kartu'])->name('kartu');
        Route::get('/kartu/print', [SiswaDashboardController::class, 'kartuPrint'])->name('kartu.print');
        Route::get('/face-profile', [FaceAttendanceController::class, 'profile'])->name('face-profile.show');
        Route::post('/face-profile/enroll', [FaceAttendanceController::class, 'enroll'])->name('face-profile.enroll');

        // Canonical Tugas PKG routes
        Route::prefix('/tugas-pkg')->name('tugas-pkg.')->group(function () {
            Route::get('/', [SiswaKarakterController::class, 'index'])->name('index');
            Route::post('/{karakter}/submit', [SiswaKarakterController::class, 'toggle'])->name('submit');
            Route::get('/riwayat', [SiswaKarakterController::class, 'history'])->name('history');
            Route::get('/terverifikasi', [SiswaKarakterController::class, 'verifiedHistory'])->name('verified-history');
        });

        // Tugas PKG compatibility routes
        Route::get('/karakter', [SiswaKarakterController::class, 'index'])->name('karakter.index');
        Route::post('/karakter/{karakter}/toggle', [SiswaKarakterController::class, 'toggle'])->name('karakter.toggle');
        Route::get('/karakter/history', [SiswaKarakterController::class, 'history'])->name('karakter.history');
        Route::get('/karakter/verified-history', [SiswaKarakterController::class, 'verifiedHistory'])->name('karakter.verified-history');

        // Kehadiran PKG
        Route::get('/kehadiran', [CekKehadiranController::class, 'siswaIndex'])->name('kehadiran.index');

        Route::prefix('bacaan-quran')->name('quran.')->group(function () {
            Route::get('/', [QuranReadingController::class, 'studentIndex'])->name('index');
            Route::post('/', [QuranReadingController::class, 'studentStore'])->name('store');
            Route::get('/laporan', [QuranReadingController::class, 'studentReport'])->name('report');
            Route::get('/lembar-lanjutan', [QuranReadingController::class, 'studentSheet'])->name('sheet');
            Route::get('/peta-khatam', [QuranReadingController::class, 'studentKhatamMap'])->name('khatam-map');
            Route::get('/paket-bolak-balik', [QuranReadingController::class, 'studentDuplex'])->name('duplex');
            Route::get('/scan', [QuranReadingController::class, 'scanForm'])->name('scan');
            Route::post('/scan', [QuranReadingController::class, 'scanUpload'])->name('scan.upload');
            Route::post('/barcode/identify', [QuranReadingController::class, 'studentBarcodeIdentify'])->middleware('throttle:quran-barcode')->name('barcode.identify');
            Route::post('/barcode/store', [QuranReadingController::class, 'studentBarcodeStore'])->middleware('throttle:quran-barcode')->name('barcode.store');
            Route::get('/scan/{scan}/konfirmasi', [QuranReadingController::class, 'studentScanConfirmForm'])->name('scan.confirm');
            Route::post('/scan/{scan}/konfirmasi', [QuranReadingController::class, 'studentScanConfirm'])->name('scan.confirm.store');
            Route::get('/scan/{scan}/gambar', [QuranReadingController::class, 'studentScanImage'])->name('scan.image');
            Route::put('/{entry}', [QuranReadingController::class, 'studentUpdate'])->name('update');
        });

        // Profile routes
        Route::get('/profile', [SiswaDashboardController::class, 'profile'])->name('profile');
        Route::get('/profile/surat-pernyataan', [GenerusRegistrationController::class, 'siswaPreview'])->name('profile.statement.preview');
        Route::get('/profile/surat-pernyataan/unduh', [GenerusRegistrationController::class, 'siswaDownload'])->name('profile.statement.download');
        Route::post('/profile/update-photo', [SiswaDashboardController::class, 'updatePhoto'])->name('profile.update-photo');
        Route::post('/profile/update', [SiswaDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/update-account', [SiswaDashboardController::class, 'updateAccount'])->name('profile.update-account');

        // Chat routes
        Route::get('/chat', [SiswaChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/messages', [SiswaChatController::class, 'getMessages'])->name('chat.messages');
        Route::post('/chat/send', [SiswaChatController::class, 'sendMessage'])->name('chat.send');
        Route::get('/chat/unread', [SiswaChatController::class, 'getUnreadCount'])->name('chat.unread');
        Route::get('/chat/unread-counts', [SiswaChatController::class, 'getUnreadCountPerContact'])->name('chat.unread.counts');

        // Group Chat routes for Siswa
        Route::prefix('group-chat')->name('group-chat.')->group(function () {
            Route::get('/', [UserGroupChatController::class, 'index'])->name('index');
            Route::get('/unread', [UserGroupChatController::class, 'getUnreadCount'])->name('unread');
            Route::get('/{chatGroup}/messages', [UserGroupChatController::class, 'getMessages'])->name('messages');
            Route::post('/{chatGroup}/send', [UserGroupChatController::class, 'sendMessage'])->name('send');
            Route::get('/{chatGroup}/info', [UserGroupChatController::class, 'getGroupInfo'])->name('info');
        });

        // Gamification routes for Siswa
        Route::prefix('gamification')->name('gamification.')->group(function () {
            Route::get('/', [GamificationController::class, 'dashboard'])->name('dashboard');
            Route::get('/leaderboard', [GamificationController::class, 'leaderboard'])->name('leaderboard');
            Route::get('/badges', [GamificationController::class, 'badges'])->name('badges');
            Route::get('/badges/{badge}', [GamificationController::class, 'badgeDetail'])->name('badge-detail');
            Route::get('/history', [GamificationController::class, 'pointHistory'])->name('history');
            Route::get('/widget', [GamificationController::class, 'widgetData'])->name('widget');
            Route::get('/certificate/{level}/download', [CertificateController::class, 'download'])->name('certificate.download');
        });

        // RPG Quest routes for Siswa
        Route::prefix('rpg')->name('rpg.')->group(function () {
            Route::get('/', [RpgGameController::class, 'index'])->name('index');
            Route::get('/beta-3d', [RpgGameController::class, 'beta3d'])->name('beta-3d');
            Route::get('/{rpgMap}/play', [RpgGameController::class, 'play'])->name('play');
            Route::post('/{rpgMap}/move', [RpgGameController::class, 'move'])->name('move');
            Route::post('/{rpgMap}/answer', [RpgGameController::class, 'answer'])->name('answer');
            Route::get('/{rpgMap}/state', [RpgGameController::class, 'getGameState'])->name('state');
            Route::post('/character', [RpgGameController::class, 'updateCharacter'])->name('character');
            Route::post('/heartbeat', [RpgGameController::class, 'heartbeat'])->name('heartbeat');
            Route::post('/{rpgMap}/reset', [RpgGameController::class, 'resetSession'])->name('reset');
        });

        // Game 29 Karakter Luhur (Rangkai Kata & Tebak Karakter)
        Route::prefix('game')->name('game.')->group(function () {
            Route::get('/', [KarakterGameController::class, 'index'])->name('index');
            Route::get('/solo/{mode}', [KarakterGameController::class, 'solo'])->name('solo');
            Route::post('/solo/{mode}/submit', [KarakterGameController::class, 'soloSubmit'])
                ->middleware('throttle:60,1')->name('solo.submit');
            Route::post('/duel/ai/{mode}', [KarakterGameController::class, 'createAiDuel'])
                ->middleware('throttle:30,1')->name('duel.ai');
            Route::post('/duel/pvp/{mode}', [KarakterGameController::class, 'createPvpDuel'])
                ->middleware('throttle:30,1')->name('duel.pvp');
            Route::post('/duel/join', [KarakterGameController::class, 'joinPvpDuel'])
                ->middleware('throttle:30,1')->name('duel.join');
            Route::get('/duel/{duel}', [KarakterGameController::class, 'showDuel'])->name('duel.show');
            Route::post('/duel/{duel}/answer', [KarakterGameController::class, 'answerDuel'])
                ->middleware('throttle:120,1')->name('duel.answer');
            Route::get('/duel/{duel}/state', [KarakterGameController::class, 'duelState'])
                ->middleware('throttle:120,1')->name('duel.state');

            // Boss Online (keroyok bareng)
            Route::get('/boss', [BossBattleController::class, 'arena'])->name('boss');
            Route::post('/boss/{boss}/attack', [BossBattleController::class, 'attack'])
                ->middleware('throttle:180,1')->name('boss.attack');
            Route::get('/boss/{boss}/state', [BossBattleController::class, 'state'])
                ->middleware('throttle:180,1')->name('boss.state');
        });

        // Calendar routes for Siswa
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [CalendarController::class, 'siswaIndex'])->name('index');
            Route::get('/events', [CalendarController::class, 'siswaEvents'])->name('events');
        });
    });
});

// Ortu (Parent) Authentication & Portal routes
Route::prefix('ortu')->name('ortu.')->group(function () {
    Route::get('/login', [OrtuAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [OrtuAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [OrtuAuthController::class, 'logout'])->name('logout');

    // WebAuthn biometric routes (public - no auth needed for login flow)
    Route::post('/webauthn/login-options', [WebAuthnController::class, 'loginOptions'])->middleware('throttle:biometric')->name('webauthn.login-options');
    Route::post('/webauthn/login', [WebAuthnController::class, 'login'])->middleware('throttle:biometric')->name('webauthn.login');
    Route::get('/webauthn/has-credentials', [WebAuthnController::class, 'hasCredentials'])->middleware('throttle:biometric')->name('webauthn.has-credentials');

    // Protected ortu routes
    Route::middleware('auth.ortu')->group(function () {
        Route::get('/dashboard', [OrtuDashboardController::class, 'index'])->name('dashboard');
        Route::get('/materi', [MateriController::class, 'ortuIndex'])->name('materi.index');
        Route::get('/materi/{materi}', [MateriController::class, 'ortuShow'])->name('materi.show');

        // WebAuthn biometric routes (ortu)
        Route::get('/webauthn/register-options', [WebAuthnController::class, 'registerOptions'])->name('webauthn.register-options');
        Route::post('/webauthn/register', [WebAuthnController::class, 'register'])->name('webauthn.register');
        Route::get('/webauthn/status', [WebAuthnController::class, 'status'])->name('webauthn.status');
        Route::delete('/webauthn/{id}', [WebAuthnController::class, 'destroy'])->name('webauthn.destroy');
        Route::post('/webauthn/dismiss-prompt', [WebAuthnController::class, 'dismissPrompt'])->name('webauthn.dismiss-prompt');
        Route::get('/biometrik', [WebAuthnController::class, 'settingsPage'])->name('biometrik');

        // Jadwal (view-only)
        Route::get('/jadwal', [OrtuJadwalController::class, 'index'])->name('jadwal');
        Route::get('/jadwal/events', [OrtuJadwalController::class, 'getEvents'])->name('jadwal.events');

        // Tugas PKG
        Route::get('/tugas', [OrtuTugasController::class, 'index'])->name('tugas');
        Route::post('/tugas/{checklist}/comment', [OrtuTugasController::class, 'addComment'])->name('tugas.comment');

        // Kehadiran PKG
        Route::get('/kehadiran', [CekKehadiranController::class, 'ortuIndex'])->name('kehadiran');

        Route::get('/bacaan-quran', [QuranReadingController::class, 'parentIndex'])->name('quran.index');
        Route::get('/bacaan-quran/laporan', [QuranReadingController::class, 'parentReport'])->name('quran.report');

        // Chat Pamong
        Route::get('/chat', [OrtuChatController::class, 'index'])->name('chat');
        Route::get('/chat/messages', [OrtuChatController::class, 'getMessages'])->name('chat.messages');
        Route::post('/chat/send', [OrtuChatController::class, 'sendMessage'])->name('chat.send');

        // Settings
        Route::get('/settings', [OrtuDashboardController::class, 'settings'])->name('settings');
        Route::get('/settings/surat-pernyataan', [GenerusRegistrationController::class, 'ortuPreview'])->name('settings.statement.preview');
        Route::get('/settings/surat-pernyataan/unduh', [GenerusRegistrationController::class, 'ortuDownload'])->name('settings.statement.download');
        Route::post('/settings/update', [OrtuDashboardController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/password', [OrtuDashboardController::class, 'updatePassword'])->name('settings.password');

        // Certificate Download
        Route::get('/certificate/{level}/download', [CertificateController::class, 'download'])->name('certificate.download');
    });
});

Route::get('/guru/manifest.json', function () {
    return response()->json([
        'name' => 'Portal Guru PKG Panunggangan',
        'short_name' => 'Guru PKG',
        'description' => 'Jadwal dan materi Guru PKG Panunggangan',
        'id' => '/guru',
        'start_url' => '/guru',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#f8fafc',
        'theme_color' => '#047857',
        'orientation' => 'portrait-primary',
        'lang' => 'id',
        'icons' => [
            ['src' => '/images/icons/pkg-logo-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/images/icons/pkg-logo-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/images/icons/pkg-logo-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
            ['src' => '/images/icons/pkg-logo-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'categories' => ['education', 'productivity'],
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('guru.manifest');

Route::middleware('auth')->group(function () {
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'storeWeb'])
        ->name('pwa.push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroyWeb'])
        ->name('pwa.push-subscriptions.destroy');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::prefix('guru')->name('guru.')->middleware('guru.profile')->group(function () {
        Route::get('/password-awal', [TeacherPortalController::class, 'initialPassword'])->name('password.initial');
        Route::put('/password-awal', [TeacherPortalController::class, 'updateInitialPassword'])->name('password.initial.update');

        Route::middleware('guru.password')->group(function () {
            Route::get('/', [TeacherPortalController::class, 'dashboard'])->name('dashboard');
            Route::get('/jadwal', [TeacherPortalController::class, 'schedule'])->name('schedule');
            Route::get('/jadwal/{assignment}', [TeacherPortalController::class, 'scheduleShow'])->name('schedule.show');
            Route::patch('/jadwal/{assignment}/konfirmasi', [TeacherPortalController::class, 'confirmSchedule'])->name('schedule.confirm');
            Route::post('/jadwal/{assignment}/permohonan', [TeacherPortalController::class, 'requestScheduleChange'])->name('schedule.request');
            Route::get('/materi', [TeacherPortalController::class, 'materials'])->name('materials');
            Route::get('/profil', [TeacherPortalController::class, 'profile'])->name('profile');
            Route::put('/profil', [TeacherPortalController::class, 'updateProfile'])->name('profile.update');
            Route::put('/kesediaan', [TeacherPortalController::class, 'updateAvailability'])->name('availability.update');
            Route::put('/tema', [TeacherPortalController::class, 'updateTheme'])->name('theme.update');
            Route::get('/ubah-password', [TeacherPortalController::class, 'password'])->name('password.edit');
            Route::put('/ubah-password', [TeacherPortalController::class, 'updatePassword'])->name('password.update');
            Route::get('/surat-kesediaan', [TeacherPortalController::class, 'statement'])->name('statement');
            Route::get('/kartu-id', [TeacherPortalController::class, 'idCard'])->name('id-card');
        });
    });

    // WebAuthn biometric routes (admin/pamong)
    Route::get('/webauthn/register-options', [WebAuthnController::class, 'registerOptions'])->name('webauthn.register-options');
    Route::post('/webauthn/register', [WebAuthnController::class, 'register'])->name('webauthn.register');
    Route::get('/webauthn/status', [WebAuthnController::class, 'status'])->name('webauthn.status');
    Route::delete('/webauthn/{id}', [WebAuthnController::class, 'destroy'])->name('webauthn.destroy');
    Route::post('/webauthn/dismiss-prompt', [WebAuthnController::class, 'dismissPrompt'])->name('webauthn.dismiss-prompt');
    Route::get('/biometrik', [WebAuthnController::class, 'settingsPage'])->name('biometrik');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/secondary-panels', [DashboardController::class, 'secondaryPanels'])->name('dashboard.secondary-panels');
    Route::put('/profil-penempatan', [ProfileAssignmentController::class, 'updatePamong'])->name('profile-assignment.update');

    // Profile management
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/id-card', [ProfileController::class, 'idCard'])->name('profile.id-card');
    Route::get('/profile/id-card/print', [ProfileController::class, 'idCardPrint'])->name('profile.id-card.print');
    Route::post('/profile/id-card/refresh-qr', [ProfileController::class, 'refreshIdCardQr'])->name('profile.id-card.refresh-qr');
    Route::get('/face-profile', [FaceAttendanceController::class, 'profile'])->name('face-profile.show');
    Route::post('/face-profile/enroll', [FaceAttendanceController::class, 'enroll'])->name('face-profile.enroll');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update.post');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profile/mobile-menu-favorites', [ProfileController::class, 'updateMobileMenuFavorites'])
        ->name('profile.mobile-menu-favorites.update');

    // Pendataan dan penjadwalan guru MT/MS.
    Route::get('/pendataan-guru', [TeacherPlanningController::class, 'index'])->name('teacher-planning.index');
    Route::put('/pendataan-guru/akses', [TeacherPlanningController::class, 'updateInvite'])->name('teacher-planning.invite.update');
    Route::put('/pendataan-guru/pesan-selesai', [TeacherPlanningController::class, 'updateSuccessMessage'])->name('teacher-planning.success-message.update');
    Route::put('/pendataan-guru/kontak-admin', [TeacherPlanningController::class, 'updateAdminContact'])->name('teacher-planning.admin-contact.update');
    Route::put('/pendataan-guru/profil/{teacherProfile}', [TeacherPlanningController::class, 'updateProfile'])->name('teacher-planning.profiles.update');
    Route::post('/pendataan-guru/profil/{teacherProfile}/akun', [TeacherPlanningController::class, 'createAccount'])->name('teacher-planning.profiles.account.store');
    Route::post('/pendataan-guru/profil/{teacherProfile}/akun/reset-password', [TeacherPlanningController::class, 'resetAccountPassword'])->name('teacher-planning.profiles.account.reset');
    Route::delete('/pendataan-guru/profil/{teacherProfile}', [TeacherPlanningController::class, 'destroyProfile'])->name('teacher-planning.profiles.destroy');
    Route::get('/pendataan-guru/profil/{teacherProfile}/surat', [TeacherPlanningController::class, 'statementPreview'])->name('teacher-planning.profiles.statement.preview');
    Route::get('/pendataan-guru/profil/{teacherProfile}/surat/unduh', [TeacherPlanningController::class, 'statementDownload'])->name('teacher-planning.profiles.statement.download');
    Route::post('/pendataan-guru/template', [TeacherPlanningController::class, 'storeTemplate'])->name('teacher-planning.templates.store');
    Route::patch('/pendataan-guru/template/{teacherScheduleTemplate}/toggle', [TeacherPlanningController::class, 'toggleTemplate'])->name('teacher-planning.templates.toggle');
    Route::post('/pendataan-guru/jadwal/generate', [TeacherPlanningController::class, 'generate'])->name('teacher-planning.generate');
    Route::put('/pendataan-guru/sesi/{teacherScheduleSession}/materi', [TeacherPlanningController::class, 'syncSessionMaterials'])->name('teacher-planning.sessions.materials.sync');
    Route::put('/pendataan-guru/sesi/{teacherScheduleSession}/{role}', [TeacherPlanningController::class, 'assign'])->name('teacher-planning.sessions.assign');
    Route::patch('/pendataan-guru/sesi/{teacherScheduleSession}/swap', [TeacherPlanningController::class, 'swap'])->name('teacher-planning.sessions.swap');
    Route::patch('/pendataan-guru/periode/{teacherSchedulePeriod}/publish', [TeacherPlanningController::class, 'publish'])->name('teacher-planning.periods.publish');
    Route::delete('/pendataan-guru/periode/{teacherSchedulePeriod}', [TeacherPlanningController::class, 'destroyPeriod'])->name('teacher-planning.periods.destroy');
    Route::post('/pendataan-guru/penugasan/{assignment}/whatsapp/{stage}', [TeacherPlanningController::class, 'whatsapp'])->name('teacher-planning.assignments.whatsapp');
    Route::patch('/pendataan-guru/penugasan/{assignment}/terkirim/{stage}', [TeacherPlanningController::class, 'markWhatsappSent'])->name('teacher-planning.assignments.sent');
    Route::patch('/pendataan-guru/penugasan/{assignment}/status', [TeacherPlanningController::class, 'updateConfirmationStatus'])->name('teacher-planning.assignments.status');
    Route::patch('/pendataan-guru/permohonan/{teacherScheduleRequest}/status', [TeacherPlanningController::class, 'updateScheduleRequest'])->name('teacher-planning.requests.status');
    Route::get('/pendataan-guru/periode/{teacherSchedulePeriod}/excel', [TeacherPlanningController::class, 'exportExcel'])->name('teacher-planning.export.excel');
    Route::get('/pendataan-guru/periode/{teacherSchedulePeriod}/pdf', [TeacherPlanningController::class, 'exportPdf'])->name('teacher-planning.export.pdf');
    Route::get('/pendataan-guru/periode/{teacherSchedulePeriod}/gambar', [TeacherPlanningController::class, 'exportImage'])->name('teacher-planning.export.image');
    Route::get('/pendataan-guru/materi', [TeacherMaterialController::class, 'index'])->name('teacher-materials.index');
    Route::post('/pendataan-guru/materi', [TeacherMaterialController::class, 'store'])->name('teacher-materials.store');
    Route::put('/pendataan-guru/materi/{teacherMaterial}', [TeacherMaterialController::class, 'update'])->name('teacher-materials.update');
    Route::delete('/pendataan-guru/materi/{teacherMaterial}', [TeacherMaterialController::class, 'destroy'])->name('teacher-materials.destroy');

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
    Route::patch('/siswa/{siswa}/alumni', [SiswaController::class, 'updateAlumniLifecycle'])
        ->middleware('admin.only')
        ->name('siswa.alumni.update');

    // Siswa - Ortu Account Management (admin)
    Route::post('/siswa/{siswa}/reset-ortu-password', [SiswaController::class, 'resetOrtuPassword'])->name('siswa.reset-ortu-password');
    Route::post('/siswa/{siswa}/update-ortu-account', [SiswaController::class, 'updateOrtuAccount'])->name('siswa.update-ortu-account');

    // Ortu Management (Dedicated Menu)
    Route::get('/ortu-management', [OrtuManagementController::class, 'index'])->name('ortu-management.index');
    Route::post('/ortu-management/{siswa}/reset', [OrtuManagementController::class, 'resetPassword'])->name('ortu-management.reset');
    Route::post('/ortu-management/reset-all', [OrtuManagementController::class, 'resetAllPasswords'])->name('ortu-management.reset-all');

    // Share Info Management
    Route::post('/share-info', [ShareInfoController::class, 'store'])->name('share-info.store');
    Route::put('/share-info/{shareInfo}', [ShareInfoController::class, 'update'])->name('share-info.update');
    Route::patch('/share-info/{shareInfo}/toggle', [ShareInfoController::class, 'toggle'])->name('share-info.toggle');
    Route::delete('/share-info/{shareInfo}', [ShareInfoController::class, 'destroy'])->name('share-info.destroy');

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
    Route::get('/absen-manual/siswa', [ManualAttendanceController::class, 'students'])->name('manual-attendance.students');
    Route::post('/absen-manual/siswa', [ManualAttendanceController::class, 'storeSiswa'])->name('manual-attendance.siswa.store');
    Route::post('/absen-manual/pamong', [ManualAttendanceController::class, 'storePamong'])->name('manual-attendance.pamong.store');
    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
    Route::get('/presensi/siswa', [PresensiController::class, 'students'])->name('presensi.students');
    Route::get('/presensi/recap', [PresensiController::class, 'recap'])->name('presensi.recap');
    Route::get('/presensi/rekap-generus', [GenerusRecapController::class, 'index'])->name('presensi.generus-recap');
    Route::get('/presensi/panel/laporan-periode', [PresensiController::class, 'periodPanel'])->middleware('throttle:60,1')->name('presensi.panel.period');
    Route::get('/presensi/panel/rekap-generus', [GenerusRecapController::class, 'panel'])->middleware('throttle:60,1')->name('presensi.panel.generus');
    Route::get('/presensi/create', [PresensiController::class, 'create'])->name('presensi.create');
    Route::get('/presensi/export', [PresensiController::class, 'export'])->name('presensi.export');
    Route::get('/presensi/template-import', [PresensiController::class, 'downloadTemplate'])->name('presensi.import.template');
    Route::post('/presensi/import', [PresensiController::class, 'import'])->name('presensi.import');
    Route::post('/presensi/bulk', [PresensiController::class, 'bulkStore'])->name('presensi.bulk');
    Route::post('/presensi/bulk-verify', [PresensiController::class, 'bulkVerify'])->name('presensi.bulk-verify');
    Route::put('/presensi/status-cepat', [PresensiController::class, 'quickStatus'])->middleware('throttle:60,1')->name('presensi.quick-status');
    Route::get('/presensi/ringkasan-wa', [PresensiController::class, 'shareSummary'])->middleware('throttle:60,1')->name('presensi.share-summary');
    Route::post('/presensi', [PresensiController::class, 'store'])->name('presensi.store');
    Route::put('/presensi/{presensi}', [PresensiController::class, 'update'])->name('presensi.update');
    Route::delete('/presensi/{presensi}', [PresensiController::class, 'destroy'])->name('presensi.destroy');
    Route::post('/presensi/{presensi}/verify', [PresensiController::class, 'verify'])->name('presensi.verify');

    // Attendance Schedule (Jadwal Presensi)
    Route::resource('attendance-schedule', AttendanceScheduleController::class);
    Route::patch('/attendance-schedule/{attendanceSchedule}/activate', [AttendanceScheduleController::class, 'activate'])->name('attendance-schedule.activate');
    Route::patch('/attendance-schedule/{attendanceSchedule}/deactivate', [AttendanceScheduleController::class, 'deactivate'])->name('attendance-schedule.deactivate');

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
    Route::get('/kelas-list', [KelasController::class, 'getList'])->name('kelas.list');

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
    Route::resource('berita', BeritaController::class)->except(['show'])->parameters([
        'berita' => 'berita',
    ]);
    Route::get('/berita/{berita}/download', [BeritaController::class, 'downloadPdf'])->name('berita.download');

    // Materi Management
    Route::get('/presentasi', [PresentationController::class, 'index'])->name('presentations.index');
    Route::post('/presentasi', [PresentationController::class, 'store'])->name('presentations.store');
    Route::get('/presentasi/{presentation:slug}/edit', [PresentationController::class, 'edit'])->name('presentations.edit');
    Route::put('/presentasi/{presentation:slug}', [PresentationController::class, 'update'])->name('presentations.update');
    Route::post('/presentasi/{presentation:slug}/gambar', [PresentationController::class, 'uploadAsset'])->name('presentations.assets.store');
    Route::get('/presentasi/{presentation:slug}/tayang', [PresentationController::class, 'preview'])->name('presentations.preview');
    Route::get('/presentasi/{presentation:slug}/unduh/pdf', [PresentationController::class, 'exportPdf'])->name('presentations.export.pdf');
    Route::get('/presentasi/{presentation:slug}/unduh/pptx', [PresentationController::class, 'exportPptx'])->name('presentations.export.pptx');
    Route::patch('/presentasi/{presentation:slug}/publikasi', [PresentationController::class, 'togglePublish'])->name('presentations.publish');
    Route::delete('/presentasi/{presentation:slug}', [PresentationController::class, 'destroy'])->name('presentations.destroy');
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
    Route::get('/tugas-pkg', [TugasPkgController::class, 'index'])->name('tugas-pkg.index');
    Route::get('/tugas-pkg/master', [KarakterController::class, 'index'])->name('tugas-pkg.master');
    Route::post('/tugas-pkg/master', [KarakterController::class, 'store'])->name('tugas-pkg.store');
    Route::match(['put', 'patch'], '/tugas-pkg/master/{karakter}', [KarakterController::class, 'update'])->name('tugas-pkg.update');
    Route::patch('/tugas-pkg/master/{karakter}/toggle-status', [KarakterController::class, 'toggleStatus'])->name('tugas-pkg.toggle-status');
    Route::post('/tugas-pkg/master/bulk-action', [KarakterController::class, 'bulkAction'])->name('tugas-pkg.bulk-action');
    Route::get('/tugas-pkg/verifikasi', [TracerKarakterController::class, 'index'])->name('tugas-pkg.verification');
    Route::get('/tugas-pkg/verifikasi/rekap', [TracerKarakterController::class, 'rekap'])->name('tugas-pkg.rekap');
    Route::get('/tugas-pkg/verifikasi/detail-siswa', [TracerKarakterController::class, 'detailSiswa'])->name('tugas-pkg.detail-siswa');
    Route::get('/tugas-pkg/verifikasi/export', [TracerKarakterController::class, 'export'])->name('tugas-pkg.export');
    Route::get('/tugas-pkg/verifikasi/template-import', [TracerKarakterController::class, 'downloadTemplate'])->name('tugas-pkg.import.template');
    Route::post('/tugas-pkg/verifikasi/import', [TracerKarakterController::class, 'import'])->name('tugas-pkg.import');
    Route::get('/tugas-pkg/verifikasi/{siswa}/check', [TracerKarakterController::class, 'checkKarakter'])->name('tugas-pkg.check');
    Route::post('/tugas-pkg/verifikasi/{siswa}/check', [TracerKarakterController::class, 'storeCheck'])->name('tugas-pkg.store-check');
    Route::get('/tugas-pkg/verifikasi/{siswa}/history', [TracerKarakterController::class, 'history'])->name('tugas-pkg.history');
    Route::post('/tugas-pkg/verifikasi/bulk-action', [TracerKarakterController::class, 'bulkAction'])->name('tugas-pkg.verification.bulk-action');
    Route::put('/tugas-pkg/verifikasi/{checklist}/verify', [SiswaKarakterController::class, 'verify'])->name('tugas-pkg.verification.verify');
    Route::put('/tugas-pkg/verifikasi/{checklist}/unverify', [SiswaKarakterController::class, 'unverify'])->name('tugas-pkg.verification.unverify');
    Route::delete('/tugas-pkg/verifikasi/{checklist}', [SiswaKarakterController::class, 'destroy'])->name('tugas-pkg.verification.destroy');
    Route::post('/tugas-pkg/verifikasi/{id}/restore', [SiswaKarakterController::class, 'restore'])->name('tugas-pkg.verification.restore');

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
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::put('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.update.general');
        Route::put('/settings/id-card', [SettingsController::class, 'updateIdCard'])->name('settings.update.id-card');
        Route::put('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.update.theme');
        Route::put('/settings/kelas', [SettingsController::class, 'updateTingkat'])->name('settings.update.kelas');
        Route::put('/settings/footer', [SettingsController::class, 'updateFooter'])->name('settings.update.footer');
        Route::put('/settings/permissions', [SettingsController::class, 'updateDefaultPermissions'])->name('settings.update.permissions');
        Route::put('/settings/popup', [SettingsController::class, 'updatePopups'])->name('settings.update.popup');
        Route::put('/settings/face-attendance', [SettingsController::class, 'updateFaceAttendance'])->name('settings.update.face-attendance');
        Route::put('/settings/registration-access', [SettingsController::class, 'updateRegistrationAccess'])->name('settings.update.registration-access');

        // Daftar Generus + tautan daftar-ulang + status surat pernyataan
        Route::get('/daftar-ulang-generus', [GenerusRegistrationController::class, 'adminIndex'])->name('admin.generus-registration.index');
        Route::put('/daftar-ulang-generus/template-wa', [GenerusRegistrationController::class, 'saveWaTemplate'])->name('admin.generus-registration.wa-template');
        Route::get('/daftar-ulang-generus/{siswa}/surat', [GenerusRegistrationController::class, 'adminPreview'])->name('admin.generus-registration.preview');
        Route::get('/daftar-ulang-generus/{siswa}/surat/unduh', [GenerusRegistrationController::class, 'adminDownload'])->name('admin.generus-registration.download');

        // Backup & Restore
        Route::get('/settings/backup', [BackupController::class, 'index'])->name('settings.backup');
        Route::post('/settings/backup/database', [BackupController::class, 'backupDatabase'])->name('settings.backup.database');
        Route::post('/settings/backup/files', [BackupController::class, 'backupFiles'])->name('settings.backup.files');
        Route::post('/settings/backup/all', [BackupController::class, 'backupAll'])->name('settings.backup.all');
        Route::get('/settings/backup/download/{filename}', [BackupController::class, 'download'])->name('settings.backup.download');
        Route::delete('/settings/backup/{filename}', [BackupController::class, 'delete'])->name('settings.backup.delete');
    });

    // User Management
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::get('/users-template', [UserController::class, 'downloadTemplate'])->name('users.template');
    Route::post('/users-import', [UserController::class, 'import'])->name('users.import');

    // Karakter Management
    Route::resource('karakter', KarakterController::class)->except(['create', 'show', 'edit']);
    Route::patch('/karakter/{karakter}/toggle-status', [KarakterController::class, 'toggleStatus'])->name('karakter.toggle-status');
    Route::post('/karakter/bulk-action', [KarakterController::class, 'bulkAction'])->name('karakter.bulk-action');

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
    Route::put('/pamong/assignments/board', [PamongController::class, 'updateAssignmentBoard'])
        ->middleware('admin.only')
        ->name('pamong.assignments.board');
    Route::get('/pamong/{pamong}', [PamongController::class, 'show'])->name('pamong.show');
    Route::get('/pamong/{pamong}/assign', [PamongController::class, 'assignForm'])->middleware('admin.only')->name('pamong.assign.form');
    Route::post('/pamong/{pamong}/assign', [PamongController::class, 'assignStudents'])->middleware('admin.only')->name('pamong.assign');
    Route::get('/pamong/{pamong}/permissions', [PamongController::class, 'permissionForm'])->name('pamong.permissions');
    Route::post('/pamong/{pamong}/permissions', [PamongController::class, 'updatePermissions'])->name('pamong.permissions.update');
    Route::post('/pamong/{pamong}/copy-permissions', [PamongController::class, 'copyPermissions'])->name('pamong.permissions.copy');
    Route::get('/pamong/{pamong}/activity-log', [PamongController::class, 'activityLog'])->name('pamong.activity-log');
    Route::delete('/pamong/{pamong}/siswa/{siswa}', [PamongController::class, 'removeAssignment'])->middleware('admin.only')->name('pamong.remove-assignment');
    Route::get('/pamong/students-by-kelas', [PamongController::class, 'getStudentsByKelas'])->name('pamong.students-by-kelas');
    Route::post('/pamong/{pamong}/reset-password', [PamongController::class, 'resetPassword'])->name('pamong.reset-password');
    Route::post('/pamong/{pamong}/reset-password-custom', [PamongController::class, 'resetPasswordCustom'])->name('pamong.reset-password-custom');

    // Pamong Chat
    Route::get('/pamong-chat', [PamongChatController::class, 'index'])->name('pamong.chat.index');
    Route::get('/pamong-chat/messages', [PamongChatController::class, 'getMessages'])->name('pamong.chat.messages');
    Route::post('/pamong-chat/send', [PamongChatController::class, 'sendMessage'])->name('pamong.chat.send');
    Route::get('/pamong-chat/unread', [PamongChatController::class, 'getUnreadCount'])->name('pamong.chat.unread');
    Route::get('/pamong-chat/unread-counts', [PamongChatController::class, 'getUnreadCountPerContact'])->name('pamong.chat.unread.counts');
    Route::get('/pamong-chat/broadcast', [PamongChatController::class, 'broadcastForm'])->name('pamong.chat.broadcast');
    Route::post('/pamong-chat/broadcast', [PamongChatController::class, 'sendBroadcast'])->name('pamong.chat.broadcast.send');
    Route::post('/pamong-chat/broadcast/send', [PamongChatController::class, 'sendBroadcast'])->name('pamong.chat.broadcast.send-legacy');
    Route::post('/pamong-chat/groups', [PamongChatController::class, 'storeGroup'])
        ->middleware('pamong.permission:group_chat,create')
        ->name('pamong.chat.groups.store');
    Route::get('/pamong-chat/groups/{chatGroup}', [PamongChatController::class, 'editGroup'])
        ->middleware('pamong.permission:group_chat,view')
        ->name('pamong.chat.groups.edit');
    Route::put('/pamong-chat/groups/{chatGroup}', [PamongChatController::class, 'updateGroup'])
        ->middleware('pamong.permission:group_chat,view')
        ->name('pamong.chat.groups.update');

    // Admin Broadcast
    Route::get('/admin-broadcast', [AdminBroadcastController::class, 'index'])->name('admin.broadcast.index');
    Route::post('/admin-broadcast/siswa', [AdminBroadcastController::class, 'sendToSiswa'])->name('admin.broadcast.siswa');
    Route::post('/admin-broadcast/users', [AdminBroadcastController::class, 'sendToUsers'])->name('admin.broadcast.users');
    Route::post('/admin-broadcast/pamong-groups', [AdminBroadcastController::class, 'sendToPamongGroups'])->name('admin.broadcast.pamong-groups');

    // Chat Groups (Admin)
    Route::resource('chat-groups', ChatGroupController::class);
    Route::post('/chat-groups/{chatGroup}/add-all-pamong', [ChatGroupController::class, 'addAllPamong'])->name('chat-groups.add-all-pamong');
    Route::post('/chat-groups/{chatGroup}/add-all-siswa', [ChatGroupController::class, 'addAllSiswa'])->name('chat-groups.add-all-siswa');
    Route::post('/chat-groups/{chatGroup}/add-all-users', [ChatGroupController::class, 'addAllUsers'])->name('chat-groups.add-all-users');
    Route::get('/chat-groups/{chatGroup}/messages', [ChatGroupController::class, 'getMessages'])->name('chat-groups.messages');
    Route::post('/chat-groups/{chatGroup}/send', [ChatGroupController::class, 'sendMessage'])->name('chat-groups.send');
    Route::delete('/chat-groups/{chatGroup}/members/{member}', [ChatGroupController::class, 'removeMember'])->name('chat-groups.remove-member');
    Route::post('/admin-broadcast/pamong', [AdminBroadcastController::class, 'sendToPamong'])->name('admin.broadcast.pamong');

    // Group Chat routes for Pamong/Admin
    Route::get('/group-chat', [UserGroupChatController::class, 'index'])->name('group-chat.index');
    Route::get('/group-chat/unread', [UserGroupChatController::class, 'getUnreadCount'])->name('group-chat.unread');
    Route::get('/group-chat/{chatGroup}/messages', [UserGroupChatController::class, 'getMessages'])->name('group-chat.messages');
    Route::post('/group-chat/{chatGroup}/send', [UserGroupChatController::class, 'sendMessage'])->name('group-chat.send');
    Route::get('/group-chat/{chatGroup}/info', [UserGroupChatController::class, 'getGroupInfo'])->name('group-chat.info');

    // Karakter Harian (browse page)
    Route::get('/karakter-harian', [TracerKarakterController::class, 'karakterHarian'])->name('karakter-harian.index');

    // Tracer Karakter

    Route::prefix('tracer-bacaan-quran')->name('quran.')->middleware('pamong.permission:tracer_bacaan_quran,view')->group(function () {
        Route::get('/', [QuranReadingController::class, 'operationalIndex'])->name('index');
        Route::get('/ringkasan-wa', [QuranReadingController::class, 'shareSummary'])->middleware('throttle:60,1')->name('share-summary');
        Route::post('/', [QuranReadingController::class, 'operationalStore'])->middleware('pamong.permission:tracer_bacaan_quran,create')->name('store');
        Route::put('/catatan/{entry}', [QuranReadingController::class, 'operationalUpdate'])->middleware('pamong.permission:tracer_bacaan_quran,edit')->name('update');
        Route::patch('/catatan/{entry}/verifikasi', [QuranReadingController::class, 'verify'])->middleware('pamong.permission:tracer_bacaan_quran,verify')->name('verify');
        Route::patch('/catatan/{entry}/tolak', [QuranReadingController::class, 'reject'])->middleware('pamong.permission:tracer_bacaan_quran,verify')->name('reject');
        Route::patch('/progres/{submission}/verifikasi', [QuranReadingController::class, 'verifyProgress'])->middleware('pamong.permission:tracer_bacaan_quran,verify')->name('progress.verify');
        Route::patch('/progres/{submission}/tolak', [QuranReadingController::class, 'rejectProgress'])->middleware('pamong.permission:tracer_bacaan_quran,verify')->name('progress.reject');
        Route::put('/{siswa}/progres-khatam', [QuranReadingController::class, 'correctKhatamProgress'])->middleware('pamong.permission:tracer_bacaan_quran,edit')->name('progress.correct');
        Route::post('/lembar-massal', [QuranReadingController::class, 'bulkSheets'])->middleware(['pamong.permission:tracer_bacaan_quran,export', 'throttle:10,1'])->name('bulk-sheets');
        Route::get('/dokumen-kosong/bulanan', [QuranReadingController::class, 'blankMonthly'])->middleware(['pamong.permission:tracer_bacaan_quran,export', 'throttle:10,1'])->name('blank.monthly');
        Route::get('/dokumen-kosong/referensi-114-surat', [QuranReadingController::class, 'blankSurahReference'])->middleware(['pamong.permission:tracer_bacaan_quran,export', 'throttle:10,1'])->name('blank.reference');
        Route::get('/dokumen-kosong/paket-bolak-balik', [QuranReadingController::class, 'blankDuplex'])->middleware(['pamong.permission:tracer_bacaan_quran,export', 'throttle:10,1'])->name('blank.duplex');
        Route::get('/{siswa}/laporan', [QuranReadingController::class, 'operationalReport'])->middleware('pamong.permission:tracer_bacaan_quran,export')->name('report');
        Route::get('/{siswa}/lembar-lanjutan', [QuranReadingController::class, 'operationalSheet'])->middleware('pamong.permission:tracer_bacaan_quran,export')->name('sheet');
        Route::get('/{siswa}/peta-khatam', [QuranReadingController::class, 'operationalKhatamMap'])->middleware('pamong.permission:tracer_bacaan_quran,export')->name('khatam-map');
        Route::get('/{siswa}/paket-bolak-balik', [QuranReadingController::class, 'operationalDuplex'])->middleware('pamong.permission:tracer_bacaan_quran,export')->name('duplex');
        Route::post('/scan', [QuranReadingController::class, 'scanUpload'])->middleware('pamong.permission:tracer_bacaan_quran,create')->name('scan.upload');
        Route::post('/barcode/identify', [QuranReadingController::class, 'operationalBarcodeIdentify'])->middleware(['pamong.permission:tracer_bacaan_quran,create', 'throttle:quran-barcode'])->name('barcode.identify');
        Route::post('/barcode/store', [QuranReadingController::class, 'operationalBarcodeStore'])->middleware(['pamong.permission:tracer_bacaan_quran,create', 'throttle:quran-barcode'])->name('barcode.store');
        Route::get('/scan/{scan}/konfirmasi', [QuranReadingController::class, 'operationalScanConfirmForm'])->middleware('pamong.permission:tracer_bacaan_quran,create')->name('scan.confirm');
        Route::post('/scan/{scan}/konfirmasi', [QuranReadingController::class, 'operationalScanConfirm'])->middleware('pamong.permission:tracer_bacaan_quran,create')->name('scan.confirm.store');
        Route::get('/scan/{scan}/gambar', [QuranReadingController::class, 'operationalScanImage'])->name('scan.image');
        Route::get('/{siswa}/scan', [QuranReadingController::class, 'scanForm'])->middleware('pamong.permission:tracer_bacaan_quran,create')->name('scan');
        Route::post('/{siswa}/scan', [QuranReadingController::class, 'scanUpload'])->middleware('pamong.permission:tracer_bacaan_quran,create')->name('scan.legacy.upload');
        Route::get('/{siswa}/scan/{scan}/konfirmasi', [QuranReadingController::class, 'scanConfirmForm'])->middleware('pamong.permission:tracer_bacaan_quran,create')->name('scan.legacy.confirm');
        Route::post('/{siswa}/scan/{scan}/konfirmasi', [QuranReadingController::class, 'scanConfirm'])->middleware('pamong.permission:tracer_bacaan_quran,create')->name('scan.legacy.confirm.store');
        Route::get('/{siswa}/scan/{scan}/gambar', [QuranReadingController::class, 'scanImage'])->name('scan.legacy.image');
    });

    Route::get('/tracer-karakter', [TracerKarakterController::class, 'index'])->name('tracer-karakter.index');
    Route::get('/tracer-karakter/rekap', [TracerKarakterController::class, 'rekap'])->name('tracer-karakter.rekap');
    Route::get('/tracer-karakter/detail-siswa', [TracerKarakterController::class, 'detailSiswa'])->name('tracer-karakter.detail-siswa');
    Route::get('/tracer-karakter/export', [TracerKarakterController::class, 'export'])->name('tracer-karakter.export');
    Route::get('/tracer-karakter/template-import', [TracerKarakterController::class, 'downloadTemplate'])->name('tracer-karakter.import.template');
    Route::post('/tracer-karakter/import', [TracerKarakterController::class, 'import'])->name('tracer-karakter.import');
    Route::get('/tracer-karakter/{siswa}/check', [TracerKarakterController::class, 'checkKarakter'])->name('tracer-karakter.check');
    Route::post('/tracer-karakter/{siswa}/check', [TracerKarakterController::class, 'storeCheck'])->name('tracer-karakter.store-check');
    Route::get('/tracer-karakter/{siswa}/history', [TracerKarakterController::class, 'history'])->name('tracer-karakter.history');
    Route::post('/tracer-karakter/bulk-action', [TracerKarakterController::class, 'bulkAction'])->name('tracer-karakter.bulk-action');

    // Karakter Verification (Tugas PKG) - integrated in tracer-karakter
    Route::put('/karakter/verification/{checklist}/verify', [SiswaKarakterController::class, 'verify'])->name('karakter.verification.verify');
    Route::put('/karakter/verification/{checklist}/unverify', [SiswaKarakterController::class, 'unverify'])->name('karakter.verification.unverify');
    Route::delete('/karakter/verification/{checklist}', [SiswaKarakterController::class, 'destroy'])->name('karakter.verification.destroy');
    Route::post('/karakter/verification/{id}/restore', [SiswaKarakterController::class, 'restore'])->name('karakter.verification.restore');

    // Cek Kehadiran PKG
    Route::get('/cek-kehadiran', [CekKehadiranController::class, 'index'])->name('cek-kehadiran.index');
    Route::delete('/cek-kehadiran/{transaction}', [CekKehadiranController::class, 'destroy'])->name('cek-kehadiran.destroy');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'adminIndex'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarController::class, 'adminEvents'])->name('calendar.events');
    Route::get('/calendar/date-stats', [CalendarController::class, 'getDateStats'])->name('calendar.date-stats');
    Route::get('/calendar/share-text', [CalendarController::class, 'adminShareText'])->name('calendar.share-text');
    Route::get('/calendar/export', [CalendarController::class, 'adminExport'])->name('calendar.export');

    // Schedule Reminder (Jadwal Pengingat untuk Kalender)
    Route::resource('schedule-reminder', ScheduleReminderController::class);
    Route::patch('/schedule-reminder/{scheduleReminder}/toggle', [ScheduleReminderController::class, 'toggle'])->name('schedule-reminder.toggle');
    Route::get('/schedule-reminder-events', [ScheduleReminderController::class, 'getEvents'])->name('schedule-reminder.events');

    // Laporan Penyaksian (Admin/Pamong Management)
    Route::get('/laporan-penyaksian', [LaporanPenyaksianController::class, 'index'])->name('laporan-penyaksian.index');
    Route::delete('/laporan-penyaksian/bulk', [LaporanPenyaksianController::class, 'bulkDestroy'])->name('laporan-penyaksian.bulk-destroy');
    Route::get('/laporan-penyaksian/{laporanPenyaksian}', [LaporanPenyaksianController::class, 'show'])->name('laporan-penyaksian.show');
    Route::put('/laporan-penyaksian/{laporanPenyaksian}', [LaporanPenyaksianController::class, 'update'])->name('laporan-penyaksian.update');
    Route::delete('/laporan-penyaksian/{laporanPenyaksian}', [LaporanPenyaksianController::class, 'destroy'])->name('laporan-penyaksian.destroy');

    // Pamong Presensi (Presensi Pamong/Guru)
    Route::get('/pamong-presensi', [PamongPresensiController::class, 'index'])->name('pamong-presensi.index');
    Route::get('/pamong-presensi/summary', [PamongPresensiController::class, 'summary'])->name('pamong-presensi.summary');
    Route::post('/pamong-presensi', [PamongPresensiController::class, 'store'])->name('pamong-presensi.store');
    Route::put('/pamong-presensi/{pamongPresensi}', [PamongPresensiController::class, 'update'])->name('pamong-presensi.update');
    Route::delete('/pamong-presensi/{pamongPresensi}', [PamongPresensiController::class, 'destroy'])->name('pamong-presensi.destroy');
    Route::post('/pamong-presensi/{pamongPresensi}/verify', [PamongPresensiController::class, 'verify'])->name('pamong-presensi.verify');
    Route::get('/pamong-presensi/data', [PamongPresensiController::class, 'getData'])->name('pamong-presensi.data');
    Route::get('/pamong-presensi/stats', [PamongPresensiController::class, 'getStats'])->name('pamong-presensi.stats');
    Route::get('/pamong-presensi/export', [PamongPresensiController::class, 'export'])->name('pamong-presensi.export');
    Route::get('/pamong-presensi/template-import', [PamongPresensiController::class, 'downloadTemplate'])->name('pamong-presensi.import.template');
    Route::post('/pamong-presensi/import', [PamongPresensiController::class, 'import'])->name('pamong-presensi.import');
    Route::get('/pamong-presensi/card/{user}', [PamongPresensiController::class, 'card'])->name('pamong-presensi.card');
    Route::get('/pamong-presensi/card/{user}/print', [PamongPresensiController::class, 'cardPrint'])->name('pamong-presensi.card.print');
    Route::post('/pamong-presensi/refresh-qr/{user}', [PamongPresensiController::class, 'refreshQr'])->name('pamong-presensi.refresh-qr');

    Route::get('/media/sync-proxy', [RemoteMediaController::class, 'show'])->name('admin.media.sync-proxy');

    // Admin Data Pull (Tarik data dari server online)
    Route::middleware('admin.only')->group(function () {
        Route::get('/data-pull', [DataPullController::class, 'index'])->name('admin.data-pull.index');
        Route::post('/data-pull/save-settings', [DataPullController::class, 'saveSettings'])->name('admin.data-pull.save-settings');
        Route::post('/data-pull/save-export-key', [DataPullController::class, 'saveExportKey'])->name('admin.data-pull.save-export-key');
        Route::post('/data-pull/test', [DataPullController::class, 'testConnection'])->name('admin.data-pull.test');
        Route::post('/data-pull/pull', [DataPullController::class, 'pull'])->name('admin.data-pull.pull');
        Route::post('/data-pull/sync-media', [DataPullController::class, 'syncMediaOnly'])->name('admin.data-pull.sync-media');
        Route::get('/data-pull/unavailable-media-report', [DataPullController::class, 'downloadUnavailableMediaReport'])->name('admin.data-pull.unavailable-media-report');
    });

    // Admin Gamification Management (tanpa prefix admin/ karena sudah di dalam middleware auth)
    Route::prefix('gamification')->name('admin.gamification.')->group(function () {
        Route::get('/badges', [GamificationController::class, 'adminBadges'])->name('badges');
        Route::post('/badges', [GamificationController::class, 'adminCreateBadge'])->name('badges.store');
        Route::put('/badges/{badge}', [GamificationController::class, 'adminUpdateBadge'])->name('badges.update');
        Route::delete('/badges/{badge}', [GamificationController::class, 'adminDeleteBadge'])->name('badges.destroy');
        Route::get('/levels', [GamificationController::class, 'adminLevels'])->name('levels');
        Route::post('/levels', [GamificationController::class, 'adminCreateLevel'])->name('levels.store');
        Route::put('/levels/{level}', [GamificationController::class, 'adminUpdateLevel'])->name('levels.update');
        Route::delete('/levels/{level}', [GamificationController::class, 'adminDeleteLevel'])->name('levels.destroy');
        Route::post('/point-config', [GamificationController::class, 'adminSavePointConfig'])->name('point-config');
        Route::post('/periods', [GamificationController::class, 'adminStorePeriod'])->name('periods.store');
        Route::post('/periods/{period}/activate', [GamificationController::class, 'adminActivatePeriod'])->name('periods.activate');
        Route::post('/periods/{period}/close', [GamificationController::class, 'adminClosePeriod'])->name('periods.close');
        Route::post('/periods/sync-active', [GamificationController::class, 'adminSyncActivePeriodTransactions'])->name('periods.sync-active');
        Route::post('/periods/{period}/sync', [GamificationController::class, 'adminSyncPeriodTransactions'])->name('periods.sync');
        Route::post('/periods/{period}/restore-archived-tasks', [GamificationController::class, 'adminRestoreArchivedPeriodTasks'])->name('periods.restore-archived-tasks');
        Route::get('/analytics', [GamificationController::class, 'adminAnalytics'])->name('analytics');
        Route::get('/export-analytics', [GamificationController::class, 'exportAnalytics'])->name('export-analytics');
        Route::post('/adjust-points', [GamificationController::class, 'adminAdjustPoints'])->name('adjust-points');
        Route::get('/transactions', [GamificationController::class, 'adminTransactions'])->name('transactions');
        Route::put('/transactions/{transaction}', [GamificationController::class, 'adminUpdateTransaction'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [GamificationController::class, 'adminDeleteTransaction'])->name('transactions.destroy');
        Route::post('/reset-character-points', [GamificationController::class, 'adminResetCharacterPoints'])->name('reset-character-points');
        Route::post('/reset-badges', [GamificationController::class, 'adminResetBadges'])->name('reset-badges');
        Route::post('/full-reset', [GamificationController::class, 'adminFullReset'])->name('full-reset');
    });

    // Admin RPG Quest Management
    Route::get('/admin/rpg', [RpgGameController::class, 'adminIndex'])
        ->middleware('pamong.permission:game,view')
        ->name('admin.rpg.index');

    // Bank 29 Karakter Luhur (sumber data game)
    Route::prefix('bank-karakter')->name('admin.karakter-luhur.')->group(function () {
        Route::get('/', [KarakterLuhurController::class, 'index'])->name('index');
        Route::get('/tambah', [KarakterLuhurController::class, 'create'])->name('create');
        Route::post('/', [KarakterLuhurController::class, 'store'])->name('store');
        Route::get('/{karakterLuhur}/edit', [KarakterLuhurController::class, 'edit'])->name('edit');
        Route::put('/{karakterLuhur}', [KarakterLuhurController::class, 'update'])->name('update');
        Route::delete('/{karakterLuhur}', [KarakterLuhurController::class, 'destroy'])->name('destroy');
        Route::post('/{karakterLuhur}/toggle', [KarakterLuhurController::class, 'toggle'])->name('toggle');
    });

    // Boss Online — admin kelola
    Route::prefix('admin/boss')->name('admin.boss.')->group(function () {
        Route::get('/', [BossBattleController::class, 'adminIndex'])->name('index');
        Route::post('/', [BossBattleController::class, 'adminStore'])->name('store');
        Route::post('/{boss}/end', [BossBattleController::class, 'adminEnd'])->name('end');
    });

    Route::prefix('rpg-admin')->name('admin.rpg.')->group(function () {
        Route::post('/maps', [RpgGameController::class, 'adminStoreMap'])->name('maps.store');
        Route::post('/maps/{rpgMap}/duplicate', [RpgGameController::class, 'adminDuplicateMap'])->name('maps.duplicate');
        Route::put('/maps/{rpgMap}', [RpgGameController::class, 'adminUpdateMap'])->name('maps.update');
        Route::delete('/maps/{rpgMap}', [RpgGameController::class, 'adminDeleteMap'])->name('maps.destroy');
        Route::get('/maps/{rpgMap}/detail', [RpgGameController::class, 'adminGetMap'])->name('maps.detail');
        Route::post('/npcs', [RpgGameController::class, 'adminStoreNpc'])->name('npcs.store');
        Route::put('/npcs/{rpgNpc}', [RpgGameController::class, 'adminUpdateNpc'])->name('npcs.update');
        Route::delete('/npcs/{rpgNpc}', [RpgGameController::class, 'adminDeleteNpc'])->name('npcs.destroy');
    });

    // Admin Certificate / Reward Template Management
    Route::prefix('certificate')->name('admin.certificate.')->group(function () {
        Route::get('/settings/{level}', [CertificateController::class, 'settings'])->name('settings');
        Route::post('/upload/{level}/{rewardType}', [CertificateController::class, 'uploadTemplate'])->name('upload-template');
        Route::put('/settings/{level}/{rewardType}', [CertificateController::class, 'updateTemplateSettings'])->name('update-template');
        Route::get('/preview/{level}/{rewardType}', [CertificateController::class, 'preview'])->name('preview');
    });

    // Export Data
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/', [ExportController::class, 'index'])->name('index');
        Route::get('/presensi', [ExportController::class, 'presensi'])->name('presensi');
        Route::get('/rekap-presensi', [ExportController::class, 'rekapPresensi'])->name('rekap-presensi');
        Route::get('/leaderboard', [ExportController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/period-collection', [ExportController::class, 'periodCollection'])->name('period-collection');
        Route::get('/siswa', [ExportController::class, 'siswa'])->name('siswa');
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
const CACHE_NAME = 'pkg-presensi-v17';
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
    Route::get('/catatan-rapat', [CatatanRapatController::class, 'index'])->name('catatan-rapat.index');
    Route::post('/catatan-rapat', [CatatanRapatController::class, 'store'])->name('catatan-rapat.store');
    Route::put('/catatan-rapat/{catatanRapat}', [CatatanRapatController::class, 'update'])->name('catatan-rapat.update');
    Route::delete('/catatan-rapat/{catatanRapat}', [CatatanRapatController::class, 'destroy'])->name('catatan-rapat.destroy');
    Route::post('/catatan-rapat/move', [CatatanRapatController::class, 'move'])->name('catatan-rapat.move');
    Route::post('/catatan-rapat/settings', [CatatanRapatController::class, 'updateSettings'])->name('catatan-rapat.settings');

    // Persiapan Acara
    Route::get('/persiapan-acara', [PersiapanAcaraController::class, 'index'])->name('persiapan-acara.index');
    Route::post('/persiapan-acara', [PersiapanAcaraController::class, 'store'])->name('persiapan-acara.store');
    Route::put('/persiapan-acara/{persiapanAcara}', [PersiapanAcaraController::class, 'update'])->name('persiapan-acara.update');
    Route::delete('/persiapan-acara/{persiapanAcara}', [PersiapanAcaraController::class, 'destroy'])->name('persiapan-acara.destroy');
});

Route::fallback(function () {
    abort(404);
});
