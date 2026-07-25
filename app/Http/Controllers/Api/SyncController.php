<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controller for data sync/export between online and local servers.
 * 
 * The export endpoint streams all table data as JSON for the local server to pull.
 * Protected by X-Sync-Key middleware.
 */
class SyncController extends Controller
{
    /**
     * Tables to export, ordered by foreign key dependencies (parent tables first).
     */
    protected array $exportTables = [
        'roles',
        'users',
        'kelas',
        'siswa',
        'pamong',
        'kelas_pamong',
        'pamong_siswa',
        'pamong_permissions',
        'karakter',
        'attendance_schedules',
        'presensi',
        'pamong_presensi',
        'siswa_karakter_checklist',
        'tracer_karakter',
        'levels',
        'level_reward_templates',
        'badges',
        'point_periods',
        'siswa_points',
        'point_transactions',
        'user_badges',
        'rpg_maps',
        'rpg_npcs',
        'rpg_characters',
        'rpg_game_sessions',
        'berita',
        'settings',
        'theme_settings',
        'ortu_comments',
        'share_infos',
        'materi',
        'materi_targets',
        'siswa_materi_target_progress',
        'pekerjaan_rumah',
        'pr_submissions',
        'chats',
        'chat_groups',
        'chat_group_members',
        'chat_group_messages',
        'kanban_columns',
        'catatan_rapats',
        'catatan_rapat_logs',
        'laporan_penyaksian',
        'schedule_reminders',
        'teacher_availability_invites',
        'teacher_profiles',
        'teacher_schedule_templates',
        'teacher_schedule_periods',
        'teacher_schedule_sessions',
        'teacher_schedule_assignments',
        'materi_rpp_journals',
        'materi_rpp_journal_assignees',
        'pamong_activity_logs',
        'persiapan_acaras',
    ];

    /**
     * Export all table data as JSON.
     * 
     * Returns a JSON object with each table name as key and its rows as value.
     * Also includes metadata (timestamp, table counts, server info).
     */
    public function export(Request $request): JsonResponse
    {
        $requestedTables = $request->input('tables');
        
        $tablesToExport = $this->exportTables;
        
        // If specific tables requested, filter
        if ($requestedTables && $requestedTables !== 'all') {
            $requestedList = is_array($requestedTables) ? $requestedTables : explode(',', $requestedTables);
            $tablesToExport = array_intersect($tablesToExport, $requestedList);
        }

        $data = [];
        $meta = [
            'exported_at' => now()->toIso8601String(),
            'server' => config('app.url', 'unknown'),
            'tables' => [],
        ];

        foreach ($tablesToExport as $table) {
            if (!Schema::hasTable($table)) {
                $meta['tables'][$table] = ['status' => 'skipped', 'reason' => 'table not found'];
                continue;
            }

            try {
                $rows = DB::table($table)->get()->toArray();
                
                // Convert stdClass objects to arrays for cleaner JSON
                $data[$table] = array_map(function ($row) {
                    return (array) $row;
                }, $rows);

                $meta['tables'][$table] = [
                    'status' => 'ok',
                    'count' => count($rows),
                ];
            } catch (\Exception $e) {
                $meta['tables'][$table] = [
                    'status' => 'error',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'meta' => $meta,
            'data' => $data,
        ]);
    }

    /**
     * Serve a referenced public media file through the protected sync API.
     */
    public function media(Request $request)
    {
        $path = $this->normalizePublicMediaPath($request->query('path'));

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'Path media tidak valid.',
            ], 422);
        }

        $file = $this->resolveMediaFile($path);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File media tidak ditemukan.',
                'path' => $path,
                'checked' => $this->mediaPathStatus($path),
            ], 404);
        }

        $response = response()->file($file['absolute_path'], [
            'Content-Type' => $file['mime_type'] ?: 'application/octet-stream',
        ]);
        $response->headers->set('X-Sync-Media-Path', $path);

        return $response;
    }

    /**
     * Diagnose where a referenced media path exists on the online server.
     */
    public function mediaStatus(Request $request): JsonResponse
    {
        $path = $this->normalizePublicMediaPath($request->query('path'));

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'Path media tidak valid.',
            ], 422);
        }

        $status = $this->mediaPathStatus($path);
        $found = collect($status)->firstWhere('exists', true);

        return response()->json([
            'success' => true,
            'path' => $path,
            'found' => (bool) $found,
            'matched_location' => $found['label'] ?? null,
            'resolved' => $this->resolveMediaFile($path),
            'checked' => $status,
            'directories' => $this->mediaDirectoryStatus($path),
        ]);
    }

    /**
     * Simple ping/test endpoint to verify connectivity.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Koneksi berhasil!',
            'server' => config('app.url', 'unknown'),
            'time' => now()->toIso8601String(),
            'tables_available' => count(array_filter($this->exportTables, function ($table) {
                return Schema::hasTable($table);
            })),
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

    protected function resolveMediaFile(string $path): ?array
    {
        foreach ($this->mediaPathCandidates($path) as $candidate) {
            if (!is_file($candidate['absolute_path']) || !is_readable($candidate['absolute_path'])) {
                continue;
            }

            return [
                'label' => $candidate['label'],
                'absolute_path' => $candidate['absolute_path'],
                'mime_type' => mime_content_type($candidate['absolute_path']) ?: null,
                'size' => filesize($candidate['absolute_path']) ?: 0,
            ];
        }

        return $this->resolveMediaFileByPrefix($path);
    }

    protected function resolveMediaFileByPrefix(string $path): ?array
    {
        $basename = basename($path);
        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        $prefixes = $this->mediaFallbackPrefixes($basename);

        if (empty($prefixes) || !$extension) {
            return null;
        }

        foreach ($this->mediaPathCandidates($path) as $candidate) {
            $directory = dirname($candidate['absolute_path']);

            if (!is_dir($directory) || !is_readable($directory)) {
                continue;
            }

            $files = collect(scandir($directory) ?: [])
                ->filter(fn ($file) => is_string($file) && $file !== '.' && $file !== '..')
                ->values();

            foreach ($prefixes as $prefix) {
                $matches = $files
                    ->filter(fn (string $file) => str_starts_with($file, $prefix) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === strtolower($extension))
                    ->values();

                if ($matches->count() !== 1) {
                    continue;
                }

                $absolutePath = $directory . DIRECTORY_SEPARATOR . $matches->first();

                if (!is_file($absolutePath) || !is_readable($absolutePath)) {
                    continue;
                }

                return [
                    'label' => $candidate['label'],
                    'absolute_path' => $absolutePath,
                    'mime_type' => mime_content_type($absolutePath) ?: null,
                    'size' => filesize($absolutePath) ?: 0,
                    'fallback' => true,
                    'fallback_from' => $basename,
                    'fallback_to' => $matches->first(),
                    'fallback_prefix' => $prefix,
                ];
            }
        }

        return null;
    }

    protected function mediaPathStatus(string $path): array
    {
        return array_map(function (array $candidate) {
            $exists = is_file($candidate['absolute_path']);

            return [
                'label' => $candidate['label'],
                'absolute_path' => $candidate['absolute_path'],
                'exists' => $exists,
                'readable' => $exists && is_readable($candidate['absolute_path']),
                'size' => $exists ? filesize($candidate['absolute_path']) : null,
            ];
        }, $this->mediaPathCandidates($path));
    }

    protected function mediaPathCandidates(string $path): array
    {
        return [
            [
                'label' => 'storage_disk_public',
                'absolute_path' => storage_path('app/public/' . $path),
            ],
            [
                'label' => 'public_storage',
                'absolute_path' => public_path('storage/' . $path),
            ],
            [
                'label' => 'public_path',
                'absolute_path' => public_path($path),
            ],
            [
                'label' => 'storage_app',
                'absolute_path' => storage_path('app/' . $path),
            ],
            [
                'label' => 'cpanel_public_html_storage',
                'absolute_path' => base_path('../public_html/storage/' . $path),
            ],
            [
                'label' => 'cpanel_base_storage',
                'absolute_path' => base_path('storage/' . $path),
            ],
        ];
    }

    protected function mediaDirectoryStatus(string $path): array
    {
        $basename = basename($path);
        $prefixes = $this->mediaFallbackPrefixes($basename);
        $filenamePrefix = pathinfo($basename, PATHINFO_FILENAME);

        return array_map(function (array $candidate) use ($basename, $prefixes, $filenamePrefix) {
            $directory = dirname($candidate['absolute_path']);
            $exists = is_dir($directory);
            $readable = $exists && is_readable($directory);
            $matchingFiles = [];

            if ($readable) {
                $files = scandir($directory) ?: [];
                $matchingFiles = collect($files)
                    ->filter(fn ($file) => is_string($file) && $file !== '.' && $file !== '..')
                    ->filter(function (string $file) use ($basename, $prefixes, $filenamePrefix) {
                        if ($file === $basename || str_starts_with($file, $filenamePrefix)) {
                            return true;
                        }

                        foreach ($prefixes as $prefix) {
                            if (str_starts_with($file, $prefix)) {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->take(25)
                    ->values()
                    ->all();
            }

            return [
                'label' => $candidate['label'],
                'directory' => $directory,
                'exists' => $exists,
                'readable' => $readable,
                'matching_files' => $matchingFiles,
            ];
        }, $this->mediaPathCandidates($path));
    }

    protected function mediaFallbackPrefix(string $basename): ?string
    {
        return $this->mediaFallbackPrefixes($basename)[0] ?? null;
    }

    protected function mediaFallbackPrefixes(string $basename): array
    {
        if (!str_contains($basename, '_')) {
            return [];
        }

        $prefixes = [];
        $strictPrefix = preg_replace('/_[^_]+\.[^.]+$/', '_', $basename);

        if ($strictPrefix && $strictPrefix !== $basename) {
            $prefixes[] = $strictPrefix;
        }

        if (preg_match('/^((?:proof|voice)_s\d+_k\d+_)/i', $basename, $matches)) {
            $prefixes[] = $matches[1];
        }

        return array_values(array_unique(array_filter($prefixes)));
    }
}
