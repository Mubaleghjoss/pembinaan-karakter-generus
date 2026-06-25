<?php

namespace App\Repositories;

use App\Models\Presensi;
use App\Repositories\Contracts\PresensiRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Implementasi Eloquent untuk PresensiRepositoryInterface
 *
 * Repository ini menangani semua operasi database untuk entitas Presensi
 * menggunakan Eloquent ORM. Menyediakan abstraksi untuk operasi CRUD,
 * query dengan filter, dan statistik kehadiran.
 */
class EloquentPresensiRepository implements PresensiRepositoryInterface
{
    /**
     * Mencari presensi berdasarkan ID
     *
     * @param  int  $id  ID presensi yang dicari
     * @return Presensi|null Instance Presensi jika ditemukan, null jika tidak
     */
    public function findById(int $id): ?Presensi
    {
        return Presensi::find($id);
    }

    /**
     * Mencari presensi berdasarkan siswa dan tanggal
     *
     * Digunakan untuk mengecek apakah siswa sudah melakukan presensi
     * pada tanggal tertentu (mencegah duplikasi).
     *
     * @param  int  $siswaId  ID siswa
     * @param  string  $date  Tanggal dalam format Y-m-d
     * @return Presensi|null Instance Presensi jika ditemukan, null jika tidak
     */
    public function findByStudentAndDate(int $siswaId, string $date): ?Presensi
    {
        return Presensi::where('siswa_id', $siswaId)
            ->whereDate('tanggal', $date)
            ->first();
    }

    /**
     * Membuat record presensi baru
     *
     * @param  array  $data  Data presensi yang akan disimpan
     *                       - siswa_id: int (required)
     *                       - tanggal: string Y-m-d (required)
     *                       - waktu_masuk: string H:i:s (optional)
     *                       - status: string (required)
     *                       - keterangan: string (optional)
     * @return Presensi Instance Presensi yang baru dibuat
     */
    public function create(array $data): Presensi
    {
        return Presensi::create($data);
    }

    /**
     * Mengupdate record presensi
     *
     * @param  int  $id  ID presensi yang akan diupdate
     * @param  array  $data  Data yang akan diupdate
     * @return Presensi Instance Presensi yang sudah diupdate
     *
     * @throws ModelNotFoundException Jika presensi tidak ditemukan
     */
    public function update(int $id, array $data): Presensi
    {
        $presensi = $this->findById($id);

        if (! $presensi) {
            throw new ModelNotFoundException(
                "Presensi dengan ID {$id} tidak ditemukan"
            );
        }

        $presensi->update($data);

        return $presensi->fresh();
    }

    /**
     * Menghapus record presensi
     *
     * @param  int  $id  ID presensi yang akan dihapus
     * @return bool True jika berhasil dihapus, false jika tidak ditemukan
     */
    public function delete(int $id): bool
    {
        $presensi = $this->findById($id);

        if (! $presensi) {
            return false;
        }

        return $presensi->delete();
    }

    /**
     * Mendapatkan presensi berdasarkan rentang tanggal
     *
     * @param  string  $startDate  Tanggal awal dalam format Y-m-d
     * @param  string  $endDate  Tanggal akhir dalam format Y-m-d
     * @param  int|null  $kelasId  Filter berdasarkan kelas (opsional)
     * @return Collection Koleksi Presensi dengan relasi siswa
     */
    public function getByDateRange(string $startDate, string $endDate, ?int $kelasId = null): Collection
    {
        $query = Presensi::whereBetween('tanggal', [$startDate, $endDate]);

        if ($kelasId !== null) {
            $query->whereHas('siswa', function ($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }

        return $query->with('siswa')->orderBy('tanggal', 'desc')->get();
    }

    /**
     * Mendapatkan statistik presensi dalam rentang tanggal
     *
     * Menghitung jumlah presensi berdasarkan status dan persentase kehadiran.
     *
     * @param  string  $startDate  Tanggal awal dalam format Y-m-d
     * @param  string  $endDate  Tanggal akhir dalam format Y-m-d
     * @param  int|null  $kelasId  Filter berdasarkan kelas (opsional)
     * @return array Array statistik dengan keys:
     *               - total: int
     *               - hadir: int
     *               - terlambat: int
     *               - izin: int
     *               - sakit: int
     *               - alpha: int
     *               - persentase_kehadiran: float
     */
    public function getStatistics(string $startDate, string $endDate, ?int $kelasId = null): array
    {
        $query = Presensi::whereDate('tanggal', '>=', $startDate)
                         ->whereDate('tanggal', '<=', $endDate);

        if ($kelasId !== null) {
            $query->whereHas('siswa', function ($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }

        $total = $query->count();

        // Clone query untuk setiap status
        $hadir = (clone $query)->where('status', 'hadir')->count();
        $terlambat = (clone $query)->where('status', 'terlambat')->count();
        $izin = (clone $query)->where('status', 'izin')->count();
        $sakit = (clone $query)->where('status', 'sakit')->count();
        $tidakHadir = (clone $query)->whereIn('status', ['tidak_hadir', 'alpha'])->count();
        $verified = (clone $query)->where('is_verified', true)->count();

        return [
            'total' => $total,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'izin' => $izin,
            'sakit' => $sakit,
            'tidak_hadir' => $tidakHadir,
            'alpha' => $tidakHadir, // Alias for backward compatibility
            'verified' => $verified,
            'persentase_kehadiran' => $total > 0
                ? round((($hadir + $terlambat) / $total) * 100, 2)
                : 0,
        ];
    }

    /**
     * Mendapatkan presensi dengan pagination dan filter
     *
     * @param  array  $filters  Filter untuk query:
     *                          - siswa_id: int (filter by siswa)
     *                          - tanggal: string (filter by exact date)
     *                          - start_date: string (filter by date range start)
     *                          - end_date: string (filter by date range end)
     *                          - status: string (filter by status)
     *                          - kelas_id: int (filter by kelas via siswa)
     *                          - is_verified: bool (filter by verification status)
     *                          - sort_by: string (column to sort, default: tanggal)
     *                          - sort_order: string (asc/desc, default: desc)
     * @param  int  $perPage  Jumlah item per halaman
     * @return LengthAwarePaginator Paginator dengan relasi siswa
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Presensi::query();

        // Filter berdasarkan siswa
        if (! empty($filters['siswa_id'])) {
            $query->where('siswa_id', $filters['siswa_id']);
        }

        // Filter berdasarkan tanggal
        if (! empty($filters['tanggal'])) {
            $query->where('tanggal', $filters['tanggal']);
        }

        // Filter berdasarkan rentang tanggal
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('tanggal', [$filters['start_date'], $filters['end_date']]);
        }

        // Filter berdasarkan status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter berdasarkan kelas (melalui relasi siswa)
        if (! empty($filters['kelas_id'])) {
            $query->whereHas('siswa', function ($q) use ($filters) {
                $q->where('kelas_id', $filters['kelas_id']);
            });
        }

        // Filter berdasarkan verifikasi
        if (isset($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'tanggal';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->with('siswa')->paginate($perPage);
    }

    /**
     * Mendapatkan presensi hari ini
     *
     * Menggunakan scope today() dari model Presensi.
     *
     * @param  int|null  $kelasId  Filter berdasarkan kelas (opsional)
     * @return Collection Koleksi Presensi hari ini dengan relasi siswa
     */
    public function getToday(?int $kelasId = null): Collection
    {
        $query = Presensi::today();

        if ($kelasId !== null) {
            $query->whereHas('siswa', function ($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }

        return $query->with('siswa')->get();
    }

    /**
     * Mendapatkan presensi yang belum diverifikasi
     *
     * Menggunakan scope unverified() dari model Presensi.
     * Diurutkan berdasarkan tanggal terbaru.
     *
     * @return Collection Koleksi Presensi yang belum diverifikasi dengan relasi siswa
     */
    public function getUnverified(): Collection
    {
        return Presensi::unverified()
            ->with('siswa')
            ->orderBy('tanggal', 'desc')
            ->get();
    }
}
