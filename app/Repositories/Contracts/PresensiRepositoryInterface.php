<?php

namespace App\Repositories\Contracts;

use App\Models\Presensi;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface untuk repository Presensi
 *
 * Menyediakan abstraksi untuk operasi data access layer pada entitas Presensi.
 * Implementasi interface ini memungkinkan dependency injection dan testability.
 */
interface PresensiRepositoryInterface
{
    /**
     * Mencari presensi berdasarkan ID
     *
     * @param  int  $id  ID presensi
     */
    public function findById(int $id): ?Presensi;

    /**
     * Mencari presensi berdasarkan siswa dan tanggal
     *
     * @param  int  $siswaId  ID siswa
     * @param  string  $date  Tanggal dalam format Y-m-d
     */
    public function findByStudentAndDate(int $siswaId, string $date): ?Presensi;

    /**
     * Membuat record presensi baru
     *
     * @param  array  $data  Data presensi
     */
    public function create(array $data): Presensi;

    /**
     * Mengupdate record presensi
     *
     * @param  int  $id  ID presensi
     * @param  array  $data  Data yang akan diupdate
     */
    public function update(int $id, array $data): Presensi;

    /**
     * Menghapus record presensi
     *
     * @param  int  $id  ID presensi
     */
    public function delete(int $id): bool;

    /**
     * Mendapatkan presensi berdasarkan rentang tanggal
     *
     * @param  string  $startDate  Tanggal awal (Y-m-d)
     * @param  string  $endDate  Tanggal akhir (Y-m-d)
     * @param  int|null  $kelasId  Filter berdasarkan kelas (opsional)
     */
    public function getByDateRange(string $startDate, string $endDate, ?int $kelasId = null): Collection;

    /**
     * Mendapatkan statistik presensi
     *
     * @param  string  $startDate  Tanggal awal (Y-m-d)
     * @param  string  $endDate  Tanggal akhir (Y-m-d)
     * @param  int|null  $kelasId  Filter berdasarkan kelas (opsional)
     */
    public function getStatistics(string $startDate, string $endDate, ?int $kelasId = null): array;

    /**
     * Mendapatkan presensi dengan pagination
     *
     * @param  array  $filters  Filter untuk query
     * @param  int  $perPage  Jumlah item per halaman
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Mendapatkan presensi hari ini
     *
     * @param  int|null  $kelasId  Filter berdasarkan kelas (opsional)
     */
    public function getToday(?int $kelasId = null): Collection;

    /**
     * Mendapatkan presensi yang belum diverifikasi
     */
    public function getUnverified(): Collection;
}
