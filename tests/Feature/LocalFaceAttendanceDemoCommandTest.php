<?php

namespace Tests\Feature;

use App\Models\FaceProfile;
use App\Models\Presensi;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalFaceAttendanceDemoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_command_creates_profile_and_saved_attendance(): void
    {
        $this->artisan('face:seed-local-demo')
            ->assertExitCode(0)
            ->expectsOutputToContain('FACE_LOCAL_DEMO_OK');

        $siswa = Siswa::query()->where('nis', '9999999999')->first();

        $this->assertNotNull($siswa);
        $this->assertTrue(FaceProfile::query()
            ->where('subject_id', $siswa->id)
            ->where('subject_type', FaceProfile::SUBJECT_SISWA)
            ->where('status', FaceProfile::STATUS_ACTIVE)
            ->exists());
        $this->assertTrue(Presensi::query()
            ->where('siswa_id', $siswa->id)
            ->where('metadata->attendance_method', 'face')
            ->exists());
    }
}
