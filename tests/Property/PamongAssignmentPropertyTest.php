<?php

namespace Tests\Property;

use App\Models\Kelas;
use App\Models\PamongSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Pamong-Siswa Assignment functionality.
 */
class PamongAssignmentPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin', 'display_name' => 'Administrator', 'permissions' => ['*']]);
        Role::create(['name' => 'teacher', 'display_name' => 'Pamong', 'permissions' => ['view_students']]);
    }

    /**
     * **Feature: website-settings, Property 6: Pamong-siswa assignment persistence**
     * *For any* pamong and set of students, after assignment, querying pamong's students should return exactly those students.
     * **Validates: Requirements 4.2, 4.3**
     * 
     * @test
     */
    public function pamong_siswa_assignment_persistence(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Test', 'kode_kelas' => 'KT', 'is_active' => true]);
        
        for ($i = 0; $i < 20; $i++) {
            // Create pamong
            $pamong = User::create([
                'username' => 'pamong_' . $i,
                'email' => 'pamong' . $i . '@test.com',
                'password' => 'password123',
                'role_id' => $teacherRole->id,
                'status' => 'active',
            ]);
            
            // Create random number of students
            $studentCount = rand(3, 8);
            $studentIds = [];
            
            for ($j = 0; $j < $studentCount; $j++) {
                $siswa = Siswa::create([
                    'nis' => 'NIS' . $i . '_' . $j . '_' . uniqid(),
                    'nama' => 'Siswa ' . $i . '_' . $j,
                    'kelas_id' => $kelas->id,
                    'status' => 'active',
                    'is_active' => true,
                ]);
                $studentIds[] = $siswa->id;
            }
            
            // Assign students to pamong
            foreach ($studentIds as $siswaId) {
                PamongSiswa::create([
                    'pamong_id' => $pamong->id,
                    'siswa_id' => $siswaId,
                ]);
            }
            
            // Verify assignment persistence
            $assignedIds = $pamong->assignedStudents()->pluck('siswa_id')->toArray();
            
            sort($studentIds);
            sort($assignedIds);
            
            $this->assertEquals($studentIds, $assignedIds, 'Assigned students should match exactly');
            $this->assertCount($studentCount, $assignedIds);
        }
    }

    /**
     * **Feature: website-settings, Property 7: Pamong access control**
     * *For any* pamong user, queries for students should return only assigned students.
     * **Validates: Requirements 4.4, 6.1**
     * 
     * @test
     */
    public function pamong_access_control(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Test', 'kode_kelas' => 'KT2', 'is_active' => true]);
        
        for ($i = 0; $i < 15; $i++) {
            // Create pamong
            $pamong = User::create([
                'username' => 'pamong_access_' . $i,
                'email' => 'pamong_access' . $i . '@test.com',
                'password' => 'password123',
                'role_id' => $teacherRole->id,
                'status' => 'active',
            ]);
            
            // Create assigned students
            $assignedCount = rand(2, 5);
            $assignedIds = [];
            
            for ($j = 0; $j < $assignedCount; $j++) {
                $siswa = Siswa::create([
                    'nis' => 'ASSIGNED' . $i . '_' . $j . '_' . uniqid(),
                    'nama' => 'Assigned Siswa ' . $i . '_' . $j,
                    'kelas_id' => $kelas->id,
                    'status' => 'active',
                    'is_active' => true,
                ]);
                $assignedIds[] = $siswa->id;
                
                PamongSiswa::create([
                    'pamong_id' => $pamong->id,
                    'siswa_id' => $siswa->id,
                ]);
            }
            
            // Create unassigned students
            $unassignedCount = rand(2, 5);
            $unassignedIds = [];
            
            for ($k = 0; $k < $unassignedCount; $k++) {
                $siswa = Siswa::create([
                    'nis' => 'UNASSIGNED' . $i . '_' . $k . '_' . uniqid(),
                    'nama' => 'Unassigned Siswa ' . $i . '_' . $k,
                    'kelas_id' => $kelas->id,
                    'status' => 'active',
                    'is_active' => true,
                ]);
                $unassignedIds[] = $siswa->id;
            }
            
            // Test access control - pamong should only see assigned students
            $query = Siswa::query();
            $filteredQuery = $pamong->filterSiswaByAssignment($query);
            $visibleIds = $filteredQuery->pluck('id')->toArray();
            
            // All assigned students should be visible
            foreach ($assignedIds as $id) {
                $this->assertContains($id, $visibleIds, 'Assigned student should be visible');
            }
            
            // No unassigned students should be visible
            foreach ($unassignedIds as $id) {
                $this->assertNotContains($id, $visibleIds, 'Unassigned student should not be visible');
            }
        }
    }

    /**
     * **Feature: website-settings, Property 6: Pamong-siswa assignment persistence**
     * Test that removing assignment updates the list immediately.
     * **Validates: Requirements 4.5**
     * 
     * @test
     */
    public function assignment_removal_updates_immediately(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Removal', 'kode_kelas' => 'KR', 'is_active' => true]);
        
        for ($i = 0; $i < 20; $i++) {
            $pamong = User::create([
                'username' => 'pamong_removal_' . $i,
                'email' => 'pamong_removal' . $i . '@test.com',
                'password' => 'password123',
                'role_id' => $teacherRole->id,
                'status' => 'active',
            ]);
            
            // Create and assign students
            $studentIds = [];
            for ($j = 0; $j < 5; $j++) {
                $siswa = Siswa::create([
                    'nis' => 'REMOVAL' . $i . '_' . $j . '_' . uniqid(),
                    'nama' => 'Removal Siswa ' . $i . '_' . $j,
                    'kelas_id' => $kelas->id,
                    'status' => 'active',
                    'is_active' => true,
                ]);
                $studentIds[] = $siswa->id;
                
                PamongSiswa::create([
                    'pamong_id' => $pamong->id,
                    'siswa_id' => $siswa->id,
                ]);
            }
            
            // Remove random student
            $removeIndex = array_rand($studentIds);
            $removeId = $studentIds[$removeIndex];
            
            PamongSiswa::where('pamong_id', $pamong->id)
                ->where('siswa_id', $removeId)
                ->delete();
            
            // Verify removal
            $remainingIds = $pamong->assignedStudents()->pluck('siswa_id')->toArray();
            
            $this->assertNotContains($removeId, $remainingIds, 'Removed student should not be in list');
            $this->assertCount(4, $remainingIds, 'Should have 4 students remaining');
        }
    }

    /**
     * **Feature: website-settings, Property 6: Pamong-siswa assignment persistence**
     * Test unique constraint - same student cannot be assigned twice to same pamong.
     * **Validates: Requirements 4.2**
     * 
     * @test
     */
    public function duplicate_assignment_prevented(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Unique', 'kode_kelas' => 'KU', 'is_active' => true]);
        
        $pamong = User::create([
            'username' => 'pamong_unique',
            'email' => 'pamong_unique@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        
        $siswa = Siswa::create([
            'nis' => 'UNIQUE_SISWA',
            'nama' => 'Unique Siswa',
            'kelas_id' => $kelas->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        
        // First assignment should succeed
        PamongSiswa::create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $siswa->id,
        ]);
        
        // Second assignment should fail due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        PamongSiswa::create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $siswa->id,
        ]);
    }
}
