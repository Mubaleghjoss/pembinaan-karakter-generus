<?php

namespace Tests\Feature;

use App\Models\PointTransaction;
use App\Models\Siswa;
use App\Models\SiswaPoint;
use App\Services\MobileFcmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GamificationMobileNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_positive_point_transaction_notifies_the_student(): void
    {
        $siswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        SiswaPoint::create(['siswa_id' => $siswa->id, 'total_points' => 0, 'level' => 1]);

        $mobileFcm = Mockery::mock(MobileFcmNotificationService::class);
        $mobileFcm->shouldReceive('sendToOwner')
            ->once()
            ->withArgs(function ($owner, string $type, string $route, string $title, string $body, $entityId, string $notificationId): bool {
                return $owner instanceof Siswa
                    && $type === 'gamification'
                    && $route === '/poin'
                    && $title === 'Poin bertambah'
                    && str_contains($body, '+10')
                    && (int) $entityId > 0
                    && str_starts_with($notificationId, 'gamification-');
            })
            ->andReturn(1);
        $this->app->instance(MobileFcmNotificationService::class, $mobileFcm);

        PointTransaction::create([
            'siswa_id' => $siswa->id,
            'type' => 'earned',
            'source' => 'attendance',
            'points' => 10,
            'description' => 'Menang permainan',
        ]);
    }

    /**
     * @dataProvider inactiveStudentProvider
     */
    public function test_positive_point_transaction_does_not_notify_inactive_students(array $attributes): void
    {
        $siswa = Siswa::factory()->create($attributes);
        $mobileFcm = Mockery::mock(MobileFcmNotificationService::class);
        $mobileFcm->shouldNotReceive('sendToOwner');
        $this->app->instance(MobileFcmNotificationService::class, $mobileFcm);

        $transaction = PointTransaction::create([
            'siswa_id' => $siswa->id,
            'type' => 'earned',
            'source' => 'attendance',
            'points' => 10,
            'description' => 'Poin untuk siswa tidak aktif',
        ]);

        $this->assertDatabaseHas('point_transactions', ['id' => $transaction->id, 'points' => 10]);
        $mobileFcm->shouldNotHaveReceived('sendToOwner');
    }

    public static function inactiveStudentProvider(): array
    {
        return [
            'inactive status' => [['status' => 'inactive', 'is_active' => true]],
            'inactive flag' => [['status' => 'active', 'is_active' => false]],
        ];
    }

    public function test_negative_point_transaction_does_not_notify(): void
    {
        $siswa = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        $mobileFcm = Mockery::mock(MobileFcmNotificationService::class);
        $mobileFcm->shouldNotReceive('sendToOwner');
        $this->app->instance(MobileFcmNotificationService::class, $mobileFcm);

        PointTransaction::create([
            'siswa_id' => $siswa->id,
            'type' => 'spent',
            'source' => 'manual',
            'points' => -10,
            'description' => 'Reset periode',
        ]);
    }
}
