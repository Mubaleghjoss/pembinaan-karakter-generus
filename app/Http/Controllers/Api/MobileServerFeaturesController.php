<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesSiswaToken;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatGroup;
use App\Models\FaceProfile;
use App\Models\GenerusRegistration;
use App\Models\LaporanPenyaksian;
use App\Models\MateriRppJournal;
use App\Models\MateriTarget;
use App\Models\QuranProgressSubmission;
use App\Models\QuranReadingCycle;
use App\Models\QuranReadingSheet;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Models\User;
use App\Models\WebAuthnCredential;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileServerFeaturesController extends Controller
{
    use ResolvesSiswaToken;

    /** Urutan fitur pada daftar; dipakai juga untuk validasi `?fitur=`. */
    public const KODE = [
        'chat',
        'push_notification',
        'webauthn',
        'profil',
        'materi_target_jurnal',
        'presensi_wajah',
        'sertifikat_reward',
        'quran_lanjutan',
        'laporan_penyaksian',
        'pendaftaran_generus',
    ];

    /** Contoh item yang ikut pada mode daftar (semua fitur sekaligus). */
    private const LIMIT_RINGKASAN = 3;

    /** Item yang ikut pada mode detail (`?fitur=<kode>`). */
    private const LIMIT_DETAIL = 25;

    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $kode = trim((string) $request->query('fitur', ''));

        if ($kode !== '') {
            return $this->detail($request, $actor, $kode);
        }

        return response()->json([
            'success' => true,
            'data' => array_map(
                fn (string $k) => $this->build($k, $actor, self::LIMIT_RINGKASAN),
                self::KODE
            ),
            'meta' => [
                'actor' => $actor['type'],
                'scope' => $actor['scope'],
                'total_fitur' => count(self::KODE),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Detail satu fitur: item lebih banyak + daftar aksi yang benar-benar ada
     * rutenya di server (dipakai aplikasi untuk membuka fitur, bukan sekadar
     * menampilkan ringkasan).
     */
    private function detail(Request $request, array $actor, string $kode): JsonResponse
    {
        if (! in_array($kode, self::KODE, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur "'.$kode.'" tidak dikenal.',
                'meta' => ['tersedia' => self::KODE],
            ], 404);
        }

        $limit = max(1, min((int) $request->integer('limit', self::LIMIT_DETAIL), 100));

        return response()->json([
            'success' => true,
            'data' => $this->build($kode, $actor, $limit),
            'meta' => [
                'actor' => $actor['type'],
                'scope' => $actor['scope'],
                'fitur' => $kode,
                'limit' => $limit,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function build(string $kode, array $actor, int $limit): array
    {
        return match ($kode) {
            'chat' => $this->chat($actor, $limit),
            'push_notification' => $this->pushNotification($actor, $limit),
            'webauthn' => $this->webauthn($actor, $limit),
            'profil' => $this->profil($actor, $limit),
            'materi_target_jurnal' => $this->materiTargetJurnal($actor, $limit),
            'presensi_wajah' => $this->presensiWajah($actor, $limit),
            'sertifikat_reward' => $this->sertifikatReward($actor, $limit),
            'quran_lanjutan' => $this->quranLanjutan($actor, $limit),
            'laporan_penyaksian' => $this->laporanPenyaksian($actor, $limit),
            'pendaftaran_generus' => $this->pendaftaranGenerus($actor, $limit),
        };
    }

    private function actor(Request $request): array
    {
        $user = $request->user();
        if ($user instanceof Siswa) {
            return [
                'type' => $this->tokenHasAbility($request, 'ortu') ? 'ortu' : 'siswa',
                'scope' => 'siswa',
                'model' => $user,
            ];
        }

        return [
            'type' => 'staff',
            'scope' => $user instanceof User && $user->isAdmin() ? 'semua' : 'binaan',
            'model' => $user,
        ];
    }

    private function feature(
        string $kode,
        string $judul,
        string $ringkasan,
        string $endpoint,
        int $total,
        array $items,
        ?Carbon $updatedAt,
        array $actor,
        string $status = 'tersedia'
    ): array {
        return [
            'kode' => $kode,
            'judul' => $judul,
            'ringkasan' => $ringkasan,
            'status' => $status,
            'total' => $total,
            'updated_at' => $updatedAt?->toIso8601String(),
            'endpoint' => $endpoint,
            'items' => $items,
            'aksi' => $this->aksi($kode, $actor),
        ];
    }

    /**
     * Aksi yang bisa dijalankan aplikasi untuk fitur ini.
     *
     * - `app`        : rute layar di dalam aplikasi Flutter.
     * - `web`        : halaman server yang butuh sesi login web (dibuka WebView).
     * - `web_publik` : halaman server yang boleh diakses tanpa login.
     * - `api`        : endpoint JSON yang bisa dipanggil langsung aplikasi.
     */
    private function aksi(string $kode, array $actor): array
    {
        return self::aksiFor($kode, $actor['type']);
    }

    /**
     * Daftar target `web` yang sah untuk satu tipe aktor.
     *
     * Dipakai jembatan sesi web (`MobileWebBridgeController`) sebagai
     * allowlist: aplikasi hanya boleh meminta sesi web untuk halaman yang
     * memang diiklankan di sini, bukan sembarang path.
     *
     * @return list<string>
     */
    public static function webTargets(string $tipe): array
    {
        $targets = [];
        foreach (self::KODE as $kode) {
            foreach (self::aksiFor($kode, $tipe) as $aksi) {
                if ($aksi['tipe'] === 'web') {
                    $targets[] = $aksi['target'];
                }
            }
        }

        return array_values(array_unique($targets));
    }

    private static function aksiFor(string $kode, string $tipe): array
    {
        return match ($kode) {
            'chat' => match ($tipe) {
                'siswa' => [
                    self::aksiWeb('Chat pamong', '/siswa/chat'),
                    self::aksiWeb('Grup chat', '/siswa/group-chat'),
                ],
                'ortu' => [self::aksiWeb('Chat pamong', '/ortu/chat')],
                default => [
                    self::aksiWeb('Chat pamong', '/pamong-chat'),
                    self::aksiWeb('Grup chat', '/group-chat'),
                ],
            },
            'push_notification' => array_values(array_filter([
                self::aksiApi('Perangkat terdaftar', '/api/v1/mobile/fitur-server?fitur=push_notification'),
                $tipe === 'siswa' ? self::aksiWeb('Pengaturan akun', '/siswa/profile') : null,
                $tipe === 'staff' ? self::aksiWeb('Pengaturan akun', '/profile') : null,
            ])),
            'webauthn' => [match ($tipe) {
                'siswa' => self::aksiWeb('Kelola biometrik', '/siswa/biometrik'),
                'ortu' => self::aksiWeb('Kelola biometrik', '/ortu/biometrik'),
                default => self::aksiWeb('Kelola biometrik', '/biometrik'),
            }],
            'profil' => array_values(array_filter([
                self::aksiApp('Profil aplikasi', '/profil'),
                $tipe === 'siswa' ? self::aksiWeb('Profil lengkap', '/siswa/profile') : null,
                $tipe === 'staff' ? self::aksiWeb('Profil lengkap', '/profile') : null,
            ])),
            'materi_target_jurnal' => array_values(array_filter([
                self::aksiApp('Materi', '/materi'),
                $tipe === 'siswa' ? self::aksiWeb('Jurnal RPP', '/siswa/jurnal-rpp') : null,
                $tipe === 'staff' ? self::aksiWeb('Jurnal RPP', '/materi-rpp-journals') : null,
                $tipe === 'staff' ? self::aksiWeb('Target materi', '/materi-targets') : null,
            ])),
            'presensi_wajah' => array_values(array_filter([
                $tipe === 'siswa' ? self::aksiWeb('Profil wajah', '/siswa/face-profile') : null,
                $tipe === 'staff' ? self::aksiWeb('Profil wajah', '/face-profile') : null,
                $tipe === 'staff' ? self::aksiApp('Presensi', '/presensi') : null,
            ])),
            'sertifikat_reward' => array_values(array_filter([
                self::aksiApp('Badge', '/badge'),
                self::aksiApp('Poin', '/poin'),
                $tipe === 'siswa' ? self::aksiWeb('Tugas terverifikasi', '/siswa/tugas-pkg/terverifikasi') : null,
                $tipe === 'staff' ? self::aksiWeb('Verifikasi tugas', '/tugas-pkg/verifikasi') : null,
            ])),
            'quran_lanjutan' => array_values(array_filter([
                self::aksiApp('Quran aplikasi', '/quran'),
                $tipe === 'siswa' ? self::aksiWeb('Lembar lanjutan', '/siswa/bacaan-quran/lembar-lanjutan') : null,
                $tipe === 'siswa' ? self::aksiWeb('Peta khatam', '/siswa/bacaan-quran/peta-khatam') : null,
                $tipe === 'ortu' ? self::aksiWeb('Bacaan Quran anak', '/ortu/bacaan-quran') : null,
                $tipe === 'staff' ? self::aksiWeb('Tracer bacaan Quran', '/tracer-bacaan-quran') : null,
            ])),
            'laporan_penyaksian' => array_values(array_filter([
                self::aksiWebPublik('Buat laporan', '/lapor-pkg'),
                $tipe === 'staff' ? self::aksiWeb('Daftar laporan', '/laporan-penyaksian') : null,
            ])),
            'pendaftaran_generus' => array_values(array_filter([
                self::aksiWebPublik('Formulir pendaftaran', '/daftarpkg'),
                $tipe === 'staff' ? self::aksiWeb('Daftar ulang generus', '/daftar-ulang-generus') : null,
            ])),
        };
    }

    private static function aksiApp(string $label, string $path): array
    {
        return [
            'label' => $label,
            'tipe' => 'app',
            'target' => $path,
            'url' => null,
            'butuh_sesi_web' => false,
        ];
    }

    private static function aksiWeb(string $label, string $path): array
    {
        return [
            'label' => $label,
            'tipe' => 'web',
            'target' => $path,
            'url' => url($path),
            'butuh_sesi_web' => true,
        ];
    }

    private static function aksiWebPublik(string $label, string $path): array
    {
        return [
            'label' => $label,
            'tipe' => 'web_publik',
            'target' => $path,
            'url' => url($path),
            'butuh_sesi_web' => false,
        ];
    }

    private static function aksiApi(string $label, string $path): array
    {
        return [
            'label' => $label,
            'tipe' => 'api',
            'target' => $path,
            'url' => url($path),
            'butuh_sesi_web' => false,
        ];
    }

    private function chat(array $actor, int $limit): array
    {
        $model = $actor['model'];
        $personal = Chat::query()
            ->when($model instanceof Siswa, fn (Builder $q) => $q->where(function (Builder $w) use ($model) {
                $w->where('sender_siswa_id', $model->id)->orWhere('receiver_siswa_id', $model->id);
            }))
            ->when($model instanceof User && ! $model->isAdmin(), fn (Builder $q) => $q->where(function (Builder $w) use ($model) {
                $w->where('sender_user_id', $model->id)->orWhere('receiver_user_id', $model->id);
            }));

        $groups = ChatGroup::query()->where('is_active', true)
            ->when($model instanceof Siswa, fn (Builder $q) => $q->whereHas('members', fn (Builder $m) => $m->where('siswa_id', $model->id)))
            ->when($model instanceof User && ! $model->isAdmin(), fn (Builder $q) => $q->whereHas('members', fn (Builder $m) => $m->where('user_id', $model->id)));

        $total = (clone $personal)->count() + (clone $groups)->count();
        $updated = collect([(clone $personal)->max('updated_at'), (clone $groups)->max('updated_at')])->filter()->max();

        return $this->feature(
            'chat',
            'Chat siswa/ortu/pamong',
            'Pesan personal dan grup chat yang tersimpan di database.',
            '/api/v1/mobile/fitur-server?fitur=chat',
            $total,
            array_merge(
                (clone $personal)->latest()->limit($limit)->get()->map(fn (Chat $c) => [
                    'id' => $c->id,
                    'tipe' => 'personal',
                    'judul' => $c->sender_name.' → '.$c->receiver_name,
                    'deskripsi' => str($c->message)->limit(80)->toString(),
                    'tanggal' => $c->created_at?->toIso8601String(),
                ])->all(),
                (clone $groups)->latest()->limit($limit)->get()->map(fn (ChatGroup $g) => [
                    'id' => $g->id,
                    'tipe' => 'group',
                    'judul' => $g->name,
                    'deskripsi' => $g->description,
                    'tanggal' => $g->updated_at?->toIso8601String(),
                ])->all(),
            ),
            $this->carbon($updated),
            $actor
        );
    }

    private function pushNotification(array $actor, int $limit): array
    {
        $model = $actor['model'];
        $query = DB::table('push_subscriptions')
            ->when($model instanceof Siswa, fn ($q) => $q->where('subscribable_type', Siswa::class)->where('subscribable_id', $model->id))
            ->when($model instanceof User && ! $model->isAdmin(), fn ($q) => $q->where('subscribable_type', User::class)->where('subscribable_id', $model->id));
        $total = (clone $query)->count();

        return $this->feature(
            'push_notification',
            'Push notification server',
            'Token perangkat PWA/webpush yang tersimpan untuk pengiriman notifikasi.',
            '/api/v1/mobile/fitur-server?fitur=push_notification',
            $total,
            (clone $query)->orderByDesc('updated_at')->limit($limit)->get()->map(fn ($p) => [
                'id' => $p->id,
                'tipe' => class_basename($p->subscribable_type),
                'judul' => 'Perangkat #'.$p->id,
                'deskripsi' => parse_url($p->endpoint, PHP_URL_HOST) ?: 'Endpoint push tersimpan',
                'tanggal' => $this->carbon($p->updated_at)?->toIso8601String(),
            ])->all(),
            $this->carbon((clone $query)->max('updated_at')),
            $actor
        );
    }

    private function webauthn(array $actor, int $limit): array
    {
        $model = $actor['model'];
        $query = WebAuthnCredential::query()
            ->when($model instanceof Siswa, fn (Builder $q) => $q->where('user_type', $actor['type'])->where('user_id', $model->id))
            ->when($model instanceof User && ! $model->isAdmin(), fn (Builder $q) => $q->where('user_type', 'admin')->where('user_id', $model->id));

        return $this->feature(
            'webauthn',
            'Biometrik / WebAuthn',
            'Credential passkey/biometrik yang terdaftar di server.',
            '/api/v1/mobile/fitur-server?fitur=webauthn',
            (clone $query)->count(),
            (clone $query)->latest()->limit($limit)->get()->map(fn (WebAuthnCredential $c) => [
                'id' => $c->id,
                'tipe' => $c->user_type,
                'judul' => $c->device_name ?: 'Credential #'.$c->id,
                'deskripsi' => $c->last_used_at ? 'Terakhir dipakai '.$c->last_used_at->toDateTimeString() : 'Belum pernah dipakai',
                'tanggal' => $c->updated_at?->toIso8601String(),
            ])->all(),
            $this->carbon((clone $query)->max('updated_at')),
            $actor
        );
    }

    private function profil(array $actor, int $limit): array
    {
        $model = $actor['model'];
        $items = [];
        if ($model instanceof Siswa) {
            $items[] = ['id' => $model->id, 'tipe' => 'siswa', 'judul' => $model->nama, 'deskripsi' => 'NIS '.$model->nis, 'tanggal' => $model->updated_at?->toIso8601String()];
        } elseif ($model instanceof User) {
            $items[] = ['id' => $model->id, 'tipe' => 'staff', 'judul' => $model->name ?: $model->username, 'deskripsi' => $model->role?->display_name, 'tanggal' => $model->updated_at?->toIso8601String()];
        }

        return $this->feature(
            'profil',
            'Profil lengkap + foto',
            'Data profil akun aktif yang dibaca dari tabel users/siswa.',
            '/api/v1/mobile/fitur-server?fitur=profil',
            count($items),
            array_slice($items, 0, $limit),
            $model?->updated_at,
            $actor
        );
    }

    private function materiTargetJurnal(array $actor, int $limit): array
    {
        $targets = MateriTarget::query()->active();
        $journals = MateriRppJournal::query();
        $total = (clone $targets)->count() + (clone $journals)->count();
        $updated = collect([(clone $targets)->max('updated_at'), (clone $journals)->max('updated_at')])->filter()->max();

        return $this->feature(
            'materi_target_jurnal',
            'Jurnal RPP & target materi',
            'Target materi, progres siswa, dan jurnal RPP dari database.',
            '/api/v1/mobile/fitur-server?fitur=materi_target_jurnal',
            $total,
            array_merge(
                (clone $targets)->latest()->limit($limit)->get()->map(fn (MateriTarget $t) => ['id' => $t->id, 'tipe' => 'target', 'judul' => $t->title, 'deskripsi' => $t->category_label, 'tanggal' => $t->updated_at?->toIso8601String()])->all(),
                (clone $journals)->latest()->limit($limit)->get()->map(fn (MateriRppJournal $j) => ['id' => $j->id, 'tipe' => 'jurnal', 'judul' => $j->materi_title, 'deskripsi' => $j->workflow_label, 'tanggal' => $j->updated_at?->toIso8601String()])->all(),
            ),
            $this->carbon($updated),
            $actor
        );
    }

    private function presensiWajah(array $actor, int $limit): array
    {
        $model = $actor['model'];
        $query = FaceProfile::query()->active()
            ->when($model instanceof Siswa, fn (Builder $q) => $q->where('subject_type', FaceProfile::SUBJECT_SISWA)->where('subject_id', $model->id))
            ->when($model instanceof User && ! $model->isAdmin(), fn (Builder $q) => $q->where('subject_type', FaceProfile::SUBJECT_USER)->where('subject_id', $model->id));

        return $this->feature(
            'presensi_wajah',
            'Presensi wajah',
            'Profil wajah aktif yang sudah dienroll di server.',
            '/api/v1/mobile/fitur-server?fitur=presensi_wajah',
            (clone $query)->count(),
            (clone $query)->latest()->limit($limit)->get()->map(fn (FaceProfile $f) => ['id' => $f->id, 'tipe' => $f->subject_type, 'judul' => 'Face profile #'.$f->id, 'deskripsi' => 'Status '.$f->status, 'tanggal' => $f->updated_at?->toIso8601String()])->all(),
            $this->carbon((clone $query)->max('updated_at')),
            $actor
        );
    }

    private function sertifikatReward(array $actor, int $limit): array
    {
        $model = $actor['model'];
        $query = SiswaKarakterChecklist::query()->whereNotNull('verified_at')
            ->when($model instanceof Siswa, fn (Builder $q) => $q->where('siswa_id', $model->id));

        return $this->feature(
            'sertifikat_reward',
            'Sertifikat & reward',
            'Reward dihitung dari tugas karakter yang sudah diverifikasi.',
            '/api/v1/mobile/fitur-server?fitur=sertifikat_reward',
            (clone $query)->count(),
            (clone $query)->with(['siswa', 'karakter'])->latest('verified_at')->limit($limit)->get()->map(fn (SiswaKarakterChecklist $c) => ['id' => $c->id, 'tipe' => 'reward', 'judul' => $c->karakter?->nama ?? 'Tugas terverifikasi', 'deskripsi' => $c->siswa?->nama, 'tanggal' => $c->verified_at?->toIso8601String()])->all(),
            $this->carbon((clone $query)->max('verified_at')),
            $actor
        );
    }

    private function quranLanjutan(array $actor, int $limit): array
    {
        $model = $actor['model'];
        $cycles = QuranReadingCycle::query()->when($model instanceof Siswa, fn (Builder $q) => $q->where('siswa_id', $model->id));
        $sheets = QuranReadingSheet::query()->when($model instanceof Siswa, fn (Builder $q) => $q->where('siswa_id', $model->id));
        $submissions = QuranProgressSubmission::query()->when($model instanceof Siswa, fn (Builder $q) => $q->where('siswa_id', $model->id));
        $total = (clone $cycles)->count() + (clone $sheets)->count() + (clone $submissions)->count();
        $updated = collect([(clone $cycles)->max('updated_at'), (clone $sheets)->max('updated_at'), (clone $submissions)->max('updated_at')])->filter()->max();

        return $this->feature(
            'quran_lanjutan',
            'Quran lanjutan',
            'Siklus khatam, lembar baca, dan submission progres Quran.',
            '/api/v1/mobile/fitur-server?fitur=quran_lanjutan',
            $total,
            (clone $cycles)->with('siswa')->latest()->limit($limit)->get()->map(fn (QuranReadingCycle $c) => ['id' => $c->id, 'tipe' => 'cycle', 'judul' => 'Khatam #'.$c->cycle_number, 'deskripsi' => ($c->siswa?->nama ?? 'Siswa').' • '.$c->status, 'tanggal' => $c->updated_at?->toIso8601String()])->all(),
            $this->carbon($updated),
            $actor
        );
    }

    private function laporanPenyaksian(array $actor, int $limit): array
    {
        $query = LaporanPenyaksian::query();

        return $this->feature(
            'laporan_penyaksian',
            'Laporan penyaksian',
            'Laporan pembinaan/kejadian dan status tindak lanjut.',
            '/api/v1/mobile/fitur-server?fitur=laporan_penyaksian',
            (clone $query)->count(),
            (clone $query)->latest()->limit($limit)->get()->map(fn (LaporanPenyaksian $l) => ['id' => $l->id, 'tipe' => $l->status, 'judul' => $l->nama_generus, 'deskripsi' => $l->status_label, 'tanggal' => $l->updated_at?->toIso8601String()])->all(),
            $this->carbon((clone $query)->max('updated_at')),
            $actor
        );
    }

    private function pendaftaranGenerus(array $actor, int $limit): array
    {
        $model = $actor['model'];
        $query = GenerusRegistration::query()->when($model instanceof Siswa, fn (Builder $q) => $q->where('siswa_id', $model->id));

        return $this->feature(
            'pendaftaran_generus',
            'Pendaftaran generus',
            'Data pendaftaran/daftar ulang generus yang sudah submit.',
            '/api/v1/mobile/fitur-server?fitur=pendaftaran_generus',
            (clone $query)->count(),
            (clone $query)->latest()->limit($limit)->get()->map(fn (GenerusRegistration $g) => ['id' => $g->id, 'tipe' => 'registrasi', 'judul' => $g->student_name, 'deskripsi' => $g->school_grade.' • '.$g->kelompok, 'tanggal' => $g->submitted_at?->toIso8601String()])->all(),
            $this->carbon((clone $query)->max('updated_at')),
            $actor
        );
    }

    private function carbon(mixed $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}
