<?php

namespace App\Services;

use App\Models\Siswa;
use App\Services\Contracts\QrTokenServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Service untuk mengelola QR Token siswa
 *
 * Service ini menangani semua operasi terkait QR token termasuk
 * generate, verify, dan mendapatkan data QR untuk presensi.
 */
class QrTokenService implements QrTokenServiceInterface
{
    /**
     * Generate QR token untuk siswa
     *
     * @param  Siswa  $siswa  Siswa yang akan di-generate tokennya
     * @param  int|null  $expiryMinutes  Waktu kadaluarsa dalam menit (default dari config)
     * @return string Token yang di-generate
     */
    public function generate(Siswa $siswa, ?int $expiryMinutes = null): string
    {
        // Ambil konfigurasi dari config/qrcode.php dengan fallback default
        $expiryMinutes = $expiryMinutes ?? $this->integerConfig('qrcode.token.expiry_minutes', 60);
        $hashAlgorithm = config('qrcode.token.hash_algorithm', 'sha256');
        $randomLength = $this->integerConfig('qrcode.token.random_length', 32);

        /*
         * Token Generation Algorithm:
         * 1. Kombinasikan qr_secret_salt siswa (unik per siswa) dengan timestamp
         * 2. Tambahkan random string untuk mencegah collision
         * 3. Hash menggunakan SHA-256 untuk menghasilkan token 64 karakter
         *
         * Keamanan:
         * - qr_secret_salt: memastikan token berbeda antar siswa
         * - timestamp: memastikan token berbeda setiap generate
         * - random string: menambah entropy untuk mencegah prediksi
         */
        $token = hash(
            $hashAlgorithm,
            $siswa->qr_secret_salt.now()->timestamp.Str::random($randomLength)
        );

        // Simpan token dan waktu kadaluarsa ke database
        // Token akan invalid setelah melewati qr_token_expires_at
        $siswa->update([
            'qr_token' => $token,
            'qr_token_expires_at' => Carbon::now()->addMinutes($expiryMinutes),
        ]);

        return $token;
    }

    /**
     * Verifikasi QR token
     *
     * @param  Siswa  $siswa  Siswa pemilik token
     * @param  string  $token  Token yang akan diverifikasi
     * @return bool True jika token valid dan belum expired
     */
    public function verify(Siswa $siswa, string $token): bool
    {
        return $siswa->qr_token === $token && ! $this->isExpired($siswa);
    }

    /**
     * Mendapatkan data QR untuk siswa
     *
     * @param  Siswa  $siswa  Siswa yang akan diambil data QR-nya
     * @return array Data QR (token, qr_image_base64, qr_image_svg, expires_at, siswa_info)
     */
    public function getQrData(Siswa $siswa): array
    {
        // Generate token jika belum ada atau sudah expired.
        if (! $siswa->qr_token || $this->isExpired($siswa)) {
            $token = $this->generate($siswa);
            $siswa->refresh();
        } else {
            $token = $siswa->qr_token;
        }

        // Buat payload QR
        $payload = $this->buildPayload($siswa, $token);

        // Generate QR code images
        $qrImageBase64 = $this->generateQrImageBase64($payload);
        $qrImageSvg = $this->generateQrImageSvg($payload);

        return [
            'token' => $token,
            'qr_image_base64' => $qrImageBase64,
            'qr_image_svg' => $qrImageSvg,
            'expires_at' => $siswa->qr_token_expires_at?->toISOString(),
            'siswa_info' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
            ],
        ];
    }

    /**
     * Refresh QR token (generate token baru)
     *
     * @param  Siswa  $siswa  Siswa yang tokennya akan di-refresh
     * @param  int|null  $expiryMinutes  Waktu kadaluarsa dalam menit (default dari config)
     * @return array Data QR baru (token, qr_image_base64, expires_at)
     */
    public function refreshToken(Siswa $siswa, ?int $expiryMinutes = null): array
    {
        $token = $this->generate($siswa, $expiryMinutes);
        $siswa->refresh();

        $payload = $this->buildPayload($siswa, $token);

        return [
            'token' => $token,
            'qr_image_base64' => $this->generateQrImageBase64($payload),
            'qr_image_svg' => $this->generateQrImageSvg($payload),
            'expires_at' => $siswa->qr_token_expires_at?->toISOString(),
        ];
    }

    /**
     * Cek apakah token sudah expired
     *
     * @param  Siswa  $siswa  Siswa pemilik token
     * @return bool True jika token sudah expired atau tidak ada
     */
    public function isExpired(Siswa $siswa): bool
    {
        if (! $siswa->qr_token || ! $siswa->qr_token_expires_at) {
            return true;
        }

        return $siswa->qr_token_expires_at->isPast();
    }

    /**
     * Mendapatkan waktu kadaluarsa token
     *
     * @param  Siswa  $siswa  Siswa pemilik token
     * @return Carbon|null Waktu kadaluarsa atau null jika tidak ada token
     */
    public function getExpiresAt(Siswa $siswa): ?Carbon
    {
        return $siswa->qr_token_expires_at;
    }

    /**
     * Build payload untuk QR code
     *
     * @param  Siswa  $siswa  Siswa pemilik token
     * @param  string  $token  Token QR
     * @return string Payload yang akan di-encode ke QR
     */
    protected function buildPayload(Siswa $siswa, string $token): string
    {
        /*
         * QR Payload Structure:
         * Format: PREFIX|VERSION|STUDENT_ID|TOKEN|HASH
         *
         * Komponen:
         * - PREFIX: Identifier aplikasi (PKG) untuk validasi awal
         * - VERSION: Versi format payload untuk backward compatibility
         * - STUDENT_ID: ID siswa untuk lookup di database
         * - TOKEN: Token yang di-generate untuk validasi
         * - HASH: HMAC signature untuk integritas data (16 karakter)
         *
         * Contoh: PKG|1|123|abc123...|f7a8b9c0d1e2f3g4
         */
        $prefix = config('qrcode.payload.prefix', 'PKG');
        $version = config('qrcode.payload.version', '1');
        $delimiter = config('qrcode.payload.delimiter', '|');
        $hmacAlgorithm = config('qrcode.encryption.hmac_algorithm', 'sha256');

        /*
         * HMAC Signature untuk Integritas:
         * - Menggunakan HMAC-SHA256 dengan qr_secret_salt sebagai key
         * - Data yang di-sign: student_id + token
         * - Mencegah manipulasi payload QR code
         */
        $hash = hash_hmac(
            $hmacAlgorithm,
            $siswa->id.$token,
            $siswa->qr_secret_salt
        );

        // Gabungkan semua komponen dengan delimiter
        // Hash dipotong 16 karakter untuk menjaga ukuran QR code tetap kecil
        return implode($delimiter, [
            $prefix,
            $version,
            $siswa->id,
            $token,
            substr($hash, 0, 16),
        ]);
    }

    /**
     * Generate QR code image dalam format base64
     *
     * @param  string  $payload  Payload yang akan di-encode
     * @return string Base64 encoded PNG image
     */
    protected function generateQrImageBase64(string $payload): string
    {
        $size = $this->integerConfig('qrcode.generation.size', 300);
        $margin = $this->integerConfig('qrcode.generation.margin', 10, 0);
        $errorCorrection = $this->getErrorCorrectionLevel(config('qrcode.generation.error_correction', 'M'));
        $foregroundColor = config('qrcode.generation.foreground_color', '000000');
        $backgroundColor = config('qrcode.generation.background_color', 'ffffff');

        // Parse warna hex ke RGB
        $fgColor = $this->hexToRgb($foregroundColor);
        $bgColor = $this->hexToRgb($backgroundColor);

        // Gunakan SVG writer karena tidak memerlukan GD extension
        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel($errorCorrection)
            ->size($size)
            ->margin($margin)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        // Return sebagai data URI SVG
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
     * @param  string  $payload  Payload yang akan di-encode
     * @return string SVG string
     */
    protected function generateQrImageSvg(string $payload): string
    {
        $size = $this->integerConfig('qrcode.generation.size', 300);
        $margin = $this->integerConfig('qrcode.generation.margin', 10, 0);
        $errorCorrection = $this->getErrorCorrectionLevel(config('qrcode.generation.error_correction', 'M'));

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

    /**
     * Convert hex color ke RGB array
     *
     * @param  string  $hex  Warna hex (tanpa #)
     * @return array Array dengan key r, g, b
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Numeric values from cached env config can arrive as strings in production.
     */
    protected function integerConfig(string $key, int $default, int $minimum = 1): int
    {
        $value = config($key, $default);

        if (! is_numeric($value)) {
            return max($minimum, $default);
        }

        return max($minimum, (int) $value);
    }
}
