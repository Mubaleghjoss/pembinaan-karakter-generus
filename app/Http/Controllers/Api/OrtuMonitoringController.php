<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesSiswaToken;
use App\Http\Controllers\Controller;
use App\Models\Karakter;
use App\Models\Presensi;
use App\Models\QuranReadingEntry;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * API v1 — monitoring untuk akun orang tua (dan siswa itu sendiri).
 *
 * Padanan `OrtuDashboardController::index` di web, dipecah jadi endpoint kecil
 * supaya aplikasi bisa memuat per kartu:
 *   - GET /ortu/ringkasan   : kartu ringkas (tugas, presensi, quran)
 *   - GET /ortu/presensi    : rekap presensi bulanan + daftar terakhir
 *   - GET /ortu/tugas       : rekap tugas PKG anak (terverifikasi/menunggu)
 *   - GET /ortu/quran       : ringkasan bacaan Al-Qur'an
 *
 * Token siswa juga diterima (anak melihat datanya sendiri); yang ditolak hanya
 * token staff. Batas tanggal mengikuti web: sejak awal program PKG.
 */
class OrtuMonitoringController extends Controller
{
    use ResolvesSiswaToken;

    /**
     * Tanggal mulai program PKG, disamakan dengan OrtuDashboardController.
     */
    private const PKG_START = '2025-01-01';

    public function ringkasan(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $siswa->loadMissing('kelas');

        return response()->json([
            'success' => true,
            'data' => [
                'siswa' => [
                    'id' => $siswa->id,
                    'nama' => $siswa->nama,
                    'nis' => $siswa->nis,
                    'kelas' => $siswa->kelas?->nama,
                    'is_ortu_view' => $this->tokenHasAbility($request, 'ortu'),
                ],
                'tugas' => $this->rekapTugas($siswa),
                'presensi' => $this->totalPresensi($siswa->id),
                'quran' => $this->ringkasanQuran($siswa->id),
            ],
        ]);
    }

    public function presensi(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);

        $page = Presensi::query()
            ->where('siswa_id', $siswa->id)
            ->whereDate('tanggal', '>=', self::PKG_START)
            ->orderByDesc('tanggal')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(fn (Presensi $p) => [
                'id' => $p->id,
                'tanggal' => $p->tanggal?->toDateString(),
                'status' => $p->status,
                'jam_masuk' => $p->jam_masuk,
                'jam_keluar' => $p->jam_keluar,
                'is_verified' => (bool) $p->is_verified,
                'verified_at' => $p->verified_at?->toIso8601String(),
                'keterangan' => $p->keterangan,
            ])->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'totals' => $this->totalPresensi($siswa->id),
                'bulanan' => $this->presensiBulanan($siswa->id),
            ],
        ]);
    }

    public function tugas(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);

        $query = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswa->id)
            ->with(['karakter:id,nama,kategori,poin', 'verifier:id,name'])
            ->orderByDesc('checked_at');

        $status = (string) $request->get('status', 'all');
        if ($status === 'verified') {
            $query->whereNotNull('verified_at');
        } elseif ($status === 'unverified') {
            $query->whereNull('verified_at');
        }

        $page = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(fn (SiswaKarakterChecklist $c) => [
                'id' => $c->id,
                'karakter_nama' => $c->karakter?->nama,
                'kategori' => $c->karakter?->kategori,
                'poin' => $c->karakter?->poin,
                'checked_at' => $c->checked_at?->toIso8601String(),
                'hasil_teks' => $c->hasil_teks,
                'is_verified' => $c->verified_at !== null,
                'verified_at' => $c->verified_at?->toIso8601String(),
                'verified_by' => $c->verifier?->name,
                'notes' => $c->notes,
            ])->values(),
            'meta' => array_merge(
                [
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                    'status' => $status,
                ],
                $this->rekapTugas($siswa)
            ),
        ]);
    }

    public function quran(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        return response()->json([
            'success' => true,
            'data' => $this->ringkasanQuran($siswa->id),
        ]);
    }

    /**
     * @return array{total_tugas_aktif:int,terverifikasi:int,menunggu_verifikasi:int,poin_terverifikasi:int,persentase:float}
     */
    private function rekapTugas(Siswa $siswa): array
    {
        $totalAktif = Karakter::active()->count();

        $terverifikasi = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswa->id)
            ->whereNotNull('verified_at')
            ->distinct('karakter_id')
            ->count('karakter_id');

        $menunggu = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswa->id)
            ->whereNull('verified_at')
            ->count();

        $poin = SiswaKarakterChecklist::query()
            ->join('karakter', 'siswa_karakter_checklist.karakter_id', '=', 'karakter.id')
            ->where('siswa_karakter_checklist.siswa_id', $siswa->id)
            ->whereNotNull('siswa_karakter_checklist.verified_at')
            ->whereNull('siswa_karakter_checklist.deleted_at')
            ->sum('karakter.poin');

        return [
            'total_tugas_aktif' => $totalAktif,
            'terverifikasi' => $terverifikasi,
            'menunggu_verifikasi' => $menunggu,
            'poin_terverifikasi' => (int) $poin,
            'persentase' => $totalAktif > 0
                ? round($terverifikasi / $totalAktif * 100, 1)
                : 0.0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function totalPresensi(int $siswaId): array
    {
        $row = Presensi::query()
            ->where('siswa_id', $siswaId)
            ->whereDate('tanggal', '>=', self::PKG_START)
            ->selectRaw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir")
            ->selectRaw("SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat")
            ->selectRaw("SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin")
            ->selectRaw("SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit")
            ->selectRaw("SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha")
            ->selectRaw('COUNT(*) as total')
            ->first();

        return [
            'hadir' => (int) ($row->hadir ?? 0),
            'terlambat' => (int) ($row->terlambat ?? 0),
            'izin' => (int) ($row->izin ?? 0),
            'sakit' => (int) ($row->sakit ?? 0),
            'alpha' => (int) ($row->alpha ?? 0),
            'total' => (int) ($row->total ?? 0),
        ];
    }

    /**
     * Rekap presensi per bulan (terbaru dulu, maksimal 12 bulan).
     *
     * @return array<int, array<string, mixed>>
     */
    private function presensiBulanan(int $siswaId): array
    {
        return Presensi::query()
            ->where('siswa_id', $siswaId)
            ->whereDate('tanggal', '>=', self::PKG_START)
            // SUBSTR dipilih karena portabel (MySQL/MariaDB dan SQLite);
            // DATE_FORMAT hanya ada di MySQL sehingga memecah environment lain.
            ->selectRaw('SUBSTR(tanggal, 1, 7) as periode')
            ->selectRaw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir")
            ->selectRaw("SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat")
            ->selectRaw("SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin")
            ->selectRaw("SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit")
            ->selectRaw("SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('periode')
            ->orderByDesc('periode')
            ->limit(12)
            ->get()
            ->map(fn ($r) => [
                'periode' => $r->periode,
                'hadir' => (int) $r->hadir,
                'terlambat' => (int) $r->terlambat,
                'izin' => (int) $r->izin,
                'sakit' => (int) $r->sakit,
                'alpha' => (int) $r->alpha,
                'total' => (int) $r->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkasanQuran(int $siswaId): array
    {
        $base = fn () => QuranReadingEntry::query()
            ->where('siswa_id', $siswaId)
            ->where('status', QuranReadingEntry::STATUS_VERIFIED);

        $lastDate = $base()->max('reading_date');

        return [
            'terverifikasi_total' => $base()->count(),
            'terverifikasi_bulan_ini' => $base()
                ->whereBetween('reading_date', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ])
                ->count(),
            'menunggu_verifikasi' => QuranReadingEntry::query()
                ->where('siswa_id', $siswaId)
                ->where('status', QuranReadingEntry::STATUS_PENDING)
                ->count(),
            'ditolak' => QuranReadingEntry::query()
                ->where('siswa_id', $siswaId)
                ->where('status', QuranReadingEntry::STATUS_REJECTED)
                ->count(),
            'terakhir_membaca' => $lastDate
                ? Carbon::parse($lastDate)->toDateString()
                : null,
        ];
    }
}
