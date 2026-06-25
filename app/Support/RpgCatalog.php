<?php

namespace App\Support;

class RpgCatalog
{
    public static function npcAvatarOptions(): array
    {
        return [
            ['value' => "\u{1F9D9}", 'icon' => "\u{1F9D9}", 'label' => 'Penyihir'],
            ['value' => "\u{1F9DA}", 'icon' => "\u{1F9DA}", 'label' => 'Peri'],
            ['value' => "\u{1F916}", 'icon' => "\u{1F916}", 'label' => 'Robot'],
            ['value' => "\u{1F98A}", 'icon' => "\u{1F98A}", 'label' => 'Rubah'],
            ['value' => "\u{1F981}", 'icon' => "\u{1F981}", 'label' => 'Singa'],
            ['value' => "\u{1F43C}", 'icon' => "\u{1F43C}", 'label' => 'Panda'],
            ['value' => "\u{1F989}", 'icon' => "\u{1F989}", 'label' => 'Burung'],
            ['value' => "\u{1F9DE}", 'icon' => "\u{1F9DE}", 'label' => 'Roh'],
            ['value' => "\u{1F9DC}", 'icon' => "\u{1F9DC}", 'label' => 'Penjaga Air'],
            ['value' => "\u{1F409}", 'icon' => "\u{1F409}", 'label' => 'Naga'],
            ['value' => "\u{1F9D1}\u{200D}\u{1F680}", 'icon' => "\u{1F9D1}\u{200D}\u{1F680}", 'label' => 'Penjelajah'],
            ['value' => "\u{1F9E0}", 'icon' => "\u{1F9E0}", 'label' => 'Bijak'],
        ];
    }

    public static function enemyAvatarOptions(): array
    {
        return [
            ['value' => "\u{1F47E}", 'icon' => "\u{1F47E}", 'label' => 'Alien'],
            ['value' => "\u{1F47B}", 'icon' => "\u{1F47B}", 'label' => 'Hantu'],
            ['value' => "\u{1F916}", 'icon' => "\u{1F916}", 'label' => 'Robot'],
            ['value' => "\u{1F525}", 'icon' => "\u{1F525}", 'label' => 'Api'],
            ['value' => "\u{1F409}", 'icon' => "\u{1F409}", 'label' => 'Naga'],
        ];
    }

    public static function playerAvatarOptions(): array
    {
        return [
            "\u{1F9D1}\u{200D}\u{1F393}",
            "\u{1F466}",
            "\u{1F467}",
            "\u{1F9B8}\u{200D}\u{2642}\u{FE0F}",
            "\u{1F9B8}\u{200D}\u{2640}\u{FE0F}",
            "\u{1F9D9}\u{200D}\u{2642}\u{FE0F}",
            "\u{1F9D9}\u{200D}\u{2640}\u{FE0F}",
            "\u{1F977}",
            "\u{1F3C3}\u{200D}\u{2642}\u{FE0F}",
            "\u{1F3C3}\u{200D}\u{2640}\u{FE0F}",
            "\u{1F9D1}\u{200D}\u{1F680}",
            "\u{1F98A}",
            "\u{1F431}",
            "\u{1F43B}",
            "\u{1F43C}",
            "\u{1F438}",
            "\u{1F981}",
            "\u{1F409}",
        ];
    }

    public static function enemySpeedOptions(): array
    {
        return [
            ['value' => 'slow', 'label' => 'Lambat'],
            ['value' => 'normal', 'label' => 'Normal'],
            ['value' => 'fast', 'label' => 'Cepat'],
        ];
    }

    public static function enemyIntelligenceOptions(): array
    {
        return [
            ['value' => 'low', 'label' => 'Santai'],
            ['value' => 'normal', 'label' => 'Normal'],
            ['value' => 'high', 'label' => 'Pintar'],
        ];
    }

    public static function pickupIcons(): array
    {
        return [
            'shield' => "\u{1F6E1}\u{FE0F}",
            'ammo' => "\u{1F3AF}",
        ];
    }

    public static function defaultEnemy(int $x, int $y): array
    {
        return [
            'x' => $x,
            'y' => $y,
            'avatar' => self::enemyAvatarOptions()[0]['value'],
            'speed_level' => 'normal',
            'intelligence_level' => 'normal',
        ];
    }

    public static function resolveNpcAvatar(?string $avatar): string
    {
        return self::resolveAvatar($avatar, self::legacyNpcMap(), self::npcAvatarOptions()[0]['value']);
    }

    public static function resolveEnemyAvatar(?string $avatar): string
    {
        return self::resolveAvatar($avatar, self::legacyEnemyMap(), self::enemyAvatarOptions()[0]['value']);
    }

    public static function resolvePlayerAvatar(?string $avatar): string
    {
        return self::resolveAvatar($avatar, self::legacyPlayerMap(), self::playerAvatarOptions()[0]);
    }

    public static function normalizeEnemies(?array $enemies): array
    {
        return collect($enemies ?? [])
            ->map(function ($enemy) {
                return [
                    'x' => (int) ($enemy['x'] ?? 0),
                    'y' => (int) ($enemy['y'] ?? 0),
                    'avatar' => self::resolveEnemyAvatar($enemy['avatar'] ?? null),
                    'speed_level' => self::normalizeSpeedLevel($enemy['speed_level'] ?? null),
                    'intelligence_level' => self::normalizeIntelligenceLevel($enemy['intelligence_level'] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    public static function npcAvatarLookup(): array
    {
        return self::legacyNpcMap();
    }

    public static function enemyAvatarLookup(): array
    {
        return self::legacyEnemyMap();
    }

    public static function normalizeSpeedLevel(?string $value): string
    {
        return in_array($value, ['slow', 'normal', 'fast'], true) ? $value : 'normal';
    }

    public static function normalizeIntelligenceLevel(?string $value): string
    {
        return in_array($value, ['low', 'normal', 'high'], true) ? $value : 'normal';
    }

    protected static function resolveAvatar(?string $avatar, array $legacyMap, string $fallback): string
    {
        if (!$avatar) {
            return $fallback;
        }

        if (isset($legacyMap[$avatar])) {
            return $legacyMap[$avatar];
        }

        return $avatar;
    }

    protected static function legacyNpcMap(): array
    {
        return [
            'N1' => "\u{1F9D9}",
            'N2' => "\u{1F9DA}",
            'N3' => "\u{1F916}",
            'N4' => "\u{1F98A}",
            'N5' => "\u{1F981}",
            'N6' => "\u{1F43C}",
            'N7' => "\u{1F989}",
            'RB' => "\u{1F916}",
            'M1' => "\u{1F9DE}",
            'M2' => "\u{1F9DC}",
            'M3' => "\u{1F9E0}",
            'D1' => "\u{1F409}",
            'F1' => "\u{1F9DA}",
            'A1' => "\u{1F9D9}\u{200D}\u{2640}\u{FE0F}",
            'B1' => "\u{1F43B}",
        ];
    }

    protected static function legacyEnemyMap(): array
    {
        return [
            'EN' => "\u{1F47E}",
            'RB' => "\u{1F916}",
            'GH' => "\u{1F47B}",
            'DR' => "\u{1F409}",
        ];
    }

    protected static function legacyPlayerMap(): array
    {
        return [
            'default' => self::playerAvatarOptions()[0],
        ];
    }
}
