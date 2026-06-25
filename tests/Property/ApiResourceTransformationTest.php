<?php

namespace Tests\Property;

use App\Http\Resources\KelasResource;
use App\Http\Resources\PresensiResource;
use App\Http\Resources\SiswaResource;
use App\Http\Resources\UserResource;
use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for API Resource transformation consistency.
 *
 * **Feature: clean-code-refactoring, Property 2: API Resource Transformation Consistency**
 * **Validates: Requirements 3.1, 3.2, 3.3**
 */
class ApiResourceTransformationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * **Feature: clean-code-refactoring, Property 2: API Resource Transformation Consistency**
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * Property: SiswaResource should never expose sensitive fields (qr_token, qr_secret_salt).
     */
    public function test_siswa_resource_excludes_sensitive_fields(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create([
            'kelas_id' => $kelas->id,
            'qr_token' => 'secret_token_value',
            'qr_secret_salt' => 'secret_salt_value',
        ]);

        $resource = new SiswaResource($siswa);
        $array = $resource->toArray(request());

        // Must NOT contain sensitive fields
        $this->assertArrayNotHasKey('qr_token', $array);
        $this->assertArrayNotHasKey('qr_secret_salt', $array);

        // Must contain expected fields
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('nis', $array);
        $this->assertArrayHasKey('nama', $array);
    }

    /**
     * **Feature: clean-code-refactoring, Property 2: API Resource Transformation Consistency**
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * Property: UserResource should never expose password field.
     */
    public function test_user_resource_excludes_password(): void
    {
        // Create role first for foreign key constraint
        $role = \App\Models\Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => bcrypt('secret_password'),
        ]);

        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        // Must NOT contain password
        $this->assertArrayNotHasKey('password', $array);

        // Must contain expected fields
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('email', $array);
    }


    /**
     * **Feature: clean-code-refactoring, Property 2: API Resource Transformation Consistency**
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * Property: KelasResource should contain all required fields.
     */
    public function test_kelas_resource_contains_required_fields(): void
    {
        $kelas = Kelas::factory()->create();

        $resource = new KelasResource($kelas);
        $array = $resource->toArray(request());

        // Must contain expected fields
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('nama', $array);
        $this->assertArrayHasKey('tingkat', $array);
    }

    /**
     * **Feature: clean-code-refactoring, Property 2: API Resource Transformation Consistency**
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * Property: PresensiResource should contain all required fields.
     */
    public function test_presensi_resource_contains_required_fields(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);
        $presensi = Presensi::factory()->create(['siswa_id' => $siswa->id]);

        $resource = new PresensiResource($presensi);
        $array = $resource->toArray(request());

        // Must contain expected fields
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('tanggal', $array);
        $this->assertArrayHasKey('status', $array);
    }

    /**
     * **Feature: clean-code-refactoring, Property 2: API Resource Transformation Consistency**
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * Property: Resources should handle null relationships gracefully.
     */
    public function test_resources_handle_null_relationships(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        // Don't load kelas relationship
        $resource = new SiswaResource($siswa);
        $array = $resource->toArray(request());

        // Should not throw error, kelas should be conditionally loaded
        $this->assertArrayHasKey('id', $array);
    }

    /**
     * **Feature: clean-code-refactoring, Property 2: API Resource Transformation Consistency**
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * Property: For any model, resource transformation should be consistent.
     */
    public function test_resource_transformation_is_consistent(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        // Transform same model multiple times
        $resource1 = new SiswaResource($siswa);
        $resource2 = new SiswaResource($siswa);

        $array1 = $resource1->toArray(request());
        $array2 = $resource2->toArray(request());

        // Results should be identical
        $this->assertEquals($array1, $array2);
    }

    /**
     * **Feature: clean-code-refactoring, Property 2: API Resource Transformation Consistency**
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * Property: Resource IDs should match model IDs.
     */
    public function test_resource_ids_match_model_ids(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);
        $role = \App\Models\Role::create([
            'name' => 'Test Role 2',
            'slug' => 'test-role-2',
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $siswaResource = new SiswaResource($siswa);
        $kelasResource = new KelasResource($kelas);
        $userResource = new UserResource($user);

        $this->assertEquals($siswa->id, $siswaResource->toArray(request())['id']);
        $this->assertEquals($kelas->id, $kelasResource->toArray(request())['id']);
        $this->assertEquals($user->id, $userResource->toArray(request())['id']);
    }
}
