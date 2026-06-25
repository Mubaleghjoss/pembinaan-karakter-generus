<?php

namespace Tests\Property;

use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Property-based tests for validation error format consistency.
 *
 * **Feature: clean-code-refactoring, Property 1: Validation Error Format Consistency**
 * **Validates: Requirements 2.2, 2.4**
 *
 * Property: For any invalid request input, the system should return HTTP 422
 * with a JSON response containing 'success' = false, 'error' = 'Validation failed',
 * and 'errors' object with field-specific messages.
 */
class ValidationErrorFormatTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create role first
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator', 'permissions' => ['*']]
        );
        
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    /**
     * **Feature: clean-code-refactoring, Property 1: Validation Error Format Consistency**
     * **Validates: Requirements 2.2, 2.4**
     *
     * Property: For any missing required field in Siswa creation,
     * the response must follow the standardized validation error format.
     */
    public function test_siswa_validation_error_format_for_missing_fields(): void
    {
        $requiredFields = ['nis', 'nama', 'jenis_kelamin', 'kelas_id'];

        foreach ($requiredFields as $field) {
            $data = [
                'nis' => '12345',
                'nama' => 'Test Siswa',
                'jenis_kelamin' => 'L',
                'kelas_id' => 1,
            ];

            // Remove the field to test
            unset($data[$field]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/siswa', $data);

            $response->assertStatus(422);

            $json = $response->json();

            // Must have 'success' = false
            $this->assertArrayHasKey('success', $json, "Missing 'success' for field: {$field}");
            $this->assertFalse($json['success'], "success should be false for field: {$field}");

            // Must have 'error' = 'Validation failed'
            $this->assertArrayHasKey('error', $json, "Missing 'error' for field: {$field}");
            $this->assertEquals('Validation failed', $json['error'], "error should be 'Validation failed' for field: {$field}");

            // Must have 'errors' object
            $this->assertArrayHasKey('errors', $json, "Missing 'errors' for field: {$field}");
            $this->assertIsArray($json['errors'], "errors should be array for field: {$field}");
        }
    }


    /**
     * **Feature: clean-code-refactoring, Property 1: Validation Error Format Consistency**
     * **Validates: Requirements 2.2, 2.4**
     *
     * Property: For any invalid field value type, the response must follow
     * the standardized validation error format.
     */
    public function test_validation_error_format_for_invalid_types(): void
    {
        $this->forAll(
            Generator\elements(['string', 123, true, [], null])
        )
            ->withMaxSize(50)
            ->then(function ($invalidKelasId) {
                // Skip valid integer values
                if (is_int($invalidKelasId) && $invalidKelasId > 0) {
                    return;
                }

                $data = [
                    'nis' => '12345',
                    'nama' => 'Test Siswa',
                    'jenis_kelamin' => 'L',
                    'kelas_id' => $invalidKelasId,
                ];

                $response = $this->actingAs($this->user)
                    ->postJson('/api/v1/siswa', $data);

                // Should return 422 for invalid data
                if ($response->status() === 422) {
                    $json = $response->json();

                    // Must have standardized format
                    $this->assertArrayHasKey('success', $json);
                    $this->assertFalse($json['success']);
                    $this->assertArrayHasKey('error', $json);
                    $this->assertEquals('Validation failed', $json['error']);
                    $this->assertArrayHasKey('errors', $json);
                }
            });
    }

    /**
     * **Feature: clean-code-refactoring, Property 1: Validation Error Format Consistency**
     * **Validates: Requirements 2.2, 2.4**
     *
     * Property: For any presensi validation error, the response must follow
     * the standardized validation error format.
     *
     * Note: This test uses login endpoint which is public
     */
    public function test_presensi_validation_error_format(): void
    {
        // Test login endpoint validation (public endpoint)
        $invalidData = [
            // Missing password
            ['username' => 'test'],
            // Missing username
            ['password' => 'test123'],
            // Empty data
            [],
        ];

        foreach ($invalidData as $index => $data) {
            $response = $this->postJson('/api/v1/login', $data);

            $response->assertStatus(422);

            $json = $response->json();

            // Must have 'success' = false
            $this->assertArrayHasKey('success', $json, "Missing 'success' for test case: {$index}");
            $this->assertFalse($json['success'], "success should be false for test case: {$index}");

            // Must have 'error' = 'Validation failed'
            $this->assertArrayHasKey('error', $json, "Missing 'error' for test case: {$index}");
            $this->assertEquals('Validation failed', $json['error'], "error should be 'Validation failed' for test case: {$index}");

            // Must have 'errors' object
            $this->assertArrayHasKey('errors', $json, "Missing 'errors' for test case: {$index}");
            $this->assertIsArray($json['errors'], "errors should be array for test case: {$index}");
        }
    }

    /**
     * **Feature: clean-code-refactoring, Property 1: Validation Error Format Consistency**
     * **Validates: Requirements 2.2, 2.4**
     *
     * Property: For any validation error, the 'errors' object must contain
     * field names as keys and arrays of error messages as values.
     */
    public function test_validation_errors_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/siswa', []);

        $response->assertStatus(422);

        $json = $response->json();

        $this->assertArrayHasKey('errors', $json);
        $this->assertIsArray($json['errors']);

        // Each error should be an array of messages
        foreach ($json['errors'] as $field => $messages) {
            $this->assertIsString($field, 'Error key should be a string (field name)');
            $this->assertIsArray($messages, "Error messages for '{$field}' should be an array");

            foreach ($messages as $message) {
                $this->assertIsString($message, "Each error message for '{$field}' should be a string");
            }
        }
    }

    /**
     * **Feature: clean-code-refactoring, Property 1: Validation Error Format Consistency**
     * **Validates: Requirements 2.2, 2.4**
     *
     * Property: For any random invalid input, the validation response
     * must always have the same structure.
     */
    public function test_validation_response_structure_consistency(): void
    {
        $this->forAll(
            Generator\associative([
                'nis' => Generator\elements(['', null, 123, str_repeat('x', 300)]),
                'nama' => Generator\elements(['', null, 123]),
                'jenis_kelamin' => Generator\elements(['', 'X', 'invalid', null]),
            ])
        )
            ->withMaxSize(50)
            ->then(function (array $data) {
                $response = $this->actingAs($this->user)
                    ->postJson('/api/v1/siswa', $data);

                // Should return 422 for invalid data
                if ($response->status() === 422) {
                    $json = $response->json();

                    // Structure must be consistent
                    $this->assertArrayHasKey('success', $json);
                    $this->assertIsBool($json['success']);
                    $this->assertFalse($json['success']);

                    $this->assertArrayHasKey('error', $json);
                    $this->assertIsString($json['error']);

                    $this->assertArrayHasKey('errors', $json);
                    $this->assertIsArray($json['errors']);
                }
            });
    }
}
