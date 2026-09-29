<?php

namespace Tests\Feature;

use App\Models\FaceProfile;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FaceAttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_face_profile_api_requires_sanctum_for_account_operations(): void
    {
        $this->getJson('/api/v1/presensi-wajah/profile')
            ->assertUnauthorized();

        $this->postJson('/api/v1/presensi-wajah/enroll', [])
            ->assertUnauthorized();
    }

    public function test_face_scan_api_requires_sanctum(): void
    {
        $this->postJson('/api/v1/presensi-wajah/scan', [])
            ->assertUnauthorized();
    }

    public function test_mobile_profile_does_not_report_legacy_active_profile_as_ready(): void
    {
        $siswa = Siswa::factory()->create();
        FaceProfile::query()->create([
            'subject_type' => FaceProfile::SUBJECT_SISWA,
            'subject_id' => $siswa->id,
            'status' => FaceProfile::STATUS_ACTIVE,
            'metadata' => ['face_model' => ['name' => 'Human.js']],
            'descriptor_payload' => 'legacy',
        ]);

        Sanctum::actingAs($siswa, ['siswa']);

        $this->getJson('/api/v1/presensi-wajah/profile')
            ->assertOk()
            ->assertJsonPath('data.configured', false)
            ->assertJsonPath('data.status', 'needs_mobile_enrollment')
            ->assertJsonPath('data.legacy_profile', true)
            ->assertJsonPath('data.profile_id', null);
    }
}
