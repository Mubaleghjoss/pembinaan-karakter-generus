<?php

namespace App\Services\Contracts;

use App\DTOs\CreateSiswaDTO;
use App\DTOs\UpdateSiswaDTO;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface untuk service layer Siswa
 *
 * Mendefinisikan kontrak untuk semua operasi bisnis terkait data siswa.
 */
interface SiswaServiceInterface
{
    /**
     * Membuat siswa baru
     *
     * @param  CreateSiswaDTO  $dto  Data siswa yang akan dibuat
     * @return Siswa Siswa yang berhasil dibuat
     */
    public function create(CreateSiswaDTO $dto): Siswa;

    /**
     * Mengupdate data siswa
     *
     * @param  int  $id  ID siswa yang akan diupdate
     * @param  UpdateSiswaDTO  $dto  Data siswa yang akan diupdate
     * @return Siswa Siswa yang sudah diupdate
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Jika siswa tidak ditemukan
     */
    public function update(int $id, UpdateSiswaDTO $dto): Siswa;

    /**
     * Menghapus siswa
     *
     * @param  int  $id  ID siswa yang akan dihapus
     * @return bool True jika berhasil dihapus
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Jika siswa tidak ditemukan
     */
    public function delete(int $id): bool;

    /**
     * Mendapatkan siswa berdasarkan ID
     *
     * @param  int  $id  ID siswa
     * @return Siswa|null Siswa atau null jika tidak ditemukan
     */
    public function findById(int $id): ?Siswa;

    /**
     * Mendapatkan siswa berdasarkan NIS
     *
     * @param  string  $nis  Nomor Induk Siswa
     * @return Siswa|null Siswa atau null jika tidak ditemukan
     */
    public function findByNis(string $nis): ?Siswa;

    /**
     * Generate QR code untuk siswa
     *
     * @param  int  $siswaId  ID siswa
     * @return array Data QR code (token, qr_image, expires_at)
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Jika siswa tidak ditemukan
     */
    public function generateQrCode(int $siswaId): array;

    /**
     * Mendapatkan statistik siswa
     *
     * @return array Statistik siswa (total, aktif, per_kelas, per_jenis_kelamin)
     */
    public function getStatistics(): array;

    /**
     * Mendapatkan statistik kelengkapan biodata siswa aktif
     *
     * @return array Statistik kelengkapan biodata (total_lengkap, total_belum_lengkap)
     */
    public function getBiodataStatistics(): array;

    /**
     * Mendapatkan daftar siswa aktif
     *
     * @return Collection Koleksi siswa aktif
     */
    public function getActive(): Collection;

    /**
     * Mendapatkan siswa berdasarkan kelas
     *
     * @param  int  $kelasId  ID kelas
     * @return Collection Koleksi siswa dalam kelas
     */
    public function getByKelas(int $kelasId): Collection;

    /**
     * Mendapatkan daftar siswa dengan pagination dan filter
     *
     * @param  array  $filters  Filter (nama, nis, kelas_id, is_active, dll)
     * @param  int  $perPage  Jumlah item per halaman
     * @return LengthAwarePaginator Hasil pagination
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
