<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for frequently queried columns
        
        // Siswa table indexes
        if (Schema::hasTable('siswa')) {
            Schema::table('siswa', function (Blueprint $table) {
                // Index for NIS lookups
                if (!$this->indexExists('siswa', 'siswa_nis_index')) {
                    $table->index('nis', 'siswa_nis_index');
                }
                // Index for kelas filtering
                if (Schema::hasColumn('siswa', 'kelas_id') && !$this->indexExists('siswa', 'siswa_kelas_id_index')) {
                    $table->index('kelas_id', 'siswa_kelas_id_index');
                }
                // Index for active status filtering
                if (Schema::hasColumn('siswa', 'is_active') && !$this->indexExists('siswa', 'siswa_is_active_index')) {
                    $table->index('is_active', 'siswa_is_active_index');
                }
            });
        }
        
        // Presensi table indexes
        if (Schema::hasTable('presensi')) {
            Schema::table('presensi', function (Blueprint $table) {
                // Index for siswa lookups
                if (!$this->indexExists('presensi', 'presensi_siswa_id_index')) {
                    $table->index('siswa_id', 'presensi_siswa_id_index');
                }
                // Index for date filtering
                if (!$this->indexExists('presensi', 'presensi_tanggal_index')) {
                    $table->index('tanggal', 'presensi_tanggal_index');
                }
                // Index for status filtering
                if (!$this->indexExists('presensi', 'presensi_status_index')) {
                    $table->index('status', 'presensi_status_index');
                }
            });
        }
        
        // TracerKarakter table indexes
        if (Schema::hasTable('tracer_karakter')) {
            Schema::table('tracer_karakter', function (Blueprint $table) {
                if (!$this->indexExists('tracer_karakter', 'tracer_karakter_siswa_id_index')) {
                    $table->index('siswa_id', 'tracer_karakter_siswa_id_index');
                }
                if (Schema::hasColumn('tracer_karakter', 'karakter_id') && !$this->indexExists('tracer_karakter', 'tracer_karakter_karakter_id_index')) {
                    $table->index('karakter_id', 'tracer_karakter_karakter_id_index');
                }
            });
        }
        
        // PR table indexes
        if (Schema::hasTable('pr')) {
            Schema::table('pr', function (Blueprint $table) {
                if (Schema::hasColumn('pr', 'deadline') && !$this->indexExists('pr', 'pr_deadline_index')) {
                    $table->index('deadline', 'pr_deadline_index');
                }
            });
        }
        
        // PRSubmission table indexes
        if (Schema::hasTable('pr_submissions')) {
            Schema::table('pr_submissions', function (Blueprint $table) {
                if (!$this->indexExists('pr_submissions', 'pr_submissions_pr_id_index')) {
                    $table->index('pr_id', 'pr_submissions_pr_id_index');
                }
                if (!$this->indexExists('pr_submissions', 'pr_submissions_siswa_id_index')) {
                    $table->index('siswa_id', 'pr_submissions_siswa_id_index');
                }
                if (Schema::hasColumn('pr_submissions', 'status') && !$this->indexExists('pr_submissions', 'pr_submissions_status_index')) {
                    $table->index('status', 'pr_submissions_status_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes
        if (Schema::hasTable('siswa')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropIndex('siswa_nis_index');
                $table->dropIndex('siswa_kelas_id_index');
                $table->dropIndex('siswa_is_active_index');
            });
        }
        
        if (Schema::hasTable('presensi')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->dropIndex('presensi_siswa_id_index');
                $table->dropIndex('presensi_tanggal_index');
                $table->dropIndex('presensi_status_index');
            });
        }
        
        if (Schema::hasTable('tracer_karakter')) {
            Schema::table('tracer_karakter', function (Blueprint $table) {
                $table->dropIndex('tracer_karakter_siswa_id_index');
                $table->dropIndex('tracer_karakter_karakter_id_index');
            });
        }
        
        if (Schema::hasTable('pr')) {
            Schema::table('pr', function (Blueprint $table) {
                $table->dropIndex('pr_deadline_index');
            });
        }
        
        if (Schema::hasTable('pr_submissions')) {
            Schema::table('pr_submissions', function (Blueprint $table) {
                $table->dropIndex('pr_submissions_pr_id_index');
                $table->dropIndex('pr_submissions_siswa_id_index');
                $table->dropIndex('pr_submissions_status_index');
            });
        }
    }
    
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }
        
        // For MySQL
        $indexes = $connection->select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
