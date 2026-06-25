<?php

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\PamongQrServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Service untuk mengelola QR Token pamong/guru
 *
 * Service ini menangani semua operasi terkait QR token untuk pamong
 * termasuk generate, verify, dan mendapatkan data QR untuk presensi.
 */
class PamongQrService implements PamongQrServiceInterface
{
    /**
     * Prefix untuk payload QR pamong (berbeda dengan siswa)
     */
    protected const PAYLOAD_PREFIX = 'PKG-P';
    
    /**
     * Version payload
     */
    protected const PAYLOAD_VERSION = '1';

    /**
     * Generate QR token untuk pamong
     *
     * @param User $pamong User dengan role pamong/teacher
     * @return string Token yang di-generate
     */
    public function generateToken(User $pamong): string
    {
        $hashAlgorithm = config('qrcode.token.hash_algorithm', 'sha256');
        $randomLength = config('qrcode.token.random_length', 32);

        // Generate token unik untuk pamong
        // Menggunakan kombinasi user_id, timestamp, dan random string
        $token = hash(
            $hashAlgorithm,
            $pamong->id . now()->timestamp . Str::random($randomLength)
        );

        // Simpan token ke database
        $pamong->update([
            'qr_token' => $token,
            'qr_token_generated_at' => Carbon::now(),
        ]);

        return $token;
    }

    /**
     * Verifikasi QR token pamong
     *
     * @param User $pamong User pemilik token
     * @param string $token Token yang akan diverifikasi
     * @return bool True jika token valid
     */
    public function verifyToken(User $pamong, string $token): bool
    {
        // Token pamong tidak expire (berbeda dengan siswa)
        // Hanya perlu cocokkan token
        return $pamong->qr_token === $token;
    }

    /**
     * Mendapatkan data QR untuk pamong
     *
     * @param User $pamong User yang akan diambil data QR-nya
     * @return array Data QR (token, qr_image_base64, qr_image_svg, generated_at, user_info)
     */
    public function getQrData(User $pamong): array
    {
        // Generate token baru jika belum ada
        if (!$pamong->qr_token) {
            $token = $this->generateToken($pamong);
            $pamong->refresh();
        } else {
            $token = $pamong->qr_token;
        }

        // Buat payload QR
        $payload = $this->buildPayload($pamong, $token);

        // Generate QR code images
        $qrImageBase64 = $this->generateQrImageBase64($payload);
        $qrImageSvg = $this->generateQrImageSvg($payload);

        return [
            'token' => $token,
            'qr_image_base64' => $qrImageBase64,
            'qr_image_svg' => $qrImageSvg,
            'generated_at' => $pamong->qr_token_generated_at?->toISOString(),
            'user_info' => [
                'id' => $pamong->id,
                'name' => $pamong->name,
                'username' => $pamong->username,
                'role' => $pamong->role?->name,
            ],
        ];
    }

    /**
     * Refresh QR token (generate token baru)
     *
     * @param User $pamong User yang tokennya akan di-refresh
     * @return array Data QR baru
     */
    public function refreshToken(User $pamong): array
    {
        $token = $this->generateToken($pamong);
        $pamong->refresh();

        $payload = $this->buildPayload($pamong, $token);

        return [
            'token' => $token,
            'qr_image_base64' => $this->generateQrImageBase64($payload),
            'qr_image_svg' => $this->generateQrImageSvg($payload),
            'generated_at' => $pamong->qr_token_generated_at?->toISOString(),
        ];
    }

    /**
     * Cek apakah user adalah pamong
     *
     * @param User $user User yang akan dicek
     * @return bool True jika user adalah pamong
     */
    public function isPamong(User $user): bool
    {
        // Tim operasional mencakup pamong, pengurus PKG, dan admin.
        return $user->hasAnyRole(User::attendanceRoleNames());
    }

    /**
     * Parse payload QR dan identifikasi apakah milik pamong
     *
     * @param string $payload Payload QR code
     * @return array|null Array dengan keys: type ('pamong'|'siswa'), id, token, hash. Null jika invalid
     */
    public function parsePayload(string $payload): ?array
    {
        $delimiter = config('qrcode.payload.delimiter', '|');
        $parts = explode($delimiter, $payload);

        if (count($parts) < 5) {
            return null;
        }

        [$prefix, $version, $id, $token, $hash] = $parts;

        // Tentukan tipe berdasarkan prefix
        if ($prefix === self::PAYLOAD_PREFIX) {
            return [
                'type' => 'pamong',
                'id' => (int) $id,
                'token' => $token,
                'hash' => $hash,
            ];
        } elseif ($prefix === config('qrcode.payload.prefix', 'PKG')) {
            return [
                'type' => 'siswa',
                'id' => (int) $id,
                'token' => $token,
                'hash' => $hash,
            ];
        }

        return null;
    }

    /**
     * Build payload untuk QR code pamong
     *
     * @param User $pamong User pemilik token
     * @param string $token Token QR
     * @return string Payload yang akan di-encode ke QR
     */
    protected function buildPayload(User $pamong, string $token): string
    {
        $delimiter = config('qrcode.payload.delimiter', '|');
        $hmacAlgorithm = config('qrcode.encryption.hmac_algorithm', 'sha256');

        // HMAC Signature untuk Integritas
        // Menggunakan kombinasi user_id + token sebagai data
        // Secret key menggunakan APP_KEY
        $hash = hash_hmac(
            $hmacAlgorithm,
            $pamong->id . $token,
            config('app.key')
        );

        // Gabungkan semua komponen dengan delimiter
        // Format: PKG-P|1|user_id|token|hash(16)
        return implode($delimiter, [
            self::PAYLOAD_PREFIX,
            self::PAYLOAD_VERSION,
            $pamong->id,
            $token,
            substr($hash, 0, 16),
        ]);
    }

    /**
     * Generate QR code image dalam format base64
     *
     * @param string $payload Payload yang akan di-encode
     * @return string Base64 encoded SVG image
     */
    protected function generateQrImageBase64(string $payload): string
    {
        // Ukuran besar untuk QR yang mudah di-scan dari jarak jauh
        $size = 400;
        $margin = 5;
        // Level L = lebih sedikit error correction, QR lebih sederhana, lebih mudah scan
        $errorCorrection = ErrorCorrectionLevel::Low;

        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel($errorCorrection)
            ->size($size)
            ->margin($margin)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return 'data:image/svg+xml;base64,' . base64_encode($result->getString());
    }

    /**
     * Get error correction level from config string
     */
    protected function getErrorCorrectionLevel(string $level): ErrorCorrectionLevel
    {
        return match (strtoupper($level)) {
            'L' => ErrorCorrectionLevel::Low,
            'M' => ErrorCorrectionLevel::Medium,
            'Q' => ErrorCorrectionLevel::Quartile,
            'H' => ErrorCorrectionLevel::High,
            default => ErrorCorrectionLevel::Medium,
        };
    }

    /**
     * Generate QR code image dalam format SVG
     *
     * @param string $payload Payload yang akan di-encode
     * @return string SVG string
     */
    protected function generateQrImageSvg(string $payload): string
    {
        // Ukuran besar untuk QR yang mudah di-scan dari jarak jauh
        $size = 400;
        $margin = 5;
        // Level L = lebih sedikit error correction, QR lebih sederhana, lebih mudah scan
        $errorCorrection = ErrorCorrectionLevel::Low;

        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel($errorCorrection)
            ->size($size)
            ->margin($margin)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return $result->getString();
    }
}
