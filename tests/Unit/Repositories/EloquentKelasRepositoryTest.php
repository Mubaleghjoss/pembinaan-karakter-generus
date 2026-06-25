<?php

namespace Tests\Unit\Repositories;

use App\Models\Kelas;
use App\Repositories\EloquentKelasRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentKelasRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentKelasRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentKelasRepository;
    }

    /** @test */
    public function it_can_find_kelas_by_id(): void
    {
        $kelas = Kelas::factory()->create();

        $found = $this->repository->findById($kelas->id);

        $this->assertNotNull($found);
        $this->assertEquals($kelas->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_kelas_not_found_by_id(): void
    {
        $found = $this->repository->findById(999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_find_kelas_by_kode(): void
    {
        $kelas = Kelas::factory()->create(['kode_kelas' => 'KLS-TEST']);

        $found = $this->repository->findByKode('KLS-TEST');

        $this->assertNotNull($found);
        $this->assertEquals($kelas->id, $found->id);
    }

    /** @test */
    public function it_can_create_kelas(): void
    {
        $data = [
            'nama' => 'Kelas A',
            'tingkat' => '1',
            'kode_kelas' => 'KLS-001',
            'kapasitas' => 30,
            'is_active' => true,
        ];

        $kelas = $this->repository->create($data);

        $this->assertInstanceOf(Kelas::class, $kelas);
        $this->assertEquals('Kelas A', $kelas->nama);
        $this->assertDatabaseHas('kelas', ['kode_kelas' => 'KLS-001']);
    }

    /** @test */
    public function it_can_update_kelas(): void
    {
        $kelas = Kelas::factory()->create(['nama' => 'Old Name']);

        $updated = $this->repository->update($kelas->id, ['nama' => 'New Name']);

        $this->assertEquals('New Name', $updated->nama);
        $this->assertDatabaseHas('kelas', ['id' => $kelas->id, 'nama' => 'New Name']);
    }

    /** @test */
    public function it_throws_exception_when_updating_non_existent_kelas(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->update(999, ['nama' => 'Test']);
    }

    /** @test */
    public function it_can_delete_kelas(): void
    {
        $kelas = Kelas::factory()->create();

        $result = $this->repository->delete($kelas->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('kelas', ['id' => $kelas->id]);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_kelas(): void
    {
        $result = $this->repository->delete(999);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_active_kelas(): void
    {
        Kelas::factory()->count(3)->create(['is_active' => true]);
        Kelas::factory()->count(2)->create(['is_active' => false]);

        $active = $this->repository->getActive();

        $this->assertCount(3, $active);
    }

    /** @test */
    public function it_can_get_kelas_by_tingkat(): void
    {
        Kelas::factory()->count(2)->create(['tingkat' => '1']);
        Kelas::factory()->count(3)->create(['tingkat' => '2']);

        $result = $this->repository->getByTingkat('1');

        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_can_paginate_kelas_with_filters(): void
    {
        Kelas::factory()->count(5)->create(['tingkat' => '1']);
        Kelas::factory()->count(3)->create(['tingkat' => '2']);

        $result = $this->repository->paginate(['tingkat' => '1'], 10);

        $this->assertEquals(5, $result->total());
    }
}
