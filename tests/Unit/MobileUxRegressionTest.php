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

    public function test_quran_tracer_uses_shared_tabs_and_mobile_safe_scan_forms(): void
    {
        $root = dirname(__DIR__, 2);
        $student = file_get_contents($root.'/resources/views/quran-reading/student-index.blade.php');
        $operational = file_get_contents($root.'/resources/views/quran-reading/operational-index.blade.php');
        $scan = file_get_contents($root.'/resources/views/quran-reading/partials/scan-form.blade.php');
        $scanScript = file_get_contents($root.'/resources/js/quran-scan.js');

        $this->assertStringContainsString('<x-tabs', $student);
        $this->assertStringContainsString(':sync-query="true"', $student);
        $this->assertStringContainsString("id=\"rekap\"", $student);
        $this->assertStringContainsString("id=\"input\"", $student);
        $this->assertStringContainsString("id=\"scan\"", $student);
        $this->assertStringContainsString('<x-tabs', $operational);
        $this->assertStringContainsString(':sync-query="true"', $operational);
        $this->assertStringContainsString("\$capabilities['create']", $operational);
        $this->assertStringNotContainsString('capture="environment"', $scan);
        $this->assertStringContainsString('data-quran-pdf-file', $scan);
        $this->assertStringContainsString("import('pdfjs-dist')", $scanScript);
        $this->assertStringContainsString('requestSubmit()', $scanScript);
        $this->assertStringContainsString('maksimal 8 MB', $scan);
        $this->assertStringNotContainsString('overflow-x-auto', $scan);
    }

    public function test_pamong_assignment_board_has_touch_and_mobile_safe_controls(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root.'/resources/views/pamong/assign.blade.php');
        $script = file_get_contents($root.'/resources/js/pamong-assignment-board.js');
        $styles = file_get_contents($root.'/resources/css/app.css');

        $this->assertStringContainsString('data-pamong-assignment-board', $view);
        $this->assertStringContainsString('data-board-draft-bar', $view);
        $this->assertStringContainsString('data-board-action-dialog', $view);
        $this->assertStringContainsString("addEventListener('pointerdown'", $script);
        $this->assertStringContainsString('data-column-id', $script);
        $this->assertStringContainsString('prefers-reduced-motion', $styles);
        $this->assertStringContainsString('scroll-snap-type: x mandatory', $styles);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $styles);
    }

    public function test_interactive_tables_use_mobile_cards_or_an_explicit_mobile_alternative(): void
    {
        $root = dirname(__DIR__, 2);
        $viewsRoot = $root.'/resources/views';
        $unexpected = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewsRoot));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            $relativePath = str_replace(str_replace('\\', '/', $viewsRoot).'/', '', $path);
            $source = file_get_contents($file->getPathname());

            if (! str_contains($source, '<table')) {
                continue;
            }

            if (str_contains($relativePath, '/pdf.blade.php')
                || str_contains($relativePath, '/pdf/')
                || str_contains($relativePath, 'export-pdf.blade.php')
                || str_contains($source, 'pkg-mobile-table')
                || (str_contains($source, 'lg:hidden') && str_contains($source, 'lg:block'))) {
                continue;
            }

            if ($relativePath === 'materi-targets/index.blade.php') {
                $this->assertStringContainsString('overflow-x-auto', $source);
                $this->assertStringContainsString('sticky left-0', $source);

                continue;
            }

            $unexpected[] = $relativePath;
        }

        $this->assertSame([], $unexpected, 'Tabel interaktif berikut belum memiliki tata letak mobile: '.implode(', ', $unexpected));
    }

    public function test_audited_admin_menu_views_use_compact_mobile_patterns(): void
    {
        $root = dirname(__DIR__, 2);
        $mobileTableViews = [
            'admin/chat-groups/index',
            'admin/gamification/analytics',
            'admin/gamification/badges',
            'admin/gamification/levels',
            'admin/gamification/transactions',
            'pamong/activity-log',
            'pamong/permissions-index',
            'pamong/partials/akun',
            'pamong/partials/data',
            'pamong/partials/permissions',
            'pamong/partials/qr',
            'pamong/show',
            'settings/backup',
            'siswa/accounts',
            'tugas-pkg/verification/karakter-harian',
        ];

        foreach ($mobileTableViews as $view) {
            $source = file_get_contents($root."/resources/views/{$view}.blade.php");

            $this->assertStringContainsString('pkg-mobile-table', $source, "Pola kartu mobile hilang dari {$view}.");
            $this->assertStringContainsString('data-label=', $source, "Label mobile hilang dari {$view}.");
        }

        $news = file_get_contents($root.'/resources/views/berita/index.blade.php');
        $this->assertStringContainsString('pkg-page-header', $news);
        $this->assertStringContainsString('pkg-filter-bar', $news);
        $this->assertStringContainsString('pkg-card', $news);

        $gamificationNavigation = file_get_contents($root.'/resources/views/admin/gamification/partials/navigation.blade.php');
        $styles = file_get_contents($root.'/resources/css/app.css');
        $this->assertStringContainsString('pkg-subnav-scroll', $gamificationNavigation);
        $this->assertStringContainsString('.pkg-subnav-scroll', $styles);

        foreach (['analytics', 'badges', 'levels', 'transactions'] as $view) {
            $source = file_get_contents($root."/resources/views/admin/gamification/{$view}.blade.php");
            $this->assertStringContainsString("@include('admin.gamification.partials.navigation')", $source);
        }
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
        $this->assertStringContainsString('flex min-w-max flex-nowrap gap-2 sm:gap-3', $navigation);
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
        $this->assertStringContainsString('grid grid-cols-3 gap-2', $verificationTab);
        $this->assertStringContainsString('Pilih semua kiriman di halaman ini', $verificationTab);

        $mobileCard = file_get_contents(dirname(__DIR__, 2).'/resources/views/tugas-pkg/verification/partials/mobile-checklist-card.blade.php');
        $this->assertStringContainsString('Lihat jawaban dan bukti', $mobileCard);
        $this->assertStringContainsString('grid grid-cols-2 gap-2', $mobileCard);

        foreach (['rekap', 'history', 'detail-siswa'] as $view) {
            $relatedView = file_get_contents(dirname(__DIR__, 2)."/resources/views/tugas-pkg/verification/{$view}.blade.php");
            $this->assertStringContainsString('pkg-mobile-table overflow-x-auto', $relatedView);
            $this->assertStringContainsString('data-label=', $relatedView);
        }
    }
}
