<?php

namespace App\Services;

use App\Models\QuranReadingEntry;
use App\Models\QuranReadingSheet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuranMobileBarcodeFlowService
{
    private const CONTEXT = 'mobile_quran_barcode';

    private const TTL_SECONDS = 1800;

    private const KEY_PREFIX = 'quran:mobile-barcode-flow:';

    public function create(string $actorType, int $actorId, QuranReadingSheet $sheet): array
    {
        $id = Str::random(40);
        $expiresAt = now()->addSeconds(self::TTL_SECONDS);

        Cache::put($this->key($id), [
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'context' => self::CONTEXT,
            'sheet_id' => $sheet->id,
            'siswa_id' => $sheet->siswa_id,
            'expires_at' => $expiresAt->timestamp,
            'completed_entry_id' => null,
        ], $expiresAt);

        return [
            'id' => $id,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public function get(string $id, string $actorType, int $actorId): array
    {
        $flow = Cache::get($this->key($id));

        if (! is_array($flow)
            || ($flow['context'] ?? null) !== self::CONTEXT
            || ($flow['actor_type'] ?? null) !== $actorType
            || (int) ($flow['actor_id'] ?? 0) !== $actorId
            || (int) ($flow['expires_at'] ?? 0) <= now()->timestamp) {
            throw ValidationException::withMessages([
                'flow_id' => 'Flow barcode tidak valid atau sudah kedaluwarsa. Scan lembar kembali.',
            ]);
        }

        return $flow;
    }

    public function markCompleted(string $id, array $flow, QuranReadingEntry $entry): void
    {
        $expiresAt = (int) $flow['expires_at'];
        if ($expiresAt <= now()->timestamp) {
            return;
        }

        $flow['completed_entry_id'] = $entry->id;
        Cache::put($this->key($id), $flow, now()->setTimestamp($expiresAt));
    }

    public function lock(string $id, callable $callback): mixed
    {
        return Cache::lock($this->key($id).':lock', 10)->block(5, $callback);
    }

    private function key(string $id): string
    {
        return self::KEY_PREFIX.hash('sha256', $id);
    }
}
