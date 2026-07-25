<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\ThemeSetting;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controller for pulling data from the online server to local.
 *
 * Pull database rows and referenced media from the online server while
 * preserving local sync connection settings.
 */
class DataPullController extends Controller
{
    /**
     * Cached local columns for each table.
     */
    protected array $localTableColumns = [];

    /**
     * Media columns that should be mirrored from the online server public storage.
     */
    protected array $mediaSyncColumns = [
        'theme_settings' => ['logo_path', 'favicon_path'],
        'siswa' => ['foto', 'foto_path'],
        'pamong' => ['foto_path', 'avatar_path'],
        'users' => ['avatar_path'],
        'siswa_karakter_checklist' => ['proof_path', 'voice_note_path'],
        'berita' => ['cover_path', 'pdf_path', 'images'],
        'materi' => ['file_path', 'cover_path', 'pdf_path'],
        'pr_submissions' => ['proof_path'],
        'chats' => ['attachment_path'],
        'chat_group_messages' => ['attachment_path'],
        'levels' => ['certificate_template'],
        'level_reward_templates' => ['template_path'],
    ];

    /**
     * Setting keys whose values are public storage paths.
     */
    protected array $mediaSettingKeys = [
        'site_logo',
        'card_logo',
    ];

    /**
     * File extensions considered safe to mirror from storage references.
     */
    protected array $mediaFileExtensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
        'avif',
        'heic',
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'csv',
        'txt',
        'zip',
        'rar',
        'mp3',
        'wav',
        'ogg',
        'oga',
        'webm',
        'm4a',
        'mp4',
        'aac',
    ];

    /**
     * Settings that must stay local because they control the sync connection.
     */
    protected array $preservedSettingKeys = [
        'sync_server_url',
        'sync_api_key',
        'sync_export_key',
        'sync_last_pull',
        'sync_last_pull_result',
        'sync_last_pull_notice',
        'sync_media_cursor',
        'sync_media_unavailable_paths',
    ];

    /**
     * Tables to import, ordered by foreign key dependencies.
     */
    protected array $importTables = [
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
     * Defaults for columns that may be absent/null when pulling from an older server schema.
     */
    protected array $importColumnDefaults = [
        'materi' => [
            'rpp_is_enabled' => 0,
            'rpp_status' => 'draft',
        ],
        'materi_targets' => [
            'sort_order' => 0,
            'is_active' => 1,
        ],
        'siswa_materi_target_progress' => [
            'is_completed' => 0,
        ],
        'schedule_reminders' => [
            'target_audience' => 'all',
            'is_recurring' => 0,
            'color' => '#3B82F6',
            'is_active' => 1,
        ],
        'materi_rpp_journals' => [
            'realization_status' => 'terlaksana',
            'workflow_status' => 'approved',
        ],
    ];

    public function index()
    {
        $serverUrl = Setting::get('sync_server_url', '');
        $apiKey = Setting::get('sync_api_key', '');
        $exportKey = Setting::get('sync_export_key', '');
        $lastPull = Setting::get('sync_last_pull', null);
        $lastPullResult = Setting::get('sync_last_pull_result', null);
        $lastPullNotice = Setting::get('sync_last_pull_notice', null);

        return view('admin.data-pull', compact('serverUrl', 'apiKey', 'exportKey', 'lastPull', 'lastPullResult', 'lastPullNotice'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'server_url' => 'required|url',
            'api_key' => 'required|string|min:8',
        ]);

        Setting::set('sync_server_url', rtrim($request->server_url, '/'));
        Setting::set('sync_api_key', $request->api_key);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi tarik data berhasil disimpan.',
                'server_url' => rtrim($request->server_url, '/'),
            ]);
        }

        return back()->with('success', 'Settings tarik data berhasil disimpan.');
    }

    public function saveExportKey(Request $request)
    {
        $request->validate([
            'export_key' => 'required|string|min:8',
        ]);

        Setting::set('sync_export_key', $request->export_key);

        return back()->with('success', 'API Key Ekspor berhasil disimpan.');
    }

    public function testConnection(Request $request)
    {
        $serverUrl = trim((string) $request->input('server_url', Setting::get('sync_server_url', '')));
        $apiKey = trim((string) $request->input('api_key', Setting::get('sync_api_key', '')));

        $serverUrl = rtrim($serverUrl, '/');

        if (!$serverUrl || !$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Server URL dan API Key di halaman ini harus diisi terlebih dahulu.',
            ]);
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Sync-Key' => $apiKey])
                ->get($serverUrl . '/api/sync/ping');

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi berhasil!',
                    'server' => $data['server'] ?? 'unknown',
                    'tables_available' => $data['tables_available'] ?? 0,
                    'server_time' => $data['time'] ?? '-',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Server merespon dengan status ' . $response->status() . ': ' . ($response->json('message') ?? 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal koneksi: ' . $e->getMessage(),
            ]);
        }
    }

    public function pull(Request $request)
    {
        $serverUrl = Setting::get('sync_server_url', '');
        $apiKey = Setting::get('sync_api_key', '');

        if (!$serverUrl || !$apiKey) {
            return back()->with('error', 'Server URL dan API Key harus diisi terlebih dahulu.');
        }

        set_time_limit(0);

        try {
            $response = Http::timeout(120)
                ->withHeaders(['X-Sync-Key' => $apiKey])
                ->get($serverUrl . '/api/sync/export', ['tables' => 'all']);

            if (!$response->successful()) {
                return back()->with('error', 'Server merespon dengan error: ' . $response->status());
            }

            $payload = $response->json();
            if (!isset($payload['success']) || !$payload['success']) {
                return back()->with('error', 'Response tidak valid dari server online.');
            }

            $serverData = $payload['data'] ?? [];
            $serverMeta = $payload['meta'] ?? [];
            $preservedSettingRows = $this->getPreservedLocalSettings();
            $preservedThemeMedia = [];

            $results = [];
            $totalSuccess = 0;
            $totalFailed = 0;
            $totalSkipped = 0;
            $backupPath = app(BackupService::class)->backupDatabase();

            DB::transaction(function () use ($serverData, $preservedSettingRows, $preservedThemeMedia, &$results, &$totalSuccess, &$totalSkipped) {
                $this->setForeignKeyChecks(false);
                try {
                    foreach ($this->importTables as $table) {
                        try {
                            if (!isset($serverData[$table])) {
                                $results[$table] = [
                                    'status' => 'skipped',
                                    'message' => 'Tidak tersedia di server online',
                                    'count' => 0,
                                ];
                                $totalSkipped++;
                                continue;
                            }

                            if (!Schema::hasTable($table)) {
                                $results[$table] = [
                                    'status' => 'skipped',
                                    'message' => 'Tabel tidak ada di lokal',
                                    'count' => 0,
                                ];
                                $totalSkipped++;
                                continue;
                            }

                            $rows = $serverData[$table];
                            $rows = $this->preserveLocalRows($table, $rows, $preservedSettingRows, $preservedThemeMedia);
                            $rows = $this->filterRowsForLocalTable($table, $rows);

                            DB::table($table)->delete();

                            if (!empty($rows)) {
                                foreach (array_chunk($rows, 100) as $chunk) {
                                    DB::table($table)->insert($chunk);
                                }
                            }

                            $results[$table] = [
                                'status' => 'success',
                                'message' => 'Berhasil',
                                'count' => count($rows),
                            ];
                            $totalSuccess++;
                        } catch (\Exception $e) {
                            throw new \RuntimeException("Gagal impor tabel {$table}: {$e->getMessage()}", 0, $e);
                        }
                    }
                } finally {
                    $this->setForeignKeyChecks(true);
                }
            });

            $this->setUnavailableMediaPaths([]);

            $mediaSync = $this->syncReferencedMedia($serverUrl, $apiKey, [
                'cursor' => 0,
                'limit_mode' => 'all',
                'max_limit' => null,
                'retry_unavailable' => true,
                'overwrite_existing' => true,
            ]);

            $pullResult = [
                'pulled_at' => now()->toIso8601String(),
                'server' => $serverMeta['server'] ?? $serverUrl,
                'total_success' => $totalSuccess,
                'total_failed' => $totalFailed,
                'total_skipped' => $totalSkipped,
                'details' => $results,
                'media' => $mediaSync,
                'backup_before_pull' => basename($backupPath),
            ];

            Setting::set('sync_last_pull', now()->toIso8601String());
            Setting::set('sync_last_pull_result', json_encode($this->compactPullResultForStorage($pullResult)));
            Setting::set('sync_media_cursor', '0');
            $notice = "Data berhasil ditarik setelah backup otomatis " . basename($backupPath) . ". {$totalSuccess} tabel berhasil, {$totalFailed} gagal, {$totalSkipped} dilewati. Media sinkron: {$mediaSync['downloaded']} file, {$mediaSync['failed']} gagal, {$mediaSync['unavailable_new']} tidak tersedia, {$mediaSync['skipped']} dilewati." . $this->mediaSyncContinuationNotice($mediaSync);
            Setting::set('sync_last_pull_notice', $notice);
            \Log::info('Tarik data dari server selesai.', [
                'server' => $serverMeta['server'] ?? $serverUrl,
                'tables_success' => $totalSuccess,
                'tables_skipped' => $totalSkipped,
                'media_total' => $mediaSync['total'] ?? 0,
                'media_downloaded' => $mediaSync['downloaded'] ?? 0,
                'media_failed' => $mediaSync['failed'] ?? 0,
                'media_unavailable' => $mediaSync['unavailable'] ?? 0,
                'media_remaining' => $mediaSync['remaining'] ?? 0,
                'media_overwrite_existing' => $mediaSync['overwrite_existing'] ?? false,
            ]);
            $this->recoverLogoFallbacks();
            Setting::clearCache();
            \Cache::forget(ThemeSetting::CACHE_KEY);

            return back()
                ->with('success', $notice);
        } catch (\Exception $e) {
            \Log::error('Gagal menarik data dari server.', [
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menarik data: ' . $e->getMessage());
        }
    }

    public function syncMediaOnly(Request $request)
    {
        $serverUrl = Setting::get('sync_server_url', '');
        $apiKey = Setting::get('sync_api_key', '');

        if (!$serverUrl || !$apiKey) {
            return back()->with('error', 'Server URL dan API Key harus diisi terlebih dahulu.');
        }

        set_time_limit(300);

        try {
            if ($request->boolean('retry_unavailable')) {
                $this->setUnavailableMediaPaths([]);
            }

            $cursor = (int) Setting::get('sync_media_cursor', 0);
            $syncOptions = [
                'cursor' => $cursor,
                'limit_mode' => $request->input('limit_mode', 'half'),
                'max_limit' => $request->boolean('retry_unavailable') ? 60 : 30,
                'retry_unavailable' => $request->boolean('retry_unavailable'),
            ];

            if ($request->filled('limit')) {
                $syncOptions['limit'] = max(1, min((int) $syncOptions['max_limit'], (int) $request->input('limit')));
            }

            $mediaSync = $this->syncReferencedMedia($serverUrl, $apiKey, $syncOptions);

            $pullResult = [
                'pulled_at' => now()->toIso8601String(),
                'server' => $serverUrl,
                'total_success' => 0,
                'total_failed' => 0,
                'total_skipped' => 0,
                'details' => [],
                'media' => $mediaSync,
                'media_only' => true,
            ];

            Setting::set('sync_media_cursor', (string) ($mediaSync['next_cursor'] ?? 0));

            $notice = $this->buildMediaSyncNotice($mediaSync);

            Setting::set('sync_last_pull', now()->toIso8601String());
            Setting::set('sync_last_pull_result', json_encode($this->compactPullResultForStorage($pullResult)));
            Setting::set('sync_last_pull_notice', $notice);
            Setting::clearCache();

            return back()->with('success', $notice);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal sinkron ulang media: ' . $e->getMessage());
        }
    }

    public function downloadUnavailableMediaReport()
    {
        $paths = $this->getUnavailableMediaPaths();

        return response()->streamDownload(function () use ($paths) {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'path',
                'tipe',
                'id_tugas',
                'siswa',
                'karakter',
                'tanggal_tugas',
            ]);

            if (empty($paths)) {
                fclose($output);
                return;
            }

            DB::table('siswa_karakter_checklist as c')
                ->leftJoin('siswa as s', 's.id', '=', 'c.siswa_id')
                ->leftJoin('karakter as k', 'k.id', '=', 'c.karakter_id')
                ->whereIn('c.proof_path', $paths)
                ->orWhereIn('c.voice_note_path', $paths)
                ->select([
                    'c.id',
                    's.nama as siswa',
                    'k.nama as karakter',
                    'c.checked_at',
                    'c.proof_path',
                    'c.voice_note_path',
                ])
                ->orderBy('c.checked_at')
                ->cursor()
                ->each(function ($row) use ($output, $paths) {
                    if ($row->proof_path && in_array($row->proof_path, $paths, true)) {
                        fputcsv($output, [
                            $row->proof_path,
                            'foto',
                            $row->id,
                            $row->siswa,
                            $row->karakter,
                            $row->checked_at,
                        ]);
                    }

                    if ($row->voice_note_path && in_array($row->voice_note_path, $paths, true)) {
                        fputcsv($output, [
                            $row->voice_note_path,
                            'voice_note',
                            $row->id,
                            $row->siswa,
                            $row->karakter,
                            $row->checked_at,
                        ]);
                    }
                });

            fclose($output);
        }, 'media-tidak-tersedia-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function compactPullResultForStorage(array $pullResult): array
    {
        $compact = $pullResult;
        $compact['media'] = $this->compactMediaSyncResult($pullResult['media'] ?? [], 40);

        return $compact;
    }

    protected function compactPullResultForDisplay(array $pullResult): array
    {
        $compact = $pullResult;
        $compact['media'] = $this->compactMediaSyncResult($pullResult['media'] ?? [], 120);

        return $compact;
    }

    protected function compactMediaSyncResult(array $mediaSync, int $detailLimit): array
    {
        $details = collect($mediaSync['details'] ?? []);
        $failedDetails = $details
            ->filter(fn ($item) => ($item['status'] ?? null) !== 'success')
            ->take($detailLimit)
            ->map(fn ($item) => [
                'path' => $item['path'] ?? '-',
                'status' => $item['status'] ?? 'error',
                'message' => $item['message'] ?? '-',
                'source' => $item['source'] ?? null,
            ])
            ->values()
            ->all();

        return [
            'downloaded' => (int) ($mediaSync['downloaded'] ?? 0),
            'failed' => (int) ($mediaSync['failed'] ?? 0),
            'skipped' => (int) ($mediaSync['skipped'] ?? 0),
            'total' => (int) ($mediaSync['total'] ?? 0),
            'already_local' => (int) ($mediaSync['already_local'] ?? 0),
            'pending_before' => (int) ($mediaSync['pending_before'] ?? 0),
            'batch_limit' => (int) ($mediaSync['batch_limit'] ?? 0),
            'attempted' => (int) ($mediaSync['attempted'] ?? 0),
            'remaining' => (int) ($mediaSync['remaining'] ?? 0),
            'unavailable' => (int) ($mediaSync['unavailable'] ?? 0),
            'unavailable_known' => (int) ($mediaSync['unavailable_known'] ?? 0),
            'unavailable_new' => (int) ($mediaSync['unavailable_new'] ?? 0),
            'retry_unavailable' => (bool) ($mediaSync['retry_unavailable'] ?? false),
            'overwrite_existing' => (bool) ($mediaSync['overwrite_existing'] ?? false),
            'start_cursor' => (int) ($mediaSync['start_cursor'] ?? 0),
            'next_cursor' => (int) ($mediaSync['next_cursor'] ?? 0),
            'details' => $failedDetails,
            'details_limited' => $details->count() > count($failedDetails),
            'details_note' => $this->mediaDetailsNote($details->count(), count($failedDetails), $detailLimit),
        ];
    }

    protected function mediaDetailsNote(int $totalDetails, int $failedDetails, int $detailLimit): string
    {
        if ($failedDetails <= 0) {
            return 'Semua file media pada batch ini berhasil disalin; detail sukses tidak disimpan agar log tetap ringkas.';
        }

        if ($failedDetails >= $detailLimit) {
            return "Detail dibatasi ke {$detailLimit} file media yang gagal agar log hasil tarik tetap bisa disimpan.";
        }

        if ($totalDetails > $failedDetails) {
            return 'Menampilkan file media yang gagal; detail sukses tidak disimpan agar log tetap ringkas.';
        }

        return 'Menampilkan file media yang gagal pada batch terakhir.';
    }

    protected function buildMediaSyncNotice(array $mediaSync): string
    {
        $prefix = !empty($mediaSync['retry_unavailable'])
            ? 'Coba ulang file tidak tersedia selesai.'
            : 'Sinkron ulang media selesai.';

        $notice = "{$prefix} Diproses {$mediaSync['attempted']} dari {$mediaSync['pending_before']} file yang belum ada lokal. Media sinkron: {$mediaSync['downloaded']} file, {$mediaSync['failed']} gagal, {$mediaSync['unavailable_new']} tidak tersedia, {$mediaSync['skipped']} dilewati.";

        if (($mediaSync['attempted'] ?? 0) > 0 && ($mediaSync['downloaded'] ?? 0) <= 0 && ($mediaSync['failed'] ?? 0) > 0) {
            $notice .= ' Jumlah belum tersalin belum berkurang karena semua file pada batch ini gagal diambil dari server.';
        }

        if (($mediaSync['unavailable_new'] ?? 0) > 0) {
            $notice .= " {$mediaSync['unavailable_new']} file dikonfirmasi tidak tersedia di storage server.";
        }

        return $notice . $this->mediaSyncContinuationNotice($mediaSync);
    }

    protected function mediaSyncContinuationNotice(array $mediaSync): string
    {
        $remaining = (int) ($mediaSync['remaining'] ?? 0);

        if ($remaining <= 0) {
            $unavailable = (int) ($mediaSync['unavailable'] ?? 0);

            if ($unavailable > 0) {
                return " Semua media yang tersedia sudah tersalin ke lokal. {$unavailable} referensi media tidak tersedia di storage server; pulihkan file di cPanel lalu klik Coba Ulang File Tidak Tersedia.";
            }

            return ' Semua media yang tersedia sudah tersalin ke lokal.';
        }

        $nextCursor = (int) ($mediaSync['next_cursor'] ?? 0);
        $total = (int) ($mediaSync['total'] ?? 0);
        $cursorText = $total > 0 ? " Posisi batch berikutnya: {$nextCursor}/{$total}." : '';

        return " Masih ada {$remaining} file belum tersalin. Klik Sinkron Ulang Media Saja lagi untuk melanjutkan batch berikutnya.{$cursorText}";
    }

    protected function filterRowsForLocalTable(string $table, array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $columns = $this->localTableColumns[$table] ?? Schema::getColumnListing($table);
        $this->localTableColumns[$table] = $columns;
        $allowedColumns = array_flip($columns);

        return array_map(function ($row) use ($table, $allowedColumns) {
            if (!is_array($row)) {
                return [];
            }

            $filtered = array_intersect_key($row, $allowedColumns);
            $normalized = [];

            foreach (array_keys($allowedColumns) as $column) {
                $normalized[$column] = $filtered[$column] ?? null;
            }

            return $this->applyImportColumnDefaults($table, $normalized);
        }, $rows);
    }

    protected function applyImportColumnDefaults(string $table, array $row): array
    {
        foreach ($this->importColumnDefaults[$table] ?? [] as $column => $default) {
            if (array_key_exists($column, $row) && ($row[$column] === null || $row[$column] === '')) {
                $row[$column] = $default;
            }
        }

        return $row;
    }

    protected function setForeignKeyChecks(bool $enabled): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ' . ($enabled ? 'ON' : 'OFF'));
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=' . ($enabled ? '1' : '0'));
    }

    protected function preserveLocalRows(string $table, array $rows, array $preservedSettingRows, array $preservedThemeMedia): array
    {
        if ($table === 'settings') {
            $indexed = [];

            foreach ($rows as $row) {
                if (is_array($row) && isset($row['key'])) {
                    $indexed[$row['key']] = $row;
                }
            }

            foreach ($preservedSettingRows as $key => $row) {
                $indexed[$key] = array_merge($indexed[$key] ?? [], $row);
            }

            return array_values($indexed);
        }

        if ($table === 'theme_settings' && !empty($rows)) {
            $rows[0] = array_merge($rows[0], $preservedThemeMedia);
        }

        return $rows;
    }

    protected function getPreservedLocalSettings(): array
    {
        if (!Schema::hasTable('settings')) {
            return [];
        }

        $columns = Schema::getColumnListing('settings');
        $selectColumns = array_values(array_intersect([
            'key',
            'value',
            'type',
            'description',
            'group',
            'created_at',
            'updated_at',
        ], $columns));

        return DB::table('settings')
            ->whereIn('key', $this->preservedSettingKeys)
            ->get($selectColumns)
            ->filter(function ($setting) {
                if (in_array($setting->key ?? null, ['site_logo', 'card_logo'], true)) {
                    return $this->isExistingPublicMediaPath($setting->value ?? null);
                }

                return true;
            })
            ->mapWithKeys(function ($setting) {
                $row = (array) $setting;

                if (array_key_exists('created_at', $row)) {
                    $row['created_at'] = $this->normalizeDatabaseTimestamp($row['created_at']);
                }

                if (array_key_exists('updated_at', $row)) {
                    $row['updated_at'] = $this->normalizeDatabaseTimestamp($row['updated_at']);
                }

                if (array_key_exists('type', $row) && $row['type'] === null) {
                    $row['type'] = 'string';
                }

                return [$row['key'] => $row];
            })
            ->all();
    }

    protected function normalizeDatabaseTimestamp($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $value = (string) $value;

        if (!str_contains($value, 'T')) {
            return $value;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $value;
        }
    }

    protected function getPreservedLocalThemeMedia(): array
    {
        $theme = ThemeSetting::query()->first();
        if (!$theme) {
            return [];
        }

        $preserved = [];

        if ($this->isExistingPublicMediaPath($theme->logo_path)) {
            $preserved['logo_path'] = $theme->logo_path;
        }

        if ($this->isExistingPublicMediaPath($theme->favicon_path)) {
            $preserved['favicon_path'] = $theme->favicon_path;
        }

        return $preserved;
    }

    protected function isExistingPublicMediaPath(?string $path): bool
    {
        return !empty($path) && Storage::disk('public')->exists($path);
    }

    protected function isExistingUsablePublicMediaPath(?string $path): bool
    {
        if (!$this->isExistingPublicMediaPath($path)) {
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

            return $this->isUsableMediaBody($path, $prefix, true);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function recoverLogoFallbacks(): void
    {
        $theme = ThemeSetting::query()->first();
        $siteLogo = Setting::get('site_logo');

        $missingThemeLogo = $theme && !$this->isExistingPublicMediaPath($theme->logo_path);
        $missingSiteLogo = !$this->isExistingPublicMediaPath($siteLogo);

        if (!$missingThemeLogo && !$missingSiteLogo) {
            return;
        }

        $fallback = collect(Storage::disk('public')->files('logos'))
            ->filter(fn ($path) => preg_match('/\.(png|jpe?g|svg)$/i', $path))
            ->sortByDesc(fn ($path) => Storage::disk('public')->lastModified($path))
            ->first();

        if (!$fallback) {
            return;
        }

        if ($missingSiteLogo) {
            Setting::set('site_logo', $fallback, 'general');
        }

        if ($theme && $missingThemeLogo) {
            $theme->logo_path = $fallback;
            $theme->save();
        }
    }

    protected function syncReferencedMedia(string $serverUrl, string $apiKey, array $options = []): array
    {
        $paths = $this->normalizeMediaPathList($this->collectReferencedMediaPaths());
        $serverUrl = rtrim($serverUrl, '/');
        $requestedLimit = array_key_exists('limit', $options) ? (int) $options['limit'] : null;
        $limitMode = (string) ($options['limit_mode'] ?? 'fixed');
        $maxLimit = $this->normalizeMediaMaxLimit(
            array_key_exists('max_limit', $options) ? $options['max_limit'] : 30
        );
        $cursor = max(0, (int) ($options['cursor'] ?? 0));
        $overwriteExisting = (bool) ($options['overwrite_existing'] ?? false);
        $retryUnavailable = (bool) ($options['retry_unavailable'] ?? false);
        $total = count($paths);
        $cursor = $total > 0 ? min($cursor, $total - 1) : 0;
        $startCursor = $cursor;

        $downloaded = 0;
        $failed = 0;
        $invalid = 0;
        $unavailableNew = 0;
        $unavailableKnown = 0;
        $alreadyLocal = 0;
        $details = [];
        $missingIndexes = [];
        $unavailableLookup = array_flip($this->getUnavailableMediaPaths());

        foreach ($paths as $index => $normalizedPath) {
            if (!$overwriteExisting && $this->isExistingUsablePublicMediaPath($normalizedPath)) {
                $alreadyLocal++;
            } elseif (!$overwriteExisting && $this->isExistingPublicMediaPath($normalizedPath)) {
                $invalid++;
                $missingIndexes[] = $index;
            } elseif (!$retryUnavailable && isset($unavailableLookup[$normalizedPath])) {
                $unavailableKnown++;
            } else {
                $missingIndexes[] = $index;
            }
        }

        $pendingBefore = count($missingIndexes);
        $limit = $this->resolveMediaBatchLimit($pendingBefore, $requestedLimit, $limitMode, $maxLimit);
        $batchIndexes = collect($missingIndexes)
            ->sortBy(fn (int $index) => $index < $cursor ? $index + $total : $index)
            ->take($limit)
            ->values()
            ->all();
        $nextCursor = $cursor;

        foreach ($batchIndexes as $pathIndex) {
            $normalizedPath = $paths[$pathIndex];
            $nextCursor = $total > 0 ? ($pathIndex + 1) % $total : 0;
            try {
                $download = $this->downloadRemoteMedia($serverUrl, $apiKey, $normalizedPath);

                if (empty($download['ok'])) {
                    $confirmedMissing = $this->isConfirmedRemoteMediaMissing($download);

                    if ($confirmedMissing) {
                        $unavailableNew++;
                        $unavailableLookup[$normalizedPath] = true;
                    } else {
                        $failed++;
                    }

                    $details[] = [
                        'path' => $normalizedPath,
                        'status' => $confirmedMissing ? 'missing' : 'error',
                        'message' => $download['message'],
                        'source' => $download['source'],
                        'diagnostic' => $download['diagnostic'] ?? null,
                    ];
                    continue;
                }

                Storage::disk('public')->put($normalizedPath, $download['response']->body());

                $downloaded++;
                $details[] = [
                    'path' => $normalizedPath,
                    'status' => 'success',
                    'message' => 'Tersalin ke lokal',
                    'source' => $download['source'],
                ];
            } catch (\Throwable $e) {
                $failed++;
                $details[] = [
                    'path' => $normalizedPath,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $attempted = count($batchIndexes);
        $unavailableTotal = count($unavailableLookup);
        $remaining = max(0, $pendingBefore - $downloaded - $unavailableNew);
        $this->setUnavailableMediaPaths(array_keys($unavailableLookup));

        return [
            'downloaded' => $downloaded,
            'failed' => $failed,
            'skipped' => $alreadyLocal,
            'already_local' => $alreadyLocal,
            'invalid' => $invalid,
            'unavailable' => $unavailableTotal,
            'unavailable_known' => $unavailableKnown,
            'unavailable_new' => $unavailableNew,
            'total' => $total,
            'pending_before' => $pendingBefore,
            'batch_limit' => $limit,
            'attempted' => $attempted,
            'remaining' => $remaining,
            'start_cursor' => $startCursor,
            'next_cursor' => $nextCursor,
            'retry_unavailable' => $retryUnavailable,
            'overwrite_existing' => $overwriteExisting,
            'details' => $details,
        ];
    }

    protected function normalizeMediaMaxLimit($value): ?int
    {
        if ($value === null || $value === false || $value === 'all') {
            return null;
        }

        return max(1, (int) $value);
    }

    protected function resolveMediaBatchLimit(int $pendingBefore, ?int $requestedLimit, string $limitMode, ?int $maxLimit): int
    {
        $pendingBefore = max(1, $pendingBefore);

        if ($requestedLimit && $requestedLimit > 0) {
            return $maxLimit === null
                ? max(1, $requestedLimit)
                : max(1, min($maxLimit, $requestedLimit));
        }

        if ($limitMode === 'half') {
            $limit = (int) ceil($pendingBefore / 2);

            return $maxLimit === null
                ? max(1, $limit)
                : max(1, min($maxLimit, $limit));
        }

        if ($limitMode === 'all') {
            return $maxLimit === null
                ? $pendingBefore
                : max(1, min($maxLimit, $pendingBefore));
        }

        return $maxLimit === null
            ? 12
            : max(1, min($maxLimit, 12));
    }

    protected function getUnavailableMediaPaths(): array
    {
        $decoded = json_decode((string) Setting::get('sync_media_unavailable_paths', '[]'), true);

        if (!is_array($decoded)) {
            return [];
        }

        return $this->normalizeMediaPathList($decoded);
    }

    protected function setUnavailableMediaPaths(array $paths): void
    {
        Setting::set('sync_media_unavailable_paths', json_encode($this->normalizeMediaPathList($paths)), 'sync');
    }

    protected function normalizeMediaPathList(array $paths): array
    {
        return collect($paths)
            ->map(fn ($path) => $this->normalizePublicMediaPath(is_string($path) ? $path : null))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function downloadRemoteMedia(string $serverUrl, string $apiKey, string $path): array
    {
        $apiResponse = null;
        $apiError = null;

        try {
            $apiResponse = Http::connectTimeout(4)
                ->timeout(10)
                ->accept('*/*')
                ->withHeaders(['X-Sync-Key' => $apiKey])
                ->get($serverUrl . '/api/sync/media', ['path' => $path]);
        } catch (\Throwable $e) {
            $apiError = $e;
        }

        if ($apiResponse && $this->isUsableMediaResponse($apiResponse, $path)) {
            return [
                'ok' => true,
                'response' => $apiResponse,
                'source' => 'sync-api',
                'message' => 'HTTP ' . $apiResponse->status(),
            ];
        }

        $publicUrl = $serverUrl . '/storage/' . ltrim($path, '/');
        $publicResponse = null;
        $publicError = null;

        try {
            $publicResponse = Http::connectTimeout(3)
                ->timeout(5)
                ->get($publicUrl);
        } catch (\Throwable $e) {
            $publicError = $e;
        }

        if ($publicResponse && $this->isUsableMediaResponse($publicResponse, $path)) {
            return [
                'ok' => true,
                'response' => $publicResponse,
                'source' => 'public-storage',
                'message' => 'HTTP ' . $publicResponse->status(),
            ];
        }

        $diagnostic = $this->fetchRemoteMediaStatus($serverUrl, $apiKey, $path);

        foreach ($this->remoteCandidatePathsFromDiagnostic($path, $diagnostic) as $candidatePath) {
            $candidateApiResponse = null;

            try {
                $candidateApiResponse = Http::connectTimeout(4)
                    ->timeout(10)
                    ->accept('*/*')
                    ->withHeaders(['X-Sync-Key' => $apiKey])
                    ->get($serverUrl . '/api/sync/media', ['path' => $candidatePath]);
            } catch (\Throwable) {
                $candidateApiResponse = null;
            }

            if ($candidateApiResponse && $this->isUsableMediaResponse($candidateApiResponse, $candidatePath)) {
                return [
                    'ok' => true,
                    'response' => $candidateApiResponse,
                    'source' => 'sync-api:fallback-name',
                    'message' => 'HTTP ' . $candidateApiResponse->status() . ' dari ' . $candidatePath,
                ];
            }

            $candidatePublicResponse = null;

            try {
                $candidatePublicResponse = Http::connectTimeout(3)
                    ->timeout(5)
                    ->get($serverUrl . '/storage/' . ltrim($candidatePath, '/'));
            } catch (\Throwable) {
                $candidatePublicResponse = null;
            }

            if ($candidatePublicResponse && $this->isUsableMediaResponse($candidatePublicResponse, $candidatePath)) {
                return [
                    'ok' => true,
                    'response' => $candidatePublicResponse,
                    'source' => 'public-storage:fallback-name',
                    'message' => 'HTTP ' . $candidatePublicResponse->status() . ' dari ' . $candidatePath,
                ];
            }
        }

        return [
            'ok' => false,
            'response' => $publicResponse,
            'source' => 'sync-api/public-storage',
            'message' => trim(
                'Sync API ' . $this->summarizeMediaAttemptFailure($apiResponse, $apiError) .
                ', public storage ' . $this->summarizeMediaAttemptFailure($publicResponse, $publicError) .
                '. ' . $this->summarizeMediaDiagnostic($diagnostic)
            ),
            'diagnostic' => $diagnostic,
        ];
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

    protected function isUsableMediaBody(?string $path, string $body, bool $prefixOnly = false): bool
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

    protected function isConfirmedRemoteMediaMissing(array $download): bool
    {
        $diagnostic = $download['diagnostic'] ?? null;

        if (!is_array($diagnostic)) {
            return false;
        }

        if (!empty($diagnostic['found']) || !empty($diagnostic['resolved'])) {
            return false;
        }

        $checked = collect($diagnostic['checked'] ?? []);
        if ($checked->isEmpty() || $checked->contains(fn ($item) => (bool) ($item['exists'] ?? false))) {
            return false;
        }

        return str_contains((string) ($download['message'] ?? ''), 'HTTP 404');
    }

    protected function summarizeHttpMediaFailure($response): string
    {
        $contentType = strtolower((string) $response->header('Content-Type'));

        if ($response->successful() && str_contains($contentType, 'text/html')) {
            return 'HTTP ' . $response->status() . ' HTML/login';
        }

        return 'HTTP ' . $response->status();
    }

    protected function summarizeMediaAttemptFailure($response, ?\Throwable $error): string
    {
        if ($response) {
            return $this->summarizeHttpMediaFailure($response);
        }

        if ($error) {
            return 'gagal koneksi: ' . Str::limit($error->getMessage(), 140);
        }

        return 'tidak ada respons';
    }

    protected function fetchRemoteMediaStatus(string $serverUrl, string $apiKey, string $path): ?array
    {
        try {
            $response = Http::connectTimeout(2)
                ->timeout(3)
                ->withHeaders(['X-Sync-Key' => $apiKey])
                ->get($serverUrl . '/api/sync/media-status', ['path' => $path]);

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function summarizeMediaDiagnostic(?array $diagnostic): string
    {
        if (!$diagnostic || !isset($diagnostic['checked']) || !is_array($diagnostic['checked'])) {
            return '';
        }

        $checked = collect($diagnostic['checked'])
            ->map(fn ($item) => ($item['label'] ?? '-') . ':' . (($item['exists'] ?? false) ? 'ada' : 'tidak ada'))
            ->implode(', ');

        $matchedFiles = collect($diagnostic['directories'] ?? [])
            ->flatMap(fn ($item) => is_array($item['matching_files'] ?? null) ? $item['matching_files'] : [])
            ->filter(fn ($file) => is_string($file) && $file !== '')
            ->unique()
            ->take(5)
            ->implode(', ');

        $resolved = $diagnostic['resolved']['fallback_to'] ?? null;
        $extra = '';

        if ($resolved) {
            $extra .= ' Nama server: ' . $resolved . '.';
        } elseif ($matchedFiles !== '') {
            $extra .= ' Nama mirip: ' . $matchedFiles . '.';
        }

        return $checked ? 'Dicek: ' . $checked . '.' . $extra : trim($extra);
    }

    protected function remoteCandidatePathsFromDiagnostic(string $path, ?array $diagnostic): array
    {
        if (!$diagnostic) {
            return [];
        }

        $basename = basename($path);
        $directory = trim(str_replace('\\', '/', dirname($path)), '.');
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $prefixes = $this->mediaFallbackPrefixes($basename);
        $candidateFiles = [];

        if (!empty($diagnostic['resolved']['fallback_to']) && is_string($diagnostic['resolved']['fallback_to'])) {
            $candidateFiles[] = $diagnostic['resolved']['fallback_to'];
        }

        foreach ($diagnostic['directories'] ?? [] as $directoryStatus) {
            foreach (($directoryStatus['matching_files'] ?? []) as $file) {
                if (is_string($file)) {
                    $candidateFiles[] = $file;
                }
            }
        }

        $candidateFiles = collect($candidateFiles)
            ->map(fn ($file) => trim(str_replace('\\', '/', (string) $file)))
            ->filter(function (string $file) use ($basename, $prefixes, $extension) {
                if ($file === '' || basename($file) !== $file || $file === $basename) {
                    return false;
                }

                if (empty($prefixes)) {
                    return false;
                }

                if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== $extension) {
                    return false;
                }

                foreach ($prefixes as $prefix) {
                    if (Str::startsWith($file, $prefix)) {
                        return true;
                    }
                }

                return false;
            })
            ->unique()
            ->values();

        if ($candidateFiles->count() !== 1) {
            return [];
        }

        $candidatePath = trim($directory . '/' . $candidateFiles->first(), '/');
        $normalized = $this->normalizePublicMediaPath($candidatePath);

        return $normalized ? [$normalized] : [];
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

    protected function collectReferencedMediaPaths(): array
    {
        $paths = $this->collectSettingsMediaPaths();
        $tables = collect($this->importTables)
            ->merge(array_keys($this->mediaSyncColumns))
            ->unique()
            ->values();

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $tableColumns = Schema::getColumnListing($table);
            $explicitColumns = $this->mediaSyncColumns[$table] ?? [];
            $candidateColumns = collect($tableColumns)
                ->filter(fn (string $column) => in_array($column, $explicitColumns, true) || $this->isCandidateMediaColumn($column))
                ->unique()
                ->values();

            foreach ($candidateColumns as $column) {
                $values = DB::table($table)
                    ->whereNotNull($column)
                    ->pluck($column)
                    ->filter()
                    ->values();

                foreach ($values as $value) {
                    foreach ($this->extractMediaPathCandidates($value, $column) as $candidate) {
                        $normalized = $this->normalizeCandidateMediaPath($candidate);

                        if ($normalized) {
                            $paths[] = $normalized;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($paths));
    }

    protected function collectSettingsMediaPaths(): array
    {
        if (!Schema::hasTable('settings')) {
            return [];
        }

        $columns = Schema::getColumnListing('settings');

        if (!in_array('key', $columns, true) || !in_array('value', $columns, true)) {
            return [];
        }

        $paths = [];

        DB::table('settings')
            ->whereIn('key', $this->mediaSettingKeys)
            ->whereNotNull('value')
            ->get(['key', 'value'])
            ->each(function ($setting) use (&$paths) {
                foreach ($this->extractMediaPathCandidates($setting->value, $setting->key) as $candidate) {
                    $normalized = $this->normalizeCandidateMediaPath($candidate);

                    if ($normalized) {
                        $paths[] = $normalized;
                    }
                }
            });

        return $paths;
    }

    protected function isCandidateMediaColumn(string $column): bool
    {
        $column = strtolower($column);

        if (in_array($column, [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'qr_token',
            'api_token',
        ], true)) {
            return false;
        }

        if (in_array($column, [
            'foto',
            'foto_path',
            'avatar_path',
            'proof_path',
            'voice_note_path',
            'attachment_path',
            'cover_path',
            'pdf_path',
            'images',
            'file_path',
            'logo_path',
            'favicon_path',
            'certificate_template',
            'template_path',
        ], true)) {
            return true;
        }

        return str_ends_with($column, '_path')
            || str_ends_with($column, '_paths')
            || str_ends_with($column, '_file')
            || str_ends_with($column, '_files')
            || str_ends_with($column, '_image')
            || str_ends_with($column, '_images')
            || str_ends_with($column, '_photo')
            || str_ends_with($column, '_photos')
            || str_contains($column, 'attachment')
            || str_contains($column, 'avatar')
            || str_contains($column, 'logo')
            || str_contains($column, 'favicon');
    }

    protected function isCandidateMediaValueKey(string $key): bool
    {
        $key = strtolower($key);

        return in_array($key, [
            'path',
            'paths',
            'url',
            'file',
            'files',
            'image',
            'images',
            'photo',
            'photos',
            'foto',
            'avatar',
            'logo',
            'favicon',
            'cover',
            'attachment',
            'attachments',
            'proof',
            'proof_path',
            'voice_note',
            'voice_note_path',
            'pdf',
            'pdf_path',
            'template',
            'template_path',
        ], true) || $this->isCandidateMediaColumn($key);
    }

    protected function extractMediaPathCandidates($value, ?string $key = null): array
    {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            $paths = [];
            $isList = array_is_list($value);

            foreach ($value as $childKey => $childValue) {
                if ($isList || $this->isCandidateMediaValueKey((string) $childKey)) {
                    $paths = array_merge($paths, $this->extractMediaPathCandidates(
                        $childValue,
                        is_string($childKey) ? $childKey : null
                    ));
                }
            }

            return $paths;
        }

        if (!is_string($value)) {
            return [];
        }

        $value = trim($value);

        if ($value === '') {
            return [];
        }

        $decoded = $this->decodeMediaJsonValue($value);

        if (is_array($decoded)) {
            return $this->extractMediaPathCandidates($decoded, $key);
        }

        if ($key !== null && !$this->isCandidateMediaValueKey($key)) {
            return [];
        }

        return [$value];
    }

    protected function decodeMediaJsonValue(string $value): ?array
    {
        $first = substr(ltrim($value), 0, 1);

        if (!in_array($first, ['[', '{'], true)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : null;
    }

    protected function normalizeCandidateMediaPath(?string $path): ?string
    {
        $normalized = $this->normalizePublicMediaPath($path);

        if (!$normalized || !$this->looksLikeMediaPath($normalized)) {
            return null;
        }

        return $normalized;
    }

    protected function looksLikeMediaPath(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' && in_array($extension, $this->mediaFileExtensions, true);
    }

    protected function normalizePublicMediaPath(?string $path): ?string
    {
        if (!$path || !is_string($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
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
}
