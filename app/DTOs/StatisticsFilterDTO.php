<?php

namespace App\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Data Transfer Object untuk filter statistik kehadiran.
 *
 * DTO ini digunakan untuk transfer parameter filter saat
 * mengambil data statistik kehadiran.
 */
readonly class StatisticsFilterDTO
{
    public function __construct(
        public string $startDate,
        public string $endDate,
        public ?int $kelasId = null,
        public ?int $siswaId = null,
        public ?string $status = null,
        public ?string $groupBy = null,
    ) {}

    /**
     * Membuat instance DTO dari Request.
     */
    public static function fromRequest(Request $request): self
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        return new self(
            startDate: $startDate,
            endDate: $endDate,
            kelasId: $request->filled('kelas_id') ? (int) $request->input('kelas_id') : null,
            siswaId: $request->filled('siswa_id') ? (int) $request->input('siswa_id') : null,
            status: $request->input('status'),
            groupBy: $request->input('group_by', 'day'),
        );
    }

    /**
     * Membuat instance DTO dari array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            startDate: $data['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
            endDate: $data['end_date'] ?? Carbon::now()->format('Y-m-d'),
            kelasId: $data['kelas_id'] ?? null,
            siswaId: $data['siswa_id'] ?? null,
            status: $data['status'] ?? null,
            groupBy: $data['group_by'] ?? 'day',
        );
    }

    /**
     * Membuat filter untuk bulan ini.
     */
    public static function thisMonth(?int $kelasId = null): self
    {
        return new self(
            startDate: Carbon::now()->startOfMonth()->format('Y-m-d'),
            endDate: Carbon::now()->endOfMonth()->format('Y-m-d'),
            kelasId: $kelasId,
        );
    }

    /**
     * Membuat filter untuk minggu ini.
     */
    public static function thisWeek(?int $kelasId = null): self
    {
        return new self(
            startDate: Carbon::now()->startOfWeek()->format('Y-m-d'),
            endDate: Carbon::now()->endOfWeek()->format('Y-m-d'),
            kelasId: $kelasId,
        );
    }

    /**
     * Membuat filter untuk hari ini.
     */
    public static function today(?int $kelasId = null): self
    {
        $today = Carbon::now()->format('Y-m-d');

        return new self(
            startDate: $today,
            endDate: $today,
            kelasId: $kelasId,
        );
    }

    /**
     * Konversi DTO ke array untuk query.
     */
    public function toArray(): array
    {
        return array_filter([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'kelas_id' => $this->kelasId,
            'siswa_id' => $this->siswaId,
            'status' => $this->status,
            'group_by' => $this->groupBy,
        ], fn ($value) => $value !== null);
    }

    /**
     * Mendapatkan jumlah hari dalam rentang filter.
     */
    public function getDaysCount(): int
    {
        return Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1;
    }

    /**
     * Cek apakah filter untuk satu hari saja.
     */
    public function isSingleDay(): bool
    {
        return $this->startDate === $this->endDate;
    }
}
