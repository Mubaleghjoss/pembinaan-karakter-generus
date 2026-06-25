<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Carbon\Carbon;

/**
 * Service untuk backup database dan file project
 */
class BackupService
{
    /**
     * Direktori yang akan di-include dalam backup (hanya folder penting - untuk backup cepat)
     */
    protected array $includeDirs = [
        'app',
        'config',
        'database',
        'public',
        'resources',
        'routes',
        'bootstrap',
        'storage/app/public',
    ];

    /**
     * Direktori yang akan di-include dalam FULL backup (semua file termasuk vendor & node_modules)
     */
    protected array $includeAllDirs = [
        'app',
        'config',
        'database',
        'public',
        'resources',
        'routes',
        'bootstrap',
        'storage',
        'vendor',
        'node_modules',
        'src',
        'tests',
        'migrations',
    ];

    /**
     * Direktori yang akan di-exclude dari backup
     */
    protected array $excludeDirs = [
        '.git',
        '.kiro',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'storage/app/backups',
        'public/hot',
    ];

    /**
     * File yang akan di-exclude dari backup
     */
    protected array $excludeFiles = [
        '.env',
        '.env.backup',
    ];

    /**
     * Backup database ke file SQL
     */
    public function backupDatabase(): string
    {
        $connection = config('database.default');
        
        if ($connection === 'sqlite') {
            return $this->backupSqlite();
        }
        
        return $this->backupMysql();
    }

    /**
     * Backup SQLite database
     */
    protected function backupSqlite(): string
    {
        $filename = 'database_' . Carbon::now()->format('Y-m-d_His') . '.sqlite';
        $filepath = storage_path('app/backups/' . $filename);

        $this->ensureBackupDirectory();

        $sqlitePath = config('database.connections.sqlite.database');
        
        if (!File::exists($sqlitePath)) {
            throw new \Exception('SQLite database file not found: ' . $sqlitePath);
        }

        // Copy SQLite file directly
        File::copy($sqlitePath, $filepath);

        return $filepath;
    }

    /**
     * Backup MySQL database
     */
    protected function backupMysql(): string
    {
        $filename = 'database_' . Carbon::now()->format('Y-m-d_His') . '.sql';
        $filepath = storage_path('app/backups/' . $filename);

        $this->ensureBackupDirectory();

        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $tableKey = 'Tables_in_' . $dbName;

        $sql = "-- PKG Presensi Database Backup\n";
        $sql .= "-- Generated: " . Carbon::now()->toDateTimeString() . "\n";
        $sql .= "-- Database: {$dbName}\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // Get create table statement
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= "-- Table: {$tableName}\n";
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

            // Get table data
            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                $columns = array_keys((array) $rows->first());
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = array_map(function ($value) {
                        if (is_null($value)) {
                            return 'NULL';
                        }
                        return "'" . addslashes($value) . "'";
                    }, (array) $row);
                    
                    $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::put($filepath, $sql);

        return $filepath;
    }

    /**
     * Pastikan direktori backup ada
     */
    protected function ensureBackupDirectory(): void
    {
        $backupPath = storage_path('app/backups');
        
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }
        
        // Pastikan writable
        if (!is_writable($backupPath)) {
            chmod($backupPath, 0755);
        }
    }

    /**
     * Backup semua file project ke ZIP
     */
    public function backupFiles(): string
    {
        $filename = 'files_' . Carbon::now()->format('Y-m-d_His') . '.zip';
        $filepath = storage_path('app/backups/' . $filename);

        $this->ensureBackupDirectory();

        $zip = new ZipArchive();
        $result = $zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== true) {
            throw new \Exception('Tidak dapat membuat file ZIP. Error code: ' . $result);
        }

        $basePath = base_path();
        
        // Backup hanya folder penting
        foreach ($this->includeDirs as $dir) {
            $dirPath = $basePath . DIRECTORY_SEPARATOR . $dir;
            if (File::isDirectory($dirPath)) {
                $this->addDirectoryToZipSafe($zip, $dirPath, $dir);
            }
        }

        // Tambahkan file root penting
        $rootFiles = ['composer.json', 'package.json', 'artisan', '.env.example', 'vite.config.js', 'tailwind.config.js', 'phpunit.xml'];
        foreach ($rootFiles as $file) {
            $filePath = $basePath . DIRECTORY_SEPARATOR . $file;
            if (File::exists($filePath) && is_readable($filePath)) {
                $zip->addFromString($file, file_get_contents($filePath));
            }
        }

        $closeResult = $zip->close();
        
        if (!$closeResult) {
            throw new \Exception('Gagal menutup file ZIP');
        }

        return $filepath;
    }

    /**
     * Tambahkan direktori ke ZIP dengan membaca konten file (lebih aman)
     */
    protected function addDirectoryToZipSafe(ZipArchive $zip, string $path, string $relativePath): void
    {
        if (!File::isDirectory($path)) {
            return;
        }

        $files = File::allFiles($path);
        
        foreach ($files as $file) {
            try {
                $subPath = str_replace('\\', '/', $file->getRelativePathname());
                $fullRelativePath = $relativePath . '/' . $subPath;
                
                // Skip excluded paths
                if ($this->shouldExclude($fullRelativePath)) {
                    continue;
                }
                
                // Skip files larger than 5MB
                if ($file->getSize() > 5 * 1024 * 1024) {
                    continue;
                }
                
                // Skip files that can't be read
                if (!$file->isReadable()) {
                    continue;
                }
                
                // Gunakan addFromString untuk menghindari file locking
                $content = file_get_contents($file->getRealPath());
                if ($content !== false) {
                    $zip->addFromString($fullRelativePath, $content);
                }
            } catch (\Exception $e) {
                // Skip problematic files
                continue;
            }
        }
    }

    /**
     * Backup database + files dalam 1 ZIP (FULL backup termasuk vendor & node_modules)
     */
    public function backupAll(): string
    {
        $filename = 'backup_full_' . Carbon::now()->format('Y-m-d_His') . '.zip';
        $filepath = storage_path('app/backups/' . $filename);

        $this->ensureBackupDirectory();

        // Backup database dulu
        $dbBackupPath = $this->backupDatabase();

        $zip = new ZipArchive();
        $result = $zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== true) {
            File::delete($dbBackupPath);
            throw new \Exception('Tidak dapat membuat file ZIP. Error code: ' . $result);
        }

        // Tambahkan file database backup menggunakan addFromString
        $dbContent = file_get_contents($dbBackupPath);
        if ($dbContent !== false) {
            $zip->addFromString('database/' . basename($dbBackupPath), $dbContent);
        }

        // Tambahkan SEMUA file project termasuk vendor & node_modules
        $basePath = base_path();
        foreach ($this->includeAllDirs as $dir) {
            $dirPath = $basePath . DIRECTORY_SEPARATOR . $dir;
            if (File::isDirectory($dirPath)) {
                $this->addDirectoryToZipFull($zip, $dirPath, 'project/' . $dir);
            }
        }

        // Tambahkan SEMUA file root
        $rootFiles = File::files($basePath);
        foreach ($rootFiles as $file) {
            try {
                $filename_root = $file->getFilename();
                
                // Skip excluded files
                if (in_array($filename_root, $this->excludeFiles)) {
                    continue;
                }
                
                // Skip files larger than 50MB
                if ($file->getSize() > 50 * 1024 * 1024) {
                    continue;
                }
                
                if ($file->isReadable()) {
                    $content = file_get_contents($file->getRealPath());
                    if ($content !== false) {
                        $zip->addFromString('project/' . $filename_root, $content);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $closeResult = $zip->close();
        
        // Hapus file database temporary
        File::delete($dbBackupPath);

        if (!$closeResult) {
            throw new \Exception('Gagal menutup file ZIP');
        }

        return $filepath;
    }

    /**
     * Tambahkan direktori ke ZIP untuk FULL backup (termasuk file besar)
     */
    protected function addDirectoryToZipFull(ZipArchive $zip, string $path, string $relativePath): void
    {
        if (!File::isDirectory($path)) {
            return;
        }

        $files = File::allFiles($path);
        
        foreach ($files as $file) {
            try {
                $subPath = str_replace('\\', '/', $file->getRelativePathname());
                $fullRelativePath = $relativePath . '/' . $subPath;
                
                // Skip excluded paths
                if ($this->shouldExclude($fullRelativePath)) {
                    continue;
                }
                
                // Skip files larger than 50MB untuk full backup
                if ($file->getSize() > 50 * 1024 * 1024) {
                    continue;
                }
                
                // Skip files that can't be read
                if (!$file->isReadable()) {
                    continue;
                }
                
                // Gunakan addFromString untuk menghindari file locking
                $content = file_get_contents($file->getRealPath());
                if ($content !== false) {
                    $zip->addFromString($fullRelativePath, $content);
                }
            } catch (\Exception $e) {
                // Skip problematic files
                continue;
            }
        }
    }

    /**
     * Tambahkan direktori ke ZIP secara rekursif
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $path, string $relativePath): void
    {
        if (!File::isDirectory($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            try {
                $filePath = $file->getRealPath();
                $subPath = $iterator->getSubPathname();
                
                // Normalize path separators
                $subPath = str_replace('\\', '/', $subPath);
                
                // Skip excluded paths
                $fullRelativePath = $relativePath ? $relativePath . '/' . $subPath : $subPath;
                if ($this->shouldExclude($fullRelativePath)) {
                    continue;
                }

                if ($file->isDir()) {
                    $zip->addEmptyDir($fullRelativePath);
                } else {
                    // Skip files larger than 10MB to prevent memory issues
                    if ($file->getSize() > 10 * 1024 * 1024) {
                        continue;
                    }
                    // Skip files that can't be read
                    if (!$file->isReadable()) {
                        continue;
                    }
                    $zip->addFile($filePath, $fullRelativePath);
                }
            } catch (\Exception $e) {
                // Skip problematic files
                continue;
            }
        }
    }

    /**
     * Cek apakah file/direktori harus di-exclude
     */
    protected function shouldExclude(string $path): bool
    {
        // Normalize path separators
        $path = str_replace('\\', '/', $path);

        // Check excluded directories
        foreach ($this->excludeDirs as $excludeDir) {
            if (str_starts_with($path, $excludeDir . '/') || $path === $excludeDir) {
                return true;
            }
        }

        // Check excluded files
        foreach ($this->excludeFiles as $excludeFile) {
            if (basename($path) === $excludeFile) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get daftar backup yang tersedia
     */
    public function getBackupList(): array
    {
        $backupPath = storage_path('app/backups');
        
        if (!File::exists($backupPath)) {
            return [];
        }

        $files = File::files($backupPath);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => $file->getFilename(),
                'size' => $this->formatBytes($file->getSize()),
                'size_bytes' => $file->getSize(),
                'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i:s'),
                'type' => $this->getBackupType($file->getFilename()),
            ];
        }

        // Sort by newest first
        usort($backups, fn($a, $b) => $b['size_bytes'] <=> $a['size_bytes']);

        return $backups;
    }

    /**
     * Hapus file backup
     */
    public function deleteBackup(string $filename): bool
    {
        $filepath = storage_path('app/backups/' . $filename);
        
        if (File::exists($filepath)) {
            return File::delete($filepath);
        }

        return false;
    }

    /**
     * Get path file backup untuk download
     */
    public function getBackupPath(string $filename): ?string
    {
        $filepath = storage_path('app/backups/' . $filename);
        
        if (File::exists($filepath)) {
            return $filepath;
        }

        return null;
    }

    /**
     * Format bytes ke human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get tipe backup dari nama file
     */
    protected function getBackupType(string $filename): string
    {
        if (str_starts_with($filename, 'backup_full_')) {
            return 'Full Backup';
        } elseif (str_starts_with($filename, 'database_')) {
            return 'Database';
        } elseif (str_starts_with($filename, 'files_')) {
            return 'Files';
        }
        return 'Unknown';
    }
}
