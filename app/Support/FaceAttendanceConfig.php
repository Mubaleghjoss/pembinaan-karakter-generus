<?php

namespace App\Support;

use App\Models\Setting;

class FaceAttendanceConfig
{
    public const GROUP = 'face_attendance';

    public const DEFAULT_CENTER_LAT = -6.219501040781815;
    public const DEFAULT_CENTER_LNG = 106.64336089878178;
    public const DEFAULT_RADIUS_VALUE = 100;
    public const DEFAULT_RADIUS_UNIT = 'meter';
    public const DEFAULT_MATCH_THRESHOLD = 35.00;
    public const MATCH_DISTANCE_NORMALIZER = 20.0;
    public const DEFAULT_MAX_ACCURACY_METERS = 150;

    public static function all(): array
    {
        $radiusValue = self::float('face_attendance_radius_value', self::DEFAULT_RADIUS_VALUE);
        $radiusUnit = self::unit(Setting::get('face_attendance_radius_unit', self::DEFAULT_RADIUS_UNIT));

        return [
            'enabled_siswa' => self::bool('face_attendance_enabled_siswa', true),
            'enabled_pamong' => self::bool('face_attendance_enabled_pamong', true),
            'center_lat' => self::float('face_attendance_center_lat', self::DEFAULT_CENTER_LAT),
            'center_lng' => self::float('face_attendance_center_lng', self::DEFAULT_CENTER_LNG),
            'radius_value' => $radiusValue,
            'radius_unit' => $radiusUnit,
            'radius_meters' => self::radiusMeters($radiusValue, $radiusUnit),
            'match_threshold' => self::matchThreshold(),
            'max_accuracy_meters' => (int) self::float('face_attendance_max_accuracy_meters', self::DEFAULT_MAX_ACCURACY_METERS),
        ];
    }

    public static function radiusMeters(float $value, string $unit): float
    {
        return self::unit($unit) === 'kilometer' ? $value * 1000 : $value;
    }

    public static function unit(?string $unit): string
    {
        return $unit === 'kilometer' ? 'kilometer' : 'meter';
    }

    public static function store(array $values): void
    {
        $map = [
            'face_attendance_enabled_siswa' => ! empty($values['enabled_siswa']) ? '1' : '0',
            'face_attendance_enabled_pamong' => ! empty($values['enabled_pamong']) ? '1' : '0',
            'face_attendance_center_lat' => (string) $values['center_lat'],
            'face_attendance_center_lng' => (string) $values['center_lng'],
            'face_attendance_radius_value' => (string) $values['radius_value'],
            'face_attendance_radius_unit' => self::unit($values['radius_unit'] ?? null),
            'face_attendance_match_threshold' => (string) $values['match_threshold'],
            'face_attendance_max_accuracy_meters' => (string) $values['max_accuracy_meters'],
        ];

        foreach ($map as $key => $value) {
            Setting::set($key, $value, self::GROUP);
        }
    }

    private static function bool(string $key, bool $default): bool
    {
        $value = Setting::get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private static function float(string $key, float $default): float
    {
        $value = Setting::get($key);

        return is_numeric($value) ? (float) $value : $default;
    }

    private static function matchThreshold(): float
    {
        $value = self::float('face_attendance_match_threshold', self::DEFAULT_MATCH_THRESHOLD);

        if ($value < 20 || $value > 100) {
            return self::DEFAULT_MATCH_THRESHOLD;
        }

        return $value;
    }
}
