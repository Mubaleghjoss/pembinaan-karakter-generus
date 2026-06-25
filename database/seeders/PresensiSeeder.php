<?php

namespace Database\Seeders;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PresensiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siswaIds = Siswa::pluck('id')->toArray();
        $verifierId = User::where('username', 'admin')->first()->id;

        // Generate presensi untuk 7 hari terakhir
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::now()->subDays($i)->format('Y-m-d');

            foreach ($siswaIds as $siswaId) {
                $siswa = Siswa::find($siswaId);

                // 80% kemungkinan hadir, 15% terlambat, 5% tidak hadir
                $random = rand(1, 100);

                if ($random <= 80) {
                    // Hadir tepat waktu
                    $jamMasuk = Carbon::createFromFormat('Y-m-d', $tanggal)
                        ->setTime(7, rand(0, 30), rand(0, 59));

                    Presensi::create([
                        'siswa_id' => $siswaId,
                        'tanggal' => $tanggal,
                        'jam_masuk' => $jamMasuk->format('H:i:s'),
                        'jam_keluar' => $jamMasuk->copy()->addHours(8)->format('H:i:s'),
                        'status' => 'hadir',
                        'qr_code_used' => $siswa->qr_code,
                        'scan_location' => 'Gerbang Utama PKG',
                        'scan_device_info' => 'Mobile Scanner v1.0',
                        'scan_ip_address' => '192.168.1.'.rand(10, 254),
                        'is_verified' => true,
                        'verified_by' => $verifierId,
                        'verified_at' => $jamMasuk,
                        'keterangan' => 'Scan QR Code berhasil',
                        'metadata' => json_encode([
                            'scan_method' => 'qr_code',
                            'device_type' => 'mobile',
                            'browser' => 'Chrome Mobile',
                            'location_accuracy' => '5m',
                        ]),
                    ]);
                } elseif ($random <= 95) {
                    // Terlambat
                    $jamMasuk = Carbon::createFromFormat('Y-m-d', $tanggal)
                        ->setTime(8, rand(0, 59), rand(0, 59));

                    Presensi::create([
                        'siswa_id' => $siswaId,
                        'tanggal' => $tanggal,
                        'jam_masuk' => $jamMasuk->format('H:i:s'),
                        'jam_keluar' => $jamMasuk->copy()->addHours(7)->format('H:i:s'),
                        'status' => 'terlambat',
                        'qr_code_used' => $siswa->qr_code,
                        'scan_location' => 'Gerbang Utama PKG',
                        'scan_device_info' => 'Mobile Scanner v1.0',
                        'scan_ip_address' => '192.168.1.'.rand(10, 254),
                        'is_verified' => true,
                        'verified_by' => $verifierId,
                        'verified_at' => $jamMasuk,
                        'keterangan' => 'Terlambat - Scan QR Code',
                        'metadata' => json_encode([
                            'scan_method' => 'qr_code',
                            'device_type' => 'mobile',
                            'browser' => 'Chrome Mobile',
                            'late_reason' => 'Macet di jalan',
                        ]),
                    ]);
                } else {
                    // Tidak hadir (alpha, izin, atau sakit)
                    $statusOptions = ['alpha', 'izin', 'sakit'];
                    $status = $statusOptions[array_rand($statusOptions)];

                    Presensi::create([
                        'siswa_id' => $siswaId,
                        'tanggal' => $tanggal,
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'status' => $status,
                        'qr_code_used' => null,
                        'scan_location' => null,
                        'scan_device_info' => null,
                        'scan_ip_address' => null,
                        'is_verified' => true,
                        'verified_by' => $verifierId,
                        'verified_at' => Carbon::createFromFormat('Y-m-d', $tanggal)->setTime(9, 0, 0),
                        'keterangan' => $status === 'izin' ? 'Izin keperluan keluarga' :
                                      ($status === 'sakit' ? 'Sakit demam' : 'Tidak hadir tanpa keterangan'),
                        'metadata' => json_encode([
                            'input_method' => 'manual',
                            'input_by' => 'teacher',
                        ]),
                    ]);
                }
            }
        }
    }
}
