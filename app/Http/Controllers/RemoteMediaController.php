<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RemoteMediaController extends Controller
{
    public function show(Request $request)
    {
        $path = $this->normalizePublicMediaPath($request->query('path'));

        if (!$path) {
            abort(404);
        }

        if ($this->isExistingUsablePublicMediaPath($path)) {
            return $this->localFileResponse($path);
        }

        $serverUrl = rtrim((string) Setting::get('sync_server_url', ''), '/');
        $apiKey = (string) Setting::get('sync_api_key', '');

        if ($serverUrl === '' || $apiKey === '') {
            abort(404);
        }

        $response = Http::timeout(60)
            ->accept('*/*')
            ->withHeaders(['X-Sync-Key' => $apiKey])
            ->get($serverUrl . '/api/sync/media', ['path' => $path]);

        if (!$this->isUsableMediaResponse($response, $path)) {
            abort(404);
        }

        Storage::disk('public')->put($path, $response->body());

        return $this->localFileResponse($path);
    }

    protected function localFileResponse(string $path)
    {
        $absolutePath = Storage::disk('public')->path($path);

        if (!is_file($absolutePath)) {
            abort(404);
        }

        return response()->file($absolutePath, [
            'Content-Type' => $this->mediaContentType($path),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    protected function normalizePublicMediaPath($path): ?string
    {
        if (!$path || !is_string($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '' || Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/[#?].*$/', '', $path);
        $path = preg_replace('#^/?(?:storage|public)/#', '', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '../') || str_starts_with($path, '/')) {
            return null;
        }

        return $path;
    }

    protected function isExistingUsablePublicMediaPath(?string $path): bool
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            return false;
        }

        try {
            $absolutePath = Storage::disk('public')->path($path);
            if (!is_file($absolutePath) || filesize($absolutePath) <= 0) {
                return false;
            }

            $handle = fopen($absolutePath, 'rb');
            if (!$handle) {
                return false;
            }

            $prefix = fread($handle, 512) ?: '';
            fclose($handle);

            return $this->isUsableMediaBody($path, $prefix);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isUsableMediaResponse($response, ?string $path = null): bool
    {
        if (!$response->successful()) {
            return false;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (str_contains($contentType, 'text/html')) {
            return false;
        }

        $prefix = strtolower(ltrim(substr($response->body(), 0, 80)));

        if (str_starts_with($prefix, '<!doctype html')
            || str_starts_with($prefix, '<html')
            || str_starts_with($prefix, '{')) {
            return false;
        }

        return $this->isUsableMediaBody($path, $response->body());
    }

    protected function isUsableMediaBody(?string $path, string $body): bool
    {
        if ($body === '') {
            return false;
        }

        $prefix = substr($body, 0, 512);
        $textPrefix = strtolower(ltrim($prefix));

        if (str_starts_with($textPrefix, '<!doctype html')
            || str_starts_with($textPrefix, '<html')
            || str_starts_with($textPrefix, '{')) {
            return false;
        }

        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => $this->hasImageSignature($prefix, $textPrefix),
            'pdf' => str_starts_with($prefix, '%PDF-'),
            'mp3', 'wav', 'ogg', 'oga', 'webm', 'm4a', 'mp4', 'aac' => $this->hasAudioSignature($prefix),
            default => true,
        };
    }

    protected function hasImageSignature(string $prefix, string $textPrefix): bool
    {
        return str_starts_with($prefix, "\xFF\xD8")
            || str_starts_with($prefix, "\x89PNG\r\n\x1A\n")
            || str_starts_with($prefix, 'GIF87a')
            || str_starts_with($prefix, 'GIF89a')
            || (substr($prefix, 0, 4) === 'RIFF' && substr($prefix, 8, 4) === 'WEBP')
            || str_contains(substr($textPrefix, 0, 240), '<svg');
    }

    protected function hasAudioSignature(string $prefix): bool
    {
        return str_starts_with($prefix, 'ID3')
            || $this->startsWithMpegFrame($prefix)
            || (substr($prefix, 0, 4) === 'RIFF' && substr($prefix, 8, 4) === 'WAVE')
            || str_starts_with($prefix, 'OggS')
            || str_starts_with($prefix, "\x1A\x45\xDF\xA3")
            || substr($prefix, 4, 4) === 'ftyp'
            || $this->startsWithAdtsFrame($prefix);
    }

    protected function mediaContentType(string $path): string
    {
        $prefix = '';

        try {
            $absolutePath = Storage::disk('public')->path($path);
            $handle = is_file($absolutePath) ? fopen($absolutePath, 'rb') : false;
            if ($handle) {
                $prefix = fread($handle, 16) ?: '';
                fclose($handle);
            }
        } catch (\Throwable) {
            $prefix = '';
        }

        return $this->contentTypeFromSignature($prefix)
            ?: Storage::disk('public')->mimeType($path)
            ?: 'application/octet-stream';
    }

    protected function contentTypeFromSignature(string $prefix): ?string
    {
        if (str_starts_with($prefix, "\xFF\xD8")) {
            return 'image/jpeg';
        }

        if (str_starts_with($prefix, "\x89PNG\r\n\x1A\n")) {
            return 'image/png';
        }

        if (str_starts_with($prefix, 'GIF87a') || str_starts_with($prefix, 'GIF89a')) {
            return 'image/gif';
        }

        if (substr($prefix, 0, 4) === 'RIFF' && substr($prefix, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        if (str_starts_with($prefix, 'ID3') || $this->startsWithMpegFrame($prefix)) {
            return 'audio/mpeg';
        }

        if (substr($prefix, 0, 4) === 'RIFF' && substr($prefix, 8, 4) === 'WAVE') {
            return 'audio/wav';
        }

        if (str_starts_with($prefix, 'OggS')) {
            return 'audio/ogg';
        }

        if (str_starts_with($prefix, "\x1A\x45\xDF\xA3")) {
            return 'audio/webm';
        }

        if (substr($prefix, 4, 4) === 'ftyp') {
            return 'audio/mp4';
        }

        if ($this->startsWithAdtsFrame($prefix)) {
            return 'audio/aac';
        }

        return null;
    }

    protected function startsWithMpegFrame(string $prefix): bool
    {
        return strlen($prefix) >= 2
            && ord($prefix[0]) === 0xFF
            && (ord($prefix[1]) & 0xE0) === 0xE0;
    }

    protected function startsWithAdtsFrame(string $prefix): bool
    {
        return strlen($prefix) >= 2
            && ord($prefix[0]) === 0xFF
            && (ord($prefix[1]) & 0xF0) === 0xF0;
    }
}
