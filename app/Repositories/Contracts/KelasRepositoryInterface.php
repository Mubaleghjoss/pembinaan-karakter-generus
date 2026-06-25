<?php

namespace App\Repositories\Contracts;

use App\Models\Kelas;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface untuk repository Kelas
 *
 * Menyediakan abstraksi untuk operasi data access layer pada entitas Kelas.
 * Implementasi interface ini memungkinkan dependency injection dan testability.
 */
interface KelasRepositoryInterface
{
    /**
     * Mencari kelas berdasarkan ID
     *
     * @param  int  $id  ID kelas
     */
    public function findById(int $id): ?Kelas;

    /**
     * Mencari kelas berdasarkan kode kelas
     *
     * @param  string  $kodeKelas  Kode kelas
     */
    public function findByKode(string $kodeKelas): ?Kelas;

    /**
     * Membuat record kelas baru
     *
     * @param  array  $data  Data kelas
     */
    public function create(array $data): Kelas;

    /**
     * Mengupdate record kelas
     *
     * @param  int  $id  ID kelas
     * @param  array  $data  Data yang akan diupdate
     */
    public function update(int $id, array $data): Kelas;

    /**
     * Menghapus record kelas
     *
     * @param  int  $id  ID kelas
     */
    public function delete(int $id): bool;

    /**
     * Mendapatkan semua kelas aktif
     */
    public function getActive(): Collection;

    /**
     * Mendapatkan kelas berdasarkan tingkat
     *
     * @param  string  $tingkat  Tingkat kelas
     */
    public function getByTingkat(string $tingkat): Collection;

    /**
     * Mendapatkan kelas dengan pagination
     *
     * @param  array  $filters  Filter untuk query (nama, tingkat, pamong_id)
     * @param  int  $perPage  Jumlah item per halaman
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Mendapatkan semua kelas untuk dropdown/select
     */
    public function getAllForSelect(): Collection;

    /**
     * Mendapatkan kelas dengan relasi yang di-load
     *
     * @param  int  $id  ID kelas
     * @param  array  $relations  Relasi yang akan di-load
     */
    public function findWithRelations(int $id, array $relations = []): ?Kelas;

    /**
     * Mengecek apakah kelas sudah penuh
     *
     * @param  int  $id  ID kelas
     */
    public function isFull(int $id): bool;
}
