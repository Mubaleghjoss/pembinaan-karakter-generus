<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use Illuminate\Console\Command;

class TestPendingVerificationClean extends Command
{
    protected $signature = 'test:pending-verification-clean {nis? : NIS siswa}';
    protected $description = 'Hapus data test pending verification';

    public function handle()
    {
        $nis = $this->argument('nis');
        
        if ($nis) {
            $siswa = Siswa::where('nis', $nis)->first();
        } else {
            $siswa = Siswa::where('is_active', true)->first();
        }

        if (!$siswa) {
            $this->error('Siswa tidak ditemukan!');
            return 1;
        }

        $deleted = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->where(function($q) {
                $q->where('student_note', 'like', 'Test dari%');
            })
            ->delete();

        $this->info("🧹 {$deleted} data test berhasil dihapus untuk {$siswa->nama}.");
        return 0;
    }
}
