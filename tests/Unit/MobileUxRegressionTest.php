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

        $adminLayout = file_get_contents(dirname(__DIR__, 2).'/resources/views/layouts/app.blade.php');
        $this->assertStringContainsString('$tugasPkgGroupExpanded = $tugasPkgGroupActive', $adminLayout);
        $this->assertStringContainsString("! request()->routeIs('tugas-pkg.verification')", $adminLayout);
        $this->assertStringContainsString("x-data=\"{ open: {{ \$tugasPkgGroupExpanded ? 'true' : 'false' }} }\"", $adminLayout);
        $this->assertStringNotContainsString('|| $pendingLaporanPenyaksianCountForSidebar > 0;', $adminLayout);
    }

    public function test_siswa_and_ortu_share_mobile_app_navigation_without_removing_desktop_sidebar(): void
    {
        $root = dirname(__DIR__, 2);
        $navigation = file_get_contents($root.'/resources/views/layouts/partials/portal-mobile-navigation.blade.php');
        $styles = file_get_contents($root.'/resources/css/app.css');

        $this->assertStringContainsString('grid h-16 {{ $bottomColumnClass }}', $navigation);
        $this->assertStringContainsString('mobileMenuOpen = true', $navigation);
        $this->assertStringContainsString('pkg-portal-mobile-sheet', $navigation);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $styles);
        $this->assertStringContainsString('.pkg-portal-mobile-chat', $styles);
        $this->assertStringContainsString('Favorit Admin', $navigation);

        $expectedRoutes = [
            'siswa' => [
                'siswa.dashboard',
                'siswa.calendar.index',
                'siswa.tugas-pkg.index',
                'siswa.materi.index',
                'siswa.chat.index',
                'siswa.materi-rpp-journals.index',
                'siswa.kehadiran.index',
                'siswa.gamification.dashboard',
                'siswa.rpg.index',
                'siswa.profile',
                'siswa.kartu',
                'siswa.kartu.print',
                'siswa.biometrik',
            ],
            'ortu' => [
                'ortu.dashboard',
                'ortu.jadwal',
                'ortu.tugas',
                'ortu.materi.index',
                'ortu.chat',
                'ortu.kehadiran',
                'ortu.settings',
                'ortu.biometrik',
            ],
        ];

        foreach ($expectedRoutes as $portal => $routes) {
            $layout = file_get_contents($root."/resources/views/layouts/{$portal}.blade.php");

            $this->assertStringContainsString('viewport-fit=cover', $layout);
            $this->assertStringContainsString('mobileMenuOpen: false', $layout);
            $this->assertStringContainsString("@include('layouts.partials.portal-mobile-navigation')", $layout);
            $this->assertStringContainsString('hidden w-64 flex-col', $layout);
            $this->assertStringContainsString('lg:flex', $layout);
            $this->assertStringContainsString('pkg-portal-mobile-main', $layout);

            foreach ($routes as $route) {
                $this->assertStringContainsString("route('{$route}')", $layout);
            }
        }
    }

    public function test_admin_and_pamong_use_permission_aware_mobile_navigation_without_changing_desktop_sidebar(): void
    {
        $root = dirname(__DIR__, 2);
        $layout = file_get_contents($root.'/resources/views/layouts/app.blade.php');
        $navigation = file_get_contents($root.'/app/Support/OperationalMobileNavigation.php');

        $this->assertStringContainsString('mobileMenuOpen: false', $layout);
        $this->assertStringContainsString("@include('layouts.partials.portal-mobile-navigation')", $layout);
        $this->assertStringContainsString('hidden w-64 flex-col', $layout);
        $this->assertStringContainsString('lg:relative lg:flex', $layout);
        $this->assertStringContainsString('pkg-portal-mobile-main', $layout);
        $this->assertStringContainsString('Portal Admin', $navigation);
        $this->assertStringContainsString('Portal Pamong', $navigation);
        $this->assertStringContainsString('pamongBottomItems', $navigation);
        $this->assertStringContainsString('MAX_FAVORITES = 6', $navigation);
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
        $this->assertStringContainsString('pkg-portal-mobile-chat', file_get_contents(dirname(__DIR__, 2).'/resources/views/siswa/chat/index.blade.php'));
        $this->assertStringContainsString('pkg-portal-mobile-chat', file_get_contents(dirname(__DIR__, 2).'/resources/views/ortu/chat/index.blade.php'));
        $this->assertStringContainsString('pkg-portal-mobile-chat', file_get_contents(dirname(__DIR__, 2).'/resources/views/siswa/group-chat/index.blade.php'));
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

    public function test_login_pages_offer_clean_direct_role_switching(): void
    {
        $root = dirname(__DIR__, 2);
        $layout = file_get_contents($root.'/resources/views/layouts/auth.blade.php');
        $switcher = file_get_contents($root.'/resources/views/auth/partials/role-switcher.blade.php');

        $this->assertStringContainsString('pkg-auth-appbar', $layout);
        $this->assertStringContainsString('data-auth-theme-toggle', $layout);
        $this->assertStringContainsString("route('public.index')", $layout);

        foreach (['siswa.login', 'ortu.login', 'login'] as $route) {
            $this->assertStringContainsString("'route' => '{$route}'", $switcher);
        }

        foreach (['login', 'siswa-login', 'ortu-login'] as $view) {
            $source = file_get_contents($root."/resources/views/auth/{$view}.blade.php");
            $this->assertStringContainsString("@include('auth.partials.role-switcher'", $source);
            $this->assertStringContainsString("@section('auth_public_navigation', 'false')", $source);
        }
    }

    public function test_pkg_verification_navigation_stays_on_one_mobile_row(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/resources/views/tugas-pkg/verification/index.blade.php');
        $navigation = file_get_contents(dirname(__DIR__, 2).'/resources/views/tugas-pkg/verification/partials/navigation.blade.php');
        $verificationTab = file_get_contents(dirname(__DIR__, 2).'/resources/views/tugas-pkg/verification/partials/verification-tab.blade.php');

        $headerPosition = strpos($source, '<div class="pkg-page-header">');
        $navigationPosition = strpos($source, "@include('tugas-pkg.verification.partials.navigation')");
        $analyticsPosition = strpos($source, 'Analitik Keaktifan Pamong');

        $this->assertStringContainsString('sticky top-0 z-30', $navigation);
        $this->assertStringContainsString('flex min-w-max flex-nowrap gap-3', $navigation);
        $this->assertStringContainsString('role="tablist"', $navigation);
        $this->assertStringContainsString("'pkg-tab-link pkg-tab-link-active'", $navigation);
        $this->assertStringNotContainsString('pkg-filter-bar mb-6 flex flex-nowrap', $source);
        $this->assertNotFalse($headerPosition);
        $this->assertNotFalse($navigationPosition);
        $this->assertNotFalse($analyticsPosition);
        $this->assertLessThan($headerPosition, $navigationPosition);
        $this->assertLessThan($analyticsPosition, $navigationPosition);

        $filterClosePosition = strpos($verificationTab, '</x-collapsible-section>');
        $checklistPosition = strpos($verificationTab, '@if(isset($checklists))');
        $this->assertSame(1, substr_count($verificationTab, '<x-collapsible-section'));
        $this->assertSame(1, substr_count($verificationTab, '</x-collapsible-section>'));
        $this->assertNotFalse($filterClosePosition);
        $this->assertNotFalse($checklistPosition);
        $this->assertLessThan($checklistPosition, $filterClosePosition);
    }
}
