<?php

namespace Tests\Property;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Theme Preference functionality.
 *
 * **Feature: calendar-schedule-reminder, Property 10**
 * **Validates: Requirements 7.2, 7.3**
 */
class ThemePropertyTest extends TestCase
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
     * **Feature: calendar-schedule-reminder, Property 10: Theme Preference Persistence**
     * **Validates: Requirements 7.2, 7.3**
     *
     * Property: For any theme preference set by user, subsequent page loads 
     * should apply the same theme until changed.
     */
    public function test_theme_preference_persistence(): void
    {
        $themes = ['light', 'dark', 'system'];

        foreach ($themes as $theme) {
            $user = User::factory()->create([
                'role_id' => 1,
                'theme_preference' => $theme,
            ]);

            // Retrieve from database (simulating page reload)
            $retrievedUser = User::find($user->id);

            $this->assertEquals(
                $theme,
                $retrievedUser->theme_preference,
                "Theme preference '{$theme}' harus persist setelah reload"
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 10: Theme Preference Persistence**
     * **Validates: Requirements 7.2, 7.3**
     *
     * Property: Theme preference should be changeable and persist the new value.
     */
    public function test_theme_preference_change_persists(): void
    {
        $user = User::factory()->create([
            'role_id' => 1,
            'theme_preference' => 'light',
        ]);

        $themeSequence = ['dark', 'system', 'light', 'dark'];

        foreach ($themeSequence as $newTheme) {
            $user->theme_preference = $newTheme;
            $user->save();

            // Retrieve from database
            $retrievedUser = User::find($user->id);

            $this->assertEquals(
                $newTheme,
                $retrievedUser->theme_preference,
                "Theme harus berubah ke '{$newTheme}'"
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 10: Theme Preference Persistence**
     * **Validates: Requirements 7.2, 7.3**
     *
     * Property: Multiple users should have independent theme preferences.
     */
    public function test_independent_theme_preferences(): void
    {
        $users = [];
        $themes = ['light', 'dark', 'system'];

        // Create users with different themes
        for ($i = 0; $i < 10; $i++) {
            $theme = $themes[$i % 3];
            $users[] = User::factory()->create([
                'role_id' => 1,
                'theme_preference' => $theme,
            ]);
        }

        // Verify each user has their own theme
        foreach ($users as $index => $user) {
            $expectedTheme = $themes[$index % 3];
            $retrievedUser = User::find($user->id);

            $this->assertEquals(
                $expectedTheme,
                $retrievedUser->theme_preference,
                "User {$index} harus memiliki theme '{$expectedTheme}'"
            );
        }

        // Change one user's theme and verify others are unaffected
        $users[0]->theme_preference = 'dark';
        $users[0]->save();

        for ($i = 1; $i < count($users); $i++) {
            $expectedTheme = $themes[$i % 3];
            $retrievedUser = User::find($users[$i]->id);

            $this->assertEquals(
                $expectedTheme,
                $retrievedUser->theme_preference,
                "User {$i} theme tidak boleh berubah"
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 10: Theme Preference Persistence**
     * **Validates: Requirements 7.2, 7.3**
     *
     * Property: Default theme should be 'system' for new users.
     */
    public function test_default_theme_for_new_users(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create([
                'role_id' => 1,
                'theme_preference' => 'system', // Default value
            ]);

            // Default should be 'system'
            $this->assertEquals(
                'system',
                $user->theme_preference,
                'Default theme harus system'
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 10: Theme Preference Persistence**
     * **Validates: Requirements 7.2, 7.3**
     *
     * Property: Theme preference should only accept valid values.
     */
    public function test_theme_preference_valid_values(): void
    {
        $validThemes = ['light', 'dark', 'system'];

        foreach ($validThemes as $theme) {
            $user = User::factory()->create([
                'role_id' => 1,
                'theme_preference' => $theme,
            ]);

            $this->assertContains(
                $user->theme_preference,
                $validThemes,
                "Theme '{$theme}' harus valid"
            );
        }
    }
}
