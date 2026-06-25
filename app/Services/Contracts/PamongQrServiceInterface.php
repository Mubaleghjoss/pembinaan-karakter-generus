<?php

namespace App\Services\Contracts;

use App\Models\User;

/**
 * Interface untuk PamongQrService
 * 
 * Service ini menangani operasi QR token untuk pamong/guru
 */
interface PamongQrServiceInterface
{
    /**
     * Generate QR token untuk pamong
     *
     * @param User $pamong User dengan role pamong/teacher
     * @return string Token yang di-generate
     */
    public function generateToken(User $pamong): string;

    /**
     * Verifikasi QR token pamong
     *
     * @param User $pamong User pemilik token
     * @param string $token Token yang akan diverifikasi
     * @return bool True jika token valid
     */
    public function verifyToken(User $pamong, string $token): bool;

    /**
     * Mendapatkan data QR untuk pamong
     *
     * @param User $pamong User yang akan diambil data QR-nya
     * @return array Data QR (token, qr_image_base64, qr_image_svg, generated_at, user_info)
     */
    public function getQrData(User $pamong): array;

    /**
     * Refresh QR token (generate token baru)
     *
     * @param User $pamong User yang tokennya akan di-refresh
     * @return array Data QR baru
     */
    public function refreshToken(User $pamong): array;

    /**
     * Cek apakah user adalah pamong
     *
     * @param User $user User yang akan dicek
     * @return bool True jika user adalah pamong
     */
    public function isPamong(User $user): bool;

    /**
     * Parse payload QR dan identifikasi apakah milik pamong
     *
     * @param string $payload Payload QR code
     * @return array|null Array dengan keys: type ('pamong'|'siswa'), id, token, hash. Null jika invalid
     */
    public function parsePayload(string $payload): ?array;
}
