<?php

namespace App\Console\Commands;

use App\Models\AttendanceSchedule;
use App\Models\FaceProfile;
use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Services\FaceAttendanceService;
use App\Services\PresensiService;
use App\Support\FaceAttendanceConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SeedLocalFaceAttendanceDemo extends Command
{
    protected $signature = 'face:seed-local-demo';

    protected $description = 'Create one local-only dummy face enrollment and attendance record';

    public function handle(FaceAttendanceService $faces, PresensiService $presensi): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Refused: this command is only available in local/testing environments.');
            return self::FAILURE;
        }

        $this->configureLocalAttendance();
        $schedule = $this->openLocalSchedule();
        $descriptor = array_fill(0, 128, 0.05);
        $image = 'data:image/jpeg;base64,'.base64_encode('local-face-demo-image');

        $result = DB::transaction(function () use ($faces, $presensi, $descriptor, $image, $schedule) {
            $kelas = Kelas::firstOrCreate(
                ['kode_kelas' => 'LOCAL-FACE-DEMO'],
                ['nama' => 'Local Face Demo', 'tingkat' => '7', 'kapasitas' => 1, 'is_active' => true]
            );

            $siswa = Siswa::query()->where('nis', '9999999999')->first();
            if (! $siswa) {
                $siswa = Siswa::factory()->create([
                    'nis' => '9999999999',
                    'nama' => 'Local Face Demo',
                    'kelas_id' => $kelas->id,
                    'status' => 'active',
                    'is_active' => true,
                ]);
            } else {
                $siswa->forceFill(['kelas_id' => $kelas->id, 'status' => 'active', 'is_active' => true])->save();
            }

            Presensi::query()->where('siswa_id', $siswa->id)->whereDate('tanggal', today())->delete();
            FaceProfile::query()
                ->where('subject_type', FaceProfile::SUBJECT_SISWA)
                ->where('subject_id', $siswa->id)
                ->delete();

            $profile = $faces->enroll($siswa, $descriptor, $image, null, [
                'local_demo' => true,
                'client_captured_at' => now()->toIso8601String(),
            ]);

            $location = $faces->validateLocation([
                'lat' => FaceAttendanceConfig::DEFAULT_CENTER_LAT,
                'lng' => FaceAttendanceConfig::DEFAULT_CENTER_LNG,
                'accuracy_meters' => 10,
            ]);
            $match = $faces->findBestProfileMatch($descriptor, [FaceProfile::SUBJECT_SISWA]);
            if (! $match || ! $match['accepted'] || $match['profile']->id !== $profile->id) {
                throw new RuntimeException('Local dummy descriptor did not match its enrolled profile.');
            }

            $proofPath = $faces->storeProofImage($image);
            $attendance = $presensi->recordFaceAttendance($siswa, [
                'face' => [
                    'method' => 'face',
                    'profile_id' => $profile->id,
                    'local_demo' => true,
                    'match_distance' => $match['distance'],
                    'similarity_percent' => $match['similarity_percent'],
                    'proof_path' => $proofPath,
                    'location' => $location,
                ],
                'scan_location' => $location['lat'].','.$location['lng'],
                'scan_device_info' => ['user_agent' => 'local-face-demo'],
                'scan_ip_address' => '127.0.0.1',
            ]);

            return [$siswa, $profile, $attendance['presensi']];
        });

        [$siswa, $profile, $attendance] = $result;
        $this->info('FACE_LOCAL_DEMO_OK');
        $this->line('subject_id='.$siswa->id);
        $this->line('profile_id='.$profile->id);
        $this->line('presensi_id='.$attendance->id);
        $this->line('status='.$attendance->status);
        $this->line('attendance_method='.data_get($attendance->metadata, 'attendance_method'));

        return self::SUCCESS;
    }

    private function configureLocalAttendance(): void
    {
        foreach ([
            'face_attendance_enabled_siswa' => '1',
            'face_attendance_enabled_pamong' => '1',
            'face_attendance_center_lat' => (string) FaceAttendanceConfig::DEFAULT_CENTER_LAT,
            'face_attendance_center_lng' => (string) FaceAttendanceConfig::DEFAULT_CENTER_LNG,
            'face_attendance_radius_value' => '200',
            'face_attendance_radius_unit' => 'meter',
            'face_attendance_match_threshold' => '35.00',
            'face_attendance_max_accuracy_meters' => '150',
        ] as $key => $value) {
            \App\Models\Setting::set($key, $value, FaceAttendanceConfig::GROUP);
        }
    }

    private function openLocalSchedule(): AttendanceSchedule
    {
        AttendanceSchedule::query()->where('name', 'Local Face Demo Schedule')->delete();

        return AttendanceSchedule::create([
            'name' => 'Local Face Demo Schedule',
            'open_time' => '00:00:00',
            'late_threshold' => '23:59:00',
            'close_time' => '23:59:59',
            'target_audience' => AttendanceSchedule::TARGET_SISWA,
            'is_active' => true,
        ]);
    }
}
