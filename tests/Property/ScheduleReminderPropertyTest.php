<?php

namespace Tests\Property;

use App\Models\Role;
use App\Models\ScheduleReminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-based tests for Schedule Reminder functionality.
 *
 * **Feature: calendar-schedule-reminder, Properties 1-3**
 * **Validates: Requirements 1.2, 1.3, 1.5, 2.1, 2.3, 3.1, 3.3**
 */
class ScheduleReminderPropertyTest extends TestCase
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
     * **Feature: calendar-schedule-reminder, Property 1: Schedule Reminder CRUD Consistency**
     * **Validates: Requirements 1.2, 1.3**
     *
     * Property: For any schedule reminder data, creating then retrieving 
     * should return equivalent data with all fields preserved.
     */
    public function test_schedule_reminder_crud_consistency(): void
    {
        $user = User::factory()->create(['role_id' => 1]);
        $audiences = ['siswa', 'pamong', 'all'];
        $colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6'];

        for ($i = 0; $i < 20; $i++) {
            $title = 'Jadwal Test ' . $i . ' ' . Str::random(10);
            $description = 'Deskripsi jadwal ' . $i . '. ' . Str::random(50);
            $targetAudience = $audiences[$i % 3];
            $color = $colors[$i % 5];
            $location = 'Ruang ' . ($i + 1);
            $startDate = Carbon::today()->addDays($i);
            $endDate = Carbon::today()->addDays($i + 7);
            $startTime = sprintf('%02d:00:00', 7 + ($i % 5));
            $endTime = sprintf('%02d:00:00', 8 + ($i % 5));

            $schedule = ScheduleReminder::create([
                'title' => $title,
                'description' => $description,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'target_audience' => $targetAudience,
                'is_recurring' => false,
                'location' => $location,
                'color' => $color,
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            // Query kembali dari database
            $retrieved = ScheduleReminder::find($schedule->id);

            $this->assertNotNull($retrieved, 'Schedule harus tersimpan di database');
            $this->assertEquals($title, $retrieved->title, 'Title harus sama');
            $this->assertEquals($description, $retrieved->description, 'Description harus sama');
            $this->assertEquals($targetAudience, $retrieved->target_audience, 'Target audience harus sama');
            $this->assertEquals($color, $retrieved->color, 'Color harus sama');
            $this->assertEquals($location, $retrieved->location, 'Location harus sama');
            $this->assertTrue($retrieved->start_date->isSameDay($startDate), 'Start date harus sama');
            $this->assertTrue($retrieved->end_date->isSameDay($endDate), 'End date harus sama');
            $this->assertEquals($user->id, $retrieved->created_by, 'Created by harus sama');
        }
    }


    /**
     * **Feature: calendar-schedule-reminder, Property 1: Schedule Reminder CRUD Consistency**
     * **Validates: Requirements 1.2, 1.3**
     *
     * Property: For any schedule reminder, update should persist changes correctly.
     */
    public function test_schedule_reminder_update_persists_changes(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        for ($i = 0; $i < 10; $i++) {
            $schedule = ScheduleReminder::create([
                'title' => 'Original Title ' . $i,
                'description' => 'Original description',
                'start_date' => Carbon::today(),
                'target_audience' => 'all',
                'is_recurring' => false,
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            $newTitle = 'Updated Title ' . $i . ' ' . Str::random(10);
            $newDescription = 'Updated description ' . Str::random(30);
            $newAudiences = ['siswa', 'pamong', 'all'];
            $newAudience = $newAudiences[$i % 3];

            $schedule->update([
                'title' => $newTitle,
                'description' => $newDescription,
                'target_audience' => $newAudience,
            ]);

            $schedule->refresh();

            $this->assertEquals($newTitle, $schedule->title, 'Title harus terupdate');
            $this->assertEquals($newDescription, $schedule->description, 'Description harus terupdate');
            $this->assertEquals($newAudience, $schedule->target_audience, 'Target audience harus terupdate');
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 1: Schedule Reminder CRUD Consistency**
     * **Validates: Requirements 1.2, 1.3**
     *
     * Property: For any schedule reminder, deletion should remove the record.
     */
    public function test_schedule_reminder_deletion_removes_record(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        for ($i = 0; $i < 10; $i++) {
            $schedule = ScheduleReminder::create([
                'title' => 'To Delete ' . $i,
                'start_date' => Carbon::today(),
                'target_audience' => 'all',
                'is_recurring' => false,
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            $scheduleId = $schedule->id;
            $this->assertNotNull(ScheduleReminder::find($scheduleId), 'Schedule harus ada sebelum dihapus');

            $schedule->delete();

            $this->assertNull(ScheduleReminder::find($scheduleId), 'Schedule harus null setelah dihapus');
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 2: Schedule Target Audience Filtering**
     * **Validates: Requirements 2.1, 3.1, 3.3**
     *
     * Property: For any schedule with target_audience 'siswa', it should appear 
     * in siswa queries but not in pamong-only queries.
     */
    public function test_target_audience_filtering_siswa(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        // Create schedules for different audiences
        $siswaSchedules = [];
        $pamongSchedules = [];
        $allSchedules = [];

        for ($i = 0; $i < 5; $i++) {
            $siswaSchedules[] = ScheduleReminder::create([
                'title' => 'Siswa Schedule ' . $i,
                'start_date' => Carbon::today(),
                'target_audience' => 'siswa',
                'is_recurring' => false,
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            $pamongSchedules[] = ScheduleReminder::create([
                'title' => 'Pamong Schedule ' . $i,
                'start_date' => Carbon::today(),
                'target_audience' => 'pamong',
                'is_recurring' => false,
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            $allSchedules[] = ScheduleReminder::create([
                'title' => 'All Schedule ' . $i,
                'start_date' => Carbon::today(),
                'target_audience' => 'all',
                'is_recurring' => false,
                'is_active' => true,
                'created_by' => $user->id,
            ]);
        }

        // Query for siswa audience
        $siswaResults = ScheduleReminder::forAudience('siswa')->get();

        // Siswa schedules should appear
        foreach ($siswaSchedules as $schedule) {
            $this->assertTrue(
                $siswaResults->contains('id', $schedule->id),
                'Siswa schedule harus muncul di siswa query'
            );
        }

        // All schedules should appear
        foreach ($allSchedules as $schedule) {
            $this->assertTrue(
                $siswaResults->contains('id', $schedule->id),
                'All schedule harus muncul di siswa query'
            );
        }

        // Pamong-only schedules should NOT appear
        foreach ($pamongSchedules as $schedule) {
            $this->assertFalse(
                $siswaResults->contains('id', $schedule->id),
                'Pamong-only schedule tidak boleh muncul di siswa query'
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 2: Schedule Target Audience Filtering**
     * **Validates: Requirements 2.1, 3.1, 3.3**
     *
     * Property: For any schedule with target_audience 'pamong', it should appear 
     * in pamong queries but not in siswa-only queries.
     */
    public function test_target_audience_filtering_pamong(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        $siswaSchedule = ScheduleReminder::create([
            'title' => 'Siswa Only',
            'start_date' => Carbon::today(),
            'target_audience' => 'siswa',
            'is_recurring' => false,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $pamongSchedule = ScheduleReminder::create([
            'title' => 'Pamong Only',
            'start_date' => Carbon::today(),
            'target_audience' => 'pamong',
            'is_recurring' => false,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $allSchedule = ScheduleReminder::create([
            'title' => 'For All',
            'start_date' => Carbon::today(),
            'target_audience' => 'all',
            'is_recurring' => false,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // Query for pamong audience
        $pamongResults = ScheduleReminder::forAudience('pamong')->get();

        $this->assertTrue($pamongResults->contains('id', $pamongSchedule->id), 'Pamong schedule harus muncul');
        $this->assertTrue($pamongResults->contains('id', $allSchedule->id), 'All schedule harus muncul');
        $this->assertFalse($pamongResults->contains('id', $siswaSchedule->id), 'Siswa-only tidak boleh muncul');
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 3: Recurring Schedule Expansion**
     * **Validates: Requirements 1.5, 2.3**
     *
     * Property: For any daily recurring schedule, expandToEvents should return 
     * one event for each day in the range.
     */
    public function test_recurring_daily_schedule_expansion(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        $startDate = Carbon::today();
        $schedule = ScheduleReminder::create([
            'title' => 'Daily Meeting',
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays(6), // 7 days total
            'target_audience' => 'all',
            'is_recurring' => true,
            'recurrence_pattern' => 'daily',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $rangeStart = $startDate->copy();
        $rangeEnd = $startDate->copy()->addDays(6);

        $events = $schedule->expandToEvents($rangeStart, $rangeEnd);

        // Should have 7 events (one for each day)
        $this->assertCount(7, $events, 'Daily recurring harus menghasilkan 7 events untuk 7 hari');

        // Each event should have correct structure
        foreach ($events as $event) {
            $this->assertArrayHasKey('id', $event);
            $this->assertArrayHasKey('title', $event);
            $this->assertArrayHasKey('start', $event);
            $this->assertArrayHasKey('type', $event);
            $this->assertEquals('schedule-reminder', $event['type']);
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 3: Recurring Schedule Expansion**
     * **Validates: Requirements 1.5, 2.3**
     *
     * Property: For any weekly recurring schedule with specific days, 
     * expandToEvents should only return events for those days.
     */
    public function test_recurring_weekly_schedule_expansion(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        // Start from a known Monday
        $startDate = Carbon::parse('next monday');
        
        $schedule = ScheduleReminder::create([
            'title' => 'Weekly Meeting',
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addWeeks(2), // 2 weeks
            'target_audience' => 'all',
            'is_recurring' => true,
            'recurrence_pattern' => 'weekly',
            'recurrence_days' => ['monday', 'wednesday', 'friday'],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $rangeStart = $startDate->copy();
        $rangeEnd = $startDate->copy()->addWeeks(2);

        $events = $schedule->expandToEvents($rangeStart, $rangeEnd);

        // Should have events only on Monday, Wednesday, Friday
        foreach ($events as $event) {
            $eventDate = Carbon::parse($event['start']);
            $dayName = strtolower($eventDate->format('l'));
            
            $this->assertContains(
                $dayName,
                ['monday', 'wednesday', 'friday'],
                "Event harus jatuh pada hari yang ditentukan, bukan {$dayName}"
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 3: Recurring Schedule Expansion**
     * **Validates: Requirements 1.5, 2.3**
     *
     * Property: For any non-recurring schedule, expandToEvents should return 
     * exactly one event.
     */
    public function test_non_recurring_schedule_returns_single_event(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        for ($i = 0; $i < 10; $i++) {
            $startDate = Carbon::today()->addDays($i);
            
            $schedule = ScheduleReminder::create([
                'title' => 'Single Event ' . $i,
                'start_date' => $startDate,
                'target_audience' => 'all',
                'is_recurring' => false,
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            $rangeStart = Carbon::today();
            $rangeEnd = Carbon::today()->addMonth();

            $events = $schedule->expandToEvents($rangeStart, $rangeEnd);

            $this->assertCount(1, $events, 'Non-recurring schedule harus menghasilkan tepat 1 event');
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 2: Schedule Target Audience Filtering**
     * **Validates: Requirements 2.1, 3.1, 3.3**
     *
     * Property: Active scope should only return schedules with is_active = true.
     */
    public function test_active_scope_filters_correctly(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        $activeSchedules = [];
        $inactiveSchedules = [];

        for ($i = 0; $i < 5; $i++) {
            $activeSchedules[] = ScheduleReminder::create([
                'title' => 'Active ' . $i,
                'start_date' => Carbon::today(),
                'target_audience' => 'all',
                'is_recurring' => false,
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            $inactiveSchedules[] = ScheduleReminder::create([
                'title' => 'Inactive ' . $i,
                'start_date' => Carbon::today(),
                'target_audience' => 'all',
                'is_recurring' => false,
                'is_active' => false,
                'created_by' => $user->id,
            ]);
        }

        $results = ScheduleReminder::active()->get();

        foreach ($activeSchedules as $schedule) {
            $this->assertTrue($results->contains('id', $schedule->id), 'Active schedule harus muncul');
        }

        foreach ($inactiveSchedules as $schedule) {
            $this->assertFalse($results->contains('id', $schedule->id), 'Inactive schedule tidak boleh muncul');
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 3: Recurring Schedule Expansion**
     * **Validates: Requirements 1.5, 2.3**
     *
     * Property: For monthly recurring schedule, events should only appear 
     * on the same day of month as start_date.
     */
    public function test_recurring_monthly_schedule_expansion(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        // Use day 15 to avoid month-end issues
        $startDate = Carbon::today()->day(15);
        
        $schedule = ScheduleReminder::create([
            'title' => 'Monthly Meeting',
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths(3),
            'target_audience' => 'all',
            'is_recurring' => true,
            'recurrence_pattern' => 'monthly',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $rangeStart = $startDate->copy();
        $rangeEnd = $startDate->copy()->addMonths(3);

        $events = $schedule->expandToEvents($rangeStart, $rangeEnd);

        // Each event should be on day 15
        foreach ($events as $event) {
            $eventDate = Carbon::parse($event['start']);
            $this->assertEquals(
                15,
                $eventDate->day,
                'Monthly event harus jatuh pada tanggal yang sama dengan start_date'
            );
        }
    }
}
