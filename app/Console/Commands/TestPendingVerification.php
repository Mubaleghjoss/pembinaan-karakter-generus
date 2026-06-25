<?php

namespace App\Console\Commands;

use App\Models\Karakter;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestPendingVerification extends Command
{
    protected $signature = 'test:pending-verification {nis? : NIS siswa (default: siswa pertama)}';
    protected $description = 'Buat data test: tugas PKG dari hari kemarin & 2 hari lalu yang BELUM diverifikasi';

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

        $this->info("📋 Siswa: {$siswa->nama} (NIS: {$siswa->nis})");

        // Get active karakter tasks
        $karakters = Karakter::where('is_active', true)->take(4)->get();

        if ($karakters->isEmpty()) {
            $this->error('Tidak ada karakter aktif di database!');
            return 1;
        }

        $this->info("🔧 Membuat data test...\n");

        $created = 0;

        // Create unverified tasks from 1 day ago
        $yesterday = Carbon::yesterday();
        foreach ($karakters->take(2) as $karakter) {
            // Check if already exists
            $exists = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
                ->where('karakter_id', $karakter->id)
                ->whereDate('checked_at', $yesterday)
                ->exists();

            if ($exists) {
                $this->warn("  ⚠ Skip: {$karakter->nama} ({$yesterday->format('d M Y')}) — sudah ada");
                continue;
            }

            SiswaKarakterChecklist::create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $karakter->id,
                'checked_at' => $yesterday->copy()->setTime(rand(4, 8), rand(0, 59)),
                'verified_at' => null,
                'verified_by' => null,
                'student_note' => 'Test dari kemarin - belum diverifikasi',
            ]);
            $created++;
            $this->line("  ✅ Created: {$karakter->nama} — {$yesterday->format('d M Y')} (BELUM DIVERIFIKASI)");
        }

        // Create unverified tasks from 2 days ago
        $twoDaysAgo = Carbon::today()->subDays(2);
        foreach ($karakters->skip(2)->take(2) as $karakter) {
            $exists = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
                ->where('karakter_id', $karakter->id)
                ->whereDate('checked_at', $twoDaysAgo)
                ->exists();

            if ($exists) {
                $this->warn("  ⚠ Skip: {$karakter->nama} ({$twoDaysAgo->format('d M Y')}) — sudah ada");
                continue;
            }

            SiswaKarakterChecklist::create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $karakter->id,
                'checked_at' => $twoDaysAgo->copy()->setTime(rand(4, 8), rand(0, 59)),
                'verified_at' => null,
                'verified_by' => null,
                'student_note' => 'Test dari 2 hari lalu - belum diverifikasi',
            ]);
            $created++;
            $this->line("  ✅ Created: {$karakter->nama} — {$twoDaysAgo->format('d M Y')} (BELUM DIVERIFIKASI)");
        }

        $this->newLine();
        $this->info("🎉 Selesai! {$created} data test dibuat.");
        $this->newLine();
        $this->info("📌 Cara mengecek:");
        $this->line("  1. Buka browser: http://localhost:8000/siswa/login");
        $this->line("  2. Login dengan NIS: {$siswa->nis} dan password siswa");
        $this->line("  3. Buka menu 'Tugas PKG' atau langsung ke: http://localhost:8000/siswa/karakter");
        $this->line("  4. Lihat section kuning '⏳ Menunggu Verifikasi' di atas daftar tugas");
        $this->newLine();
        $this->info("🧹 Untuk membersihkan data test:");
        $this->line("  php artisan test:pending-verification-clean {$siswa->nis}");

        return 0;
    }
}
