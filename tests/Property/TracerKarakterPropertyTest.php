<?php

namespace Tests\Property;

use App\Models\Karakter;
use App\Models\TracerKarakter;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Role;
use App\Models\User;
use App\Models\PamongSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Tracer Karakter functionality.
 */
class TracerKarakterPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $pamongUser;
    protected Kelas $kelas;
    protected array $karakterList = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrator', 'permissions' => ['*']]);
        $teacherRole = Role::create(['name' => 'teacher', 'display_name' => 'Pamong', 'permissions' => ['tracer_karakter']]);
        
        // Create users
        $this->adminUser = User::create([
            'username' => 'admin_test',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        
        $this->pamongUser = User::create([
            'username' => 'pamong_test',
            'email' => 'pamong@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        
        // Create kelas
        $this->kelas = Kelas::create([
            'nama' => 'Kelas Test',
            'kode_kelas' => 'KT_' . uniqid(),
            'is_active' => true,
        ]);
        
        // Create karakter
        for ($i = 1; $i <= 5; $i++) {
            $this->karakterList[] = Karakter::create([
                'nama' => 'Karakter ' . $i,
                'deskripsi' => 'Deskripsi karakter ' . $i,
                'is_active' => true,
            ]);
        }
    }


    /**
     * **Feature: website-settings, Property 10: Tracer karakter records all required fields**
     * *For any* karakter check, the record should contain siswa_id, karakter_id, pamong_id, and checked_at.
     * **Validates: Requirements 6.3**
     * 
     * @test
     */
    public function tracer_karakter_records_all_required_fields(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Create random siswa
            $siswa = Siswa::create([
                'nis' => 'NIS_' . $i . '_' . uniqid(),
                'nama' => 'Siswa Test ' . $i,
                'kelas_id' => $this->kelas->id,
                'is_active' => true,
                'status' => 'active',
            ]);
            
            // Pick random karakter
            $karakter = $this->karakterList[array_rand($this->karakterList)];
            
            // Pick random pamong (admin or teacher)
            $pamong = rand(0, 1) ? $this->adminUser : $this->pamongUser;
            
            // Generate random checked_at within last 30 days
            $checkedAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23));
            
            // Random catatan (sometimes null)
            $catatan = rand(0, 1) ? 'Catatan untuk record ' . $i : null;
            
            // Create tracer record
            $tracer = TracerKarakter::create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $karakter->id,
                'pamong_id' => $pamong->id,
                'checked_at' => $checkedAt,
                'catatan' => $catatan,
            ]);
            
            // Retrieve and verify all required fields are present
            $retrieved = TracerKarakter::find($tracer->id);
            
            $this->assertNotNull($retrieved, 'Tracer record should exist');
            $this->assertNotNull($retrieved->siswa_id, 'siswa_id should not be null');
            $this->assertNotNull($retrieved->karakter_id, 'karakter_id should not be null');
            $this->assertNotNull($retrieved->pamong_id, 'pamong_id should not be null');
            $this->assertNotNull($retrieved->checked_at, 'checked_at should not be null');
            
            // Verify values match
            $this->assertEquals($siswa->id, $retrieved->siswa_id);
            $this->assertEquals($karakter->id, $retrieved->karakter_id);
            $this->assertEquals($pamong->id, $retrieved->pamong_id);
            $this->assertEquals($catatan, $retrieved->catatan);
            
            // Verify relationships work
            $this->assertNotNull($retrieved->siswa);
            $this->assertNotNull($retrieved->karakter);
            $this->assertNotNull($retrieved->pamong);
            
            $this->assertEquals($siswa->nama, $retrieved->siswa->nama);
            $this->assertEquals($karakter->nama, $retrieved->karakter->nama);
            $this->assertEquals($pamong->username, $retrieved->pamong->username);
        }
    }

    /**
     * **Feature: website-settings, Property 11: Rekap calculation correctness**
     * *For any* student, the karakter completion percentage should equal 
     * (checked karakter count / total active karakter count) * 100.
     * **Validates: Requirements 7.4, 14.3**
     * 
     * @test
     */
    public function rekap_calculation_correctness(): void
    {
        $totalActiveKarakter = count($this->karakterList);
        
        for ($i = 0; $i < 50; $i++) {
            // Create siswa
            $siswa = Siswa::create([
                'nis' => 'NIS_REKAP_' . $i . '_' . uniqid(),
                'nama' => 'Siswa Rekap ' . $i,
                'kelas_id' => $this->kelas->id,
                'is_active' => true,
                'status' => 'active',
            ]);
            
            // Randomly check some karakter (0 to all)
            $numToCheck = rand(0, $totalActiveKarakter);
            $checkedKarakterIds = [];
            
            // Shuffle and pick random karakter to check
            $shuffledKarakter = $this->karakterList;
            shuffle($shuffledKarakter);
            
            for ($j = 0; $j < $numToCheck; $j++) {
                $karakter = $shuffledKarakter[$j];
                
                TracerKarakter::create([
                    'siswa_id' => $siswa->id,
                    'karakter_id' => $karakter->id,
                    'pamong_id' => $this->pamongUser->id,
                    'checked_at' => now(),
                    'catatan' => null,
                ]);
                
                $checkedKarakterIds[] = $karakter->id;
            }
            
            // Calculate expected percentage
            $uniqueCheckedCount = count(array_unique($checkedKarakterIds));
            $expectedPercentage = $totalActiveKarakter > 0 
                ? round(($uniqueCheckedCount / $totalActiveKarakter) * 100, 1) 
                : 0;
            
            // Calculate actual percentage using the same logic as controller
            $actualCheckedCount = TracerKarakter::where('siswa_id', $siswa->id)
                ->distinct('karakter_id')
                ->count('karakter_id');
            
            $actualPercentage = $totalActiveKarakter > 0 
                ? round(($actualCheckedCount / $totalActiveKarakter) * 100, 1) 
                : 0;
            
            // Verify calculation
            $this->assertEquals($uniqueCheckedCount, $actualCheckedCount, 
                "Checked count should match for siswa {$siswa->nama}");
            $this->assertEquals($expectedPercentage, $actualPercentage, 
                "Percentage should match for siswa {$siswa->nama}");
        }
    }


    /**
     * **Feature: website-settings, Property 10: Tracer karakter records all required fields**
     * Test that multiple checks on same day for same karakter are allowed.
     * **Validates: Requirements 6.3**
     * 
     * @test
     */
    public function tracer_allows_multiple_checks_same_day(): void
    {
        $siswa = Siswa::create([
            'nis' => 'NIS_MULTI_' . uniqid(),
            'nama' => 'Siswa Multi Check',
            'kelas_id' => $this->kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]);
        
        $karakter = $this->karakterList[0];
        
        // Create multiple checks on same day
        for ($i = 0; $i < 10; $i++) {
            $tracer = TracerKarakter::create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $karakter->id,
                'pamong_id' => $this->pamongUser->id,
                'checked_at' => now()->addMinutes($i),
                'catatan' => 'Check ' . ($i + 1),
            ]);
            
            $this->assertNotNull($tracer->id);
        }
        
        // Verify all records exist
        $count = TracerKarakter::where('siswa_id', $siswa->id)
            ->where('karakter_id', $karakter->id)
            ->whereDate('checked_at', today())
            ->count();
        
        $this->assertEquals(10, $count);
    }

    /**
     * **Feature: website-settings, Property 11: Rekap calculation correctness**
     * Test that duplicate checks don't inflate unique karakter count.
     * **Validates: Requirements 7.4**
     * 
     * @test
     */
    public function rekap_handles_duplicate_checks_correctly(): void
    {
        $siswa = Siswa::create([
            'nis' => 'NIS_DUP_' . uniqid(),
            'nama' => 'Siswa Duplicate Check',
            'kelas_id' => $this->kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]);
        
        // Check same karakter multiple times
        $karakter = $this->karakterList[0];
        
        for ($i = 0; $i < 5; $i++) {
            TracerKarakter::create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $karakter->id,
                'pamong_id' => $this->pamongUser->id,
                'checked_at' => now()->subDays($i),
                'catatan' => null,
            ]);
        }
        
        // Total checks should be 5
        $totalChecks = TracerKarakter::where('siswa_id', $siswa->id)->count();
        $this->assertEquals(5, $totalChecks);
        
        // But unique karakter count should be 1
        $uniqueKarakter = TracerKarakter::where('siswa_id', $siswa->id)
            ->distinct('karakter_id')
            ->count('karakter_id');
        $this->assertEquals(1, $uniqueKarakter);
        
        // Percentage should be based on unique count
        $totalActiveKarakter = count($this->karakterList);
        $expectedPercentage = round((1 / $totalActiveKarakter) * 100, 1);
        $actualPercentage = round(($uniqueKarakter / $totalActiveKarakter) * 100, 1);
        
        $this->assertEquals($expectedPercentage, $actualPercentage);
    }

    /**
     * **Feature: website-settings, Property 10: Tracer karakter records all required fields**
     * Test that tracer records preserve data integrity with relationships.
     * **Validates: Requirements 6.3**
     * 
     * @test
     */
    public function tracer_preserves_relationship_integrity(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $siswa = Siswa::create([
                'nis' => 'NIS_REL_' . $i . '_' . uniqid(),
                'nama' => 'Siswa Relationship ' . $i,
                'kelas_id' => $this->kelas->id,
                'is_active' => true,
                'status' => 'active',
            ]);
            
            // Check all karakter for this siswa
            foreach ($this->karakterList as $karakter) {
                TracerKarakter::create([
                    'siswa_id' => $siswa->id,
                    'karakter_id' => $karakter->id,
                    'pamong_id' => $this->pamongUser->id,
                    'checked_at' => now(),
                    'catatan' => null,
                ]);
            }
            
            // Verify through relationships
            $tracerRecords = TracerKarakter::where('siswa_id', $siswa->id)->get();
            
            $this->assertCount(count($this->karakterList), $tracerRecords);
            
            foreach ($tracerRecords as $record) {
                // Verify siswa relationship
                $this->assertEquals($siswa->id, $record->siswa->id);
                $this->assertEquals($siswa->nama, $record->siswa->nama);
                
                // Verify pamong relationship
                $this->assertEquals($this->pamongUser->id, $record->pamong->id);
                $this->assertEquals($this->pamongUser->username, $record->pamong->username);
                
                // Verify karakter relationship
                $this->assertNotNull($record->karakter);
                $this->assertTrue(in_array($record->karakter->id, array_column($this->karakterList, 'id')));
            }
        }
    }
}
