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

    public static function bossAvatarOptions(): array
    {
        return [
            ['value' => "\u{1F479}", 'icon' => "\u{1F479}", 'label' => 'Raksasa'],
            ['value' => "\u{1F47F}", 'icon' => "\u{1F47F}", 'label' => 'Iblis'],
            ['value' => "\u{1F409}", 'icon' => "\u{1F409}", 'label' => 'Naga'],
            ['value' => "\u{1F480}", 'icon' => "\u{1F480}", 'label' => 'Tengkorak'],
            ['value' => "\u{1F47A}", 'icon' => "\u{1F47A}", 'label' => 'Goblin'],
            ['value' => "\u{1F9DF}", 'icon' => "\u{1F9DF}", 'label' => 'Zombie'],
        ];
    }

    /**
     * Normalisasi konfigurasi bos map (dari admin) → struktur aman untuk klien.
     */
    public static function normalizeBossConfig(?array $config, int $gridSize = 10): array
    {
        $config = $config ?? [];
        $max = max(1, $gridSize) - 1;

        $clampCoord = fn ($v) => max(0, min($max, (int) $v));

        $avatar = $config['avatar'] ?? self::bossAvatarOptions()[0]['value'];
        $bossAvatarValues = array_column(self::bossAvatarOptions(), 'value');
        if (! in_array($avatar, $bossAvatarValues, true)) {
            $avatar = self::bossAvatarOptions()[0]['value'];
        }

        $maxHp = (int) ($config['max_hp'] ?? 300);
        $maxHp = max(50, min(5000, $maxHp));

        $spawn = $config['spawn'] ?? ['x' => $max, 'y' => 0];
        $safe = $config['safe_zone'] ?? ['x' => 0, 'y' => $max, 'radius' => 1];

        $moveSpeed = $config['move_speed'] ?? 'normal';
        if (! in_array($moveSpeed, ['slow', 'normal', 'fast'], true)) {
            $moveSpeed = 'normal';
        }

        return [
            'nama' => trim((string) ($config['nama'] ?? 'Bos')) ?: 'Bos',
            'avatar' => $avatar,
            'max_hp' => $maxHp,
            'size' => max(2, min(5, (int) ($config['size'] ?? 3))),
            'contact_damage' => max(1, min(3, (int) ($config['contact_damage'] ?? 1))),
            'move_speed' => $moveSpeed,
            'reward_points' => max(0, min(200, (int) ($config['reward_points'] ?? 25))),
            'bullet_damage' => max(1, min(50, (int) ($config['bullet_damage'] ?? 10))),
            'player_lives' => max(1, min(9, (int) ($config['player_lives'] ?? 3))),
            // 0 = tidak respawn (kalah = selesai). >0 = bos hidup lagi setelah N detik (lebih menantang).
            'respawn_seconds' => max(0, min(60, (int) ($config['respawn_seconds'] ?? 0))),
            // Berapa kali bos bisa respawn (kesempatan total = respawn_count+1). 0 = tanpa batas selama sesi.
            'respawn_count' => max(0, min(20, (int) ($config['respawn_count'] ?? 3))),
            // Setiap respawn, HP bos naik persen ini (mis. 20 = +20% tiap bangkit). Bikin makin sulit.
            'respawn_hp_growth' => max(0, min(200, (int) ($config['respawn_hp_growth'] ?? 25))),
            // === Fitur tantangan lanjutan ===
            // Bos menembak proyektil ke pemain.
            'boss_shoots' => (bool) ($config['boss_shoots'] ?? true),
            // Tiap respawn, kecepatan gerak bos naik persen ini (mempercepat langkah).
            'respawn_speed_growth' => max(0, min(80, (int) ($config['respawn_speed_growth'] ?? 15))),
            // Zona aman menyusut (radius -1) tiap kali bos respawn.
            'shrink_safezone' => (bool) ($config['shrink_safezone'] ?? true),
            // Minion kecil muncul saat HP bos < 50%.
            'spawn_minions' => (bool) ($config['spawn_minions'] ?? true),
            // Jumlah drop darah (pemulih nyawa) di peta saat lawan bos.
            'health_drops_count' => max(0, min(10, (int) ($config['health_drops_count'] ?? 2))),
            // Jumlah drop energi (untuk skill) di peta saat lawan bos.
            'energy_drops_count' => max(0, min(10, (int) ($config['energy_drops_count'] ?? 3))),
            'spawn' => [
                'x' => $clampCoord($spawn['x'] ?? $max),
                'y' => $clampCoord($spawn['y'] ?? 0),
            ],
            'safe_zone' => [
                'x' => $clampCoord($safe['x'] ?? 0),
                'y' => $clampCoord($safe['y'] ?? $max),
                'radius' => max(0, min(4, (int) ($safe['radius'] ?? 1))),
            ],
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
