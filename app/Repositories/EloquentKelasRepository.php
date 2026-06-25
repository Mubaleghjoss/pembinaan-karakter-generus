<?php

namespace App\Repositories;

use App\Models\Kelas;
use App\Repositories\Contracts\KelasRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Implementasi Eloquent untuk KelasRepositoryInterface
 *
 * Repository ini menangani semua operasi database untuk entitas Kelas
 * menggunakan Eloquent ORM. Menyediakan abstraksi untuk operasi CRUD,
 * query dengan filter, dan pengecekan kapasitas kelas.
 */
class EloquentKelasRepository implements KelasRepositoryInterface
{
    /**
     * Mencari kelas berdasarkan ID
     *
     * @param  int  $id  ID kelas yang dicari
     * @return Kelas|null Instance Kelas jika ditemukan, null jika tidak
     */
    public function findById(int $id): ?Kelas
    {
        return Kelas::find($id);
    }

    /**
     * Mencari kelas berdasarkan kode kelas
     *
     * @param  string  $kodeKelas  Kode unik kelas
     * @return Kelas|null Instance Kelas jika ditemukan, null jika tidak
     */
    public function findByKode(string $kodeKelas): ?Kelas
    {
        return Kelas::where('kode_kelas', $kodeKelas)->first();
    }

    /**
     * Membuat record kelas baru
     *
     * @param  array  $data  Data kelas yang akan disimpan
     *                       - nama: string (required)
     *                       - kode_kelas: string (required, unique)
     *                       - tingkat: string (required)
     *                       - pamong_id: int (optional)
     *                       - kapasitas: int (optional)
     *                       - is_active: bool (default: true)
     * @return Kelas Instance Kelas yang baru dibuat
     */
    public function create(array $data): Kelas
    {
        return Kelas::create($data);
    }

    /**
     * Mengupdate record kelas
     *
     * @param  int  $id  ID kelas yang akan diupdate
     * @param  array  $data  Data yang akan diupdate
     * @return Kelas Instance Kelas yang sudah diupdate
     *
     * @throws ModelNotFoundException Jika kelas tidak ditemukan
     */
    public function update(int $id, array $data): Kelas
    {
        $kelas = $this->findById($id);

        if (! $kelas) {
            throw new ModelNotFoundException(
                "Kelas dengan ID {$id} tidak ditemukan"
            );
        }

        $kelas->update($data);

        return $kelas->fresh();
    }

    /**
     * Menghapus record kelas
     *
     * @param  int  $id  ID kelas yang akan dihapus
     * @return bool True jika berhasil dihapus, false jika tidak ditemukan
     */
    public function delete(int $id): bool
    {
        $kelas = $this->findById($id);

        if (! $kelas) {
            return false;
        }

        return $kelas->delete();
    }

    /**
     * Mendapatkan semua kelas aktif
     *
     * Menggunakan scope active() dari model Kelas.
     *
     * @return Collection Koleksi Kelas yang aktif
     */
    public function getActive(): Collection
    {
        return Kelas::active()->get();
    }

    /**
     * Mendapatkan kelas berdasarkan tingkat
     *
     * Menggunakan scope byTingkat() dari model Kelas.
     *
     * @param  string  $tingkat  Tingkat kelas (e.g., 'X', 'XI', 'XII')
     * @return Collection Koleksi Kelas pada tingkat tersebut
     */
    public function getByTingkat(string $tingkat): Collection
    {
        return Kelas::byTingkat($tingkat)->get();
    }

    /**
     * Mendapatkan kelas dengan pagination dan filter
     *
     * @param  array  $filters  Filter untuk query:
     *                          - nama: string (filter by nama, partial match)
     *                          - tingkat: string (filter by tingkat)
     *                          - pamong_id: int (filter by pamong/wali kelas)
     *                          - is_active: bool (filter by active status)
     *                          - sort_by: string (column to sort, default: nama)
     *                          - sort_order: string (asc/desc, default: asc)
     * @param  int  $perPage  Jumlah item per halaman
     * @return LengthAwarePaginator Paginator dengan data kelas
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Kelas::query();

        // Filter berdasarkan nama
        if (! empty($filters['nama'])) {
            $query->where('nama', 'like', '%'.$filters['nama'].'%');
        }

        // Filter berdasarkan tingkat
        if (! empty($filters['tingkat'])) {
            $query->where('tingkat', $filters['tingkat']);
        }

        // Filter berdasarkan pamong
        if (! empty($filters['pamong_id'])) {
            $query->where('pamong_id', $filters['pamong_id']);
        }

        // Filter berdasarkan is_active
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'nama';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Mendapatkan semua kelas aktif untuk dropdown/select
     *
     * Mengembalikan data minimal (id, nama, tingkat) yang diurutkan
     * berdasarkan tingkat dan nama.
     *
     * @return Collection Koleksi Kelas dengan field id, nama, tingkat
     */
    public function getAllForSelect(): Collection
    {
        return Kelas::active()
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get(['id', 'nama', 'tingkat']);
    }

    /**
     * Mendapatkan kelas dengan relasi yang di-load (eager loading)
     *
     * @param  int  $id  ID kelas
     * @param  array  $relations  Nama relasi yang akan di-load (e.g., ['siswa', 'pamong'])
     * @return Kelas|null Instance Kelas dengan relasi, null jika tidak ditemukan
     */
    public function findWithRelations(int $id, array $relations = []): ?Kelas
    {
        $query = Kelas::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Mengecek apakah kelas sudah penuh
     *
     * Membandingkan jumlah siswa aktif dengan kapasitas kelas.
     *
     * @param  int  $id  ID kelas
     * @return bool True jika kelas penuh atau tidak ditemukan, false jika masih ada slot
     */
    public function isFull(int $id): bool
    {
        $kelas = $this->findById($id);

        if (! $kelas) {
            return true; // Kelas tidak ditemukan, anggap penuh
        }

        return $kelas->isFull();
    }
}
