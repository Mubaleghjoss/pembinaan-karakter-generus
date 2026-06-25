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

    public static function options(): array
    {
        return [
            self::SMP_7 => 'SMP 7',
            self::SMP_8 => 'SMP 8',
            self::SMP_9 => 'SMP 9',
            self::SMA_10 => 'SMA 10',
            self::SMA_11 => 'SMA 11',
            self::SMA_12 => 'SMA 12',
        ];
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
