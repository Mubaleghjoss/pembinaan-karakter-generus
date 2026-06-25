<?php

namespace Tests\Unit;

use App\Http\Controllers\DataPullController;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class DataPullControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_preserved_sync_settings_use_database_timestamp_format(): void
    {
        Setting::set('sync_last_pull', '2026-04-23T09:17:12+07:00');

        DB::table('settings')
            ->where('key', 'sync_last_pull')
            ->update([
                'created_at' => '2026-04-23 09:17:12',
                'updated_at' => '2026-04-23 09:17:12',
            ]);

        $controller = new DataPullController();
        $method = new ReflectionMethod($controller, 'getPreservedLocalSettings');
        $method->setAccessible(true);

        $rows = $method->invoke($controller);

        $this->assertArrayHasKey('sync_last_pull', $rows);
        $this->assertSame('2026-04-23 09:17:12', $rows['sync_last_pull']['created_at']);
        $this->assertSame('2026-04-23 09:17:12', $rows['sync_last_pull']['updated_at']);
        $this->assertSame('string', $rows['sync_last_pull']['type']);
        $this->assertStringNotContainsString('T', $rows['sync_last_pull']['created_at']);
        $this->assertStringNotContainsString('Z', $rows['sync_last_pull']['updated_at']);
    }
}
