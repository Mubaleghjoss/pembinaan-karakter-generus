<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->middleware(['auth']);
        $this->backupService = $backupService;
    }

    /**
     * Display backup management page.
     */
    public function index()
    {
        $backups = $this->backupService->getBackupList();

        return view('settings.backup', compact('backups'));
    }

    /**
     * Create database backup.
     */
    public function backupDatabase()
    {
        try {
            $filepath = $this->backupService->backupDatabase();
            $filename = basename($filepath);

            return redirect()->route('settings.index', ['tab' => 'backup'])
                ->with('success', "Backup database berhasil dibuat: {$filename}");
        } catch (\Exception $e) {
            Log::error('Backup database failed: ' . $e->getMessage());
            return redirect()->route('settings.index', ['tab' => 'backup'])
                ->with('error', 'Gagal membuat backup database: ' . $e->getMessage());
        }
    }

    /**
     * Create files backup.
     */
    public function backupFiles()
    {
        try {
            set_time_limit(300); // 5 minutes for large projects
            $filepath = $this->backupService->backupFiles();
            $filename = basename($filepath);

            return redirect()->route('settings.index', ['tab' => 'backup'])
                ->with('success', "Backup files berhasil dibuat: {$filename}");
        } catch (\Exception $e) {
            Log::error('Backup files failed: ' . $e->getMessage());
            return redirect()->route('settings.index', ['tab' => 'backup'])
                ->with('error', 'Gagal membuat backup files: ' . $e->getMessage());
        }
    }

    /**
     * Create full backup (database + files).
     */
    public function backupAll()
    {
        try {
            set_time_limit(600); // 10 minutes for full backup
            $filepath = $this->backupService->backupAll();
            $filename = basename($filepath);

            return redirect()->route('settings.index', ['tab' => 'backup'])
                ->with('success', "Full backup berhasil dibuat: {$filename}");
        } catch (\Exception $e) {
            Log::error('Full backup failed: ' . $e->getMessage());
            return redirect()->route('settings.index', ['tab' => 'backup'])
                ->with('error', 'Gagal membuat full backup: ' . $e->getMessage());
        }
    }

    /**
     * Download backup file.
     */
    public function download(string $filename)
    {
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $filepath = $this->backupService->getBackupPath($filename);

        if (!$filepath) {
            return redirect()->route('settings.index', ['tab' => 'backup'])
                ->with('error', 'File backup tidak ditemukan.');
        }

        return response()->download($filepath, $filename);
    }

    /**
     * Delete backup file.
     */
    public function delete(string $filename)
    {
        // Sanitize filename
        $filename = basename($filename);
        
        if ($this->backupService->deleteBackup($filename)) {
            return redirect()->route('settings.index', ['tab' => 'backup'])
                ->with('success', "Backup {$filename} berhasil dihapus.");
        }

        return redirect()->route('settings.index', ['tab' => 'backup'])
            ->with('error', 'Gagal menghapus backup.');
    }
}
