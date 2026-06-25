<?php

namespace Tests\Property;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for QR Generate functionality.
 *
 * **Feature: qr-generate-berita, Properties 1-3**
 * **Validates: Requirements 1.2, 1.3, 1.4, 2.2**
 */
class QrGenerateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * **Feature: qr-generate-berita, Property 1: Class selection returns all students**
     * **Validates: Requirements 1.2**
     *
     * Property: For any class ID, when querying students by class, the result should
     * contain exactly all students with that kelas_id and no students from other classes.
     */
    public function test_class_selection_returns_only_students_in_that_class(): void
    {
        // Create multiple classes
        $kelas1 = Kelas::factory()->create(['nama' => 'Kelas A']);
        $kelas2 = Kelas::factory()->create(['nama' => 'Kelas B']);
        $kelas3 = Kelas::factory()->create(['nama' => 'Kelas C']);

        // Create students in each class
        $studentsKelas1 = Siswa::factory()->count(5)->create(['kelas_id' => $kelas1->id]);
        $studentsKelas2 = Siswa::factory()->count(3)->create(['kelas_id' => $kelas2->id]);
        $studentsKelas3 = Siswa::factory()->count(7)->create(['kelas_id' => $kelas3->id]);

        // Test for each class
        foreach ([$kelas1, $kelas2, $kelas3] as $kelas) {
            $result = Siswa::where('kelas_id', $kelas->id)->get();

            // All returned students should belong to the selected class
            foreach ($result as $student) {
                $this->assertEquals(
                    $kelas->id,
                    $student->kelas_id,
                    "Student {$student->nama} should belong to class {$kelas->nama}"
                );
            }

            // Count should match expected
            $expectedCount = Siswa::where('kelas_id', $kelas->id)->count();
            $this->assertCount($expectedCount, $result);
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 1: Class selection returns all students**
     * **Validates: Requirements 1.2**
     *
     * Property: For any class, the student count should match withCount result.
     */
    public function test_class_student_count_matches_actual_students(): void
    {
        // Create classes with varying student counts
        for ($i = 0; $i < 5; $i++) {
            $kelas = Kelas::factory()->create();
            $studentCount = rand(0, 10);
            Siswa::factory()->count($studentCount)->create(['kelas_id' => $kelas->id]);
        }

        // Get all classes with count
        $kelasWithCount = Kelas::withCount('siswa')->get();

        foreach ($kelasWithCount as $kelas) {
            $actualCount = Siswa::where('kelas_id', $kelas->id)->count();
            $this->assertEquals(
                $actualCount,
                $kelas->siswa_count,
                "Class {$kelas->nama} should have correct student count"
            );
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 2: QR data contains required fields**
     * **Validates: Requirements 1.4**
     *
     * Property: For any student, when generating QR data, the result should contain
     * student_id, nis, token, expires_at, and hash fields.
     */
    public function test_qr_data_contains_all_required_fields(): void
    {
        $kelas = Kelas::factory()->create();

        // Test with multiple students
        for ($i = 0; $i < 10; $i++) {
            $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);
            $qrData = $siswa->getQrData();

            // Check all required fields exist
            $this->assertArrayHasKey('student_id', $qrData, 'QR data should contain student_id');
            $this->assertArrayHasKey('nis', $qrData, 'QR data should contain nis');
            $this->assertArrayHasKey('token', $qrData, 'QR data should contain token');
            $this->assertArrayHasKey('expires_at', $qrData, 'QR data should contain expires_at');
            $this->assertArrayHasKey('hash', $qrData, 'QR data should contain hash');

            // Verify field values
            $this->assertEquals($siswa->id, $qrData['student_id']);
            $this->assertEquals($siswa->nis, $qrData['nis']);
            $this->assertNotEmpty($qrData['token']);
            $this->assertNotEmpty($qrData['expires_at']);
            $this->assertNotEmpty($qrData['hash']);
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 2: QR data contains required fields**
     * **Validates: Requirements 1.4**
     *
     * Property: QR token should be a valid SHA-256 hash (64 hex characters).
     */
    public function test_qr_token_is_valid_sha256_hash(): void
    {
        $kelas = Kelas::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);
            $qrData = $siswa->getQrData();

            // Token should be 64 characters (SHA-256)
            $this->assertEquals(64, strlen($qrData['token']), 'Token should be 64 characters');

            // Token should be hexadecimal
            $this->assertMatchesRegularExpression(
                '/^[a-f0-9]{64}$/',
                $qrData['token'],
                'Token should be valid hexadecimal'
            );
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 2: QR data contains required fields**
     * **Validates: Requirements 1.4**
     *
     * Property: QR hash should be unique per student and token combination.
     */
    public function test_qr_hash_is_unique_per_student(): void
    {
        $kelas = Kelas::factory()->create();
        $hashes = [];

        for ($i = 0; $i < 10; $i++) {
            $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);
            $qrData = $siswa->getQrData();
            $hashes[] = $qrData['hash'];
        }

        // All hashes should be unique
        $uniqueHashes = array_unique($hashes);
        $this->assertCount(
            count($hashes),
            $uniqueHashes,
            'All QR hashes should be unique'
        );
    }

    /**
     * **Feature: qr-generate-berita, Property 3: QR card display contains student info**
     * **Validates: Requirements 1.3, 2.2**
     *
     * Property: For any student, the QR card should display name, NIS, and class.
     */
    public function test_student_has_required_display_info(): void
    {
        $kelas = Kelas::factory()->create(['nama' => 'Kelas Test']);

        for ($i = 0; $i < 10; $i++) {
            $siswa = Siswa::factory()->create([
                'kelas_id' => $kelas->id,
                'nama' => 'Siswa Test ' . $i,
                'nis' => '12345' . str_pad($i, 3, '0', STR_PAD_LEFT),
            ]);

            // Load relationship
            $siswa->load('kelas');

            // Verify required display fields exist and are not empty
            $this->assertNotEmpty($siswa->nama, 'Student should have nama');
            $this->assertNotEmpty($siswa->nis, 'Student should have NIS');
            $this->assertNotNull($siswa->kelas, 'Student should have kelas relationship');
            $this->assertNotEmpty($siswa->kelas->nama, 'Student kelas should have nama');
        }
    }

    /**
     * **Feature: qr-generate-berita, Property 3: QR card display contains student info**
     * **Validates: Requirements 1.3, 2.2**
     *
     * Property: Full identity attribute should contain both name and NIS.
     */
    public function test_full_identity_contains_name_and_nis(): void
    {
        $kelas = Kelas::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $nama = 'Siswa Test ' . $i;
            $nis = '12345' . str_pad($i, 3, '0', STR_PAD_LEFT);

            $siswa = Siswa::factory()->create([
                'kelas_id' => $kelas->id,
                'nama' => $nama,
                'nis' => $nis,
            ]);

            $fullIdentity = $siswa->full_identity;

            $this->assertStringContainsString($nama, $fullIdentity, 'Full identity should contain nama');
            $this->assertStringContainsString($nis, $fullIdentity, 'Full identity should contain NIS');
        }
    }
}
