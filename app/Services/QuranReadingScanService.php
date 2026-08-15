<?php

namespace App\Services;

use App\Models\QuranReadingScan;
use App\Models\QuranReadingSheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuranReadingScanService
{
    private const PUBLIC_CODE_TYPES = [
        'monthly' => 1,
        'weekly' => 2,
        'surah_map' => 3,
    ];

    public function resolveSheet(string $payload): QuranReadingSheet
    {
        [$publicId, $token] = $this->parsePayload($payload);
        $sheet = QuranReadingSheet::query()->where('public_id', $publicId)->first();

        if (! $sheet || ! $sheet->verifyToken($token)) {
            throw ValidationException::withMessages([
                'sheet_payload' => 'QR lembar tidak valid atau lembar sudah dicabut.',
            ]);
        }

        return $sheet;
    }

    public function create(
        QuranReadingSheet $sheet,
        UploadedFile $image,
        ?UploadedFile $processedImage,
        string $actorType,
        ?int $actorId,
        ?string $ocrSuggestion,
    ): QuranReadingScan {
        $originalPath = $this->storeSanitizedImage($image, 'original');
        $processedPath = $processedImage
            ? $this->storeSanitizedImage($processedImage, 'processed')
            : null;

        $decodedOcr = json_decode((string) $ocrSuggestion, true);
        if (! is_array($decodedOcr)) {
            $decodedOcr = [];
        }

        return QuranReadingScan::create([
            'siswa_id' => $sheet->siswa_id,
            'sheet_id' => $sheet->id,
            'uploaded_by_type' => $actorType,
            'uploaded_by_id' => $actorId,
            'original_path' => $originalPath,
            'processed_path' => $processedPath,
            'status' => 'awaiting_confirmation',
            'metadata' => [
                'original_name' => mb_substr($image->getClientOriginalName(), 0, 200),
                'mime' => 'image/jpeg',
                'original_size' => $image->getSize(),
                'ocr_suggestion' => $decodedOcr,
                'scanner_version' => 4,
                'template_version' => (int) ($sheet->template_version ?: 1),
                'sheet_type' => $sheet->sheet_type ?: 'weekly',
            ],
        ]);
    }

    public function payload(QuranReadingSheet $sheet, string $plainToken): string
    {
        if ($sheet->sheet_type === 'surah_map') {
            return 'PKGQM:'.strtoupper(str_replace('-', '', (string) $sheet->public_id)).':'.strtoupper($plainToken);
        }

        if ($sheet->sheet_type === 'monthly') {
            return 'PKGQMB:'.strtoupper(str_replace('-', '', (string) $sheet->public_id)).':'.strtoupper($plainToken);
        }

        if ((int) $sheet->template_version >= 2) {
            return 'PKGQ:'.strtoupper(str_replace('-', '', (string) $sheet->public_id)).':'.strtoupper($plainToken);
        }

        return 'PKGQURAN:'.$sheet->public_id.':'.$plainToken;
    }

    public function publicCode(QuranReadingSheet $sheet, string $plainToken): string
    {
        $uuidHex = str_replace('-', '', strtolower((string) $sheet->public_id));
        $tokenHex = strtolower($plainToken);
        $type = self::PUBLIC_CODE_TYPES[$sheet->sheet_type ?: 'weekly'] ?? null;

        if ($type === null || ! preg_match('/^[0-9a-f]{32}$/', $uuidHex) || ! preg_match('/^[0-9a-f]{32}$/', $tokenHex)) {
            throw new \InvalidArgumentException('Data QR lembar tidak dapat dikodekan sebagai URL publik.');
        }

        $binary = chr($type).hex2bin($uuidHex).hex2bin($tokenHex);

        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    public function payloadFromPublicCode(string $code): string
    {
        if (! preg_match('/^[A-Za-z0-9_-]{44}$/', $code)) {
            throw ValidationException::withMessages(['code' => 'Tautan lembar tidak valid.']);
        }

        $binary = base64_decode(strtr($code, '-_', '+/'), true);
        if ($binary === false || strlen($binary) !== 33) {
            throw ValidationException::withMessages(['code' => 'Tautan lembar tidak valid.']);
        }

        $type = ord($binary[0]);
        $prefix = match ($type) {
            1 => 'PKGQMB',
            2 => 'PKGQ',
            3 => 'PKGQM',
            default => null,
        };
        if ($prefix === null) {
            throw ValidationException::withMessages(['code' => 'Tautan lembar tidak valid.']);
        }

        $uuid = $this->uuidFromHex(bin2hex(substr($binary, 1, 16)));
        $token = bin2hex(substr($binary, 17, 16));

        return $prefix.':'.strtoupper(str_replace('-', '', $uuid)).':'.strtoupper($token);
    }

    public function purgeFilesIfComplete(QuranReadingScan $scan): bool
    {
        $scan->loadMissing(['entries:id,scan_id,status', 'progressSubmission:id,scan_id,status']);
        if ($scan->entries->contains('status', 'pending')) {
            return false;
        }
        if ($scan->progressSubmission?->status === 'pending') {
            return false;
        }
        if ($scan->entries->isEmpty() && ! $scan->confirmed_at && $scan->status !== 'confirmed') {
            return false;
        }

        return $this->purgeFiles($scan);
    }

    public function purgeFiles(QuranReadingScan $scan): bool
    {
        if ($scan->files_purged_at) {
            return true;
        }

        $paths = array_values(array_unique(array_filter([$scan->original_path, $scan->processed_path])));
        try {
            foreach ($paths as $path) {
                if (Storage::disk('local')->exists($path) && ! Storage::disk('local')->delete($path)) {
                    throw new \RuntimeException("Gagal menghapus file scan {$path}");
                }
            }

            $scan->forceFill([
                'original_path' => null,
                'processed_path' => null,
                'files_purged_at' => now(),
            ])->save();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Gagal membersihkan file scan bacaan Al-Quran.', [
                'scan_id' => $scan->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function cleanup(): array
    {
        $summary = ['completed' => 0, 'expired' => 0, 'failed' => 0];

        QuranReadingScan::query()
            ->whereNull('files_purged_at')
            ->whereNotNull('confirmed_at')
            ->whereDoesntHave('entries', fn ($query) => $query->where('status', 'pending'))
            ->whereDoesntHave('progressSubmission', fn ($query) => $query->where('status', 'pending'))
            ->with(['entries:id,scan_id,status', 'progressSubmission:id,scan_id,status'])
            ->chunkById(100, function ($scans) use (&$summary) {
                foreach ($scans as $scan) {
                    $this->purgeFilesIfComplete($scan) ? $summary['completed']++ : $summary['failed']++;
                }
            });

        QuranReadingScan::query()
            ->whereNull('files_purged_at')
            ->whereNull('confirmed_at')
            ->where('created_at', '<=', now()->subDay())
            ->chunkById(100, function ($scans) use (&$summary) {
                foreach ($scans as $scan) {
                    if ($this->purgeFiles($scan)) {
                        $scan->forceFill(['status' => 'expired'])->save();
                        $summary['expired']++;
                    } else {
                        $summary['failed']++;
                    }
                }
            });

        return $summary;
    }

    private function parsePayload(string $payload): array
    {
        if (preg_match('/^PKGQMB:([0-9A-F]{32}):([0-9A-F]{32})$/i', trim($payload), $matches)) {
            return [$this->uuidFromHex($matches[1]), strtolower($matches[2])];
        }

        if (preg_match('/^PKGQM:([0-9A-F]{32}):([0-9A-F]{32})$/i', trim($payload), $matches)) {
            return [$this->uuidFromHex($matches[1]), strtolower($matches[2])];
        }

        if (preg_match('/^PKGQ:([0-9A-F]{32}):([0-9A-F]{32})$/i', trim($payload), $matches)) {
            return [$this->uuidFromHex($matches[1]), strtolower($matches[2])];
        }

        if (preg_match('/^PKGQURAN:([0-9a-f-]{36}):([A-Za-z0-9]+)$/i', trim($payload), $matches)) {
            return [strtolower($matches[1]), $matches[2]];
        }

        throw ValidationException::withMessages([
            'sheet_payload' => 'QR bukan lembar Tracer Bacaan Al-Qur’an PKG.',
        ]);
    }

    private function uuidFromHex(string $value): string
    {
        $hex = strtolower($value);

        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4),
            substr($hex, 16, 4), substr($hex, 20, 12),
        );
    }

    private function storeSanitizedImage(UploadedFile $file, string $suffix): string
    {
        $contents = file_get_contents($file->getRealPath());
        $image = $contents !== false && function_exists('imagecreatefromstring')
            ? @imagecreatefromstring($contents)
            : false;

        if (! $image) {
            throw ValidationException::withMessages([
                'scan_image' => 'Foto tidak dapat dibaca. Gunakan JPG, PNG, atau WebP yang valid.',
            ]);
        }

        $temporary = tmpfile();
        if ($temporary === false) {
            imagedestroy($image);
            throw ValidationException::withMessages(['scan_image' => 'Foto tidak dapat diproses.']);
        }

        $meta = stream_get_meta_data($temporary);
        imagejpeg($image, $meta['uri'], 90);
        imagedestroy($image);

        $path = 'quran-reading-scans/'.now()->format('Y/m').'/'.Str::uuid().'-'.$suffix.'.jpg';
        Storage::disk('local')->put($path, file_get_contents($meta['uri']));
        fclose($temporary);

        return $path;
    }
}
