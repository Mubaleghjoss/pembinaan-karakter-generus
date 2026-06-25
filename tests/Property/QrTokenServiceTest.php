<?php

namespace Tests\Property;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\QrTokenService;
use Carbon\Carbon;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for QR Token Service.
 *
 * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
 * **Validates: Requirements 9.3**
 */
class QrTokenServiceTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    protected QrTokenService $qrTokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qrTokenService = new QrTokenService;
    }

    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: For any siswa, generating a token should produce a unique 64-character hash.
     */
    public function test_generated_token_is_64_character_hash(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        $token = $this->qrTokenService->generate($siswa);

        // Token should be 64 characters (SHA-256 hash)
        $this->assertEquals(64, strlen($token));

        // Token should be hexadecimal
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: For any siswa, generating multiple tokens should produce different tokens.
     */
    public function test_multiple_token_generations_produce_unique_tokens(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $tokens[] = $this->qrTokenService->generate($siswa);
            usleep(1000); // Small delay to ensure different timestamps
        }

        // All tokens should be unique
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(count($tokens), $uniqueTokens, 'All generated tokens should be unique');
    }

    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: For any two different siswa, their tokens should be different.
     */
    public function test_different_siswa_have_different_tokens(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa1 = Siswa::factory()->create(['kelas_id' => $kelas->id]);
        $siswa2 = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        $token1 = $this->qrTokenService->generate($siswa1);
        $token2 = $this->qrTokenService->generate($siswa2);

        $this->assertNotEquals($token1, $token2, 'Different siswa should have different tokens');
    }


    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: For any valid token, verification should return true before expiration.
     */
    public function test_valid_token_verification_returns_true(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        $token = $this->qrTokenService->generate($siswa);
        $siswa->refresh();

        $isValid = $this->qrTokenService->verify($siswa, $token);

        $this->assertTrue($isValid, 'Valid token should pass verification');
    }

    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: For any expired token, verification should return false.
     */
    public function test_expired_token_verification_returns_false(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        // Generate token with 1 minute expiry
        $token = $this->qrTokenService->generate($siswa, 1);
        $siswa->refresh();

        // Travel 2 minutes into the future
        Carbon::setTestNow(Carbon::now()->addMinutes(2));

        $isValid = $this->qrTokenService->verify($siswa, $token);

        $this->assertFalse($isValid, 'Expired token should fail verification');

        // Reset time
        Carbon::setTestNow();
    }

    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: For any wrong token, verification should return false.
     */
    public function test_wrong_token_verification_returns_false(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        $this->qrTokenService->generate($siswa);
        $siswa->refresh();

        // Try to verify with a wrong token
        $wrongToken = hash('sha256', 'wrong_token_data');
        $isValid = $this->qrTokenService->verify($siswa, $wrongToken);

        $this->assertFalse($isValid, 'Wrong token should fail verification');
    }

    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: For any expiry time, the token should expire exactly at that time.
     */
    public function test_token_expiry_time_is_correct(): void
    {
        $this->forAll(
            Generator\choose(1, 120) // 1 to 120 minutes
        )
            ->withMaxSize(10)
            ->then(function (int $expiryMinutes) {
                $kelas = Kelas::factory()->create();
                $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

                $beforeGenerate = Carbon::now();
                $this->qrTokenService->generate($siswa, $expiryMinutes);
                $siswa->refresh();

                $expectedExpiry = $beforeGenerate->copy()->addMinutes($expiryMinutes);

                // Allow 1 second tolerance for test execution time
                $this->assertTrue(
                    $siswa->qr_token_expires_at->diffInSeconds($expectedExpiry) <= 1,
                    "Token expiry should be approximately {$expiryMinutes} minutes from generation"
                );
            });
    }

    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: isExpired should return true when token is null or expired.
     */
    public function test_is_expired_returns_correct_status(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create([
            'kelas_id' => $kelas->id,
            'qr_token' => null,
            'qr_token_expires_at' => null,
        ]);

        // No token should be expired
        $this->assertTrue($this->qrTokenService->isExpired($siswa));

        // Generate token
        $this->qrTokenService->generate($siswa, 60);
        $siswa->refresh();

        // Fresh token should not be expired
        $this->assertFalse($this->qrTokenService->isExpired($siswa));

        // Travel to future
        Carbon::setTestNow(Carbon::now()->addMinutes(61));

        // Token should now be expired
        $this->assertTrue($this->qrTokenService->isExpired($siswa));

        Carbon::setTestNow();
    }

    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: Token stored in database should match generated token.
     */
    public function test_token_stored_in_database_matches_generated(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        $generatedToken = $this->qrTokenService->generate($siswa);
        $siswa->refresh();

        // Token in database should match generated token
        $this->assertEquals($generatedToken, $siswa->qr_token);

        // Expiry should be set
        $this->assertNotNull($siswa->qr_token_expires_at);
    }

    /**
     * **Feature: clean-code-refactoring, Property 7: QR Token Uniqueness and Expiration**
     * **Validates: Requirements 9.3**
     *
     * Property: getExpiresAt should return correct expiry time.
     */
    public function test_get_expires_at_returns_correct_time(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create([
            'kelas_id' => $kelas->id,
            'qr_token' => null,
            'qr_token_expires_at' => null,
        ]);

        // No token - should return null
        $this->assertNull($this->qrTokenService->getExpiresAt($siswa));

        // Generate token
        $this->qrTokenService->generate($siswa, 30);
        $siswa->refresh();

        // Should return expiry time
        $expiresAt = $this->qrTokenService->getExpiresAt($siswa);
        $this->assertNotNull($expiresAt);
        $this->assertEquals($siswa->qr_token_expires_at, $expiresAt);
    }
}
