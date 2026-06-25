<?php

namespace Tests\Property;

use App\Models\Karakter;
use App\Models\TracerKarakter;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for Karakter Management functionality.
 */
class KarakterPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'admin', 'display_name' => 'Administrator', 'permissions' => ['*']]);
    }

    /**
     * **Feature: website-settings, Property 8: Karakter CRUD persistence**
     * *For any* karakter data, creating and then retrieving should return the same data.
     * **Validates: Requirements 5.2, 5.3**
     * 
     * @test
     */
    public function karakter_crud_persistence(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $nama = 'Karakter_' . $i . '_' . uniqid();
            $deskripsi = 'Deskripsi untuk ' . $nama;
            
            // Create
            $karakter = Karakter::create([
                'nama' => $nama,
                'deskripsi' => $deskripsi,
                'is_active' => true,
            ]);
            
            // Retrieve and verify
            $retrieved = Karakter::find($karakter->id);
            
            $this->assertNotNull($retrieved);
            $this->assertEquals($nama, $retrieved->nama);
            $this->assertEquals($deskripsi, $retrieved->deskripsi);
            $this->assertTrue($retrieved->is_active);
            
            // Update
            $newNama = 'Updated_' . $nama;
            $newDeskripsi = 'Updated deskripsi';
            
            $retrieved->update([
                'nama' => $newNama,
                'deskripsi' => $newDeskripsi,
            ]);
            
            // Verify update
            $updated = Karakter::find($karakter->id);
            $this->assertEquals($newNama, $updated->nama);
            $this->assertEquals($newDeskripsi, $updated->deskripsi);
        }
    }

    /**
     * **Feature: website-settings, Property 9: Karakter soft delete preserves history**
     * *For any* karakter with tracer records, deactivating should preserve the history.
     * **Validates: Requirements 5.4**
     * 
     * @test
     */
    public function karakter_soft_delete_preserves_history(): void
    {
        $kelas = Kelas::create([
            'nama' => 'Kelas Test',
            'kode_kelas' => 'KT_' . uniqid(),
            'is_active' => true,
        ]);
        
        $adminRole = Role::where('name', 'admin')->first();
        $user = User::create([
            'username' => 'admin_test',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        
        for ($i = 0; $i < 20; $i++) {
            // Create karakter
            $karakter = Karakter::create([
                'nama' => 'Karakter_' . $i . '_' . uniqid(),
                'deskripsi' => 'Test karakter',
                'is_active' => true,
            ]);
            
            // Create siswa
            $siswa = Siswa::create([
                'nis' => 'NIS_' . $i . '_' . uniqid(),
                'nama' => 'Siswa Test ' . $i,
                'kelas_id' => $kelas->id,
                'is_active' => true,
                'status' => 'active',
            ]);
            
            // Create tracer record
            $tracer = TracerKarakter::create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $karakter->id,
                'pamong_id' => $user->id,
                'checked_at' => now(),
                'catatan' => 'Test catatan',
            ]);
            
            // Soft delete (deactivate) karakter
            $karakter->update(['is_active' => false]);
            
            // Verify karakter is deactivated but still exists
            $deactivated = Karakter::find($karakter->id);
            $this->assertNotNull($deactivated);
            $this->assertFalse($deactivated->is_active);
            
            // Verify tracer record still exists and references the karakter
            $tracerRecord = TracerKarakter::find($tracer->id);
            $this->assertNotNull($tracerRecord);
            $this->assertEquals($karakter->id, $tracerRecord->karakter_id);
            
            // Verify relationship still works
            $this->assertNotNull($tracerRecord->karakter);
            $this->assertEquals($karakter->nama, $tracerRecord->karakter->nama);
        }
    }

    /**
     * **Feature: website-settings, Property 8: Karakter CRUD persistence**
     * Test that karakter toggle status works correctly.
     * **Validates: Requirements 5.3**
     * 
     * @test
     */
    public function karakter_toggle_status(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $initialStatus = $i % 2 === 0;
            
            $karakter = Karakter::create([
                'nama' => 'Toggle_' . $i . '_' . uniqid(),
                'deskripsi' => 'Test toggle',
                'is_active' => $initialStatus,
            ]);
            
            // Toggle status
            $karakter->update(['is_active' => !$karakter->is_active]);
            
            // Verify toggle
            $toggled = Karakter::find($karakter->id);
            $this->assertEquals(!$initialStatus, $toggled->is_active);
            
            // Toggle back
            $toggled->update(['is_active' => !$toggled->is_active]);
            
            // Verify back to original
            $final = Karakter::find($karakter->id);
            $this->assertEquals($initialStatus, $final->is_active);
        }
    }

    /**
     * **Feature: website-settings, Property 8: Karakter CRUD persistence**
     * Test that karakter can be filtered by status.
     * **Validates: Requirements 5.2**
     * 
     * @test
     */
    public function karakter_filter_by_status(): void
    {
        // Create active karakter
        for ($i = 0; $i < 10; $i++) {
            Karakter::create([
                'nama' => 'Active_' . $i . '_' . uniqid(),
                'deskripsi' => 'Active karakter',
                'is_active' => true,
            ]);
        }
        
        // Create inactive karakter
        for ($i = 0; $i < 5; $i++) {
            Karakter::create([
                'nama' => 'Inactive_' . $i . '_' . uniqid(),
                'deskripsi' => 'Inactive karakter',
                'is_active' => false,
            ]);
        }
        
        // Filter active
        $activeKarakter = Karakter::where('is_active', true)->get();
        $this->assertCount(10, $activeKarakter);
        
        // Filter inactive
        $inactiveKarakter = Karakter::where('is_active', false)->get();
        $this->assertCount(5, $inactiveKarakter);
        
        // Get all
        $allKarakter = Karakter::all();
        $this->assertCount(15, $allKarakter);
    }

    #[Test]
    public function expired_active_karakter_are_deactivated_automatically(): void
    {
        $expired = Karakter::create([
            'nama' => 'Expired_' . uniqid(),
            'deskripsi' => 'Expired task',
            'tanggal_selesai' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $endsToday = Karakter::create([
            'nama' => 'Today_' . uniqid(),
            'deskripsi' => 'Still available today',
            'tanggal_selesai' => now()->toDateString(),
            'is_active' => true,
        ]);

        $inactiveExpired = Karakter::create([
            'nama' => 'Inactive_' . uniqid(),
            'deskripsi' => 'Already inactive',
            'tanggal_selesai' => now()->subDays(2)->toDateString(),
            'is_active' => false,
        ]);

        $withoutEndDate = Karakter::create([
            'nama' => 'Open_' . uniqid(),
            'deskripsi' => 'No end date',
            'is_active' => true,
        ]);

        $updated = Karakter::deactivateExpiredTasks(now());

        $this->assertSame(1, $updated);
        $this->assertFalse($expired->fresh()->is_active);
        $this->assertTrue($endsToday->fresh()->is_active);
        $this->assertFalse($inactiveExpired->fresh()->is_active);
        $this->assertTrue($withoutEndDate->fresh()->is_active);
    }
}
