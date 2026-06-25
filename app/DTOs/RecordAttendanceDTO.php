<?php

namespace App\DTOs;

use App\Http\Requests\Presensi\StorePresensiRequest;

/**
 * Data Transfer Object untuk pencatatan kehadiran.
 * 
 * DTO ini digunakan untuk transfer data antara controller dan service layer
 * saat mencatat kehadiran siswa.
 */
readonly class RecordAttendanceDTO
{
    public function __construct(
        public int $siswaId,
        public string $tanggal,
        public string $status,
        public ?string $jamMasuk = null,
        public ?string $jamKeluar = null,
        public ?string $keterangan = null,
        public ?int $verifiedBy = null,
    ) {}

    /**
     * Membuat instance DTO dari StorePresensiRequest.
     */
    public static function fromRequest(StorePresensiRequest $request): self
    {
        return new self(
            siswaId: $request->validated('siswa_id'),
            tanggal: $request->validated('tanggal'),
            status: $request->validated('status'),
            jamMasuk: $request->validated('jam_masuk'),
            jamKeluar: $request->validated('jam_keluar'),
            keterangan: $request->validated('keterangan'),
            verifiedBy: $request->user()?->id,
        );
    }

    /**
     * Membuat instance DTO dari array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            siswaId: $data['siswa_id'],
            tanggal: $data['tanggal'],
            status: $data['status'],
            jamMasuk: $data['jam_masuk'] ?? null,
            jamKeluar: $data['jam_keluar'] ?? null,
            keterangan: $data['keterangan'] ?? null,
            verifiedBy: $data['verified_by'] ?? null,
        );
    }

    /**
     * Konversi DTO ke array untuk penyimpanan database.
     */
    public function toArray(): array
    {
        return [
            'siswa_id' => $this->siswaId,
            'tanggal' => $this->tanggal,
            'status' => $this->status,
            'jam_masuk' => $this->jamMasuk,
            'jam_keluar' => $this->jamKeluar,
            'keterangan' => $this->keterangan,
            'verified_by' => $this->verifiedBy,
        ];
    }
}
