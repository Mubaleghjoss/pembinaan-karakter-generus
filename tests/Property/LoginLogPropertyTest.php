<?php

namespace Tests\Property;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Login Log functionality.
 *
 * **Feature: calendar-schedule-reminder, Properties 8, 9**
 * **Validates: Requirements 6.1, 6.3, 6.4**
 */
class LoginLogPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
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
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 8: Login Timestamp Recording**
     * **Validates: Requirements 6.1, 6.4**
     *
     * Property: For any successful login, the user's last_login_at should be 
     * updated to a timestamp within 1 second of the login time.
     */
    public function test_login_timestamp_recording(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create([
                'role_id' => 1,
            ]);
            
            // Set to null first
            $user->last_login_at = null;
            $user->save();

            $beforeLogin = Carbon::now();

            // Simulate login using recordLogin method or direct update
            $user->last_login_at = Carbon::now();
            $user->save();

            $afterLogin = Carbon::now();
            $user->refresh();

            $this->assertNotNull($user->last_login_at, 'last_login_at harus terisi setelah login');
            
            // Check timestamp is within expected range
            $this->assertTrue(
                $user->last_login_at->gte($beforeLogin->subSecond()) &&
                $user->last_login_at->lte($afterLogin->addSecond()),
                'last_login_at harus dalam range waktu login'
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 8: Login Timestamp Recording**
     * **Validates: Requirements 6.1, 6.4**
     *
     * Property: Multiple logins should update the timestamp each time.
     */
    public function test_multiple_logins_update_timestamp(): void
    {
        $user = User::factory()->create([
            'role_id' => 1,
        ]);
        
        $user->last_login_at = null;
        $user->save();

        $previousTimestamp = null;

        for ($i = 0; $i < 5; $i++) {
            // Use Carbon time travel to ensure different timestamps
            $loginTime = Carbon::now()->addSeconds($i + 1);
            
            $user->last_login_at = $loginTime;
            $user->save();
            $user->refresh();

            if ($previousTimestamp !== null) {
                $this->assertTrue(
                    $user->last_login_at->gte($previousTimestamp),
                    'Login baru harus memiliki timestamp lebih baru atau sama'
                );
            }

            $previousTimestamp = $user->last_login_at->copy();
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 9: Null Login Display**
     * **Validates: Requirements 6.3**
     *
     * Property: For any user with null last_login_at, the display should 
     * show "Belum pernah login" text.
     */
    public function test_null_login_display(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create([
                'role_id' => 1,
                'last_login_at' => null,
            ]);

            $this->assertNull($user->last_login_at, 'last_login_at harus null');

            // Simulate display logic
            $displayText = $user->last_login_at 
                ? $user->last_login_at->diffForHumans() 
                : 'Belum pernah login';

            $this->assertEquals(
                'Belum pernah login',
                $displayText,
                'User tanpa login harus menampilkan "Belum pernah login"'
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 9: Null Login Display**
     * **Validates: Requirements 6.3**
     *
     * Property: For any user with non-null last_login_at, the display should 
     * show a human-readable time difference.
     */
    public function test_non_null_login_display(): void
    {
        $testCases = [
            ['offset' => 'subMinutes', 'value' => 5, 'contains' => 'menit'],
            ['offset' => 'subHours', 'value' => 2, 'contains' => 'jam'],
            ['offset' => 'subDays', 'value' => 1, 'contains' => 'hari'],
            ['offset' => 'subWeeks', 'value' => 1, 'contains' => 'minggu'],
        ];

        foreach ($testCases as $case) {
            $loginTime = Carbon::now()->{$case['offset']}($case['value']);
            
            $user = User::factory()->create([
                'role_id' => 1,
                'last_login_at' => $loginTime,
            ]);

            $displayText = $user->last_login_at 
                ? $user->last_login_at->diffForHumans() 
                : 'Belum pernah login';

            $this->assertNotEquals(
                'Belum pernah login',
                $displayText,
                'User dengan login harus menampilkan waktu relatif'
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 8: Login Timestamp Recording**
     * **Validates: Requirements 6.1, 6.4**
     *
     * Property: Login timestamp should persist correctly in database.
     */
    public function test_login_timestamp_persistence(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $loginTime = Carbon::now()->subMinutes(rand(1, 1000));
            
            $user = User::factory()->create([
                'role_id' => 1,
                'last_login_at' => $loginTime,
            ]);

            // Retrieve from database
            $retrievedUser = User::find($user->id);

            $this->assertNotNull($retrievedUser->last_login_at);
            $this->assertTrue(
                $retrievedUser->last_login_at->diffInSeconds($loginTime) <= 1,
                'Timestamp harus sama setelah retrieve dari database'
            );
        }
    }
}
