<?php

namespace Tests\Unit;

use App\Models\Siswa;
use App\Support\TargetGrade;
use Carbon\Carbon;
use Tests\TestCase;

class TargetGradeTest extends TestCase
{
    public function test_age_twelve_on_july_first_maps_to_smp_7(): void
    {
        $this->assertSame(
            TargetGrade::SMP_7,
            TargetGrade::fromBirthDate('2014-06-30', Carbon::parse('2026-06-19'))
        );
    }

    public function test_age_seventeen_on_july_first_maps_to_sma_12(): void
    {
        $this->assertSame(
            TargetGrade::SMA_12,
            TargetGrade::fromBirthDate('2009-06-30', Carbon::parse('2026-06-19'))
        );
    }

    public function test_age_outside_mapping_returns_null(): void
    {
        $this->assertNull(TargetGrade::fromBirthDate('2016-06-30', Carbon::parse('2026-06-19')));
    }

    public function test_manual_override_wins_over_birth_date(): void
    {
        $siswa = new Siswa([
            'tanggal_lahir' => '2014-06-30',
            'target_grade_override' => TargetGrade::SMA_10,
        ]);

        $this->assertSame(TargetGrade::SMA_10, TargetGrade::resolveForSiswa($siswa));
    }
}
