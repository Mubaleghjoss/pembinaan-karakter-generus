<?php

namespace App\Http\Middleware;

use App\Models\PamongActivityLog;
use Closure;
use Illuminate\Http\Request;

class LogPamongActivity
{
    /**
     * Routes to log with their action and module mapping.
     * Format: 'METHOD:path_pattern' => ['action', 'module', 'description']
     */
    protected array $routeMap = [
        // Presensi
        'POST:presensi/store' => ['action' => 'create', 'module' => 'presensi', 'desc' => 'Membuat data presensi'],
        'POST:presensi/verify' => ['action' => 'verify', 'module' => 'presensi', 'desc' => 'Memverifikasi presensi'],
        'PUT:presensi' => ['action' => 'edit', 'module' => 'presensi', 'desc' => 'Mengedit presensi'],
        'DELETE:presensi' => ['action' => 'delete', 'module' => 'presensi', 'desc' => 'Menghapus presensi'],

        // Siswa
        'POST:siswa' => ['action' => 'create', 'module' => 'siswa', 'desc' => 'Menambah data siswa'],
        'PUT:siswa' => ['action' => 'edit', 'module' => 'siswa', 'desc' => 'Mengedit data siswa'],
        'DELETE:siswa' => ['action' => 'delete', 'module' => 'siswa', 'desc' => 'Menghapus data siswa'],

        // Tracer Karakter - Verifikasi & Aksi
        'POST:tracer-karakter/bulk-action' => ['action' => 'verify', 'module' => 'tracer_karakter', 'desc' => 'Bulk action tracer karakter'],
        'POST:tracer-karakter' => ['action' => 'verify', 'module' => 'tracer_karakter', 'desc' => 'Memverifikasi tracer karakter'],
        'PUT:tracer-karakter' => ['action' => 'edit', 'module' => 'tracer_karakter', 'desc' => 'Mengedit tracer karakter'],
        'DELETE:tracer-karakter' => ['action' => 'delete', 'module' => 'tracer_karakter', 'desc' => 'Menghapus tracer karakter'],

        // Karakter Harian
        'POST:karakter-harian' => ['action' => 'create', 'module' => 'tracer_karakter', 'desc' => 'Mencatat karakter harian'],

        // Cek Tugas PKG
        'POST:tugas-pkg' => ['action' => 'verify', 'module' => 'tracer_karakter', 'desc' => 'Memverifikasi tugas PKG'],

        // Materi
        'POST:materi' => ['action' => 'create', 'module' => 'materi', 'desc' => 'Membuat materi'],
        'PUT:materi' => ['action' => 'edit', 'module' => 'materi', 'desc' => 'Mengedit materi'],
        'DELETE:materi' => ['action' => 'delete', 'module' => 'materi', 'desc' => 'Menghapus materi'],

        // Legacy endpoint Tugas PKG
        'POST:pr' => ['action' => 'create', 'module' => 'pr', 'desc' => 'Akses endpoint lama tugas PKG'],
        'PUT:pr' => ['action' => 'edit', 'module' => 'pr', 'desc' => 'Mengubah endpoint lama tugas PKG'],
        'DELETE:pr' => ['action' => 'delete', 'module' => 'pr', 'desc' => 'Menghapus data pada endpoint lama tugas PKG'],

        // Berita
        'POST:berita' => ['action' => 'create', 'module' => 'berita', 'desc' => 'Membuat berita'],
        'PUT:berita' => ['action' => 'edit', 'module' => 'berita', 'desc' => 'Mengedit berita'],
        'DELETE:berita' => ['action' => 'delete', 'module' => 'berita', 'desc' => 'Menghapus berita'],

        // Chat
        'POST:pamong/chat' => ['action' => 'send', 'module' => 'chat', 'desc' => 'Mengirim chat'],
        'POST:broadcast' => ['action' => 'broadcast', 'module' => 'chat', 'desc' => 'Mengirim broadcast'],

        // Laporan Penyaksian
        'POST:laporan-penyaksian' => ['action' => 'create', 'module' => 'laporan_penyaksian', 'desc' => 'Membuat laporan penyaksian'],
        'PUT:laporan-penyaksian' => ['action' => 'edit', 'module' => 'laporan_penyaksian', 'desc' => 'Mengedit laporan penyaksian'],
        'DELETE:laporan-penyaksian' => ['action' => 'delete', 'module' => 'laporan_penyaksian', 'desc' => 'Menghapus laporan penyaksian'],

        // Cek Kehadiran PKG
        'POST:cek-kehadiran' => ['action' => 'verify', 'module' => 'presensi', 'desc' => 'Cek kehadiran PKG'],
        'DELETE:cek-kehadiran' => ['action' => 'delete', 'module' => 'presensi', 'desc' => 'Menghapus data kehadiran'],

        // Gamifikasi
        'POST:gamification' => ['action' => 'create', 'module' => 'gamification', 'desc' => 'Aksi gamifikasi'],
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log for authenticated pamong users on successful write operations
        $user = $request->user();
        if (!$user || !$user->usesPamongPermissionSystem()) {
            return $response;
        }

        // Only log POST, PUT, PATCH, DELETE (write operations)
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // Only log successful responses (2xx and 3xx)
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            return $response;
        }

        // Try to match the request to a known action
        $path = $request->path();
        $method = $request->method();

        // Try exact-ish match with routeMap (more specific patterns first)
        foreach ($this->routeMap as $pattern => $info) {
            [$patternMethod, $patternPath] = explode(':', $pattern, 2);

            // Match method and path contains pattern
            if ($method === $patternMethod && str_contains($path, $patternPath)) {
                PamongActivityLog::log(
                    userId: $user->id,
                    action: $info['action'],
                    description: $info['desc'],
                    module: $info['module'],
                    metadata: ['path' => $path, 'method' => $method],
                    ipAddress: $request->ip()
                );
                return $response;
            }

            // PATCH is treated same as PUT
            if ($patternMethod === 'PUT' && $method === 'PATCH' && str_contains($path, $patternPath)) {
                PamongActivityLog::log(
                    userId: $user->id,
                    action: $info['action'],
                    description: $info['desc'],
                    module: $info['module'],
                    metadata: ['path' => $path, 'method' => $method],
                    ipAddress: $request->ip()
                );
                return $response;
            }
        }

        return $response;
    }
}
