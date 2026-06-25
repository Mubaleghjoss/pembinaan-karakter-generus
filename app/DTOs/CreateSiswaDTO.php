<?php

namespace App\DTOs;

use App\Http\Requests\Siswa\StoreSiswaRequest;
use Illuminate\Http\UploadedFile;

/**
 * Data Transfer Object untuk pembuatan data siswa baru.
 *
 * DTO ini digunakan untuk transfer data saat membuat siswa baru
 * dari controller ke service layer.
 */
readonly class CreateSiswaDTO
{
    public function __construct(
        public string $nis,
        public string $nama,
        public string $jenisKelamin,
        public int $kelasId,
        public ?string $tanggalLahir = null,
        public ?string $kelompok = null,
        public ?string $targetGradeOverride = null,
        public ?UploadedFile $foto = null,
        public ?string $namaWali = null,
        public ?string $phoneWali = null,
        public ?string $phone = null,
        public ?string $emailWali = null,
    ) {}

    /**
     * Membuat instance DTO dari StoreSiswaRequest.
     */
    public static function fromRequest(StoreSiswaRequest $request): self
    {
        return new self(
            nis: $request->validated('nis'),
            nama: $request->validated('nama'),
            jenisKelamin: $request->validated('jenis_kelamin'),
            kelasId: $request->validated('kelas_id'),
            tanggalLahir: $request->validated('tanggal_lahir'),
            kelompok: $request->validated('kelompok'),
            targetGradeOverride: $request->validated('target_grade_override'),
            foto: $request->file('foto'),
            namaWali: $request->validated('nama_wali'),
            phoneWali: $request->validated('phone_wali'),
            phone: $request->validated('phone'),
            emailWali: $request->validated('email_wali'),
        );
    }

    /**
     * Membuat instance DTO dari array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nis: $data['nis'],
            nama: $data['nama'],
            jenisKelamin: $data['jenis_kelamin'],
            kelasId: $data['kelas_id'],
            tanggalLahir: $data['tanggal_lahir'] ?? null,
            kelompok: $data['kelompok'] ?? null,
            targetGradeOverride: $data['target_grade_override'] ?? null,
            foto: $data['foto'] ?? null,
            namaWali: $data['nama_wali'] ?? null,
            phoneWali: $data['phone_wali'] ?? null,
            phone: $data['phone'] ?? null,
            emailWali: $data['email_wali'] ?? null,
        );
    }

    /**
     * Konversi DTO ke array untuk penyimpanan database.
     * Tidak termasuk foto karena perlu diproses terpisah.
     */
    public function toArray(): array
    {
        return [
            'nis' => $this->nis,
            'nama' => $this->nama,
            'jenis_kelamin' => $this->jenisKelamin,
            'tanggal_lahir' => $this->tanggalLahir,
            'kelas_id' => $this->kelasId,
            'kelompok' => $this->kelompok,
            'target_grade_override' => $this->targetGradeOverride,
            'nama_wali' => $this->namaWali,
            'phone_wali' => $this->phoneWali,
            'phone' => $this->phone,
            'email_wali' => $this->emailWali,
            'status' => 'active',
            'is_active' => true,
        ];
    }

    /**
     * Cek apakah ada foto yang diupload.
     */
    public function hasFoto(): bool
    {
        return $this->foto !== null;
    }
}
