<?php

namespace Tests\Unit\Repositories;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Repositories\EloquentSiswaRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EloquentSiswaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentSiswaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentSiswaRepository;
    }

    /** @test */
    public function it_can_find_siswa_by_id(): void
    {
        $siswa = Siswa::factory()->create();

        $found = $this->repository->findById($siswa->id);

        $this->assertNotNull($found);
        $this->assertEquals($siswa->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_siswa_not_found_by_id(): void
    {
        $found = $this->repository->findById(999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_find_siswa_by_nis(): void
    {
        $siswa = Siswa::factory()->create(['nis' => '1234567890']);

        $found = $this->repository->findByNis('1234567890');

        $this->assertNotNull($found);
        $this->assertEquals($siswa->id, $found->id);
    }

    /** @test */
    public function it_can_create_siswa(): void
    {
        $kelas = Kelas::factory()->create();
        $data = [
            'nis' => '9876543210',
            'nama' => 'Test Siswa',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
            'status' => 'active',
            'is_active' => true,
            'qr_secret_salt' => str_repeat('a', 64),
        ];

        $siswa = $this->repository->create($data);

        $this->assertInstanceOf(Siswa::class, $siswa);
        $this->assertEquals('Test Siswa', $siswa->nama);
        $this->assertDatabaseHas('siswa', ['nis' => '9876543210']);
    }

    /** @test */
    public function it_can_update_siswa(): void
    {
        $siswa = Siswa::factory()->create(['nama' => 'Old Name']);

        $updated = $this->repository->update($siswa->id, ['nama' => 'New Name']);

        $this->assertEquals('New Name', $updated->nama);
        $this->assertDatabaseHas('siswa', ['id' => $siswa->id, 'nama' => 'New Name']);
    }

    /** @test */
    public function it_throws_exception_when_updating_non_existent_siswa(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->update(999, ['nama' => 'Test']);
    }

    /** @test */
    public function it_can_delete_siswa(): void
    {
        $siswa = Siswa::factory()->create();

        $result = $this->repository->delete($siswa->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('siswa', ['id' => $siswa->id]);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_siswa(): void
    {
        $result = $this->repository->delete(999);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_active_siswa(): void
    {
        Siswa::factory()->count(3)->create(['status' => 'active', 'is_active' => true]);
        Siswa::factory()->count(2)->create(['status' => 'inactive', 'is_active' => false]);

        $active = $this->repository->getActive();

        $this->assertCount(3, $active);
    }

    /** @test */
    public function it_can_get_siswa_by_kelas(): void
    {
        $kelas1 = Kelas::factory()->create();
        $kelas2 = Kelas::factory()->create();

        Siswa::factory()->count(3)->create(['kelas_id' => $kelas1->id]);
        Siswa::factory()->count(2)->create(['kelas_id' => $kelas2->id]);

        $result = $this->repository->getByKelas($kelas1->id);

        $this->assertCount(3, $result);
    }

    /** @test */
    public function it_can_paginate_siswa_with_filters(): void
    {
        $kelas = Kelas::factory()->create();
        Siswa::factory()->count(5)->create(['kelas_id' => $kelas->id]);
        Siswa::factory()->count(3)->create();

        $result = $this->repository->paginate(['kelas_id' => $kelas->id], 10);

        $this->assertEquals(5, $result->total());
    }

    /** @test */
    public function it_filters_students_by_group_and_includes_unknown_values_as_unassigned(): void
    {
        $north = Siswa::factory()->create(['kelompok' => Siswa::KELOMPOK_PANUNGGANGAN_UTARA]);
        $unknown = Siswa::factory()->create();
        DB::table('siswa')->where('id', $unknown->id)->update(['kelompok' => 'Kelompok Lama', 'alamat' => 'Kelompok Lama']);

        $northResult = $this->repository->paginate(['kelompok' => Siswa::KELOMPOK_PANUNGGANGAN_UTARA], 20);
        $unassignedResult = $this->repository->paginate(['kelompok' => '__unassigned__'], 20);

        $this->assertSame([$north->id], $northResult->getCollection()->pluck('id')->all());
        $this->assertContains($unknown->id, $unassignedResult->getCollection()->pluck('id')->all());
        $this->assertNotContains($north->id, $unassignedResult->getCollection()->pluck('id')->all());
    }

    /** @test */
    public function it_can_count_siswa_by_kelas(): void
    {
        $kelas = Kelas::factory()->create();
        Siswa::factory()->count(4)->create(['kelas_id' => $kelas->id, 'is_active' => true]);
        Siswa::factory()->count(2)->create(['kelas_id' => $kelas->id, 'is_active' => false]);

        $count = $this->repository->countByKelas($kelas->id);

        $this->assertEquals(4, $count);
    }
}
