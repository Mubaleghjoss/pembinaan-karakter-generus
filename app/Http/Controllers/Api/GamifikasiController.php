<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesSiswaToken;
use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\SiswaKarakterChecklist;
use App\Models\SiswaPoint;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * API v1 — gamifikasi untuk aplikasi mobile (akun siswa / orang tua).
 *
 * Padanan `GamificationController` di web, dipecah jadi endpoint kecil:
 *   - GET /gamifikasi/ringkasan   : poin, level, peringkat, streak, badge
 *   - GET /gamifikasi/leaderboard : papan peringkat (all/daily/weekly/monthly)
 *   - GET /gamifikasi/history     : riwayat transaksi poin siswa (paginasi)
 *   - GET /gamifikasi/badges      : koleksi badge + progres
 *
 * Sumber poin yang dicatat backend (enum point_transactions.source):
 * attendance, character, badge, manual, streak, perfect_month, game.
 * "Tugas PKG aktif" masuk sebagai `character`, "hadir/tidak terlambat"
 * sebagai `attendance` — keduanya sudah ditulis oleh alur web maupun API,
 * jadi endpoint ini hanya membaca, tidak menambah aturan poin baru.
 *
 * Token orang tua diterima (memantau anak, read-only); token staf ditolak.
 */
class GamifikasiController extends Controller
{
    use ResolvesSiswaToken;

    public function __construct(private readonly GamificationService $gamification) {}

    /**
     * Ringkasan gamifikasi satu siswa: poin, level, peringkat, streak, badge.
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $stats = $this->gamification->getSiswaStats($siswa);
        $point = $stats['points'];

        return response()->json([
            'success' => true,
            'data' => [
                'siswa' => [
                    'id' => $siswa->id,
                    'nama' => $siswa->nama,
                    'nis' => $siswa->nis,
                    'is_ortu_view' => $this->tokenHasAbility($request, 'ortu'),
                ],
                'poin' => [
                    'total' => (int) $point->total_points,
                    'kehadiran' => (int) $point->attendance_points,
                    'karakter' => (int) $point->character_points,
                    'bonus' => (int) $point->bonus_points,
                    'terpakai' => (int) $point->spent_points,
                ],
                'level' => [
                    'angka' => (int) $point->level,
                    'nama' => $stats['current_level']->nama ?? null,
                    'warna' => $stats['current_level']->warna ?? null,
                    'level_berikutnya' => $stats['next_level']->level ?? null,
                    'nama_berikutnya' => $stats['next_level']->nama ?? null,
                    'progres_persen' => (int) $stats['progress_to_next'],
                    'poin_ke_berikutnya' => (int) $stats['points_to_next'],
                ],
                'peringkat' => (int) $stats['rank'],
                'streak' => [
                    'kehadiran' => (int) $stats['attendance_streak'],
                    'karakter' => (int) $stats['character_streak'],
                ],
                'total_badge' => (int) $stats['total_badges'],
                'periode_aktif' => $stats['active_period'],
                'poin_periode_aktif' => (int) $stats['active_period_points'],
                'rekap_sumber' => $this->rekapSumber($siswa->id),
            ],
        ]);
    }

    /**
     * Papan peringkat. `periode`: all|daily|weekly|monthly (default all).
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $periode = in_array($request->query('periode'), ['daily', 'weekly', 'monthly'], true)
            ? $request->query('periode')
            : 'all';
        $limit = min(max((int) $request->query('limit', 20), 5), 50);

        $rows = $this->gamification->getLeaderboard($limit, $periode === 'all' ? null : $periode);

        $entries = [];
        foreach (array_values($rows) as $i => $row) {
            $poinPeriode = $periode === 'all'
                ? null
                : (int) ($row['period_points'] ?? 0);

            $entries[] = [
                'peringkat' => $i + 1,
                'siswa_id' => (int) ($row['siswa']['id'] ?? $row['siswa_id']),
                'nama' => $row['siswa']['nama'] ?? '—',
                'kelas' => $row['siswa']['kelas']['nama'] ?? null,
                'total_poin' => (int) ($row['total_points'] ?? 0),
                'poin_periode' => $poinPeriode,
                'level' => (int) ($row['level'] ?? 1),
                'nama_level' => $row['current_level']['nama'] ?? null,
                'is_saya' => (int) ($row['siswa_id'] ?? 0) === $siswa->id,
            ];
        }

        $poinSaya = (int) ($siswa->siswaPoint?->total_points ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'periode' => $periode,
                'entries' => $entries,
                'saya' => [
                    'siswa_id' => $siswa->id,
                    'nama' => $siswa->nama,
                    'total_poin' => $poinSaya,
                    'peringkat' => SiswaPoint::where('total_points', '>', $poinSaya)->count() + 1,
                    'masuk_daftar' => collect($entries)->contains('is_saya', true),
                ],
            ],
        ]);
    }

    /**
     * Riwayat poin siswa. Filter `sumber` (attendance|character|game|...),
     * paginasi `per_page` (maks 50).
     */
    public function history(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $sumber = $request->query('sumber');
        $sumberValid = ['attendance', 'character', 'badge', 'manual', 'streak', 'perfect_month', 'game'];
        $perPage = min(max((int) $request->query('per_page', 20), 5), 50);

        $paginator = $siswa->pointTransactions()
            ->when(in_array($sumber, $sumberValid, true), fn ($q) => $q->where('source', $sumber))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $items = collect($paginator->items())->map(fn ($t) => [
            'id' => (int) $t->id,
            'tipe' => $t->type,
            'sumber' => $t->source,
            'sumber_label' => $this->labelSumber($t->source),
            'poin' => (int) $t->points,
            'keterangan' => $t->description,
            'tanggal' => $t->created_at?->toIso8601String(),
        ])->all();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'filter_sumber' => in_array($sumber, $sumberValid, true) ? $sumber : null,
                'rekap_sumber' => $this->rekapSumber($siswa->id),
            ],
        ]);
    }

    /**
     * Koleksi badge + progres (memakai logika web agar konsisten).
     */
    public function badges(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $badges = collect($this->gamification->getBadgesWithProgress($siswa))
            ->map(fn ($b) => [
                'id' => (int) ($b['badge']->id ?? 0),
                'nama' => $b['badge']->nama ?? '—',
                'deskripsi' => $b['badge']->deskripsi ?? null,
                'icon' => $b['badge']->icon ?? null,
                'kategori' => $b['badge']->kategori ?? null,
                'warna' => $b['badge']->warna ?? null,
                'poin_reward' => (int) ($b['badge']->poin_reward ?? 0),
                'sudah_didapat' => (bool) ($b['earned'] ?? false),
                'progres_persen' => (int) ($b['progress'] ?? 0),
                'didapat_pada' => isset($b['earned_at']) && $b['earned_at']
                    ? Carbon::parse($b['earned_at'])->toIso8601String()
                    : null,
            ])->values()->all();

        return response()->json([
            'success' => true,
            'data' => $badges,
            'meta' => [
                'total' => count($badges),
                'sudah_didapat' => collect($badges)->where('sudah_didapat', true)->count(),
            ],
        ]);
    }

    /**
     * Rekap jumlah aktivitas berpoin: tugas PKG terverifikasi & kehadiran.
     * Angka diambil dari sumber datanya (bukan dari transaksi poin) supaya
     * tetap benar walau ada transaksi yang gagal tersimpan.
     */
    private function rekapSumber(int $siswaId): array
    {
        $tugasVerified = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswaId)
            ->verified()
            ->count();

        $presensi = Presensi::query()
            ->where('siswa_id', $siswaId)
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return [
            'tugas_terverifikasi' => $tugasVerified,
            'hadir' => (int) ($presensi['hadir'] ?? 0),
            'terlambat' => (int) ($presensi['terlambat'] ?? 0),
            'izin' => (int) ($presensi['izin'] ?? 0),
            'sakit' => (int) ($presensi['sakit'] ?? 0),
            'alpha' => (int) ($presensi['alpha'] ?? 0),
        ];
    }

    private function labelSumber(?string $source): string
    {
        return match ($source) {
            'attendance' => 'Kehadiran',
            'character' => 'Tugas PKG',
            'badge' => 'Badge',
            'streak' => 'Streak',
            'perfect_month' => 'Bulan sempurna',
            'game' => 'Game',
            'manual' => 'Bonus/penyesuaian',
            default => (string) $source,
        };
    }
}
