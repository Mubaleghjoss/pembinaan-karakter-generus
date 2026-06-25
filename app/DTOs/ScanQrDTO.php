<?php

namespace App\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object untuk scan QR Code.
 *
 * DTO ini digunakan untuk transfer data saat proses scan QR Code
 * untuk presensi siswa.
 */
readonly class ScanQrDTO
{
    public function __construct(
        public int $studentId,
        public string $token,
        public ?string $location = null,
        public ?string $deviceInfo = null,
        public ?string $ipAddress = null,
    ) {}

    /**
     * Membuat instance DTO dari Request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            studentId: (int) $request->input('student_id'),
            token: $request->input('token'),
            location: $request->input('location'),
            deviceInfo: $request->header('User-Agent'),
            ipAddress: $request->ip(),
        );
    }

    /**
     * Membuat instance DTO dari array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            studentId: $data['student_id'],
            token: $data['token'],
            location: $data['location'] ?? null,
            deviceInfo: $data['device_info'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
        );
    }

    /**
     * Konversi DTO ke array untuk logging atau penyimpanan.
     */
    public function toArray(): array
    {
        return [
            'student_id' => $this->studentId,
            'token' => $this->token,
            'location' => $this->location,
            'device_info' => $this->deviceInfo,
            'ip_address' => $this->ipAddress,
        ];
    }

    /**
     * Mendapatkan data scan untuk penyimpanan presensi.
     */
    public function getScanData(): array
    {
        return [
            'location' => $this->location,
            'device_info' => $this->deviceInfo,
            'ip_address' => $this->ipAddress,
        ];
    }
}
