<?php

namespace App\Support;

class ParticipantProfileOptions
{
    public const SAWAH_DALAM_1 = 'sawah dalam 1';
    public const SAWAH_DALAM_2 = 'sawah dalam 2';
    public const PANUNGGANGAN_UTARA = 'panunggangan utara';
    public const PAKULONAN = 'pakulonan';

    public static function groups(): array
    {
        return [
            self::SAWAH_DALAM_1 => 'Sawah Dalam 1',
            self::SAWAH_DALAM_2 => 'Sawah Dalam 2',
            self::PANUNGGANGAN_UTARA => 'Panunggangan Utara',
            self::PAKULONAN => 'Pakulonan',
        ];
    }

    public static function normalizeGroup(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return array_key_exists($normalized, self::groups()) ? $normalized : null;
    }
}
