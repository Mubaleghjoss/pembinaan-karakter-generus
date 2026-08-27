<?php

namespace Tests\Property;

use App\Models\AttendanceSchedule;
use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PresensiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Presensi functionality.
 *
 * **Feature: calendar-schedule-reminder, Properties 5, 6, 7**
 * **Validates: Requirements 4.3, 4.4, 5.1, 5.2, 9.2, 9.3**
 */
class PresensiPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected PresensiService $presensiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->presensiService = app(PresensiService::class);
        
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
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 6: Date Filter Correctness**
     * **Validates: Requirements 4.3, 9.2**
     *
     * Property: For any date range filter applied to presensi data, 
     * all returned records should have tanggal within the specified range.
     */
    public function test_date_filter_returns_only_records_in_range(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        // Create presensi records across different dates
        $dates = [];
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::today()->subDays($i);
            $dates[] = $date;
            
            Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $date,
                'jam_masuk' => '07:15:00',
                'status' => 'hadir',
            ]);
        }

        // Test various date ranges
        $testRanges = [
            ['start' => 0, 'end' => 7],   // Last week
            ['start' => 7, 'end' => 14],  // Week before
            ['start' => 0, 'end' => 14],  // Last 2 weeks
            ['start' => 14, 'end' => 30], // 2-4 weeks ago
        ];

        foreach ($testRanges as $range) {
            $startDate = Carbon::today()->subDays($range['end']);
            $endDate = Carbon::today()->subDays($range['start']);

            $records = Presensi::whereBetween('tanggal', [$startDate, $endDate])->get();

            foreach ($records as $record) {
                $recordDate = Carbon::parse($record->tanggal);
                
                $this->assertTrue(
                    $recordDate->gte($startDate) && $recordDate->lte($endDate),
                    "Record tanggal {$record->tanggal} harus dalam range {$startDate->format('Y-m-d')} - {$endDate->format('Y-m-d')}"
                );
            }
        }
    }


    /**
     * **Feature: calendar-schedule-reminder, Property 6: Date Filter Correctness**
     * **Validates: Requirements 4.3, 9.2**
     *
     * Property: No records outside the specified date range should be included.
     */
    public function test_date_filter_excludes_records_outside_range(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        // Create records inside and outside range
        $insideRange = [];
        $outsideRange = [];

        // Inside range: last 7 days
        for ($i = 0; $i < 7; $i++) {
            $insideRange[] = Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => Carbon::today()->subDays($i),
                'jam_masuk' => '07:15:00',
                'status' => 'hadir',
            ]);
        }

        // Outside range: 10-20 days ago
        for ($i = 10; $i < 20; $i++) {
            $outsideRange[] = Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => Carbon::today()->subDays($i),
                'jam_masuk' => '07:15:00',
                'status' => 'hadir',
            ]);
        }

        $startDate = Carbon::today()->subDays(7);
        $endDate = Carbon::today();

        $records = Presensi::whereBetween('tanggal', [$startDate, $endDate])->get();

        // All inside range records should be included
        foreach ($insideRange as $record) {
            $this->assertTrue(
                $records->contains('id', $record->id),
                'Record dalam range harus termasuk'
            );
        }

        // All outside range records should be excluded
        foreach ($outsideRange as $record) {
            $this->assertFalse(
                $records->contains('id', $record->id),
                'Record di luar range tidak boleh termasuk'
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 7: Late Time Difference Calculation**
     * **Validates: Requirements 5.1, 5.2**
     *
     * Property: For any late attendance record, the calculated time difference 
     * should equal (jam_masuk - late_threshold) in minutes.
     */
    public function test_late_time_difference_calculation(): void
    {
        // Verify schedule exists
        $schedule = AttendanceSchedule::getActiveSchedule();
        $this->assertNotNull($schedule, 'Schedule harus ada');
        $this->assertNotNull($schedule->late_threshold, 'Late threshold harus ada');

        // Test various late times and expected differences
        $testCases = [
            ['jam_masuk' => '07:31:00', 'expected_minutes' => 1],
            ['jam_masuk' => '07:45:00', 'expected_minutes' => 15],
            ['jam_masuk' => '08:00:00', 'expected_minutes' => 30],
            ['jam_masuk' => '08:30:00', 'expected_minutes' => 60],
            ['jam_masuk' => '09:00:00', 'expected_minutes' => 90],
            ['jam_masuk' => '10:00:00', 'expected_minutes' => 150],
        ];

        foreach ($testCases as $case) {
            $lateDuration = $this->presensiService->calculateLateDuration($case['jam_masuk']);

            $this->assertEquals(
                $case['expected_minutes'],
                $lateDuration,
                "Jam masuk {$case['jam_masuk']} harus terlambat {$case['expected_minutes']} menit (late_threshold: " . $schedule->late_threshold->format('H:i:s') . ")"
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 7: Late Time Difference Calculation**
     * **Validates: Requirements 5.1, 5.2**
     *
     * Property: For on-time attendance, late duration should be null or 0.
     */
    public function test_on_time_has_no_late_duration(): void
    {
        // Verify schedule exists
        $schedule = AttendanceSchedule::getActiveSchedule();
        $this->assertNotNull($schedule, 'Schedule harus ada');

        $onTimeTimes = ['06:30:00', '07:00:00', '07:15:00', '07:29:00', '07:30:00'];

        foreach ($onTimeTimes as $time) {
            $lateDuration = $this->presensiService->calculateLateDuration($time);

            $this->assertNull(
                $lateDuration,
                "Jam masuk {$time} tidak boleh memiliki late duration (late_threshold: " . $schedule->late_threshold->format('H:i:s') . ")"
            );
        }
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 5: Attendance Statistics Accuracy**
     * **Validates: Requirements 4.4, 9.3**
     *
     * Property: For any set of presensi records, the statistics should equal 
     * the actual count of records with each respective status.
     */
    public function test_attendance_statistics_accuracy(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        $startDate = Carbon::today()->subDays(10);
        $endDate = Carbon::today();

        // Create known set of records
        $statusCounts = [
            'hadir' => 4,
            'terlambat' => 3,
            'izin' => 2,
            'sakit' => 1,
            'alpha' => 1,
        ];

        $dayOffset = 0;
        foreach ($statusCounts as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                Presensi::create([
                    'siswa_id' => $siswa->id,
                    'tanggal' => $startDate->copy()->addDays($dayOffset),
                    'jam_masuk' => in_array($status, ['hadir', 'terlambat']) ? '07:00:00' : null,
                    'status' => $status,
                ]);
                $dayOffset++;
            }
        }

        // Get statistics using repository
        $stats = Presensi::whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        foreach ($statusCounts as $status => $expectedCount) {
            $actualCount = $stats[$status] ?? 0;
            $this->assertEquals(
                $expectedCount,
                $actualCount,
                "Jumlah {$status} harus {$expectedCount}, dapat {$actualCount}"
            );
        }
    }

    public function test_closed_schedule_backfill_creates_alpha_for_missing_active_siswa(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-12 17:00:00'));
        AttendanceSchedule::query()->delete();

        AttendanceSchedule::create([
            'name' => 'Jadwal Siswa',
            'open_time' => '07:00:00',
            'close_time' => '16:00:00',
            'late_threshold' => '07:30:00',
            'days' => ['tuesday'],
            'target_audience' => AttendanceSchedule::TARGET_SISWA,
            'start_date' => '2026-05-12',
            'end_date' => '2026-05-12',
            'is_active' => true,
        ]);

        $kelas = Kelas::factory()->create();
        $presentSiswa = Siswa::factory()->create(['kelas_id' => $kelas->id, 'status' => 'active', 'is_active' => true]);
        $missingSiswa = Siswa::factory()->create(['kelas_id' => $kelas->id, 'status' => 'active', 'is_active' => true]);
        $inactiveSiswa = Siswa::factory()->inactive()->create(['kelas_id' => $kelas->id]);

        Presensi::create([
            'siswa_id' => $presentSiswa->id,
            'tanggal' => '2026-05-12',
            'jam_masuk' => '07:05:00',
            'status' => 'hadir',
        ]);

        $created = $this->presensiService->backfillClosedAlpha('2026-05-12', '2026-05-12', $kelas->id);

        $this->assertEquals(1, $created);
        $this->assertDatabaseHas('presensi', [
            'siswa_id' => $missingSiswa->id,
            'tanggal' => '2026-05-12',
            'status' => 'alpha',
        ]);
        $this->assertDatabaseMissing('presensi', [
            'siswa_id' => $inactiveSiswa->id,
            'tanggal' => '2026-05-12',
        ]);
        $this->assertEquals(
            1,
            Presensi::where('siswa_id', $presentSiswa->id)->whereDate('tanggal', '2026-05-12')->count()
        );

        Carbon::setTestNow();
    }

    public function test_closed_schedule_backfill_waits_until_close_time_for_siswa(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-12 15:00:00'));
        AttendanceSchedule::query()->delete();

        AttendanceSchedule::create([
            'name' => 'Jadwal Siswa',
            'open_time' => '07:00:00',
            'close_time' => '16:00:00',
            'late_threshold' => '07:30:00',
            'days' => ['tuesday'],
            'target_audience' => AttendanceSchedule::TARGET_SISWA,
            'start_date' => '2026-05-12',
            'end_date' => '2026-05-12',
            'is_active' => true,
        ]);

        $missingSiswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);

        $created = $this->presensiService->backfillClosedAlpha('2026-05-12', '2026-05-12');

        $this->assertEquals(0, $created);
        $this->assertDatabaseMissing('presensi', [
            'siswa_id' => $missingSiswa->id,
            'tanggal' => '2026-05-12',
        ]);

        Carbon::setTestNow();
    }

    public function test_bulk_verify_updates_filtered_unverified_presensi(): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            [
                'display_name' => 'Administrator',
                'permissions' => ['*'],
                'is_active' => true,
            ]
        );
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);
        $otherSiswa = Siswa::factory()->create();

        $target = Presensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-05-12',
            'jam_masuk' => '07:15:00',
            'status' => 'hadir',
            'is_verified' => false,
        ]);

        $other = Presensi::create([
            'siswa_id' => $otherSiswa->id,
            'tanggal' => '2026-05-12',
            'jam_masuk' => '07:15:00',
            'status' => 'hadir',
            'is_verified' => false,
        ]);

        $preview = $this->actingAs($admin)->postJson(route('presensi.bulk-verify'), [
            'scope' => 'selected',
            'ids' => [$target->id],
            'tanggal' => '2026-05-12',
            'kelas_id' => $kelas->id,
            'status' => 'hadir',
            'preview' => true,
        ]);

        $preview->assertOk()->assertJsonPath('affected', 1);
        $previewToken = $preview->json('preview_token');
        $this->assertNotEmpty($previewToken);
        $this->assertFalse($target->fresh()->is_verified);

        $response = $this->actingAs($admin)->postJson(route('presensi.bulk-verify'), [
            'scope' => 'selected',
            'ids' => [$target->id],
            'tanggal' => '2026-05-12',
            'kelas_id' => $kelas->id,
            'status' => 'hadir',
            'preview_token' => $previewToken,
        ]);

        $response->assertOk()->assertJsonPath('updated', 1);
        $this->assertTrue($target->fresh()->is_verified);
        $this->assertFalse($other->fresh()->is_verified);
    }

    public function test_bulk_verify_requires_explicit_scope_and_non_empty_selected_ids(): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]
        );
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $this->actingAs($admin)->postJson(route('presensi.bulk-verify'), [
            'scope' => 'selected',
            'ids' => [],
            'preview' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('ids');

        $this->actingAs($admin)->postJson(route('presensi.bulk-verify'), [
            'scope' => 'filtered',
            'ids' => [1],
            'preview' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('ids');
    }

    public function test_bulk_verify_is_fail_closed_for_role_outside_pamong_permissions(): void
    {
        $siswaRole = Role::query()->firstOrCreate(
            ['name' => 'siswa'],
            ['display_name' => 'Siswa', 'permissions' => [], 'is_active' => true]
        );
        $siswa = User::factory()->create(['role_id' => $siswaRole->id]);

        $this->actingAs($siswa)->postJson(route('presensi.bulk-verify'), [
            'scope' => 'filtered',
            'preview' => true,
        ])->assertForbidden();
    }

    public function test_filtered_bulk_verify_executes_the_previewed_snapshot_only(): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['name' => User::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]
        );
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $previewed = Presensi::create([
            'siswa_id' => Siswa::factory()->create()->id,
            'tanggal' => '2026-05-12',
            'jam_masuk' => '07:15:00',
            'status' => 'hadir',
            'is_verified' => false,
        ]);

        $preview = $this->actingAs($admin)->postJson(route('presensi.bulk-verify'), [
            'scope' => 'filtered',
            'tanggal' => '2026-05-12',
            'status' => 'hadir',
            'preview' => true,
        ])->assertOk()->assertJsonPath('affected', 1);

        $addedAfterPreview = Presensi::create([
            'siswa_id' => Siswa::factory()->create()->id,
            'tanggal' => '2026-05-12',
            'jam_masuk' => '07:30:00',
            'status' => 'hadir',
            'is_verified' => false,
        ]);

        $this->actingAs($admin)->postJson(route('presensi.bulk-verify'), [
            'scope' => 'filtered',
            'tanggal' => '2026-05-12',
            'status' => 'hadir',
            'preview_token' => $preview->json('preview_token'),
        ])->assertOk()->assertJsonPath('updated', 1);

        $this->assertTrue($previewed->fresh()->is_verified);
        $this->assertFalse($addedAfterPreview->fresh()->is_verified);
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 5: Attendance Statistics Accuracy**
     * **Validates: Requirements 4.4, 9.3**
     *
     * Property: Statistics filtered by kelas should only count records from that kelas.
     */
    public function test_statistics_filtered_by_kelas(): void
    {
        $kelas1 = Kelas::factory()->create();
        $kelas2 = Kelas::factory()->create();
        
        $siswa1 = Siswa::factory()->create(['kelas_id' => $kelas1->id]);
        $siswa2 = Siswa::factory()->create(['kelas_id' => $kelas2->id]);

        $startDate = Carbon::today()->subDays(5);
        $endDate = Carbon::today();

        // Create records for kelas1
        for ($i = 0; $i < 3; $i++) {
            Presensi::create([
                'siswa_id' => $siswa1->id,
                'tanggal' => $startDate->copy()->addDays($i),
                'jam_masuk' => '07:15:00',
                'status' => 'hadir',
            ]);
        }

        // Create records for kelas2
        for ($i = 0; $i < 5; $i++) {
            Presensi::create([
                'siswa_id' => $siswa2->id,
                'tanggal' => $startDate->copy()->addDays($i),
                'jam_masuk' => '07:15:00',
                'status' => 'hadir',
            ]);
        }

        // Get statistics for kelas1 only
        $kelas1Count = Presensi::whereHas('siswa', function ($q) use ($kelas1) {
            $q->where('kelas_id', $kelas1->id);
        })->whereBetween('tanggal', [$startDate, $endDate])->count();

        $this->assertEquals(3, $kelas1Count, 'Kelas 1 harus memiliki 3 record');

        // Get statistics for kelas2 only
        $kelas2Count = Presensi::whereHas('siswa', function ($q) use ($kelas2) {
            $q->where('kelas_id', $kelas2->id);
        })->whereBetween('tanggal', [$startDate, $endDate])->count();

        $this->assertEquals(5, $kelas2Count, 'Kelas 2 harus memiliki 5 record');
    }

    /**
     * **Feature: calendar-schedule-reminder, Property 6: Date Filter Correctness**
     * **Validates: Requirements 4.3, 9.2**
     *
     * Property: Empty date range should return no records.
     */
    public function test_empty_date_range_returns_no_records(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

        // Create some records
        for ($i = 0; $i < 5; $i++) {
            Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => Carbon::today()->subDays($i),
                'jam_masuk' => '07:15:00',
                'status' => 'hadir',
            ]);
        }

        // Query for a date range with no records (future dates)
        $startDate = Carbon::today()->addDays(10);
        $endDate = Carbon::today()->addDays(20);

        $records = Presensi::whereBetween('tanggal', [$startDate, $endDate])->get();

        $this->assertCount(0, $records, 'Future date range harus mengembalikan 0 record');
    }
}
