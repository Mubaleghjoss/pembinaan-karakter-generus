<?php

namespace App\Services\Contracts;

use App\Models\Siswa;

/**
 * Interface untuk service layer QR Token
 *
 * Mendefinisikan kontrak untuk semua operasi terkait QR token
 * termasuk generate, verify, dan mendapatkan data QR.
 */
interface QrTokenServiceInterface
{
    /**
     * Generate QR token untuk siswa
     *
     * @param  Siswa  $siswa  Siswa yang akan di-generate tokennya
     * @param  int  $expiryMinutes  Waktu kadaluarsa dalam menit (default dari config)
     * @return string Token yang di-generate
     */
    public function generate(Siswa $siswa, ?int $expiryMinutes = null): string;

    /**
     * Verifikasi QR token
     *
     * @param  Siswa  $siswa  Siswa pemilik token
     * @param  string  $token  Token yang akan diverifikasi
     * @return bool True jika token valid dan belum expired
     */
    public function verify(Siswa $siswa, string $token): bool;

    /**
     * Mendapatkan data QR untuk siswa
     *
     * @param  Siswa  $siswa  Siswa yang akan diambil data QR-nya
     * @return array Data QR (token, qr_image_base64, qr_image_svg, expires_at, siswa_info)
     */
    public function getQrData(Siswa $siswa): array;

    /**
     * Refresh QR token (generate token baru)
     *
     * @param  Siswa  $siswa  Siswa yang tokennya akan di-refresh
     * @param  int|null  $expiryMinutes  Waktu kadaluarsa dalam menit (default dari config)
     * @return array Data QR baru (token, qr_image_base64, expires_at)
     */
    public function refreshToken(Siswa $siswa, ?int $expiryMinutes = null): array;

    /**
     * Cek apakah token sudah expired
     *
     * @param  Siswa  $siswa  Siswa pemilik token
     * @return bool True jika token sudah expired atau tidak ada
     */
    public function isExpired(Siswa $siswa): bool;

    /**
     * Mendapatkan waktu kadaluarsa token
     *
     * @param  Siswa  $siswa  Siswa pemilik token
     * @return \Carbon\Carbon|null Waktu kadaluarsa atau null jika tidak ada token
     */
    public function getExpiresAt(Siswa $siswa): ?\Carbon\Carbon;
}
