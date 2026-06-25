<?php

namespace Tests\Unit\Repositories;

use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Repositories\EloquentPresensiRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentPresensiRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPresensiRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentPresensiRepository;
    }

    /** @test */
    public function it_can_find_presensi_by_id(): void
    {
        $presensi = Presensi::factory()->create();

        $found = $this->repository->findById($presensi->id);

        $this->assertNotNull($found);
        $this->assertEquals($presensi->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_presensi_not_found_by_id(): void
    {
        $found = $this->repository->findById(999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_find_presensi_by_student_and_date(): void
    {
        $siswa = Siswa::factory()->create();
        $tanggal = Carbon::today()->subDays(100); // Use a unique date to avoid conflicts
        $presensi = Presensi::factory()->create([
            'siswa_id' => $siswa->id,
            'tanggal' => $tanggal,
        ]);

        // Refresh to get the actual stored date
        $presensi->refresh();
        $found = $this->repository->findByStudentAndDate($siswa->id, $presensi->tanggal->format('Y-m-d'));

        $this->assertNotNull($found);
        $this->assertEquals($presensi->id, $found->id);
    }

    /** @test */
    public function it_can_create_presensi(): void
    {
        $siswa = Siswa::factory()->create();
        $tanggal = Carbon::parse('2024-01-15');
        $data = [
            'siswa_id' => $siswa->id,
            'tanggal' => $tanggal,
            'jam_masuk' => '07:30:00',
            'status' => 'hadir',
            'is_verified' => false,
        ];

        $presensi = $this->repository->create($data);

        $this->assertInstanceOf(Presensi::class, $presensi);
        $this->assertEquals('hadir', $presensi->status);
        $this->assertEquals($siswa->id, $presensi->siswa_id);
        $this->assertEquals($tanggal->format('Y-m-d'), $presensi->tanggal->format('Y-m-d'));
    }

    /** @test */
    public function it_can_update_presensi(): void
    {
        $presensi = Presensi::factory()->create(['status' => 'hadir']);

        $updated = $this->repository->update($presensi->id, ['status' => 'terlambat']);

        $this->assertEquals('terlambat', $updated->status);
        $this->assertDatabaseHas('presensi', ['id' => $presensi->id, 'status' => 'terlambat']);
    }

    /** @test */
    public function it_throws_exception_when_updating_non_existent_presensi(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->update(999, ['status' => 'hadir']);
    }

    /** @test */
    public function it_can_delete_presensi(): void
    {
        $presensi = Presensi::factory()->create();

        $result = $this->repository->delete($presensi->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('presensi', ['id' => $presensi->id]);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_presensi(): void
    {
        $result = $this->repository->delete(999);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_presensi_by_date_range(): void
    {
        $siswa = Siswa::factory()->create();
        $today = Carbon::today();
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => $today->copy()->subDays(5)]);
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => $today->copy()->subDays(3)]);
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => $today->copy()->addDays(5)]);

        $startDate = $today->copy()->subDays(10)->format('Y-m-d');
        $endDate = $today->format('Y-m-d');
        $result = $this->repository->getByDateRange($startDate, $endDate);

        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_can_get_presensi_by_date_range_filtered_by_kelas(): void
    {
        $kelas1 = Kelas::factory()->create();
        $kelas2 = Kelas::factory()->create();
        $siswa1 = Siswa::factory()->create(['kelas_id' => $kelas1->id]);
        $siswa2 = Siswa::factory()->create(['kelas_id' => $kelas2->id]);

        Presensi::factory()->create(['siswa_id' => $siswa1->id, 'tanggal' => '2024-01-15']);
        Presensi::factory()->create(['siswa_id' => $siswa2->id, 'tanggal' => '2024-01-15']);

        $result = $this->repository->getByDateRange('2024-01-01', '2024-01-31', $kelas1->id);

        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_can_get_statistics(): void
    {
        $siswa = Siswa::factory()->create();
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => '2024-01-10', 'status' => 'hadir']);
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => '2024-01-11', 'status' => 'hadir']);
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => '2024-01-12', 'status' => 'terlambat']);
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => '2024-01-13', 'status' => 'izin']);
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => '2024-01-14', 'status' => 'alpha']);

        $stats = $this->repository->getStatistics('2024-01-01', '2024-01-31');

        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(2, $stats['hadir']);
        $this->assertEquals(1, $stats['terlambat']);
        $this->assertEquals(1, $stats['izin']);
        $this->assertEquals(1, $stats['alpha']);
        $this->assertEquals(60.0, $stats['persentase_kehadiran']);
    }

    /** @test */
    public function it_can_paginate_presensi_with_filters(): void
    {
        $siswa = Siswa::factory()->create();
        Presensi::factory()->count(3)->create(['siswa_id' => $siswa->id, 'status' => 'hadir']);
        Presensi::factory()->count(2)->create(['status' => 'alpha']);

        $result = $this->repository->paginate(['status' => 'hadir'], 10);

        $this->assertEquals(3, $result->total());
    }

    /** @test */
    public function it_can_get_today_presensi(): void
    {
        $siswa = Siswa::factory()->create();
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => Carbon::today()]);
        Presensi::factory()->create(['siswa_id' => $siswa->id, 'tanggal' => Carbon::yesterday()]);

        $result = $this->repository->getToday();

        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_can_get_unverified_presensi(): void
    {
        Presensi::factory()->count(3)->create(['is_verified' => false]);
        Presensi::factory()->count(2)->create(['is_verified' => true]);

        $result = $this->repository->getUnverified();

        $this->assertCount(3, $result);
    }
}
