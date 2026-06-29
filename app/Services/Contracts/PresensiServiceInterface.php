<?php

namespace App\Services\Contracts;

use App\DTOs\RecordAttendanceDTO;
use App\DTOs\ScanQrDTO;
use App\DTOs\StatisticsFilterDTO;
use App\Models\Presensi;
use App\Models\Siswa;

/**
 * Interface untuk service layer Presensi
 *
 * Mendefinisikan kontrak untuk semua operasi bisnis terkait presensi/kehadiran.
 */
interface PresensiServiceInterface
{
    /**
     * Mencatat kehadiran siswa
     *
     * @param  RecordAttendanceDTO  $dto  Data kehadiran yang akan dicatat
     * @return Presensi Record presensi yang berhasil dibuat
     *
     * @throws \App\Exceptions\DuplicateAttendanceException Jika sudah ada presensi untuk siswa di tanggal yang sama
     */
    public function recordAttendance(RecordAttendanceDTO $dto): Presensi;

    /**
     * Memproses scan QR code untuk presensi
     *
     * @param  ScanQrDTO  $dto  Data scan QR code
     * @return array Hasil scan dengan status dan data presensi
     *
     * @throws \App\Exceptions\QrTokenExpiredException Jika token QR sudah expired
     */
    public function scanQrCode(ScanQrDTO $dto): array;

    /**
     * Memproses scan wajah untuk presensi siswa.
     *
     * @param Siswa $siswa Siswa yang cocok dengan hasil scan wajah
     * @param array $metadata Metadata bukti scan wajah dan lokasi
     * @return array Hasil scan dengan status dan data presensi
     */
    public function recordFaceAttendance(Siswa $siswa, array $metadata): array;

    /**
     * Mendapatkan statistik kehadiran
     *
     * @param  StatisticsFilterDTO  $dto  Filter untuk statistik
     * @return array Statistik kehadiran (total, hadir, terlambat, izin, sakit, alpha, persentase)
     */
    public function getStatistics(StatisticsFilterDTO $dto): array;

    /**
     * Membuat record alpha otomatis untuk siswa aktif yang belum presensi
     * pada tanggal yang jadwal presensi siswanya sudah ditutup.
     *
     * @param string $startDate Tanggal mulai (Y-m-d)
     * @param string $endDate Tanggal akhir (Y-m-d)
     * @param int|null $kelasId Filter berdasarkan kelas (opsional)
     * @return int Jumlah record alpha yang dibuat
     */
    public function backfillClosedAlpha(string $startDate, string $endDate, ?int $kelasId = null): int;

    /**
     * Memverifikasi record presensi
     *
     * @param  int  $presensiId  ID presensi yang akan diverifikasi
     * @param  int  $verifierId  ID user yang memverifikasi
     * @return Presensi Record presensi yang sudah diverifikasi
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Jika presensi tidak ditemukan
     */
    public function verifyAttendance(int $presensiId, int $verifierId): Presensi;

    /**
     * Mendapatkan presensi hari ini
     *
     * @param  int|null  $kelasId  Filter berdasarkan kelas (opsional)
     * @return \Illuminate\Support\Collection Koleksi presensi hari ini
     */
    public function getToday(?int $kelasId = null): \Illuminate\Support\Collection;

    /**
     * Mendapatkan presensi yang belum diverifikasi
     *
     * @return \Illuminate\Support\Collection Koleksi presensi yang belum diverifikasi
     */
    public function getUnverified(): \Illuminate\Support\Collection;
}
