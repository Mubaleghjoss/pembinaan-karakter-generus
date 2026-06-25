<?php

namespace Tests\Unit;

use App\Services\MateriRppPlanner;
use Carbon\Carbon;
use Tests\TestCase;

class MateriRppPlannerTest extends TestCase
{
    public function test_one_thousand_pages_with_six_pages_weekly_creates_one_hundred_sixty_seven_sessions(): void
    {
        $plan = (new MateriRppPlanner())->plan([
            'total_pages' => 1000,
            'start_page' => 1,
            'pages_per_session' => 6,
            'start_date' => '2026-01-05',
        ]);

        $this->assertSame(167, $plan['total_sessions']);
        $this->assertSame(
            Carbon::parse('2026-01-05')->addWeeks(166)->toDateString(),
            $plan['end_date']
        );
    }

    public function test_extra_weekly_day_can_make_plan_finish_sooner(): void
    {
        $planner = new MateriRppPlanner();

        $regularPlan = $planner->plan([
            'total_pages' => 12,
            'start_page' => 1,
            'pages_per_session' => 6,
            'start_date' => '2026-01-05',
        ]);

        $extraPlan = $planner->plan([
            'total_pages' => 12,
            'start_page' => 1,
            'pages_per_session' => 6,
            'start_date' => '2026-01-05',
            'extra_sessions' => [
                ['date' => '2026-01-06'],
            ],
        ]);

        $this->assertSame('2026-01-12', $regularPlan['end_date']);
        $this->assertSame('2026-01-06', $extraPlan['end_date']);
    }

    public function test_extra_weekly_day_repeats_each_week(): void
    {
        $plan = (new MateriRppPlanner())->plan([
            'total_pages' => 20,
            'start_page' => 1,
            'pages_per_session' => 5,
            'start_date' => '2026-01-05',
            'extra_sessions' => [
                ['date' => '2026-01-07'],
            ],
        ]);

        $this->assertSame(
            ['2026-01-05', '2026-01-07', '2026-01-12', '2026-01-14'],
            array_column($plan['sessions'], 'date')
        );
        $this->assertSame('2026-01-14', $plan['end_date']);
    }

    public function test_catch_up_range_creates_daily_one_time_sessions(): void
    {
        $plan = (new MateriRppPlanner())->plan([
            'total_pages' => 26,
            'start_page' => 1,
            'pages_per_session' => 5,
            'start_date' => '2026-01-05',
            'catch_up_ranges' => [
                [
                    'start_date' => '2026-01-06',
                    'end_date' => '2026-01-08',
                    'pages' => 5,
                ],
            ],
        ]);

        $this->assertSame(
            ['2026-01-05', '2026-01-06', '2026-01-07', '2026-01-08', '2026-01-12', '2026-01-19'],
            array_column($plan['sessions'], 'date')
        );
        $this->assertSame('catch_up', $plan['sessions'][1]['type']);
        $this->assertSame('Halaman 6-10', $plan['sessions'][1]['page_range']);
        $this->assertSame('2026-01-19', $plan['end_date']);
    }

    public function test_teacher_pool_rotates_across_sessions(): void
    {
        $plan = (new MateriRppPlanner())->plan([
            'total_pages' => 24,
            'start_page' => 1,
            'pages_per_session' => 6,
            'start_date' => '2026-01-05',
            'teacher_pool' => [
                ['name' => 'Mas A'],
                ['name' => 'Mas B'],
                ['name' => 'Mas C'],
            ],
        ]);

        $this->assertSame(
            ['Mas A', 'Mas B', 'Mas C', 'Mas A'],
            array_column($plan['sessions'], 'teacher_name')
        );
        $this->assertFalse($plan['sessions'][0]['teacher_is_override']);
        $this->assertFalse($plan['sessions'][1]['teacher_is_override']);
    }

    public function test_custom_extra_session_target_builds_correct_page_ranges(): void
    {
        $plan = (new MateriRppPlanner())->plan([
            'total_pages' => 15,
            'start_page' => 1,
            'pages_per_session' => 5,
            'start_date' => '2026-01-05',
            'extra_sessions' => [
                ['date' => '2026-01-06', 'pages' => 3],
            ],
        ]);

        $this->assertSame('Halaman 1-5', $plan['sessions'][0]['page_range']);
        $this->assertSame('Halaman 6-8', $plan['sessions'][1]['page_range']);
        $this->assertSame('weekly_extra', $plan['sessions'][1]['type']);
        $this->assertSame('Halaman 14-15', $plan['sessions'][3]['page_range']);
    }

    public function test_last_page_never_exceeds_total_pages(): void
    {
        $plan = (new MateriRppPlanner())->plan([
            'total_pages' => 10,
            'start_page' => 1,
            'pages_per_session' => 6,
            'start_date' => '2026-01-05',
        ]);

        $lastSession = end($plan['sessions']);

        $this->assertSame(10, $lastSession['page_end']);
        $this->assertSame('Halaman 7-10', $lastSession['page_range']);
    }
}
