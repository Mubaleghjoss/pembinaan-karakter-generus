<?php

namespace Tests\Feature;

use App\Models\AttendanceSchedule;
use App\Models\FaceProfile;
use App\Models\Kelas;
use App\Models\PamongPresensi;
use App\Models\Presensi;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\User;
use App\Services\FaceAttendanceService;
use App\Support\ParticipantProfileOptions;
use App\Support\TargetGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceAttendanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seedRoles();
        $this->configureFaceAttendance();
    }

    public function test_siswa_can_enroll_face_profile(): void
    {
        $siswa = Siswa::factory()->create([
            'kelas_id' => Kelas::factory(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $response = $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.face-profile.enroll'), [
                'descriptor' => $this->descriptor(0.1),
                'reference_image' => $this->imageData(),
                'client_captured_at' => now()->toIso8601String(),
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('face_profiles', [
            'subject_type' => FaceProfile::SUBJECT_SISWA,
            'subject_id' => $siswa->id,
            'status' => FaceProfile::STATUS_ACTIVE,
        ]);
    }

    public function test_required_face_enrollment_prompt_appears_until_profile_exists(): void
    {
        $siswa = Siswa::factory()->create([
            'kelas_id' => Kelas::factory(),
            'status' => 'active',
            'is_active' => true,
            'kelompok' => ParticipantProfileOptions::SAWAH_DALAM_1,
            'target_grade_override' => TargetGrade::SMP_7,
            'profile_assignment_confirmed_at' => now(),
        ]);

        $this->actingAs($siswa, 'siswa')
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Daftarkan Wajah Presensi')
            ->assertSee('Lewati dulu');

        app(FaceAttendanceService::class)->enroll($siswa, $this->descriptor(0.15), $this->imageData(), null);

        $this->actingAs($siswa->fresh(), 'siswa')
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertDontSee('Daftarkan Wajah Presensi');
    }

    public function test_profile_id_card_shows_saved_face_profile_update_panel(): void
    {
        $pamong = User::factory()->create([
            'role_id' => Role::query()->where('name', User::ROLE_TEACHER)->value('id'),
            'status' => 'active',
        ]);
        app(FaceAttendanceService::class)->enroll($pamong, $this->descriptor(0.18), $this->imageData(), $pamong->id);

        $this->actingAs($pamong)
            ->get(route('profile.id-card'))
            ->assertOk()
            ->assertSee('Data Wajah Presensi')
            ->assertSee('Terdaftar')
            ->assertSee('Perbarui Data Wajah');
    }

    public function test_public_face_scan_records_siswa_inside_radius(): void
    {
        $this->openSchedule(AttendanceSchedule::TARGET_SISWA);
        $siswa = Siswa::factory()->create([
            'kelas_id' => Kelas::factory(),
            'status' => 'active',
            'is_active' => true,
        ]);
        app(FaceAttendanceService::class)->enroll($siswa, $this->descriptor(0.2), $this->imageData(), null);

        $response = $this->postJson(route('face-presensi.scan'), [
            'descriptor' => $this->descriptor(0.2),
            'proof_image' => $this->imageData(),
            'location' => $this->insideLocation(),
            'client_captured_at' => now()->toIso8601String(),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.nis', $siswa->nis);

        $presensi = Presensi::query()->where('siswa_id', $siswa->id)->first();

        $this->assertNotNull($presensi);
        $this->assertFalse($presensi->is_verified);
        $this->assertSame('face', data_get($presensi->metadata, 'face.method'));
    }

    public function test_public_face_scan_rejects_outside_radius(): void
    {
        $this->openSchedule(AttendanceSchedule::TARGET_SISWA);
        $siswa = Siswa::factory()->create([
            'kelas_id' => Kelas::factory(),
            'status' => 'active',
            'is_active' => true,
        ]);
        app(FaceAttendanceService::class)->enroll($siswa, $this->descriptor(0.3), $this->imageData(), null);

        $response = $this->postJson(route('face-presensi.scan'), [
            'descriptor' => $this->descriptor(0.3),
            'proof_image' => $this->imageData(),
            'location' => [
                'lat' => -6.300000,
                'lng' => 106.700000,
                'accuracy_meters' => 10,
            ],
            'client_captured_at' => now()->toIso8601String(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => $response->json('message')]);

        $this->assertStringContainsString('di luar radius', $response->json('message'));
    }

    public function test_public_face_scan_rejects_inaccurate_location(): void
    {
        $this->openSchedule(AttendanceSchedule::TARGET_SISWA);
        $siswa = Siswa::factory()->create([
            'kelas_id' => Kelas::factory(),
            'status' => 'active',
            'is_active' => true,
        ]);
        app(FaceAttendanceService::class)->enroll($siswa, $this->descriptor(0.35), $this->imageData(), null);

        $response = $this->postJson(route('face-presensi.scan'), [
            'descriptor' => $this->descriptor(0.35),
            'proof_image' => $this->imageData(),
            'location' => [
                'lat' => -6.219501040781815,
                'lng' => 106.64336089878178,
                'accuracy_meters' => 500,
            ],
            'client_captured_at' => now()->toIso8601String(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('belum cukup akurat', $response->json('message'));
    }

    public function test_public_face_scan_rejects_non_matching_face(): void
    {
        $this->openSchedule(AttendanceSchedule::TARGET_SISWA);
        $siswa = Siswa::factory()->create([
            'kelas_id' => Kelas::factory(),
            'status' => 'active',
            'is_active' => true,
        ]);
        app(FaceAttendanceService::class)->enroll($siswa, $this->descriptor(0.0), $this->imageData(), null);

        $response = $this->postJson(route('face-presensi.scan'), [
            'descriptor' => $this->descriptor(3.0),
            'proof_image' => $this->imageData(),
            'location' => $this->insideLocation(),
            'client_captured_at' => now()->toIso8601String(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'FACE_NOT_MATCHED');
    }

    public function test_public_face_scan_rejects_when_no_active_schedule(): void
    {
        AttendanceSchedule::query()->delete();

        $siswa = Siswa::factory()->create([
            'kelas_id' => Kelas::factory(),
            'status' => 'active',
            'is_active' => true,
        ]);
        app(FaceAttendanceService::class)->enroll($siswa, $this->descriptor(0.25), $this->imageData(), null);

        $response = $this->postJson(route('face-presensi.scan'), [
            'descriptor' => $this->descriptor(0.25),
            'proof_image' => $this->imageData(),
            'location' => $this->insideLocation(),
            'client_captured_at' => now()->toIso8601String(),
        ]);

        $response->assertServerError()
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('Jadwal presensi belum dikonfigurasi', $response->json('message'));
    }

    public function test_public_face_scan_rejects_duplicate_after_checkout(): void
    {
        $this->openSchedule(AttendanceSchedule::TARGET_SISWA);
        $siswa = Siswa::factory()->create([
            'kelas_id' => Kelas::factory(),
            'status' => 'active',
            'is_active' => true,
        ]);
        app(FaceAttendanceService::class)->enroll($siswa, $this->descriptor(0.45), $this->imageData(), null);

        Presensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => now()->subHour(),
            'jam_keluar' => now(),
            'status' => 'hadir',
            'is_verified' => false,
        ]);

        $response = $this->postJson(route('face-presensi.scan'), [
            'descriptor' => $this->descriptor(0.45),
            'proof_image' => $this->imageData(),
            'location' => $this->insideLocation(),
            'client_captured_at' => now()->toIso8601String(),
        ]);

        $response->assertBadRequest()
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('sudah melakukan presensi', $response->json('message'));
    }

    public function test_public_face_scan_records_pamong_inside_radius(): void
    {
        $this->openSchedule(AttendanceSchedule::TARGET_PAMONG);
        $pamong = User::factory()->create([
            'role_id' => Role::query()->where('name', User::ROLE_TEACHER)->value('id'),
            'status' => 'active',
        ]);
        app(FaceAttendanceService::class)->enroll($pamong, $this->descriptor(0.4), $this->imageData(), $pamong->id);

        $response = $this->postJson(route('face-presensi.scan'), [
            'descriptor' => $this->descriptor(0.4),
            'proof_image' => $this->imageData(),
            'location' => $this->insideLocation(),
            'client_captured_at' => now()->toIso8601String(),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pamong.username', $pamong->username);

        $record = PamongPresensi::query()->where('user_id', $pamong->id)->first();

        $this->assertNotNull($record);
        $this->assertFalse($record->is_verified);
        $this->assertSame('face', data_get($record->metadata, 'face.method'));
    }

    private function configureFaceAttendance(): void
    {
        Setting::set('face_attendance_enabled_siswa', '1', 'face_attendance');
        Setting::set('face_attendance_enabled_pamong', '1', 'face_attendance');
        Setting::set('face_attendance_center_lat', '-6.219501040781815', 'face_attendance');
        Setting::set('face_attendance_center_lng', '106.64336089878178', 'face_attendance');
        Setting::set('face_attendance_radius_value', '200', 'face_attendance');
        Setting::set('face_attendance_radius_unit', 'meter', 'face_attendance');
        Setting::set('face_attendance_match_threshold', '35.00', 'face_attendance');
        Setting::set('face_attendance_max_accuracy_meters', '150', 'face_attendance');
    }

    private function openSchedule(string $target): AttendanceSchedule
    {
        AttendanceSchedule::query()->delete();

        return AttendanceSchedule::create([
            'name' => 'Jadwal Test',
            'open_time' => '00:00:00',
            'late_threshold' => '23:59:00',
            'close_time' => '23:59:59',
            'target_audience' => $target,
            'is_active' => true,
        ]);
    }

    private function descriptor(float $value): array
    {
        return array_fill(0, 64, $value);
    }

    private function imageData(): string
    {
        return 'data:image/jpeg;base64,'.base64_encode('fake-image');
    }

    private function insideLocation(): array
    {
        return [
            'lat' => -6.219501040781815,
            'lng' => 106.64336089878178,
            'accuracy_meters' => 10,
        ];
    }

    private function seedRoles(): void
    {
        Role::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => User::ROLE_ADMIN,
                'display_name' => 'Administrator',
                'permissions' => ['*'],
                'is_active' => true,
            ]
        );

        Role::query()->updateOrCreate(
            ['id' => 2],
            [
                'name' => User::ROLE_TEACHER,
                'display_name' => 'Pamong',
                'permissions' => ['view_students'],
                'is_active' => true,
            ]
        );

        Role::query()->updateOrCreate(
            ['id' => 3],
            [
                'name' => User::ROLE_PKG_MANAGER,
                'display_name' => 'Pengurus PKG',
                'permissions' => ['view_students'],
                'is_active' => true,
            ]
        );
    }
}
