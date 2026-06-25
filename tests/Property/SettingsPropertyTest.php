<?php

namespace Tests\Property;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Settings functionality.
 * 
 * **Feature: website-settings, Property 1: Settings round-trip persistence**
 */
class SettingsPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * **Feature: website-settings, Property 1: Settings round-trip persistence**
     * *For any* valid setting key-value pair, saving and then retrieving the setting should return the same value.
     * **Validates: Requirements 1.3, 2.3**
     * 
     * @test
     */
    public function settings_round_trip_persistence(): void
    {
        $iterations = 100;
        
        $testValues = [
            'Simple text value',
            '#667EEA',
            'https://example.com/logo.png',
            '12345',
            '',
            'Value with special chars: <>&"\'',
            'Unicode: 日本語 中文 한국어',
        ];
        
        $groups = ['general', 'id_card', 'theme', 'custom'];
        
        for ($i = 0; $i < $iterations; $i++) {
            $key = 'test_key_' . $i . '_' . uniqid();
            $value = $testValues[array_rand($testValues)];
            $group = $groups[array_rand($groups)];
            
            // Set the value
            Setting::set($key, $value, $group);
            
            // Clear cache to ensure we're reading from DB
            Setting::clearCache();
            
            // Retrieve and verify
            $retrieved = Setting::get($key);
            
            $this->assertEquals(
                $value,
                $retrieved,
                "Setting round-trip failed for key: {$key}"
            );
            
            // Verify group is correct
            $setting = Setting::where('key', $key)->first();
            $this->assertEquals($group, $setting->group);
        }
    }

    /**
     * **Feature: website-settings, Property 1: Settings round-trip persistence**
     * Test that settings can be retrieved by group.
     * **Validates: Requirements 1.3, 2.3**
     * 
     * @test
     */
    public function settings_group_retrieval(): void
    {
        $groups = ['general', 'id_card', 'theme'];
        $expectedByGroup = [];
        
        // Create settings in different groups
        for ($i = 0; $i < 30; $i++) {
            $key = 'group_test_' . $i;
            $value = 'Value ' . $i;
            $group = $groups[$i % count($groups)];
            
            Setting::set($key, $value, $group);
            
            if (!isset($expectedByGroup[$group])) {
                $expectedByGroup[$group] = [];
            }
            $expectedByGroup[$group][$key] = $value;
        }
        
        // Verify each group contains correct settings
        foreach ($groups as $group) {
            $retrieved = Setting::getByGroup($group);
            
            foreach ($expectedByGroup[$group] as $key => $value) {
                $this->assertArrayHasKey($key, $retrieved);
                $this->assertEquals($value, $retrieved[$key]);
            }
        }
    }

    /**
     * **Feature: website-settings, Property 1: Settings round-trip persistence**
     * Test that setting update overwrites previous value.
     * **Validates: Requirements 1.3, 2.3**
     * 
     * @test
     */
    public function settings_update_overwrites_value(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $key = 'update_test_' . $i;
            $originalValue = 'Original value ' . $i;
            $newValue = 'New value ' . $i;
            
            // Set original value
            Setting::set($key, $originalValue, 'general');
            
            // Update with new value
            Setting::set($key, $newValue, 'general');
            
            // Clear cache
            Setting::clearCache();
            
            // Verify new value is returned
            $retrieved = Setting::get($key);
            $this->assertEquals($newValue, $retrieved);
            
            // Verify only one record exists
            $count = Setting::where('key', $key)->count();
            $this->assertEquals(1, $count);
        }
    }

    /**
     * **Feature: website-settings, Property 1: Settings round-trip persistence**
     * Test that default value is returned for non-existent keys.
     * **Validates: Requirements 1.3, 2.3**
     * 
     * @test
     */
    public function settings_returns_default_for_missing_keys(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $nonExistentKey = 'non_existent_' . uniqid();
            $defaultValue = 'Default ' . $i;
            
            $retrieved = Setting::get($nonExistentKey, $defaultValue);
            
            $this->assertEquals($defaultValue, $retrieved);
        }
    }

    /**
     * **Feature: website-settings, Property 1: Settings round-trip persistence**
     * Test setMany persists all settings correctly.
     * **Validates: Requirements 1.3, 2.3**
     * 
     * @test
     */
    public function settings_set_many_persists_all(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $settings = [];
            $count = rand(3, 10);
            
            for ($j = 0; $j < $count; $j++) {
                $key = 'batch_' . $i . '_' . $j;
                $settings[$key] = 'Batch value ' . $i . '_' . $j;
            }
            
            $group = 'batch_group_' . $i;
            Setting::setMany($settings, $group);
            
            // Clear cache
            Setting::clearCache();
            
            // Verify all settings
            foreach ($settings as $key => $expectedValue) {
                $retrieved = Setting::get($key);
                $this->assertEquals($expectedValue, $retrieved);
            }
        }
    }
}
