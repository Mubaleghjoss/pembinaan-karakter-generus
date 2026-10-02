<?php

namespace Tests\Feature;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Services\MobileFcmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PresensiMobileNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_student_attendance_sends_one_typed_notification(): void
    {
        config(['fcm.enabled' => true]);
        $siswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        $mobileFcm = Mockery::mock(MobileFcmNotificationService::class);
        $mobileFcm->shouldReceive('sendToOwner')
            ->once()
            ->withArgs(function ($owner, string $type, string $route, string $title, string $body, $entityId, string $notificationId): bool {
                return $owner instanceof Siswa
                    && $type === 'attendance'
                    && $route === '/presensi'
                    && $title === 'Presensi tercatat'
                    && str_contains($body, 'hadir')
                    && (int) $entityId > 0
                    && str_starts_with($notificationId, 'attendance-');
            })
            ->andReturn(1);
        $this->app->instance(MobileFcmNotificationService::class, $mobileFcm);

        $presensi = Presensi::factory()->create([
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
            'jam_keluar' => null,
        ]);

        $this->assertSame($siswa->id, $presensi->siswa_id);
    }

    public function test_attendance_checkout_update_does_not_send_another_notification(): void
    {
        config(['fcm.enabled' => true]);
        $siswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        $presensi = Presensi::withoutEvents(fn () => Presensi::factory()->create([
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
            'jam_keluar' => null,
        ]));

        $mobileFcm = Mockery::mock(MobileFcmNotificationService::class);
        $mobileFcm->shouldNotReceive('sendToOwner');
        $this->app->instance(MobileFcmNotificationService::class, $mobileFcm);

        $presensi->update(['jam_keluar' => now()]);

        $this->assertNotNull($presensi->fresh()->jam_keluar);
    }
}
