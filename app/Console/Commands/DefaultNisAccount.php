<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Alat bantu akun login berbasis NIS.
 *
 * Tujuan: memastikan akun SISWA dan ORANG TUA tetap bisa login memakai NIS
 * sebagai password default, khususnya untuk akun lama yang password-nya
 * belum pernah diset (null/kosong).
 *
 * Perilaku aman:
 * - Secara default HANYA menginisialisasi akun yang password-nya masih kosong
 *   (tidak menimpa password yang sudah pernah diubah oleh pengguna).
 * - Gunakan --force untuk benar-benar mereset password ke NIS (menimpa yang ada).
 * - Selalu ada mode --check (dry-run) untuk melihat dampak tanpa menyimpan.
 *
 * Contoh:
 *   php artisan akun:default-nis --nis=25 --check
 *   php artisan akun:default-nis --nis=25            (inisialisasi bila kosong)
 *   php artisan akun:default-nis --nis=25 --force    (reset paksa ke NIS)
 *   php artisan akun:default-nis --all               (semua akun kosong -> NIS)
 *   php artisan akun:default-nis --all --scope=ortu  (batasi ke akun ortu saja)
 */
class DefaultNisAccount extends Command
{
    protected $signature = 'akun:default-nis
        {--nis= : Proses satu akun berdasarkan NIS}
        {--all : Proses semua siswa aktif}
        {--scope=both : Cakupan kredensial: siswa | ortu | both}
        {--force : Reset paksa password ke NIS (menimpa password lama)}
        {--check : Dry-run, tampilkan rencana tanpa menyimpan}';

    protected $description = 'Pastikan/atur NIS sebagai password default untuk akun siswa dan orang tua';

    public function handle(): int
    {
        $scope = strtolower((string) $this->option('scope'));
        if (! in_array($scope, ['siswa', 'ortu', 'both'], true)) {
            $this->error("Nilai --scope tidak valid: {$scope}. Gunakan siswa | ortu | both.");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $check = (bool) $this->option('check');
        $nis = $this->option('nis');
        $all = (bool) $this->option('all');

        if (! $nis && ! $all) {
            $this->error('Wajib pilih --nis=<NIS> atau --all.');

            return self::FAILURE;
        }

        $query = Siswa::query();
        if ($nis) {
            $query->where('nis', (string) $nis);
        }

        $targets = $query->orderBy('nis')->get();

        if ($targets->isEmpty()) {
            $this->warn('Tidak ada akun yang cocok.');

            return self::SUCCESS;
        }

        $mode = $force ? 'RESET PAKSA ke NIS' : 'inisialisasi bila kosong';
        $this->info(sprintf(
            '%s%s | scope=%s | mode=%s | target=%d akun',
            $check ? '[DRY-RUN] ' : '',
            'Default NIS',
            $scope,
            $mode,
            $targets->count()
        ));
        $this->newLine();

        $rows = [];
        $siswaChanged = 0;
        $ortuChanged = 0;

        foreach ($targets as $siswa) {
            $nisValue = (string) $siswa->nis;
            $siswaAction = '-';
            $ortuAction = '-';

            // ---- Kredensial SISWA (login pakai NIS sebagai username & password) ----
            if ($scope === 'siswa' || $scope === 'both') {
                $needs = $force || blank($siswa->getRawOriginal('password') ?? $siswa->password);
                // Bila sudah punya password dan cocok dengan NIS, anggap sudah default.
                $alreadyNis = filled($siswa->password) && Hash::check($nisValue, $siswa->password);

                if ($force) {
                    $siswaAction = 'reset -> NIS';
                } elseif (blank($siswa->password)) {
                    $siswaAction = 'init -> NIS';
                } elseif ($alreadyNis) {
                    $siswaAction = 'sudah=NIS';
                    $needs = false;
                } else {
                    $siswaAction = 'lewati (custom)';
                    $needs = false;
                }

                if ($needs && ! $check) {
                    $siswa->password = $nisValue; // mutator akan Hash::make
                    $siswaChanged++;
                } elseif ($needs) {
                    $siswaChanged++;
                }
            }

            // ---- Kredensial ORANG TUA (username default = NIS, password default = NIS) ----
            if ($scope === 'ortu' || $scope === 'both') {
                // Pastikan ortu_username terisi (default = NIS)
                if (blank($siswa->ortu_username)) {
                    if (! $check) {
                        $siswa->ortu_username = $nisValue;
                    }
                }

                $alreadyNisOrtu = filled($siswa->ortu_password) && Hash::check($nisValue, $siswa->ortu_password);

                if ($force) {
                    $ortuAction = 'reset -> NIS';
                    if (! $check) {
                        $siswa->ortu_password = $nisValue; // mutator akan Hash::make
                    }
                    $ortuChanged++;
                } elseif (blank($siswa->ortu_password)) {
                    $ortuAction = 'init -> NIS';
                    if (! $check) {
                        $siswa->ortu_password = $nisValue;
                    }
                    $ortuChanged++;
                } elseif ($alreadyNisOrtu) {
                    $ortuAction = 'sudah=NIS';
                } else {
                    $ortuAction = 'lewati (custom)';
                }
            }

            if (! $check && $siswa->isDirty()) {
                $siswa->save();
            }

            $rows[] = [
                $nisValue,
                mb_strimwidth((string) $siswa->nama, 0, 24, '…'),
                $siswa->canLogin() ? 'ya' : 'TIDAK',
                $siswaAction,
                $siswa->ortu_username ?: '(kosong)',
                $ortuAction,
            ];
        }

        $this->table(
            ['NIS', 'Nama', 'Aktif', 'Siswa pwd', 'Ortu user', 'Ortu pwd'],
            $rows
        );

        $this->newLine();
        if ($check) {
            $this->warn(sprintf(
                '[DRY-RUN] tidak ada yang disimpan. Rencana: siswa=%d, ortu=%d perubahan.',
                $siswaChanged,
                $ortuChanged
            ));
        } else {
            $this->info(sprintf(
                'Selesai. Password siswa diubah: %d | Password ortu diubah: %d.',
                $siswaChanged,
                $ortuChanged
            ));
            $this->line('Catatan: akun dengan password kustom TIDAK diubah (kecuali --force).');
        }

        return self::SUCCESS;
    }
}
