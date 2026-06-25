<?php

namespace Tests\Property;

use App\Models\Kelas;
use App\Models\PamongPermission;
use App\Models\PamongSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Pamong-Siswa Assignment functionality.
 */
class PamongSiswaPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
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
                    'is_active' => true,
                    'status' => 'active',
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
            
            $this->assertCount($studentCount, $assignedIds);
            foreach ($studentIds as $expectedId) {
                $this->assertContains($expectedId, $assignedIds);
            }
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
        
        // Create two pamong
        $pamong1 = User::create([
            'username' => 'pamong1',
            'email' => 'pamong1@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        
        $pamong2 = User::create([
            'username' => 'pamong2',
            'email' => 'pamong2@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        
        // Create students for pamong1
        $pamong1Students = [];
        for ($i = 0; $i < 5; $i++) {
            $siswa = Siswa::create([
                'nis' => 'P1_' . $i . '_' . uniqid(),
                'nama' => 'Siswa P1 ' . $i,
                'kelas_id' => $kelas->id,
                'is_active' => true,
                'status' => 'active',
            ]);
            $pamong1Students[] = $siswa->id;
            PamongSiswa::create(['pamong_id' => $pamong1->id, 'siswa_id' => $siswa->id]);
        }
        
        // Create students for pamong2
        $pamong2Students = [];
        for ($i = 0; $i < 3; $i++) {
            $siswa = Siswa::create([
                'nis' => 'P2_' . $i . '_' . uniqid(),
                'nama' => 'Siswa P2 ' . $i,
                'kelas_id' => $kelas->id,
                'is_active' => true,
                'status' => 'active',
            ]);
            $pamong2Students[] = $siswa->id;
            PamongSiswa::create(['pamong_id' => $pamong2->id, 'siswa_id' => $siswa->id]);
        }
        
        // Verify pamong1 only sees their students
        $pamong1Visible = Siswa::forUser($pamong1)->pluck('id')->toArray();
        $this->assertCount(5, $pamong1Visible);
        foreach ($pamong1Students as $id) {
            $this->assertContains($id, $pamong1Visible);
        }
        foreach ($pamong2Students as $id) {
            $this->assertNotContains($id, $pamong1Visible);
        }
        
        // Verify pamong2 only sees their students
        $pamong2Visible = Siswa::forUser($pamong2)->pluck('id')->toArray();
        $this->assertCount(3, $pamong2Visible);
        foreach ($pamong2Students as $id) {
            $this->assertContains($id, $pamong2Visible);
        }
        foreach ($pamong1Students as $id) {
            $this->assertNotContains($id, $pamong2Visible);
        }
    }

    /**
     * **Feature: website-settings, Property 6: Pamong-siswa assignment persistence**
     * Test that removing assignment updates correctly.
     * **Validates: Requirements 4.5**
     * 
     * @test
     */
    public function assignment_removal_updates_correctly(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Test', 'kode_kelas' => 'KT3', 'is_active' => true]);
        
        for ($i = 0; $i < 15; $i++) {
            $pamong = User::create([
                'username' => 'pamong_rem_' . $i,
                'email' => 'pamong_rem' . $i . '@test.com',
                'password' => 'password123',
                'role_id' => $teacherRole->id,
                'status' => 'active',
            ]);
            
            // Create and assign students
            $studentIds = [];
            for ($j = 0; $j < 5; $j++) {
                $siswa = Siswa::create([
                    'nis' => 'REM' . $i . '_' . $j . '_' . uniqid(),
                    'nama' => 'Siswa Rem ' . $i . '_' . $j,
                    'kelas_id' => $kelas->id,
                    'is_active' => true,
                    'status' => 'active',
                ]);
                $studentIds[] = $siswa->id;
                PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $siswa->id]);
            }
            
            // Remove random student
            $removeIndex = array_rand($studentIds);
            $removedId = $studentIds[$removeIndex];
            
            PamongSiswa::where('pamong_id', $pamong->id)
                ->where('siswa_id', $removedId)
                ->delete();
            
            // Verify removal
            $remainingIds = $pamong->assignedStudents()->pluck('siswa_id')->toArray();
            $this->assertCount(4, $remainingIds);
            $this->assertNotContains($removedId, $remainingIds);
        }
    }

    /**
     * **Feature: website-settings, Property 7: Pamong access control**
     * Test that admin sees all students.
     * **Validates: Requirements 4.4**
     * 
     * @test
     */
    public function admin_sees_all_students(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Test', 'kode_kelas' => 'KT4', 'is_active' => true]);
        
        $admin = User::create([
            'username' => 'admin_test',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        
        $pamong = User::create([
            'username' => 'pamong_test',
            'email' => 'pamong@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        
        // Create students - some assigned, some not
        $allStudentIds = [];
        for ($i = 0; $i < 10; $i++) {
            $siswa = Siswa::create([
                'nis' => 'ADM_' . $i . '_' . uniqid(),
                'nama' => 'Siswa Admin ' . $i,
                'kelas_id' => $kelas->id,
                'is_active' => true,
                'status' => 'active',
            ]);
            $allStudentIds[] = $siswa->id;
            
            // Assign only half to pamong
            if ($i < 5) {
                PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $siswa->id]);
            }
        }
        
        // Admin should see all 10 students
        $adminVisible = Siswa::forUser($admin)->pluck('id')->toArray();
        $this->assertCount(10, $adminVisible);
        
        // Pamong should see only 5 assigned students
        $pamongVisible = Siswa::forUser($pamong)->pluck('id')->toArray();
        $this->assertCount(5, $pamongVisible);
    }

    /**
     * @test
     */
    public function full_access_operational_user_sees_all_students(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Full Access', 'kode_kelas' => 'KFA', 'is_active' => true]);

        $pamong = User::create([
            'username' => 'pamong_full_access',
            'email' => 'pamong.full@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);

        PamongPermission::create([
            'user_id' => $pamong->id,
            'menu_permissions' => [],
            'crud_permissions' => [],
            'is_excluded' => true,
        ]);

        $students = collect(range(1, 4))->map(fn (int $index) => Siswa::create([
            'nis' => 'FULL_' . $index . '_' . uniqid(),
            'nama' => 'Siswa Full ' . $index,
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]));

        PamongSiswa::create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $students->first()->id,
        ]);

        $visibleIds = Siswa::forUser($pamong)->pluck('id')->all();

        $this->assertEqualsCanonicalizing($students->pluck('id')->all(), $visibleIds);
    }

    /**
     * @test
     */
    public function manual_attendance_limited_pamong_sees_only_assigned_students(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Manual Limited', 'kode_kelas' => 'KML', 'is_active' => true]);

        $pamong = User::create([
            'username' => 'pamong_manual_limited',
            'email' => 'pamong.manual.limited@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);

        PamongPermission::create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['manual_attendance'],
            'crud_permissions' => ['manual_attendance' => ['view', 'create']],
            'is_excluded' => false,
        ]);

        $assigned = Siswa::create([
            'nis' => 'LIMITED_ASSIGNED_' . uniqid(),
            'nama' => 'Siswa Manual Binaan',
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        $unassigned = Siswa::create([
            'nis' => 'LIMITED_OTHER_' . uniqid(),
            'nama' => 'Siswa Manual Lain',
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        PamongSiswa::create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $assigned->id,
        ]);

        $visibleIds = Siswa::forManualAttendance($pamong)->pluck('id')->all();

        $this->assertContains($assigned->id, $visibleIds);
        $this->assertNotContains($unassigned->id, $visibleIds);
    }

    /**
     * @test
     */
    public function manual_attendance_all_students_permission_sees_all_active_students(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Manual All', 'kode_kelas' => 'KMA', 'is_active' => true]);

        $pamong = User::create([
            'username' => 'pamong_manual_all',
            'email' => 'pamong.manual.all@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);

        PamongPermission::create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['manual_attendance'],
            'crud_permissions' => ['manual_attendance' => ['view', 'create', 'all_students']],
            'is_excluded' => false,
        ]);

        $activeStudents = collect(range(1, 3))->map(fn (int $index) => Siswa::create([
            'nis' => 'MANUAL_ALL_' . $index . '_' . uniqid(),
            'nama' => 'Siswa Manual Semua ' . $index,
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]));

        Siswa::create([
            'nis' => 'MANUAL_INACTIVE_' . uniqid(),
            'nama' => 'Siswa Nonaktif',
            'kelas_id' => $kelas->id,
            'is_active' => false,
            'status' => 'inactive',
        ]);

        $visibleIds = Siswa::active()->forManualAttendance($pamong)->pluck('id')->all();

        $this->assertEqualsCanonicalizing($activeStudents->pluck('id')->all(), $visibleIds);
    }

    /**
     * @test
     */
    public function manual_attendance_accepts_legacy_all_students_alias(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Manual Alias', 'kode_kelas' => 'KMA2', 'is_active' => true]);

        $pamong = User::create([
            'username' => 'pamong_manual_alias',
            'email' => 'pamong.manual.alias@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);

        PamongPermission::create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['manual_attendance'],
            'crud_permissions' => ['manual_attendance' => ['view', 'create', 'semua_siswa']],
            'is_excluded' => false,
        ]);

        $activeStudents = collect(range(1, 2))->map(fn (int $index) => Siswa::create([
            'nis' => 'MANUAL_ALIAS_' . $index . '_' . uniqid(),
            'nama' => 'Siswa Manual Alias ' . $index,
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]));

        PamongSiswa::create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $activeStudents->first()->id,
        ]);

        $visibleIds = Siswa::active()->forManualAttendance($pamong)->pluck('id')->all();

        $this->assertTrue($pamong->canAccessAllManualAttendanceStudents());
        $this->assertEqualsCanonicalizing($activeStudents->pluck('id')->all(), $visibleIds);
    }

    /**
     * @test
     */
    public function presensi_manual_student_search_uses_all_students_scope(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Presensi Manual', 'kode_kelas' => 'KPM', 'is_active' => true]);

        $pamong = User::create([
            'username' => 'pamong_presensi_manual_all',
            'email' => 'pamong.presensi.manual.all@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);

        PamongPermission::create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['manual_attendance'],
            'crud_permissions' => ['manual_attendance' => ['view', 'create', 'all_students']],
            'is_excluded' => false,
        ]);

        $students = collect(range(1, 3))->map(fn (int $index) => Siswa::create([
            'nis' => 'PRESENSI_MANUAL_' . $index . '_' . uniqid(),
            'nama' => 'Siswa Presensi Manual ' . $index,
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'status' => 'active',
        ]));

        PamongSiswa::create([
            'pamong_id' => $pamong->id,
            'siswa_id' => $students->first()->id,
        ]);

        $response = $this->actingAs($pamong)->getJson(route('presensi.students', [
            'search' => 'Siswa Presensi Manual',
            'per_page' => 10,
        ]));

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            $students->pluck('id')->all(),
            collect($response->json('data'))->pluck('id')->all()
        );
    }

    /**
     * @test
     */
    public function presensi_bulk_manual_input_requires_all_students_scope(): void
    {
        $teacherRole = Role::where('name', 'teacher')->first();
        $kelas = Kelas::create(['nama' => 'Kelas Bulk Terbatas', 'kode_kelas' => 'KBT', 'is_active' => true]);

        $pamong = User::create([
            'username' => 'pamong_bulk_limited',
            'email' => 'pamong.bulk.limited@test.com',
            'password' => 'password123',
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);

        PamongPermission::create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['manual_attendance'],
            'crud_permissions' => ['manual_attendance' => ['view', 'create']],
            'is_excluded' => false,
        ]);

        $response = $this->actingAs($pamong)->postJson(route('presensi.bulk'), [
            'kelas_id' => $kelas->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
        ]);

        $response->assertForbidden();
    }
}
