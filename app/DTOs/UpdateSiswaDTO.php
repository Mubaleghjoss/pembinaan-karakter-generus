<?php

namespace App\DTOs;

use App\Http\Requests\Siswa\UpdateSiswaRequest;
use Illuminate\Http\UploadedFile;

/**
 * Data Transfer Object untuk update data siswa.
 *
 * DTO ini digunakan untuk transfer data saat mengupdate siswa
 * dari controller ke service layer.
 */
readonly class UpdateSiswaDTO
{
    public function __construct(
        public ?string $nama = null,
        public ?string $jenisKelamin = null,
        public ?string $tanggalLahir = null,
        public ?int $kelasId = null,
        public ?string $schoolGrade = null,
        public bool $schoolGradeProvided = false,
        public ?string $kelompok = null,
        public ?string $targetGradeOverride = null,
        public bool $targetGradeOverrideProvided = false,
        public ?UploadedFile $foto = null,
        public ?string $namaWali = null,
        public ?string $phoneWali = null,
        public ?string $emailWali = null,
        public ?string $status = null,
        public ?bool $isActive = null,
    ) {}

    /**
     * Membuat instance DTO dari UpdateSiswaRequest.
     */
    public static function fromRequest(UpdateSiswaRequest $request): self
    {
        return new self(
            nama: $request->validated('nama'),
            jenisKelamin: $request->validated('jenis_kelamin'),
            tanggalLahir: $request->validated('tanggal_lahir'),
            kelasId: $request->validated('kelas_id'),
            schoolGrade: $request->input('school_grade') ?: null,
            schoolGradeProvided: $request->has('school_grade'),
            kelompok: $request->validated('kelompok'),
            targetGradeOverride: $request->input('target_grade_override') ?: null,
            targetGradeOverrideProvided: $request->has('target_grade_override'),
            foto: $request->file('foto'),
            namaWali: $request->validated('nama_wali'),
            phoneWali: $request->validated('phone_wali'),
            emailWali: $request->validated('email_wali'),
            status: $request->validated('status'),
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
        );
    }

    /**
     * Membuat instance DTO dari array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nama: $data['nama'] ?? null,
            jenisKelamin: $data['jenis_kelamin'] ?? null,
            tanggalLahir: $data['tanggal_lahir'] ?? null,
            kelasId: $data['kelas_id'] ?? null,
            schoolGrade: $data['school_grade'] ?? null,
            schoolGradeProvided: array_key_exists('school_grade', $data),
            kelompok: $data['kelompok'] ?? null,
            targetGradeOverride: $data['target_grade_override'] ?? null,
            targetGradeOverrideProvided: array_key_exists('target_grade_override', $data),
            foto: $data['foto'] ?? null,
            namaWali: $data['nama_wali'] ?? null,
            phoneWali: $data['phone_wali'] ?? null,
            emailWali: $data['email_wali'] ?? null,
            status: $data['status'] ?? null,
            isActive: $data['is_active'] ?? null,
        );
    }

    /**
     * Konversi DTO ke array untuk update database.
     * Hanya menyertakan field yang tidak null.
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->nama !== null) {
            $data['nama'] = $this->nama;
        }
        if ($this->jenisKelamin !== null) {
            $data['jenis_kelamin'] = $this->jenisKelamin;
        }
        if ($this->tanggalLahir !== null) {
            $data['tanggal_lahir'] = $this->tanggalLahir;
        }
        if ($this->kelasId !== null) {
            $data['kelas_id'] = $this->kelasId;
        }
        if ($this->schoolGradeProvided) {
            $data['school_grade'] = $this->schoolGrade;
        }
        if ($this->kelompok !== null) {
            $data['kelompok'] = $this->kelompok;
        }
        if ($this->targetGradeOverrideProvided) {
            $data['target_grade_override'] = $this->targetGradeOverride;
        }
        if ($this->namaWali !== null) {
            $data['nama_wali'] = $this->namaWali;
        }
        if ($this->phoneWali !== null) {
            $data['phone_wali'] = $this->phoneWali;
        }
        if ($this->emailWali !== null) {
            $data['email_wali'] = $this->emailWali;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        return $data;
    }

    /**
     * Cek apakah ada foto yang diupload.
     */
    public function hasFoto(): bool
    {
        return $this->foto !== null;
    }

    /**
     * Cek apakah ada data yang perlu diupdate.
     */
    public function hasChanges(): bool
    {
        return ! empty($this->toArray()) || $this->hasFoto();
    }
}
