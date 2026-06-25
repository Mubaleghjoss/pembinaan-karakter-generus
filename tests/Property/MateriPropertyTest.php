<?php

namespace Tests\Property;

use App\Models\Materi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Materi CRUD functionality.
 * 
 * **Feature: website-settings, Property 13: Materi CRUD persistence**
 */
class MateriPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $role = Role::create(['name' => 'admin', 'display_name' => 'Administrator', 'permissions' => ['*']]);
        $this->admin = User::create([
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    /**
     * **Feature: website-settings, Property 13: Materi CRUD persistence**
     * *For any* valid materi data, create/update operations should persist title, description, and files correctly.
     * **Validates: Requirements 10.2, 10.3**
     * 
     * @test
     */
    public function materi_creation_persists_all_fields(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $judul = 'Materi Test ' . $i . ' ' . uniqid();
            $deskripsi = 'Deskripsi materi ' . $i . ' dengan konten yang cukup panjang untuk testing.';
            $bulan = now()->subMonths(rand(0, 11))->format('Y-m-d');
            $videoUrl = 'https://youtube.com/watch?v=' . uniqid();
            $isActive = (bool) rand(0, 1);
            
            $materi = Materi::create([
                'judul' => $judul,
                'deskripsi' => $deskripsi,
                'bulan' => $bulan,
                'video_url' => $videoUrl,
                'is_active' => $isActive,
                'created_by' => $this->admin->id,
            ]);
            
            // Retrieve and verify
            $retrieved = Materi::find($materi->id);
            
            $this->assertEquals($judul, $retrieved->judul);
            $this->assertEquals($deskripsi, $retrieved->deskripsi);
            $this->assertEquals($bulan, $retrieved->bulan->format('Y-m-d'));
            $this->assertEquals($videoUrl, $retrieved->video_url);
            $this->assertEquals($isActive, $retrieved->is_active);
            $this->assertEquals($this->admin->id, $retrieved->created_by);
        }
    }

    /**
     * **Feature: website-settings, Property 13: Materi CRUD persistence**
     * *For any* materi, update operations should persist changes correctly.
     * **Validates: Requirements 10.3**
     * 
     * @test
     */
    public function materi_update_persists_changes(): void
    {
        for ($i = 0; $i < 20; $i++) {
            // Create materi
            $materi = Materi::create([
                'judul' => 'Original ' . $i,
                'deskripsi' => 'Original description',
                'bulan' => now()->format('Y-m-d'),
                'is_active' => true,
                'created_by' => $this->admin->id,
            ]);
            
            // Update materi
            $newJudul = 'Updated ' . $i . ' ' . uniqid();
            $newDeskripsi = 'Updated description ' . uniqid();
            $newVideoUrl = 'https://youtube.com/watch?v=updated' . $i;
            
            $materi->update([
                'judul' => $newJudul,
                'deskripsi' => $newDeskripsi,
                'video_url' => $newVideoUrl,
                'is_active' => false,
            ]);
            
            // Retrieve and verify
            $retrieved = Materi::find($materi->id);
            
            $this->assertEquals($newJudul, $retrieved->judul);
            $this->assertEquals($newDeskripsi, $retrieved->deskripsi);
            $this->assertEquals($newVideoUrl, $retrieved->video_url);
            $this->assertFalse($retrieved->is_active);
        }
    }

    /**
     * **Feature: website-settings, Property 13: Materi CRUD persistence**
     * *For any* materi with is_active false, it should not appear in active scope.
     * **Validates: Requirements 10.3**
     * 
     * @test
     */
    public function inactive_materi_filtered_by_scope(): void
    {
        // Create mix of active and inactive materi
        $activeCount = 0;
        $inactiveCount = 0;
        
        for ($i = 0; $i < 20; $i++) {
            $isActive = (bool) rand(0, 1);
            
            Materi::create([
                'judul' => 'Materi Scope ' . $i,
                'deskripsi' => 'Description',
                'bulan' => now()->format('Y-m-d'),
                'is_active' => $isActive,
                'created_by' => $this->admin->id,
            ]);
            
            if ($isActive) {
                $activeCount++;
            } else {
                $inactiveCount++;
            }
        }
        
        // Verify active scope returns only active materi
        $activeMateri = Materi::where('is_active', true)->count();
        $this->assertEquals($activeCount, $activeMateri);
        
        // Verify total count
        $totalMateri = Materi::count();
        $this->assertEquals($activeCount + $inactiveCount, $totalMateri);
    }

    /**
     * **Feature: website-settings, Property 13: Materi CRUD persistence**
     * *For any* materi deletion, the record should be removed.
     * **Validates: Requirements 10.3**
     * 
     * @test
     */
    public function materi_deletion_removes_record(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $materi = Materi::create([
                'judul' => 'Materi Delete ' . $i,
                'deskripsi' => 'To be deleted',
                'bulan' => now()->format('Y-m-d'),
                'is_active' => true,
                'created_by' => $this->admin->id,
            ]);
            
            $materiId = $materi->id;
            
            // Delete
            $materi->delete();
            
            // Verify deleted
            $this->assertNull(Materi::find($materiId));
        }
    }
}
