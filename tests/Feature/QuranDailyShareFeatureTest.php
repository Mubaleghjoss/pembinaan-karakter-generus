<?php

namespace Tests\Feature;

use App\Models\QuranReadingEntry;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuranDailyShareFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_summary_includes_pending_verified_missing_and_unassigned_students(): void
    {
        $admin = $this->admin();
        $pending = Siswa::factory()->create(['nama' => 'Generus Pending', 'kelompok' => Siswa::KELOMPOK_PAKULONAN]);
        $missing = Siswa::factory()->create(['nama' => 'Generus Belum']);
        DB::table('siswa')->where('id', $missing->id)->update(['kelompok' => null, 'alamat' => null]);

        QuranReadingEntry::create([
            'siswa_id' => $pending->id,
            'reading_date' => now()->toDateString(),
            'page_start' => 2,
            'page_end' => 3,
            'surah_start' => 2,
            'ayah_start' => 1,
            'surah_end' => 2,
            'ayah_end' => 5,
            'source' => 'manual',
            'submitted_by_type' => 'siswa',
            'submitted_by_id' => $pending->id,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->getJson(route('quran.share-summary', [
            'reading_date' => now()->toDateString(),
        ]))->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['student_count' => 2])
            ->assertSee('Generus Pending')
            ->assertSee('Menunggu verifikasi')
            ->assertSee('Generus Belum')
            ->assertSee('Belum mengisi')
            ->assertSee('Belum Ada Data Kelompok');
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => User::ROLE_ADMIN], [
            'display_name' => 'Administrator',
            'permissions' => ['*'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
