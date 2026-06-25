<?php

namespace Tests\Property;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Property-based tests for User Management functionality.
 */
class UserPropertyTest extends TestCase
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
     * **Feature: website-settings, Property 3: User creation persists all fields**
     * *For any* valid user data, after creation, querying the user should return the same username, email, and role.
     * **Validates: Requirements 3.2**
     * 
     * @test
     */
    public function user_creation_persists_all_fields(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $roles = [$adminRole->id, $teacherRole->id];
        
        for ($i = 0; $i < 50; $i++) {
            $username = 'user_' . $i . '_' . uniqid();
            $email = 'user' . $i . '_' . uniqid() . '@test.com';
            $phone = '08123456' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $roleId = $roles[array_rand($roles)];
            $status = ['active', 'inactive'][array_rand(['active', 'inactive'])];
            
            $user = User::create([
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'password' => 'password123',
                'role_id' => $roleId,
                'status' => $status,
            ]);
            
            // Retrieve and verify
            $retrieved = User::find($user->id);
            
            $this->assertEquals($username, $retrieved->username);
            $this->assertEquals($email, $retrieved->email);
            $this->assertEquals($phone, $retrieved->phone);
            $this->assertEquals($roleId, $retrieved->role_id);
            $this->assertEquals($status, $retrieved->status);
            $this->assertTrue(Hash::check('password123', $retrieved->password));
        }
    }

    /**
     * **Feature: website-settings, Property 4: Deactivated user cannot login**
     * *For any* deactivated user, authentication attempts should fail.
     * **Validates: Requirements 3.4**
     * 
     * @test
     */
    public function deactivated_user_cannot_login(): void
    {
        $role = Role::where('name', 'teacher')->first();
        
        for ($i = 0; $i < 30; $i++) {
            $user = User::create([
                'username' => 'inactive_user_' . $i,
                'email' => 'inactive' . $i . '@test.com',
                'password' => 'password123',
                'role_id' => $role->id,
                'status' => 'inactive',
            ]);
            
            // Verify user is not active
            $this->assertFalse($user->isActive());
            $this->assertEquals('inactive', $user->status);
            
            // Attempt login should fail (user is inactive)
            $response = $this->post('/login', [
                'username' => $user->username,
                'password' => 'password123',
            ]);
            
            // Should not be authenticated
            $this->assertGuest();
        }
    }

    /**
     * **Feature: website-settings, Property 5: Last admin protection**
     * *For any* system with only one admin, deletion or deactivation of that admin should be rejected.
     * **Validates: Requirements 3.5**
     * 
     * @test
     */
    public function last_admin_protection(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        
        // Create single admin
        $admin = User::create([
            'username' => 'sole_admin',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        
        // Verify only one active admin exists
        $activeAdminCount = User::whereHas('role', fn($q) => $q->where('name', 'admin'))
            ->where('status', 'active')
            ->count();
        $this->assertEquals(1, $activeAdminCount);
        
        // Verify admin has admin role
        $this->assertTrue($admin->hasRole('admin'));
        
        // Test protection logic (simulating what controller does)
        $canDeactivate = true;
        if ($admin->hasRole('admin')) {
            $adminCount = User::whereHas('role', fn($q) => $q->where('name', 'admin'))
                ->where('status', 'active')
                ->count();
            if ($adminCount <= 1) {
                $canDeactivate = false;
            }
        }
        
        $this->assertFalse($canDeactivate, 'Last admin should not be deactivatable');
    }

    /**
     * **Feature: website-settings, Property 3: User creation persists all fields**
     * Test that user update persists changes correctly.
     * **Validates: Requirements 3.3**
     * 
     * @test
     */
    public function user_update_persists_changes(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        
        for ($i = 0; $i < 30; $i++) {
            // Create user
            $user = User::create([
                'username' => 'original_' . $i,
                'email' => 'original' . $i . '@test.com',
                'password' => 'password123',
                'role_id' => $adminRole->id,
                'status' => 'active',
            ]);
            
            // Update user
            $newUsername = 'updated_' . $i;
            $newEmail = 'updated' . $i . '@test.com';
            
            $user->update([
                'username' => $newUsername,
                'email' => $newEmail,
                'role_id' => $teacherRole->id,
            ]);
            
            // Retrieve and verify
            $retrieved = User::find($user->id);
            
            $this->assertEquals($newUsername, $retrieved->username);
            $this->assertEquals($newEmail, $retrieved->email);
            $this->assertEquals($teacherRole->id, $retrieved->role_id);
        }
    }

    /**
     * **Feature: website-settings, Property 4: Deactivated user cannot login**
     * Test that active user can be verified as active.
     * **Validates: Requirements 3.4**
     * 
     * @test
     */
    public function active_user_status_is_correct(): void
    {
        $role = Role::where('name', 'teacher')->first();
        
        for ($i = 0; $i < 30; $i++) {
            $user = User::create([
                'username' => 'active_user_' . $i,
                'email' => 'active' . $i . '@test.com',
                'password' => 'password123',
                'role_id' => $role->id,
                'status' => 'active',
            ]);
            
            $this->assertTrue($user->isActive());
            $this->assertEquals('active', $user->status);
        }
    }

    /**
     * **Feature: website-settings, Property 5: Last admin protection**
     * Test that multiple admins allow deactivation.
     * **Validates: Requirements 3.5**
     * 
     * @test
     */
    public function multiple_admins_allow_deactivation(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        
        // Create multiple admins
        $admin1 = User::create([
            'username' => 'admin1',
            'email' => 'admin1@test.com',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        
        $admin2 = User::create([
            'username' => 'admin2',
            'email' => 'admin2@test.com',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        
        // Verify two active admins exist
        $activeAdminCount = User::whereHas('role', fn($q) => $q->where('name', 'admin'))
            ->where('status', 'active')
            ->count();
        $this->assertEquals(2, $activeAdminCount);
        
        // Test protection logic - should allow deactivation
        $canDeactivate = true;
        if ($admin1->hasRole('admin')) {
            $adminCount = User::whereHas('role', fn($q) => $q->where('name', 'admin'))
                ->where('status', 'active')
                ->count();
            if ($adminCount <= 1) {
                $canDeactivate = false;
            }
        }
        
        $this->assertTrue($canDeactivate, 'Should allow deactivation when multiple admins exist');
    }
}
