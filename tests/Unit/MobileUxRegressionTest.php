<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MobileUxRegressionTest extends TestCase
{
    public function test_portal_layouts_protect_the_mobile_viewport_and_pause_page_scrolling_for_sidebar(): void
    {
        foreach (['app', 'siswa', 'ortu'] as $layout) {
            $source = file_get_contents(dirname(__DIR__, 2)."/resources/views/layouts/{$layout}.blade.php");

            $this->assertStringContainsString('overflow-x-hidden', $source);
            $this->assertStringContainsString("classList.toggle('overflow-hidden'", $source);
            $this->assertStringContainsString('transition-transform ease-out duration-200', $source);
        }
    }

    public function test_chat_views_use_mobile_master_detail_and_visibility_aware_polling(): void
    {
        $views = [
            'resources/views/siswa/chat/index.blade.php',
            'resources/views/ortu/chat/index.blade.php',
            'resources/views/pamong/chat/index.blade.php',
        ];

        foreach ($views as $view) {
            $source = file_get_contents(dirname(__DIR__, 2).'/'.$view);

            $this->assertStringContainsString('visibilitychange', $source);
            $this->assertStringContainsString('pagehide', $source);
            $this->assertStringContainsString('document.hidden', $source);
        }

        $this->assertStringContainsString('closeConversation()', file_get_contents(dirname(__DIR__, 2).'/resources/views/siswa/chat/index.blade.php'));
        $this->assertStringContainsString('closeConversation()', file_get_contents(dirname(__DIR__, 2).'/resources/views/ortu/chat/index.blade.php'));
        $this->assertStringContainsString('closePribadiConversation()', file_get_contents(dirname(__DIR__, 2).'/resources/views/pamong/chat/partials/pribadi.blade.php'));
        $this->assertStringContainsString('closeGrupConversation()', file_get_contents(dirname(__DIR__, 2).'/resources/views/pamong/chat/partials/grup.blade.php'));
    }

    public function test_lazy_tab_panels_and_mobile_tables_are_enabled(): void
    {
        $tabPanel = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/tab-panel.blade.php');
        $verification = file_get_contents(dirname(__DIR__, 2).'/resources/views/tugas-pkg/verification/index.blade.php');
        $materi = file_get_contents(dirname(__DIR__, 2).'/resources/views/siswa/materi/index.blade.php');

        $this->assertStringContainsString('@if($lazy)', $tabPanel);
        $this->assertStringContainsString('<template x-if=', $tabPanel);
        $this->assertStringContainsString('pkg-mobile-table', $verification);
        $this->assertStringContainsString('pkg-mobile-table', $materi);
        $this->assertStringContainsString('data-label="Target"', $materi);
    }

    public function test_attendance_schedule_cards_stay_inside_the_mobile_viewport(): void
    {
        $index = file_get_contents(dirname(__DIR__, 2).'/resources/views/attendance-schedule/index.blade.php');
        $form = file_get_contents(dirname(__DIR__, 2).'/resources/views/attendance-schedule/form.blade.php');

        $this->assertStringContainsString('w-full min-w-0 max-w-7xl', $index);
        $this->assertStringContainsString('min-w-0 max-w-full overflow-hidden', $index);
        $this->assertStringContainsString('grid w-full grid-cols-1', $index);
        $this->assertStringContainsString('w-full min-w-0 max-w-4xl', $form);
        $this->assertStringContainsString('pkg-panel-lg min-w-0 p-4', $form);
    }
}
