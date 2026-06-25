<?php

namespace App\Repositories\Contracts;

use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface untuk repository Siswa
 *
 * Menyediakan abstraksi untuk operasi data access layer pada entitas Siswa.
 * Implementasi interface ini memungkinkan dependency injection dan testability.
 */
interface SiswaRepositoryInterface
{
    /**
     * Mencari siswa berdasarkan ID
     *
     * @param  int  $id  ID siswa
     */
    public function findById(int $id): ?Siswa;

    /**
     * Mencari siswa berdasarkan NIS
     *
     * @param  string  $nis  Nomor Induk Siswa
     */
    public function findByNis(string $nis): ?Siswa;

    /**
     * Membuat record siswa baru
     *
     * @param  array  $data  Data siswa
     */
    public function create(array $data): Siswa;

    /**
     * Mengupdate record siswa
     *
     * @param  int  $id  ID siswa
     * @param  array  $data  Data yang akan diupdate
     */
    public function update(int $id, array $data): Siswa;

    /**
     * Menghapus record siswa
     *
     * @param  int  $id  ID siswa
     */
    public function delete(int $id): bool;

    /**
     * Mendapatkan semua siswa aktif
     */
    public function getActive(): Collection;

    /**
     * Mendapatkan siswa berdasarkan kelas
     *
     * @param  int  $kelasId  ID kelas
     */
    public function getByKelas(int $kelasId): Collection;

    /**
     * Mendapatkan siswa dengan pagination dan filter
     *
     * @param  array  $filters  Filter untuk query (nama, nis, kelas_id, status)
     * @param  int  $perPage  Jumlah item per halaman
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Menghitung jumlah siswa berdasarkan kelas
     *
     * @param  int  $kelasId  ID kelas
     */
    public function countByKelas(int $kelasId): int;

    /**
     * Mendapatkan siswa dengan relasi yang di-load
     *
     * @param  int  $id  ID siswa
     * @param  array  $relations  Relasi yang akan di-load
     */
    public function findWithRelations(int $id, array $relations = []): ?Siswa;
}
