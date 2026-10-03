<?php

namespace Tests\Unit;

use App\Models\MobileDeviceToken;
use App\Models\Siswa;
use App\Models\User;
use App\Services\FcmService;
use App\Services\MobileFcmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MobileFcmNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_broadcast_filters_by_target_audience(): void
    {
        config(['fcm.enabled' => true]);
        $student = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        $user = User::factory()->create(['status' => 'active', 'role_id' => 2]);
        foreach ([$student, $user] as $owner) {
            MobileDeviceToken::create([
                'owner_type' => $owner::class,
                'owner_id' => $owner->id,
                'token_hash' => hash('sha256', 'audience-'.$owner->id.'-'.$owner::class),
                'token' => 'audience-'.$owner->id,
                'platform' => 'android',
            ]);
        }
        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldReceive('send')->once()->with('audience-'.$student->id, Mockery::type('string'), Mockery::type('string'), Mockery::type('array'))->andReturnTrue();
        $service = new MobileFcmNotificationService($fcm);

        $this->assertSame(1, $service->sendToActiveMobileUsers(
            'calendar', '/kalender', 'Agenda', 'Isi', 50, 'calendar-50-1', [], 'siswa'
        ));
    }

    public function test_calendar_broadcast_targets_only_active_mobile_owners(): void
    {
        config(['fcm.enabled' => true]);

        $activeStudent = Siswa::factory()->create(['status' => 'active', 'is_active' => true]);
        $inactiveStudent = Siswa::factory()->create(['status' => 'inactive', 'is_active' => true]);
        $activeUser = User::factory()->create(['status' => 'active']);
        $inactiveUser = User::factory()->create(['status' => 'inactive']);

        foreach ([$activeStudent, $inactiveStudent, $activeUser, $inactiveUser] as $owner) {
            MobileDeviceToken::create([
                'owner_type' => $owner::class,
                'owner_id' => $owner->id,
                'token_hash' => hash('sha256', 'token-'.$owner->id.'-'.$owner::class),
                'token' => 'token-'.$owner->id.'-'.$owner::class,
                'platform' => 'android',
            ]);
        }

        $sentTokens = [];
        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldReceive('send')->times(2)->andReturnUsing(
            function (string $token, string $title, string $body, array $data) use (&$sentTokens): bool {
                $sentTokens[] = $token;
                $this->assertSame('calendar', $data['type']);
                $this->assertSame('/kalender', $data['route']);
                $this->assertSame('42', $data['entity_id']);
                $this->assertSame('calendar-42-1', $data['notification_id']);
                return true;
            },
        );

        $service = new MobileFcmNotificationService($fcm);
        $sent = $service->sendToActiveMobileUsers(
            'calendar',
            '/kalender',
            'Agenda baru',
            'Kajian besok pukul 08.00.',
            42,
            'calendar-42-1',
        );

        $this->assertSame(2, $sent);
        $this->assertCount(2, $sentTokens);
        $this->assertContains('token-'.$activeStudent->id.'-'.$activeStudent::class, $sentTokens);
        $this->assertContains('token-'.$activeUser->id.'-'.$activeUser::class, $sentTokens);
    }
}
