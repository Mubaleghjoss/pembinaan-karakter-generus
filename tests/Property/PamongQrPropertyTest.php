<?php

namespace Tests\Property;

use App\Models\Role;
use App\Models\User;
use App\Services\PamongQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Pamong QR Token functionality.
 *
 * **Feature: calendar-schedule-reminder, Properties 13, 14**
 * **Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5**
 */
class PamongQrPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected PamongQrService $pamongQrService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->pamongQrService = new PamongQrService();
    }

    private function seedRoles(): void
    {
        if (Role::count() === 0) {
            Role::create([
                'id' => 1,
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access',
                'permissions' => ['view_students', 'manage_students'],
                'is_active' => true,
            ]);
            Role::create([
                'id' => 2,
                'name' => 'teacher',
                'display_name' => 'Guru/Pamong',
                'description' => 'Teacher access',
                'permissions' => ['view_students'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 13: Pamong QR Token Round-Trip**
     * **Validates: Requirements 10.1, 10.2, 10.5**
     *
     * Property: For any pamong user, generating a QR token and then verifying it 
     * should successfully identify the pamong.
     */
    public function test_qr_token_round_trip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $pamong = User::factory()->create(['role_id' => 2]);

            // Generate token
            $token = $this->pamongQrService->generateToken($pamong);
            $pamong->refresh();

            // Verify token
            $isValid = $this->pamongQrService->verifyToken($pamong, $token);

            $this->assertTrue($isValid, 'Token yang baru di-generate harus valid');
            $this->assertEquals($token, $pamong->qr_token, 'Token harus tersimpan di database');
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 13: Pamong QR Token Round-Trip**
     * **Validates: Requirements 10.1, 10.2, 10.5**
     *
     * Property: Different pamong should have different tokens.
     */
    public function test_different_pamong_have_different_tokens(): void
    {
        $tokens = [];

        for ($i = 0; $i < 10; $i++) {
            $pamong = User::factory()->create(['role_id' => 2]);
            $token = $this->pamongQrService->generateToken($pamong);
            $tokens[] = $token;
        }

        // All tokens should be unique
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(
            count($tokens),
            $uniqueTokens,
            'Setiap pamong harus memiliki token unik'
        );
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 13: Pamong QR Token Round-Trip**
     * **Validates: Requirements 10.1, 10.2, 10.5**
     *
     * Property: Wrong token should fail verification.
     */
    public function test_wrong_token_fails_verification(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $pamong = User::factory()->create(['role_id' => 2]);
            $this->pamongQrService->generateToken($pamong);
            $pamong->refresh();

            // Try to verify with wrong token
            $wrongToken = hash('sha256', 'wrong_token_' . $i);
            $isValid = $this->pamongQrService->verifyToken($pamong, $wrongToken);

            $this->assertFalse($isValid, 'Token yang salah harus gagal verifikasi');
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 13: Pamong QR Token Round-Trip**
     * **Validates: Requirements 10.1, 10.2, 10.5**
     *
     * Property: Token refresh should generate new valid token.
     */
    public function test_token_refresh_generates_new_token(): void
    {
        $pamong = User::factory()->create(['role_id' => 2]);

        $oldToken = $this->pamongQrService->generateToken($pamong);
        $pamong->refresh();

        // Refresh token
        $newTokenData = $this->pamongQrService->refreshToken($pamong);
        $pamong->refresh();

        $this->assertNotEquals($oldToken, $newTokenData['token'], 'Token baru harus berbeda');
        $this->assertTrue(
            $this->pamongQrService->verifyToken($pamong, $newTokenData['token']),
            'Token baru harus valid'
        );
        $this->assertFalse(
            $this->pamongQrService->verifyToken($pamong, $oldToken),
            'Token lama harus invalid'
        );
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 13: Pamong QR Token Round-Trip**
     * **Validates: Requirements 10.1, 10.2, 10.5**
     *
     * Property: getQrData should return complete QR data.
     */
    public function test_get_qr_data_returns_complete_data(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $pamong = User::factory()->create(['role_id' => 2]);

            $qrData = $this->pamongQrService->getQrData($pamong);

            $this->assertArrayHasKey('token', $qrData);
            $this->assertArrayHasKey('qr_image_base64', $qrData);
            $this->assertArrayHasKey('qr_image_svg', $qrData);
            $this->assertArrayHasKey('generated_at', $qrData);
            $this->assertArrayHasKey('user_info', $qrData);

            $this->assertNotEmpty($qrData['token']);
            $this->assertStringStartsWith('data:image/svg+xml;base64,', $qrData['qr_image_base64']);
            $this->assertStringContainsString('<svg', $qrData['qr_image_svg']);

            $this->assertEquals($pamong->id, $qrData['user_info']['id']);
            $this->assertEquals($pamong->name, $qrData['user_info']['name']);
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 13: Pamong QR Token Round-Trip**
     * **Validates: Requirements 10.1, 10.2, 10.5**
     *
     * Property: parsePayload should correctly identify pamong QR.
     */
    public function test_parse_payload_identifies_pamong(): void
    {
        $pamong = User::factory()->create(['role_id' => 2]);
        $token = $this->pamongQrService->generateToken($pamong);
        $pamong->refresh();

        // Build payload manually to test parsing
        $qrData = $this->pamongQrService->getQrData($pamong);
        
        // The payload is embedded in the QR code
        // We can test parsePayload with a constructed payload
        $delimiter = config('qrcode.payload.delimiter', '|');
        $hmacAlgorithm = config('qrcode.encryption.hmac_algorithm', 'sha256');
        $hash = hash_hmac($hmacAlgorithm, $pamong->id . $token, config('app.key'));
        
        $payload = implode($delimiter, [
            'PKG-P',
            '1',
            $pamong->id,
            $token,
            substr($hash, 0, 16),
        ]);

        $parsed = $this->pamongQrService->parsePayload($payload);

        $this->assertNotNull($parsed);
        $this->assertEquals('pamong', $parsed['type']);
        $this->assertEquals($pamong->id, $parsed['id']);
        $this->assertEquals($token, $parsed['token']);
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 14: Pamong Card Data Completeness**
     * **Validates: Requirements 10.3, 10.4**
     *
     * Property: For any pamong with a generated QR code, the card view should 
     * contain: nama, NIP/ID, and valid QR code image.
     */
    public function test_pamong_card_data_completeness(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $pamong = User::factory()->create([
                'role_id' => 2,
                'name' => 'Pamong Test ' . $i,
                'username' => 'pamong' . $i,
            ]);

            $qrData = $this->pamongQrService->getQrData($pamong);

            // Card should have all required data
            $this->assertNotEmpty($qrData['user_info']['name'], 'Nama harus ada');
            $this->assertNotEmpty($qrData['user_info']['id'], 'ID harus ada');
            $this->assertNotEmpty($qrData['qr_image_svg'], 'QR code harus ada');

            // QR code should be valid SVG
            $this->assertStringContainsString('<svg', $qrData['qr_image_svg']);
            $this->assertStringContainsString('</svg>', $qrData['qr_image_svg']);
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 13: Pamong QR Token Round-Trip**
     * **Validates: Requirements 10.1, 10.2, 10.5**
     *
     * Property: isPamong should correctly identify pamong users.
     */
    public function test_is_pamong_identification(): void
    {
        // Teacher role should be pamong
        $teacher = User::factory()->create(['role_id' => 2]);
        $this->assertTrue(
            $this->pamongQrService->isPamong($teacher),
            'User dengan role teacher harus diidentifikasi sebagai pamong'
        );

        // Admin role should also be pamong
        $admin = User::factory()->create(['role_id' => 1]);
        $this->assertTrue(
            $this->pamongQrService->isPamong($admin),
            'User dengan role admin harus diidentifikasi sebagai pamong'
        );
    }
}
