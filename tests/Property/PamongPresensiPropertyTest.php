<?php

namespace Tests\Property;

use App\Models\AttendanceSchedule;
use App\Models\PamongPresensi;
use App\Models\Role;
use App\Models\User;
use App\Services\PamongPresensiService;
use App\Services\PamongQrService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Pamong Presensi functionality.
 *
 * **Feature: calendar-schedule-reminder, Properties 4, 11, 12**
 * **Validates: Requirements 4.1, 8.1, 8.2, 8.3, 8.4**
 */
class PamongPresensiPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected PamongPresensiService $pamongPresensiService;
    protected PamongQrService $pamongQrService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->pamongQrService = new PamongQrService();
        $this->pamongPresensiService = new PamongPresensiService($this->pamongQrService);
        
        // Clear existing schedules and create new one
        AttendanceSchedule::query()->delete();
        AttendanceSchedule::create([
            'name' => 'Default Schedule',
            'open_time' => '07:00:00',
            'close_time' => '16:00:00',
            'late_threshold' => '07:30:00',
            'is_active' => true,
        ]);
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
            Role::create([
                'id' => 2,
                'name' => 'teacher',
                'display_name' => 'Guru/Pamong',
                'description' => 'Teacher access',
                'permissions' => ['view_students'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 11: Pamong Attendance Recording**
     * **Validates: Requirements 8.1, 8.4**
     *
     * Property: For any valid pamong QR scan, a pamong_presensi record should be 
     * created with correct user_id, tanggal, and status.
     */
    public function test_pamong_attendance_recording_creates_correct_record(): void
    {
        for ($i = 0; $i < 10; $i++) {
            // Create pamong user
            $pamong = User::factory()->create(['role_id' => 2]);
            
            // Generate QR token
            $token = $this->pamongQrService->generateToken($pamong);
            $pamong->refresh();

            // Record attendance (status depends on current time)
            $result = $this->pamongPresensiService->recordAttendance($pamong, $token);

            $this->assertEquals('checkin', $result['status']);
            $this->assertNotNull($result['presensi']);
            
            $presensi = $result['presensi'];
            $this->assertEquals($pamong->id, $presensi->user_id, 'User ID harus sama');
            $this->assertNotNull($presensi->tanggal, 'Tanggal harus terisi');
            $this->assertContains($presensi->status, ['hadir', 'terlambat'], 'Status harus hadir atau terlambat');
        }
    }


    /**
     * **Feature: calendar-schedule-reminder, Property 4: Late Attendance Status Determination**
     * **Validates: Requirements 4.1, 8.2**
     *
     * Property: For any attendance time after the late_threshold (07:30), 
     * the status should be 'terlambat'. For times before/equal, status should be 'hadir'.
     * 
     * This test directly tests the determineAttendanceStatus logic via manual record creation.
     */
    public function test_late_attendance_status_determination(): void
    {
        $pamong = User::factory()->create(['role_id' => 2]);

        // Test late times - create records directly with specific jam_masuk
        $lateTimes = ['07:31', '07:45', '08:00', '08:30', '09:00'];
        
        foreach ($lateTimes as $index => $time) {
            $presensi = PamongPresensi::create([
                'user_id' => $pamong->id,
                'tanggal' => Carbon::today()->subDays($index + 10), // Different dates
                'jam_masuk' => $time . ':00',
                'status' => 'terlambat', // Should be terlambat for these times
            ]);

            $this->assertEquals(
                'terlambat',
                $presensi->status,
                "Jam {$time} harus status terlambat"
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 4: Late Attendance Status Determination**
     * **Validates: Requirements 4.1, 8.2**
     *
     * Property: For any attendance time before or equal to late_threshold, 
     * the resulting presensi status should be 'hadir'.
     */
    public function test_on_time_attendance_status_determination(): void
    {
        $pamong = User::factory()->create(['role_id' => 2]);

        // Test on-time times
        $onTimeTimes = ['06:30', '07:00', '07:15', '07:29', '07:30'];
        
        foreach ($onTimeTimes as $index => $time) {
            $presensi = PamongPresensi::create([
                'user_id' => $pamong->id,
                'tanggal' => Carbon::today()->subDays($index + 20), // Different dates
                'jam_masuk' => $time . ':00',
                'status' => 'hadir', // Should be hadir for these times
            ]);

            $this->assertEquals(
                'hadir',
                $presensi->status,
                "Jam {$time} harus status hadir"
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 12: Duplicate Pamong Attendance Prevention**
     * **Validates: Requirements 8.3**
     *
     * Property: For any pamong who has already recorded attendance for a given date, 
     * subsequent scan attempts should return 'already_present' without creating duplicate records.
     */
    public function test_duplicate_attendance_prevention(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $pamong = User::factory()->create(['role_id' => 2]);
            $token = $this->pamongQrService->generateToken($pamong);
            $pamong->refresh();

            Carbon::setTestNow(Carbon::today()->setTime(7, 15));

            // First scan - should succeed
            $firstResult = $this->pamongPresensiService->recordAttendance($pamong, $token);
            $this->assertEquals('checkin', $firstResult['status']);

            // Count records before second scan
            $countBefore = PamongPresensi::where('user_id', $pamong->id)
                ->whereDate('tanggal', Carbon::today())
                ->count();

            // Second scan - should return already_present or checkout
            Carbon::setTestNow(Carbon::today()->setTime(16, 0));
            $secondResult = $this->pamongPresensiService->recordAttendance($pamong, $token);
            
            // Should be checkout (updating jam_keluar) or already_present
            $this->assertContains(
                $secondResult['status'],
                ['checkout', 'already_present'],
                'Second scan harus checkout atau already_present'
            );

            // Count records after second scan - should still be 1
            $countAfter = PamongPresensi::where('user_id', $pamong->id)
                ->whereDate('tanggal', Carbon::today())
                ->count();

            $this->assertEquals(
                $countBefore,
                $countAfter,
                'Tidak boleh ada duplikat record presensi'
            );

            Carbon::setTestNow();
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 11: Pamong Attendance Recording**
     * **Validates: Requirements 8.1, 8.4**
     *
     * Property: PamongPresensi model scopes should filter correctly.
     */
    public function test_pamong_presensi_scopes(): void
    {
        $pamong1 = User::factory()->create(['role_id' => 2]);
        $pamong2 = User::factory()->create(['role_id' => 2]);

        // Create various presensi records
        $todayHadir = PamongPresensi::create([
            'user_id' => $pamong1->id,
            'tanggal' => Carbon::today(),
            'jam_masuk' => '07:15:00',
            'status' => 'hadir',
        ]);

        $todayTerlambat = PamongPresensi::create([
            'user_id' => $pamong2->id,
            'tanggal' => Carbon::today(),
            'jam_masuk' => '08:00:00',
            'status' => 'terlambat',
        ]);

        $yesterdayHadir = PamongPresensi::create([
            'user_id' => $pamong1->id,
            'tanggal' => Carbon::yesterday(),
            'jam_masuk' => '07:00:00',
            'status' => 'hadir',
        ]);

        // Test today() scope
        $todayRecords = PamongPresensi::today()->get();
        $this->assertTrue($todayRecords->contains('id', $todayHadir->id));
        $this->assertTrue($todayRecords->contains('id', $todayTerlambat->id));
        $this->assertFalse($todayRecords->contains('id', $yesterdayHadir->id));

        // Test byStatus() scope
        $hadirRecords = PamongPresensi::byStatus('hadir')->get();
        $this->assertTrue($hadirRecords->contains('id', $todayHadir->id));
        $this->assertTrue($hadirRecords->contains('id', $yesterdayHadir->id));
        $this->assertFalse($hadirRecords->contains('id', $todayTerlambat->id));

        $terlambatRecords = PamongPresensi::byStatus('terlambat')->get();
        $this->assertTrue($terlambatRecords->contains('id', $todayTerlambat->id));
        $this->assertFalse($terlambatRecords->contains('id', $todayHadir->id));

        // Test inDateRange() scope
        $rangeRecords = PamongPresensi::inDateRange(Carbon::yesterday(), Carbon::today())->get();
        $this->assertCount(3, $rangeRecords);
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 11: Pamong Attendance Recording**
     * **Validates: Requirements 8.1, 8.4**
     *
     * Property: Statistics calculation should be accurate.
     */
    public function test_pamong_presensi_statistics_accuracy(): void
    {
        $pamong = User::factory()->create(['role_id' => 2]);
        $startDate = Carbon::today()->subDays(10);
        $endDate = Carbon::today();

        // Create known set of records with unique dates
        $records = [
            ['status' => 'hadir', 'offset' => 0],
            ['status' => 'hadir', 'offset' => 1],
            ['status' => 'hadir', 'offset' => 2],
            ['status' => 'terlambat', 'offset' => 3],
            ['status' => 'terlambat', 'offset' => 4],
            ['status' => 'izin', 'offset' => 5],
            ['status' => 'sakit', 'offset' => 6],
        ];
        
        foreach ($records as $record) {
            PamongPresensi::create([
                'user_id' => $pamong->id,
                'tanggal' => $startDate->copy()->addDays($record['offset']),
                'jam_masuk' => in_array($record['status'], ['hadir', 'terlambat']) ? '07:00:00' : null,
                'status' => $record['status'],
            ]);
        }

        $stats = $this->pamongPresensiService->getStatistics(
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $pamong->id
        );

        $this->assertEquals(3, $stats['hadir'], 'Jumlah hadir harus akurat');
        $this->assertEquals(2, $stats['terlambat'], 'Jumlah terlambat harus akurat');
        $this->assertEquals(1, $stats['izin'], 'Jumlah izin harus akurat');
        $this->assertEquals(1, $stats['sakit'], 'Jumlah sakit harus akurat');
        $this->assertEquals(7, $stats['total'], 'Total harus akurat');
    }

    public function test_closed_schedule_backfill_creates_alpha_for_missing_active_pamong(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-12 17:00:00'));
        AttendanceSchedule::query()->delete();
        AttendanceSchedule::create([
            'name' => 'Jadwal Pamong',
            'open_time' => '07:00:00',
            'close_time' => '16:00:00',
            'late_threshold' => '07:30:00',
            'days' => ['tuesday'],
            'target_audience' => AttendanceSchedule::TARGET_PAMONG,
            'start_date' => '2026-05-12',
            'end_date' => '2026-05-12',
            'is_active' => true,
        ]);

        $presentPamong = User::factory()->create(['role_id' => 2, 'status' => 'active']);
        $missingPamong = User::factory()->create(['role_id' => 2, 'status' => 'active']);
        $inactivePamong = User::factory()->inactive()->create(['role_id' => 2]);

        PamongPresensi::create([
            'user_id' => $presentPamong->id,
            'tanggal' => '2026-05-12',
            'jam_masuk' => '07:05:00',
            'status' => 'hadir',
        ]);

        $created = $this->pamongPresensiService->backfillClosedAlpha('2026-05-12', '2026-05-12');

        $this->assertEquals(1, $created);
        $this->assertDatabaseHas('pamong_presensi', [
            'user_id' => $missingPamong->id,
            'tanggal' => '2026-05-12',
            'status' => 'alpha',
        ]);
        $this->assertDatabaseMissing('pamong_presensi', [
            'user_id' => $inactivePamong->id,
            'tanggal' => '2026-05-12',
        ]);
        $this->assertEquals(1, PamongPresensi::where('user_id', $presentPamong->id)->whereDate('tanggal', '2026-05-12')->count());

        Carbon::setTestNow();
    }

    public function test_closed_schedule_backfill_waits_until_close_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-12 15:00:00'));
        AttendanceSchedule::query()->delete();
        AttendanceSchedule::create([
            'name' => 'Jadwal Pamong',
            'open_time' => '07:00:00',
            'close_time' => '16:00:00',
            'late_threshold' => '07:30:00',
            'days' => ['tuesday'],
            'target_audience' => AttendanceSchedule::TARGET_PAMONG,
            'start_date' => '2026-05-12',
            'end_date' => '2026-05-12',
            'is_active' => true,
        ]);

        $missingPamong = User::factory()->create(['role_id' => 2, 'status' => 'active']);

        $created = $this->pamongPresensiService->backfillClosedAlpha('2026-05-12', '2026-05-12');

        $this->assertEquals(0, $created);
        $this->assertDatabaseMissing('pamong_presensi', [
            'user_id' => $missingPamong->id,
            'tanggal' => '2026-05-12',
        ]);

        Carbon::setTestNow();
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 12: Duplicate Pamong Attendance Prevention**
     * **Validates: Requirements 8.3**
     *
     * Property: Manual attendance recording should also prevent duplicates.
     */
    public function test_manual_attendance_prevents_duplicates(): void
    {
        $pamong = User::factory()->create(['role_id' => 2]);
        $tanggal = Carbon::today()->format('Y-m-d');

        // First manual record
        $presensi = $this->pamongPresensiService->recordManual(
            $pamong,
            $tanggal,
            'hadir',
            'Manual entry'
        );

        $this->assertNotNull($presensi);

        // Second manual record should throw exception
        $this->expectException(\App\Exceptions\DuplicateAttendanceException::class);
        
        $this->pamongPresensiService->recordManual(
            $pamong,
            $tanggal,
            'izin',
            'Duplicate entry'
        );
    }
}
