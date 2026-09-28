<?php

namespace Tests\Feature;

use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_face_scan_api_is_public_but_validates_native_payload(): void
    {
        $this->postJson('/api/v1/presensi-wajah/scan', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'descriptor',
                'proof_image',
                'location',
            ]);
    }
}
