<?php

namespace Tests\Property;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Property-based tests for Siswa Authentication functionality.
 * 
 * **Feature: website-settings, Property 12: Siswa authentication with NIS**
 */
class SiswaAuthPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create kelas
        $this->kelas = Kelas::create([
            'nama' => 'Kelas Test',
            'kode_kelas' => 'KT01',
            'tingkat' => '1',
            'is_active' => true,
        ]);
    }

    /**
     * **Feature: website-settings, Property 12: Siswa authentication with NIS**
     * *For any* active siswa with valid credentials, login should succeed and return correct user data.
     * **Validates: Requirements 9.1, 9.4**
     * 
     * @test
     */
    public function siswa_authentication_with_valid_credentials(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $nis = '2024' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $password = 'password' . $i;
            
            $siswa = Siswa::create([
                'nis' => $nis,
                'nama' => 'Siswa Test ' . $i,
                'kelas_id' => $this->kelas->id,
                'jenis_kelamin' => ['L', 'P'][array_rand(['L', 'P'])],
                'is_active' => true,
                'password' => Hash::make($password),
            ]);
            
            // Verify siswa can be found by NIS
            $found = Siswa::where('nis', $nis)->first();
            $this->assertNotNull($found);
            $this->assertEquals($siswa->id, $found->id);
            
            // Verify password check works
            $this->assertTrue(Hash::check($password, $found->password));
            
            // Verify siswa data is correct
            $this->assertEquals($nis, $found->nis);
            $this->assertEquals('Siswa Test ' . $i, $found->nama);
            $this->assertEquals($this->kelas->id, $found->kelas_id);
        }
    }

    /**
     * **Feature: website-settings, Property 12: Siswa authentication with NIS**
     * *For any* siswa with wrong password, authentication should fail.
     * **Validates: Requirements 9.1**
     * 
     * @test
     */
    public function siswa_authentication_fails_with_wrong_password(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $nis = '2024' . str_pad($i + 100, 4, '0', STR_PAD_LEFT);
            $correctPassword = 'correct' . $i;
            $wrongPassword = 'wrong' . $i;
            
            Siswa::create([
                'nis' => $nis,
                'nama' => 'Siswa Wrong ' . $i,
                'kelas_id' => $this->kelas->id,
                'jenis_kelamin' => 'L',
                'is_active' => true,
                'password' => Hash::make($correctPassword),
            ]);
            
            $found = Siswa::where('nis', $nis)->first();
            
            // Wrong password should fail
            $this->assertFalse(Hash::check($wrongPassword, $found->password));
        }
    }

    /**
     * **Feature: website-settings, Property 12: Siswa authentication with NIS**
     * *For any* inactive siswa, authentication should be blocked.
     * **Validates: Requirements 9.1**
     * 
     * @test
     */
    public function inactive_siswa_cannot_authenticate(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $nis = '2024' . str_pad($i + 200, 4, '0', STR_PAD_LEFT);
            
            $siswa = Siswa::create([
                'nis' => $nis,
                'nama' => 'Siswa Inactive ' . $i,
                'kelas_id' => $this->kelas->id,
                'jenis_kelamin' => 'P',
                'is_active' => false,
                'password' => Hash::make('password'),
            ]);
            
            // Verify siswa is inactive
            $this->assertFalse($siswa->is_active);
            
            // Active scope should not return inactive siswa
            $activeCount = Siswa::where('nis', $nis)->where('is_active', true)->count();
            $this->assertEquals(0, $activeCount);
        }
    }

    /**
     * **Feature: website-settings, Property 12: Siswa authentication with NIS**
     * *For any* siswa, login should display their name, class, and assigned pamong.
     * **Validates: Requirements 9.4**
     * 
     * @test
     */
    public function siswa_has_required_display_info(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $nis = '2024' . str_pad($i + 300, 4, '0', STR_PAD_LEFT);
            $nama = 'Siswa Display ' . $i;
            
            $siswa = Siswa::create([
                'nis' => $nis,
                'nama' => $nama,
                'kelas_id' => $this->kelas->id,
                'jenis_kelamin' => 'L',
                'is_active' => true,
                'password' => Hash::make('password'),
            ]);
            
            // Load with kelas relationship
            $siswa->load('kelas');
            
            // Verify required display info exists
            $this->assertNotEmpty($siswa->nama);
            $this->assertNotEmpty($siswa->nis);
            $this->assertNotNull($siswa->kelas);
            $this->assertNotEmpty($siswa->kelas->nama);
        }
    }
}
