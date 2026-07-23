<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RpgAdminModalLayerTest extends TestCase
{
    public function test_desktop_map_modals_render_above_the_admin_topbar(): void
    {
        $root = dirname(__DIR__, 2);
        $rpgView = file_get_contents($root.'/resources/views/admin/rpg/index.blade.php');
        $adminLayout = file_get_contents($root.'/resources/views/layouts/app.blade.php');

        $this->assertStringContainsString('pkg-topbar relative z-[80]', $adminLayout);
        $this->assertGreaterThanOrEqual(3, substr_count($rpgView, 'z-[110]'));
        $this->assertStringNotContainsString('showMapModal" x-cloak class="fixed inset-0 z-50', $rpgView);
        $this->assertStringNotContainsString('showNpcModal" x-cloak class="fixed inset-0 z-50', $rpgView);
    }
}
