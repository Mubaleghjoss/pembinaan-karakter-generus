<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiswaResource;
use App\Models\Siswa;
use App\Support\TargetGrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Kelas Sekolah — sumber data AKTIF jenjang sekolah siswa.
 *
 * Berbeda dari `/kelas` yang deprecated: pengelompokan di sini memakai kolom
 * `siswa.school_grade` dengan nilai kanonik dari App\Support\TargetGrade
 * (`smp_7`..`sma_12`, `pranikah`), bukan tabel `kelas` yang sudah jadi arsip.
 *
 * Batas akses memakai `Siswa::scopeForUser`, jadi pamong biasa hanya melihat
 * siswa binaannya sendiri sementara admin melihat semuanya.
 */
class KelasSekolahController extends Controller
{
    /**
     * GET /kelas-sekolah — daftar kelas sekolah + jumlah siswa per kelas.
     *
     * Semua opsi TargetGrade dikembalikan (termasuk yang kosong) supaya klien
     * bisa membedakan "kelas tidak ada siswanya" dari "kelas tidak dikenal",
     * dengan penanda `jumlah_siswa` = 0. Klien yang hanya ingin kelas terpakai
     * dapat mengirim `?only_used=1`.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $jumlah = Siswa::query()
            ->forUser($user)
            ->active()
            ->selectRaw('school_grade, COUNT(*) as total')
            ->groupBy('school_grade')
            ->pluck('total', 'school_grade');

        // Banyak biodata belum mengisi `school_grade` (di DB dev: semuanya NULL).
        // Kalau hanya mengandalkan kolom itu, seluruh kelas tampil 0 dan layar
        // jadi tidak berguna. Jadi kita juga menghitung level EFEKTIF memakai
        // aturan yang sama dengan `effective_pkg_level` pada SiswaResource:
        // override → school_grade → taksiran dari tanggal lahir.
        $efektif = [];
        Siswa::query()
            ->forUser($user)
            ->active()
            ->select(['id', 'school_grade', 'target_grade_override', 'tanggal_lahir'])
            ->chunkById(500, function ($batch) use (&$efektif) {
                foreach ($batch as $siswa) {
                    $kode = TargetGrade::resolveForSiswa($siswa);
                    if ($kode !== null) {
                        $efektif[$kode] = ($efektif[$kode] ?? 0) + 1;
                    }
                }
            });

        $hanyaTerpakai = $request->boolean('only_used');

        $data = [];
        foreach (TargetGrade::schoolClassOptions() as $kode => $label) {
            $total = (int) ($jumlah[$kode] ?? 0);
            $totalEfektif = (int) ($efektif[$kode] ?? 0);
            // `only_used` menyaring berdasarkan angka efektif supaya kelas yang
            // terisi lewat taksiran tanggal lahir tidak ikut terbuang.
            if ($hanyaTerpakai && $total === 0 && $totalEfektif === 0) {
                continue;
            }
            $data[] = [
                'kode' => $kode,
                'label' => $label,
                'label_singkat' => TargetGrade::label($kode),
                'jumlah_siswa' => $total,
                'jumlah_efektif' => $totalEfektif,
            ];
        }

        // Siswa tanpa school_grade tidak boleh hilang diam-diam: pamong perlu
        // tahu ada biodata yang belum lengkap.
        $belumDiisi = (int) ($jumlah[null] ?? $jumlah[''] ?? 0);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kelas sekolah',
            'data' => $data,
            'meta' => [
                'total_kelas' => count($data),
                'total_siswa' => (int) $jumlah->sum(),
                'belum_diisi' => $belumDiisi,
                'total_efektif' => array_sum($efektif),
            ],
        ]);
    }

    /**
     * GET /kelas-sekolah/{kode}/siswa — siswa pada satu kelas sekolah.
     *
     * `kode` menerima nilai kanonik (`sma_10`) maupun tulisan manusia
     * ("SMA Kelas 1", "kelas 10") lewat TargetGrade::normalizeSchoolClassInput.
     */
    public function siswa(Request $request, string $kode): JsonResponse
    {
        $normal = TargetGrade::normalizeSchoolClassInput($kode);
        if ($normal === null) {
            return response()->json([
                'success' => false,
                'message' => "Kelas sekolah '{$kode}' tidak dikenal.",
                'code' => 'INVALID_SCHOOL_GRADE',
                'meta' => ['pilihan' => array_keys(TargetGrade::schoolClassOptions())],
            ], 422);
        }

        $query = Siswa::query()
            ->forUser($request->user())
            ->bySchoolGrade($normal)
            ->with('pamongAssignments.pamong:id,name,username');

        // `?effective=1` mencocokkan level EFEKTIF (override → school_grade →
        // taksiran tanggal lahir), bukan hanya kolom `school_grade` yang di
        // banyak biodata masih kosong. Level efektif tidak bisa difilter di SQL
        // karena berasal dari logika TargetGrade, jadi id-nya dikumpulkan dulu.
        if ($request->boolean('effective')) {
            $ids = [];
            Siswa::query()
                ->forUser($request->user())
                ->active()
                ->select(['id', 'school_grade', 'target_grade_override', 'tanggal_lahir'])
                ->chunkById(500, function ($batch) use (&$ids, $normal) {
                    foreach ($batch as $siswa) {
                        if (TargetGrade::resolveForSiswa($siswa) === $normal) {
                            $ids[] = $siswa->id;
                        }
                    }
                });

            $query = Siswa::query()
                ->forUser($request->user())
                ->whereIn('id', $ids ?: [0])
                ->with('pamongAssignments.pamong:id,name,username');
        }

        if ($request->filled('search')) {
            $cari = $request->string('search')->toString();
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                    ->orWhere('nis', 'like', "%{$cari}%");
            });
        }

        $siswa = $query->orderBy('nama')->paginate((int) $request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Siswa '.TargetGrade::schoolClassLabel($normal),
            'data' => SiswaResource::collection($siswa->items()),
            'meta' => [
                'kode' => $normal,
                'label' => TargetGrade::schoolClassLabel($normal),
                'current_page' => $siswa->currentPage(),
                'last_page' => $siswa->lastPage(),
                'per_page' => $siswa->perPage(),
                'total' => $siswa->total(),
            ],
        ]);
    }
}
