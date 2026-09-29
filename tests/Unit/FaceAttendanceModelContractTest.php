<?php

namespace Tests\Unit;

use App\Models\FaceProfile;
use PHPUnit\Framework\TestCase;

class FaceAttendanceModelContractTest extends TestCase
{
    public function test_mobilefacenet_profile_metadata_identifies_the_descriptor_contract(): void
    {
        $profile = new FaceProfile;
        $profile->setRawAttributes([
            'metadata' => json_encode([
                'face_model' => [
                    'name' => 'MobileFaceNet',
                    'version' => 'qualcomm-mobilefacenet-v0.62.2',
                    'input' => '112x112-rgb',
                    'dimensions' => 128,
                    'normalization' => 'l2',
                ],
            ]),
        ]);

        $this->assertTrue($profile->usesFaceModelContract());
    }

    public function test_legacy_profile_is_not_compatible_with_mobilefacenet(): void
    {
        $profile = new FaceProfile;
        $profile->setRawAttributes(['metadata' => json_encode(['client_captured_at' => '2026-09-29T00:00:00Z'])]);

        $this->assertFalse($profile->usesFaceModelContract());
    }
}
