<?php

namespace App\Services;

use App\Models\RpgMap;
use Illuminate\Support\Facades\Cache;

/**
 * Ephemeral, per-student InkWave board. This never participates in RPG scores.
 */
class InkWaveTerritoryService
{
    private const TTL_HOURS = 4;
    private const MAX_CELLS = 144;
    private const MAX_EVENT_IDS = 64;

    public function snapshot(int $siswaId, RpgMap $map): array
    {
        return $this->payload($this->state($siswaId, $map), $map);
    }

    public function applyPulse(int $siswaId, RpgMap $map, string $eventId, int $x, int $y): array
    {
        $key = $this->key($siswaId, $map->id);
        $lock = Cache::lock("{$key}:lock", 5);

        if (! $lock->get()) {
            return $this->snapshot($siswaId, $map);
        }

        try {
            $state = $this->state($siswaId, $map);
            if (in_array($eventId, $state['event_ids'], true)) {
                return $this->payload($state, $map);
            }

            foreach ($this->pulseCells($map, $x, $y) as $cell) {
                if (count($state['cells']) >= self::MAX_CELLS && ! isset($state['cells'][$cell])) {
                    continue;
                }
                $state['cells'][$cell] = 'pkg';
            }

            $state['event_ids'][] = $eventId;
            $state['event_ids'] = array_slice($state['event_ids'], -self::MAX_EVENT_IDS);
            $state['version']++;
            Cache::put($key, $state, now()->addHours(self::TTL_HOURS));

            return $this->payload($state, $map);
        } finally {
            $lock->release();
        }
    }

    public function clear(int $siswaId, int $mapId): void
    {
        Cache::forget($this->key($siswaId, $mapId));
    }

    public function key(int $siswaId, int $mapId): string
    {
        return "inkwave:territory:{$siswaId}:{$mapId}";
    }

    private function state(int $siswaId, RpgMap $map): array
    {
        return Cache::get($this->key($siswaId, $map->id), [
            'version' => 0,
            'cells' => [],
            'event_ids' => [],
        ]);
    }

    private function pulseCells(RpgMap $map, int $x, int $y): array
    {
        $cells = [];
        for ($dy = -1; $dy <= 1; $dy++) {
            for ($dx = -1; $dx <= 1; $dx++) {
                $tileX = $x + $dx;
                $tileY = $y + $dy;
                if ($tileX < 0 || $tileY < 0 || $tileX >= $map->grid_size || $tileY >= $map->grid_size || $this->isObstacle($map, $tileX, $tileY)) {
                    continue;
                }
                $cells[] = "{$tileX}:{$tileY}";
            }
        }

        return $cells;
    }

    private function isObstacle(RpgMap $map, int $x, int $y): bool
    {
        foreach ($map->obstacles ?? [] as $obstacle) {
            if ((int) ($obstacle['x'] ?? -1) === $x && (int) ($obstacle['y'] ?? -1) === $y) {
                return true;
            }
        }

        return false;
    }

    private function payload(array $state, RpgMap $map): array
    {
        $validTiles = min((int) $map->grid_size * (int) $map->grid_size, self::MAX_CELLS);
        foreach ($map->obstacles ?? [] as $obstacle) {
            if ((int) ($obstacle['x'] ?? -1) >= 0 && (int) ($obstacle['x'] ?? -1) < $map->grid_size && (int) ($obstacle['y'] ?? -1) >= 0 && (int) ($obstacle['y'] ?? -1) < $map->grid_size) {
                $validTiles--;
            }
        }
        $validTiles = max(1, $validTiles);
        $cells = array_slice($state['cells'] ?? [], 0, self::MAX_CELLS, true);

        return [
            'version' => (int) ($state['version'] ?? 0),
            'cells' => $cells,
            'pkg_cells' => count($cells),
            'neutral_cells' => max(0, $validTiles - count($cells)),
            'coverage_percent' => (int) floor((count($cells) / $validTiles) * 100),
        ];
    }
}
