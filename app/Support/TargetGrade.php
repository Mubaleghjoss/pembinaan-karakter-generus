<?php

namespace App\Support;

use App\Models\Siswa;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class TargetGrade
{
    public const SMP_7 = 'smp_7';
    public const SMP_8 = 'smp_8';
    public const SMP_9 = 'smp_9';
    public const SMA_10 = 'sma_10';
    public const SMA_11 = 'sma_11';
    public const SMA_12 = 'sma_12';
    public const PRANIKAH = 'pranikah';

    public static function options(): array
    {
        return [
            self::SMP_7 => 'SMP 7',
            self::SMP_8 => 'SMP 8',
            self::SMP_9 => 'SMP 9',
            self::SMA_10 => 'SMA 10',
            self::SMA_11 => 'SMA 11',
            self::SMA_12 => 'SMA 12',
            self::PRANIKAH => 'Pranikah (Selesai SMA/K)',
        ];
    }

    public static function schoolClassOptions(): array
    {
        return [
            self::SMP_7 => 'SMP Kelas 1 (Kelas 7)',
            self::SMP_8 => 'SMP Kelas 2 (Kelas 8)',
            self::SMP_9 => 'SMP Kelas 3 (Kelas 9)',
            self::SMA_10 => 'SMA/SMK Kelas 1 (Kelas 10)',
            self::SMA_11 => 'SMA/SMK Kelas 2 (Kelas 11)',
            self::SMA_12 => 'SMA/SMK Kelas 3 (Kelas 12)',
            self::PRANIKAH => 'Pranikah (Selesai SMA/K)',
        ];
    }

    public static function schoolClassLabel(?string $grade): ?string
    {
        return self::schoolClassOptions()[$grade] ?? null;
    }

    public static function normalizeSchoolClassInput(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?: '';

        return match ($normalized) {
            'smp 1', 'smp kelas 1', 'kelas 7', '7', 'smp 7', 'smp_7' => self::SMP_7,
            'smp 2', 'smp kelas 2', 'kelas 8', '8', 'smp 8', 'smp_8' => self::SMP_8,
            'smp 3', 'smp kelas 3', 'kelas 9', '9', 'smp 9', 'smp_9' => self::SMP_9,
            'sma 1', 'smk 1', 'sma smk 1', 'sma kelas 1', 'smk kelas 1', 'kelas 10', '10', 'sma 10', 'sma_10' => self::SMA_10,
            'sma 2', 'smk 2', 'sma smk 2', 'sma kelas 2', 'smk kelas 2', 'kelas 11', '11', 'sma 11', 'sma_11' => self::SMA_11,
            'sma 3', 'smk 3', 'sma smk 3', 'sma kelas 3', 'smk kelas 3', 'kelas 12', '12', 'sma 12', 'sma_12' => self::SMA_12,
            'pranikah', 'pra nikah', 'selesai sma', 'selesai smk' => self::PRANIKAH,
            default => in_array($value, self::values(), true) ? $value : null,
        };
    }

    public static function values(): array
    {
        return array_keys(self::options());
    }

    public static function label(?string $grade): ?string
    {
        return self::options()[$grade] ?? null;
    }

    public static function resolveForSiswa(Siswa $siswa, ?CarbonInterface $referenceDate = null): ?string
    {
        if ($siswa->target_grade_override && in_array($siswa->target_grade_override, self::values(), true)) {
            return $siswa->target_grade_override;
        }

        if ($siswa->school_grade && in_array($siswa->school_grade, self::values(), true)) {
            return $siswa->school_grade;
        }

        return self::fromBirthDate($siswa->tanggal_lahir, $referenceDate);
    }

    public static function fromBirthDate($birthDate, ?CarbonInterface $referenceDate = null): ?string
    {
        if (empty($birthDate)) {
            return null;
        }

        $referenceDate ??= now();
        $julyFirst = Carbon::create((int) $referenceDate->format('Y'), 7, 1)->startOfDay();
        $birth = $birthDate instanceof CarbonInterface
            ? Carbon::instance($birthDate)
            : Carbon::parse($birthDate);

        if ($birth->greaterThan($julyFirst)) {
            return null;
        }

        $age = (int) $birth->diffInYears($julyFirst);

        return match ($age) {
            12 => self::SMP_7,
            13 => self::SMP_8,
            14 => self::SMP_9,
            15 => self::SMA_10,
            16 => self::SMA_11,
            17 => self::SMA_12,
            default => null,
        };
    }
}
