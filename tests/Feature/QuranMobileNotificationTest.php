<?php

namespace Tests\Feature;

use App\Models\PamongSiswa;
use App\Models\QuranReadingEntry;
use App\Models\Siswa;
use App\Models\User;
use App\Services\MobileFcmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class QuranMobileNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_quran_entry_notifies_active_assigned_pamong(): void
    {
        $siswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        $pamong = User::factory()->create(['status' => 'active']);
        PamongSiswa::create(['siswa_id' => $siswa->id, 'pamong_id' => $pamong->id]);

        $mobileFcm = Mockery::mock(MobileFcmNotificationService::class);
        $mobileFcm->shouldReceive('sendToOwner')
            ->once()
            ->withArgs(function ($owner, string $type, string $route, string $title, string $body, $entityId, string $notificationId): bool {
                return $owner instanceof User
                    && $type === 'quran'
                    && $route === '/quran'
                    && $title === 'Catatan Quran baru'
                    && str_contains($body, 'menunggu verifikasi')
                    && (int) $entityId > 0
                    && str_starts_with($notificationId, 'quran-');
            })
            ->andReturn(1);
        $this->app->instance(MobileFcmNotificationService::class, $mobileFcm);

        $entry = QuranReadingEntry::create([
            'siswa_id' => $siswa->id,
            'reading_date' => today(),
            'surah_start' => 1,
            'ayah_start' => 1,
            'surah_end' => 1,
            'ayah_end' => 7,
            'source' => 'mobile',
            'submitted_by_type' => 'siswa',
            'submitted_by_id' => $siswa->id,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]);

        $this->assertSame($siswa->id, $entry->siswa_id);
    }

    public function test_quran_entry_does_not_notify_inactive_or_ended_pamong(): void
    {
        $siswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        $inactive = User::factory()->create(['status' => 'inactive']);
        $ended = User::factory()->create(['status' => 'active']);
        PamongSiswa::create(['siswa_id' => $siswa->id, 'pamong_id' => $inactive->id]);
        PamongSiswa::create(['siswa_id' => $siswa->id, 'pamong_id' => $ended->id, 'ended_at' => now()->subDay()]);

        $mobileFcm = Mockery::mock(MobileFcmNotificationService::class);
        $mobileFcm->shouldNotReceive('sendToOwner');
        $this->app->instance(MobileFcmNotificationService::class, $mobileFcm);

        QuranReadingEntry::create([
            'siswa_id' => $siswa->id,
            'reading_date' => today(),
            'surah_start' => 1,
            'ayah_start' => 1,
            'surah_end' => 1,
            'ayah_end' => 7,
            'source' => 'mobile',
            'submitted_by_type' => 'siswa',
            'submitted_by_id' => $siswa->id,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]);

        $this->assertDatabaseCount('quran_reading_entries', 1);
    }
}
