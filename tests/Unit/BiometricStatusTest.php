<?php

namespace Tests\Unit;

use App\Support\BiometricStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BiometricStatusTest extends TestCase
{
    #[Test]
    public function it_marks_status_as_active_when_valid_credential_exists(): void
    {
        $this->assertSame(BiometricStatus::ACTIVE, BiometricStatus::fromCounts(1, 5));
    }

    #[Test]
    public function it_marks_status_as_legacy_when_only_legacy_credentials_exist(): void
    {
        $this->assertSame(BiometricStatus::LEGACY, BiometricStatus::fromCounts(0, 2));
    }

    #[Test]
    public function it_marks_status_as_inactive_when_no_credentials_exist(): void
    {
        $this->assertSame(BiometricStatus::INACTIVE, BiometricStatus::fromCounts(0, 0));
    }
}
