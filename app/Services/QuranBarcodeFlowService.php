<?php

namespace App\Services;

use App\Models\QuranReadingEntry;
use App\Models\QuranReadingSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuranBarcodeFlowService
{
    private const SESSION_KEY = 'quran_barcode_flows';

    private const TTL_SECONDS = 1800;

    public function create(Request $request, QuranReadingSheet $sheet, string $context): array
    {
        $flows = $this->activeFlows($request);
        $id = Str::random(40);
        $flows[$id] = [
            'sheet_id' => $sheet->id,
            'siswa_id' => $sheet->siswa_id,
            'context' => $context,
            'created_at' => now()->timestamp,
            'completed_entry_id' => null,
        ];
        $request->session()->put(self::SESSION_KEY, $flows);

        return ['id' => $id, 'expires_at' => now()->addSeconds(self::TTL_SECONDS)->toIso8601String()];
    }

    public function get(Request $request, string $id, string $context): array
    {
        $flows = $this->activeFlows($request);
        $flow = $flows[$id] ?? null;
        if (! is_array($flow) || ! hash_equals((string) ($flow['context'] ?? ''), $context)) {
            throw ValidationException::withMessages([
                'flow_id' => 'Sesi barcode sudah berakhir. Scan barcode kembali.',
            ]);
        }

        return $flow;
    }

    public function markCompleted(Request $request, string $id, QuranReadingEntry $entry): void
    {
        $flows = $this->activeFlows($request);
        if (! isset($flows[$id])) {
            return;
        }

        $flows[$id]['completed_entry_id'] = $entry->id;
        $request->session()->put(self::SESSION_KEY, $flows);
    }

    private function activeFlows(Request $request): array
    {
        $cutoff = now()->subSeconds(self::TTL_SECONDS)->timestamp;
        $flows = collect($request->session()->get(self::SESSION_KEY, []))
            ->filter(fn ($flow) => is_array($flow) && (int) ($flow['created_at'] ?? 0) >= $cutoff)
            ->take(-20)
            ->all();
        $request->session()->put(self::SESSION_KEY, $flows);

        return $flows;
    }
}
