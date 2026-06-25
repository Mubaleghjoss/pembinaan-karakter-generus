<?php

namespace Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Property-based tests for config default values.
 *
 * **Feature: clean-code-refactoring, Property 4: Config Default Values**
 * **Validates: Requirements 6.4**
 */
class ConfigDefaultValuesTest extends TestCase
{
    use TestTrait;

    /**
     * Expected default values for all qrcode config keys.
     * These are the defaults defined in config/qrcode.php
     */
    private array $expectedDefaults = [
        'qrcode.token.expiry_minutes' => 60,
        'qrcode.token.hash_algorithm' => 'sha256',
        'qrcode.token.random_length' => 32,
        'qrcode.encryption.hmac_algorithm' => 'sha256',
        'qrcode.encryption.salt_length' => 64,
        'qrcode.generation.size' => 300,
        'qrcode.generation.margin' => 10,
        'qrcode.generation.error_correction' => 'M',
        'qrcode.generation.default_format' => 'png',
        'qrcode.generation.foreground_color' => '000000',
        'qrcode.generation.background_color' => 'ffffff',
        'qrcode.logo.enabled' => true,
        'qrcode.logo.path' => 'img/logo_pkg.svg',
        'qrcode.logo.width' => 60,
        'qrcode.logo.height' => 60,
        'qrcode.logo.punchout_background' => true,
        'qrcode.payload.prefix' => 'PKG',
        'qrcode.payload.version' => '1',
        'qrcode.payload.delimiter' => '|',
        'qrcode.scan.max_per_day' => 2,
        'qrcode.scan.cooldown_seconds' => 60,
        'qrcode.scan.validate_location' => false,
        'qrcode.scan.max_distance_meters' => 100,
        'qrcode.rate_limit.scan_per_minute' => 30,
        'qrcode.rate_limit.generate_per_minute' => 10,
    ];

    /**
     * **Feature: clean-code-refactoring, Property 4: Config Default Values**
     * **Validates: Requirements 6.4**
     *
     * Property: For any configuration key in qrcode config, when the corresponding
     * environment variable is not set, the system should return the defined default value.
     */
    public function test_all_config_keys_have_default_values(): void
    {
        foreach ($this->expectedDefaults as $key => $expectedDefault) {
            $actualValue = Config::get($key);

            $this->assertNotNull(
                $actualValue,
                "Config key '{$key}' should have a default value, got null"
            );

            $this->assertEquals(
                $expectedDefault,
                $actualValue,
                "Config key '{$key}' should have default value '{$expectedDefault}', got '{$actualValue}'"
            );
        }
    }

    /**
     * **Feature: clean-code-refactoring, Property 4: Config Default Values**
     * **Validates: Requirements 6.4**
     *
     * Property: For any randomly selected config key from the qrcode config,
     * the value should match the expected default when env vars are not set.
     */
    public function test_random_config_keys_return_defaults(): void
    {
        $configKeys = array_keys($this->expectedDefaults);

        $this->forAll(
            Generator\elements(...$configKeys)
        )
            ->withMaxSize(100)
            ->then(function (string $configKey) {
                $expectedDefault = $this->expectedDefaults[$configKey];
                $actualValue = Config::get($configKey);

                $this->assertNotNull(
                    $actualValue,
                    "Config key '{$configKey}' should have a default value"
                );

                $this->assertEquals(
                    $expectedDefault,
                    $actualValue,
                    "Config key '{$configKey}' default mismatch"
                );
            });
    }

    /**
     * **Feature: clean-code-refactoring, Property 4: Config Default Values**
     * **Validates: Requirements 6.4**
     *
     * Property: For any config key that doesn't exist, Config::get should return
     * the provided fallback value.
     */
    public function test_nonexistent_config_returns_fallback(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\oneOf(
                Generator\int(),
                Generator\string(),
                Generator\bool()
            )
        )
            ->withMaxSize(100)
            ->then(function (string $randomKey, $fallbackValue) {
                // Ensure we're testing a key that doesn't exist
                $nonExistentKey = "qrcode.nonexistent.{$randomKey}";

                $result = Config::get($nonExistentKey, $fallbackValue);

                $this->assertEquals(
                    $fallbackValue,
                    $result,
                    'Non-existent config key should return the fallback value'
                );
            });
    }

    /**
     * **Feature: clean-code-refactoring, Property 4: Config Default Values**
     * **Validates: Requirements 6.4**
     *
     * Property: All numeric config defaults should be positive integers.
     */
    public function test_numeric_config_defaults_are_positive(): void
    {
        $numericKeys = [
            'qrcode.token.expiry_minutes',
            'qrcode.token.random_length',
            'qrcode.encryption.salt_length',
            'qrcode.generation.size',
            'qrcode.generation.margin',
            'qrcode.logo.width',
            'qrcode.logo.height',
            'qrcode.scan.max_per_day',
            'qrcode.scan.cooldown_seconds',
            'qrcode.scan.max_distance_meters',
            'qrcode.rate_limit.scan_per_minute',
            'qrcode.rate_limit.generate_per_minute',
        ];

        foreach ($numericKeys as $key) {
            $value = Config::get($key);

            $this->assertIsInt(
                $value,
                "Config key '{$key}' should be an integer"
            );

            $this->assertGreaterThan(
                0,
                $value,
                "Config key '{$key}' should be a positive integer"
            );
        }
    }

    /**
     * **Feature: clean-code-refactoring, Property 4: Config Default Values**
     * **Validates: Requirements 6.4**
     *
     * Property: Hash algorithm configs should only contain valid algorithm names.
     */
    public function test_hash_algorithm_configs_are_valid(): void
    {
        $validAlgorithms = ['sha256', 'sha384', 'sha512'];

        $algorithmKeys = [
            'qrcode.token.hash_algorithm',
            'qrcode.encryption.hmac_algorithm',
        ];

        foreach ($algorithmKeys as $key) {
            $value = Config::get($key);

            $this->assertContains(
                $value,
                $validAlgorithms,
                "Config key '{$key}' should contain a valid hash algorithm"
            );
        }
    }

    /**
     * **Feature: clean-code-refactoring, Property 4: Config Default Values**
     * **Validates: Requirements 6.4**
     *
     * Property: Error correction level should be one of the valid QR code levels.
     */
    public function test_error_correction_config_is_valid(): void
    {
        $validLevels = ['L', 'M', 'Q', 'H'];

        $value = Config::get('qrcode.generation.error_correction');

        $this->assertContains(
            $value,
            $validLevels,
            'Error correction level should be one of: L, M, Q, H'
        );
    }

    /**
     * **Feature: clean-code-refactoring, Property 4: Config Default Values**
     * **Validates: Requirements 6.4**
     *
     * Property: Color configs should be valid 6-character hex strings.
     */
    public function test_color_configs_are_valid_hex(): void
    {
        $colorKeys = [
            'qrcode.generation.foreground_color',
            'qrcode.generation.background_color',
        ];

        foreach ($colorKeys as $key) {
            $value = Config::get($key);

            $this->assertIsString($value, "Config key '{$key}' should be a string");
            $this->assertMatchesRegularExpression(
                '/^[0-9a-fA-F]{6}$/',
                $value,
                "Config key '{$key}' should be a valid 6-character hex color"
            );
        }
    }

    /**
     * **Feature: clean-code-refactoring, Property 4: Config Default Values**
     * **Validates: Requirements 6.4**
     *
     * Property: Boolean config defaults should be actual boolean values.
     */
    public function test_boolean_config_defaults_are_booleans(): void
    {
        $booleanKeys = [
            'qrcode.logo.enabled',
            'qrcode.logo.punchout_background',
            'qrcode.scan.validate_location',
        ];

        foreach ($booleanKeys as $key) {
            $value = Config::get($key);

            $this->assertIsBool(
                $value,
                "Config key '{$key}' should be a boolean"
            );
        }
    }
}
