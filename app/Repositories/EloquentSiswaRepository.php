<?php

namespace App\Repositories;

use App\Models\Siswa;
use App\Repositories\Contracts\SiswaRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Implementasi Eloquent untuk SiswaRepositoryInterface
 *
 * Repository ini menangani semua operasi database untuk entitas Siswa
 * menggunakan Eloquent ORM. Menyediakan abstraksi untuk operasi CRUD,
 * query dengan filter, dan pencarian siswa.
 */
class EloquentSiswaRepository implements SiswaRepositoryInterface
{
    /**
     * Mencari siswa berdasarkan ID
     *
     * @param  int  $id  ID siswa yang dicari
     * @return Siswa|null Instance Siswa jika ditemukan, null jika tidak
     */
    public function findById(int $id): ?Siswa
    {
        return Siswa::find($id);
    }

    /**
     * Mencari siswa berdasarkan NIS (Nomor Induk Siswa)
     *
     * @param  string  $nis  Nomor Induk Siswa
     * @return Siswa|null Instance Siswa jika ditemukan, null jika tidak
     */
    public function findByNis(string $nis): ?Siswa
    {
        return Siswa::where('nis', $nis)->first();
    }

    /**
     * Membuat record siswa baru
     *
     * @param  array  $data  Data siswa yang akan disimpan
     *                       - nis: string (required, unique)
     *                       - nama: string (required)
     *                       - kelas_id: int (required)
     *                       - jenis_kelamin: string (required)
     *                       - alamat: string (optional)
     *                       - foto: string (optional)
     *                       - is_active: bool (default: true)
     * @return Siswa Instance Siswa yang baru dibuat
     */
    public function create(array $data): Siswa
    {
        return Siswa::create($data);
    }

    /**
     * Mengupdate record siswa
     *
     * @param  int  $id  ID siswa yang akan diupdate
     * @param  array  $data  Data yang akan diupdate
     * @return Siswa Instance Siswa yang sudah diupdate
     *
     * @throws ModelNotFoundException Jika siswa tidak ditemukan
     */
    public function update(int $id, array $data): Siswa
    {
        $siswa = $this->findById($id);

        if (! $siswa) {
            throw new ModelNotFoundException(
                "Siswa dengan ID {$id} tidak ditemukan"
            );
        }

        $siswa->update($data);

        return $siswa->fresh();
    }

    /**
     * Menghapus record siswa
     *
     * @param  int  $id  ID siswa yang akan dihapus
     * @return bool True jika berhasil dihapus, false jika tidak ditemukan
     */
    public function delete(int $id): bool
    {
        $siswa = $this->findById($id);

        if (! $siswa) {
            return false;
        }

        return $siswa->delete();
    }

    /**
     * Mendapatkan semua siswa aktif
     *
     * Menggunakan scope active() dari model Siswa.
     *
     * @return Collection Koleksi Siswa yang aktif
     */
    public function getActive(): Collection
    {
        return Siswa::active()->get();
    }

    /**
     * Mendapatkan siswa berdasarkan kelas
     *
     * @param  int  $kelasId  ID kelas
     * @return Collection Koleksi Siswa dalam kelas tersebut
     */
    public function getByKelas(int $kelasId): Collection
    {
        return Siswa::where('kelas_id', $kelasId)->get();
    }

    /**
     * Mendapatkan siswa dengan pagination dan filter
     *
     * @param  array  $filters  Filter untuk query:
     *                          - search: string (filter by nama OR nis, partial match)
     *                          - nama: string (filter by nama, partial match)
     *                          - nis: string (filter by NIS, partial match)
     *                          - kelas_id: int (filter by kelas)
     *                          - status: string (filter by status)
     *                          - is_active: bool (filter by active status)
     *                          - sort_by: string (column to sort, default: login-based)
     *                          - sort_order: string (asc/desc, default: asc)
     * @param  int  $perPage  Jumlah item per halaman
     * @return LengthAwarePaginator Paginator dengan data siswa
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Siswa::query()
            ->with(['kelas', 'alumniReviewer:id,name,status'])
            ->withCount(['validBiometricCredentials', 'legacyBiometricCredentials']);

        // Combined search filter (nama OR nis)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                  ->orWhere('nis', 'like', '%' . $search . '%');
            });
        }

        // Filter berdasarkan nama
        if (! empty($filters['nama'])) {
            $query->where('nama', 'like', '%'.$filters['nama'].'%');
        }

        // Filter berdasarkan NIS
        if (! empty($filters['nis'])) {
            $query->where('nis', 'like', '%'.$filters['nis'].'%');
        }

        // Filter berdasarkan kelas
        if (! empty($filters['kelas_id'])) {
            $query->where('kelas_id', $filters['kelas_id']);
        }

        // Filter berdasarkan status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter berdasarkan is_active
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filter berdasarkan status biodata
        if (! empty($filters['biodata_status'])) {
            $kelompokField = Siswa::hasKelompokColumn() ? 'kelompok' : 'alamat';

            if ($filters['biodata_status'] === 'complete') {
                $query->whereNotNull('nama')->where('nama', '!=', '')
                      ->whereNotNull($kelompokField)->where($kelompokField, '!=', '')
                      ->whereNotNull('tanggal_lahir')
                      ->whereNotNull('phone')->where('phone', '!=', '')
                      ->whereNotNull('phone_wali')->where('phone_wali', '!=', '')
                      ->whereNotNull('foto_path')->where('foto_path', '!=', '');
            } elseif ($filters['biodata_status'] === 'incomplete') {
                $query->where(function ($q) {
                    $kelompokField = Siswa::hasKelompokColumn() ? 'kelompok' : 'alamat';
                    $q->whereNull('nama')->orWhere('nama', '')
                      ->orWhereNull($kelompokField)->orWhere($kelompokField, '')
                      ->orWhereNull('tanggal_lahir')
                      ->orWhereNull('phone')->orWhere('phone', '')
                      ->orWhereNull('phone_wali')->orWhere('phone_wali', '')
                      ->orWhereNull('foto_path')->orWhere('foto_path', '');
                });
            }
        }

        // Sorting - default: belum pernah login di atas, lalu terbaru login
        if (! empty($filters['sort_by'])) {
            $sortOrder = $filters['sort_order'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $sortOrder);
        } else {
            $query->orderByRaw('last_login_at IS NOT NULL ASC')
                  ->orderBy('last_login_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Menghitung jumlah siswa aktif berdasarkan kelas
     *
     * @param  int  $kelasId  ID kelas
     * @return int Jumlah siswa aktif dalam kelas
     */
    public function countByKelas(int $kelasId): int
    {
        return Siswa::where('kelas_id', $kelasId)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Mendapatkan siswa dengan relasi yang di-load (eager loading)
     *
     * @param  int  $id  ID siswa
     * @param  array  $relations  Nama relasi yang akan di-load (e.g., ['kelas', 'presensi'])
     * @return Siswa|null Instance Siswa dengan relasi, null jika tidak ditemukan
     */
    public function findWithRelations(int $id, array $relations = []): ?Siswa
    {
        $query = Siswa::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }
}
