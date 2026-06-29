<?php

namespace App\Services\Contracts;

use App\Models\PamongPresensi;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Interface untuk PamongPresensiService
 * 
 * Service ini menangani operasi presensi untuk pamong/guru
 */
interface PamongPresensiServiceInterface
{
    /**
     * Mencatat kehadiran pamong via QR scan
     *
     * @param User $pamong User pamong yang akan dicatat kehadirannya
     * @param string $token Token QR yang digunakan
     * @param array $metadata Data tambahan (location, device_info, ip_address)
     * @return array Hasil scan dengan status dan data presensi
     */
    public function recordAttendance(User $pamong, string $token, array $metadata = []): array;

    /**
     * Mencatat kehadiran pamong via scan wajah.
     *
     * @param User $pamong User pamong/admin yang cocok dengan hasil scan wajah
     * @param array $metadata Metadata bukti scan wajah dan lokasi
     * @return array Hasil scan dengan status dan data presensi
     */
    public function recordFaceAttendance(User $pamong, array $metadata): array;

    /**
     * Mendapatkan statistik kehadiran pamong
     *
     * @param string $startDate Tanggal mulai (Y-m-d)
     * @param string $endDate Tanggal akhir (Y-m-d)
     * @param int|null $userId Filter berdasarkan user ID (opsional)
     * @return array Statistik kehadiran
     */
    public function getStatistics(string $startDate, string $endDate, ?int $userId = null): array;

    /**
     * Membuat record alpha otomatis untuk pamong aktif yang belum presensi
     * pada tanggal yang jadwal presensi pamongnya sudah ditutup.
     *
     * @param string $startDate Tanggal mulai (Y-m-d)
     * @param string $endDate Tanggal akhir (Y-m-d)
     * @param int|null $userId Filter berdasarkan user ID (opsional)
     * @return int Jumlah record alpha yang dibuat
     */
    public function backfillClosedAlpha(string $startDate, string $endDate, ?int $userId = null): int;

    /**
     * Mendapatkan presensi pamong hari ini
     *
     * @return Collection Koleksi presensi pamong hari ini
     */
    public function getToday(): Collection;

    /**
     * Mendapatkan data presensi pamong dengan filter
     *
     * @param array $filters Filter (start_date, end_date, user_id, status)
     * @return Collection Koleksi presensi pamong
     */
    public function getData(array $filters = []): Collection;

    /**
     * Verifikasi record presensi pamong
     *
     * @param int $presensiId ID presensi yang akan diverifikasi
     * @param int $verifierId ID user yang memverifikasi
     * @return PamongPresensi Record presensi yang sudah diverifikasi
     */
    public function verifyAttendance(int $presensiId, int $verifierId): PamongPresensi;
}
