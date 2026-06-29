<?php

namespace Tests\Unit;

use App\Services\FaceAttendanceService;
use App\Support\FaceAttendanceConfig;
use PHPUnit\Framework\TestCase;

class FaceAttendanceServiceTest extends TestCase
{
    public function test_radius_unit_conversion_to_meters(): void
    {
        $this->assertSame(250.0, FaceAttendanceConfig::radiusMeters(250, 'meter'));
        $this->assertSame(1500.0, FaceAttendanceConfig::radiusMeters(1.5, 'kilometer'));
        $this->assertSame(75.0, FaceAttendanceConfig::radiusMeters(75, 'invalid'));
    }

    public function test_haversine_distance_returns_expected_meter_range(): void
    {
        $service = new FaceAttendanceService();

        $distance = $service->distanceMeters(
            -6.219501040781815,
            106.64336089878178,
            -6.219501040781815,
            106.64436089878178
        );

        $this->assertGreaterThan(105, $distance);
        $this->assertLessThan(120, $distance);
    }

    public function test_descriptor_similarity_percent_is_normalized_from_raw_distance(): void
    {
        $service = new FaceAttendanceService();

        $this->assertSame(0.0, $service->descriptorDistance([1, 1], [1, 1]));
        $this->assertSame(100.0, $service->descriptorSimilarityPercent(0));
        $this->assertSame(50.0, $service->descriptorSimilarityPercent(10));
        $this->assertSame(0.0, $service->descriptorSimilarityPercent(25));
    }
}
