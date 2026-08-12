<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('layouts.partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1f2937">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PKG Presensi">
    <meta name="mobile-web-app-capable" content="yes">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <title>@yield('title', ($siteSettings['site_title'] ?? 'PKG Presensi') . ' - ' . ($siteSettings['site_name'] ?? 'Sistem Presensi QR Code'))</title>
    
    @include('layouts.partials.favicons')
    
    <!-- Open Graph / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $siteSettings['site_title'] ?? 'PKG Presensi' }}">
    <meta property="og:description" content="{{ $siteSettings['site_name'] ?? 'Pembinaan Karakter Generus' }}">
    @if(!empty($siteSettings['site_logo']))
    <meta property="og:image" content="{{ asset('storage/' . $siteSettings['site_logo']) }}">
    @endif
    <meta property="og:site_name" content="{{ $siteSettings['site_title'] ?? 'PKG Presensi' }}">
    @php
        $manifestVersion = is_file(public_path('manifest.json')) ? filemtime(public_path('manifest.json')) : null;
    @endphp
    <link rel="manifest" href="{{ asset('manifest.json') }}{{ $manifestVersion ? '?v=' . $manifestVersion : '' }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    @viteReactRefresh
    @vite(['resources/js/app.js'])
    @include('layouts.partials.theme-styles')
    
    @stack('styles')
    @stack('head-scripts')

    <script>
        (function () {
            try {
                var shouldCollapse = window.innerWidth < 1024 || localStorage.getItem('sidebarCollapsed') !== 'false';
                document.documentElement.classList.toggle('sidebar-preload-closed', shouldCollapse);
            } catch (error) {
                document.documentElement.classList.remove('sidebar-preload-closed');
            }
        })();
    </script>
    
    <style>
        .sidebar-collapsed { width: 64px; }
        .sidebar-expanded { width: 256px; }
        .sidebar-collapsed .nav-text { display: none; }
        .sidebar-collapsed .nav-item { justify-content: center; padding-left: 0; padding-right: 0; }
        .sidebar-collapsed .nav-icon { margin-right: 0; }
        .sidebar-collapsed .logo-text { display: none; }
        .sidebar-collapsed .collapse-btn-text { display: none; }
        /* Mobile: always show expanded sidebar */
        @media (max-width: 1023px) {
            .sidebar-overlay { display: block; }
            aside { width: 256px !important; }
            aside .nav-text { display: inline !important; }
            aside .nav-item { justify-content: flex-start !important; padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
            aside .nav-icon { margin-right: 0.75rem !important; }
            aside .logo-text { display: inline !important; }
        }

        html.sidebar-preload-closed .pkg-sidebar {
            transform: translateX(-100%);
            visibility: hidden;
        }

        html.sidebar-preload-closed .pkg-sidebar-overlay {
            display: none !important;
        }
    </style>
</head>
<body class="h-full font-sans antialiased" x-data="{ 
    darkMode: localStorage.getItem('darkMode') !== null ? localStorage.getItem('darkMode') === 'true' : window.matchMedia('(prefers-color-scheme: dark)').matches,
    sidebarCollapsed: window.innerWidth < 1024 ? true : (localStorage.getItem('sidebarCollapsed') !== 'false'),
    mobileMenuOpen: false,
    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}" 
:class="{ 'dark': darkMode }" 
x-init="document.documentElement.classList.remove('sidebar-preload-closed')"
x-effect="localStorage.setItem('sidebarCollapsed', sidebarCollapsed); document.documentElement.classList.toggle('overflow-hidden', mobileMenuOpen || (!sidebarCollapsed && window.innerWidth < 1024)); document.documentElement.classList.toggle('pkg-mobile-menu-open', mobileMenuOpen)"
@resize.window="if (window.innerWidth < 1024) sidebarCollapsed = true; else mobileMenuOpen = false">
    
    @auth
    <div class="pkg-portal-shell flex max-w-full overflow-hidden">
        <!-- Mobile Overlay -->
        <div x-show="!sidebarCollapsed" @click="sidebarCollapsed = true" class="pkg-sidebar-overlay fixed inset-0 bg-black/50 z-40 lg:hidden" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        
        <!-- Sidebar -->
        <aside 
            x-show="!sidebarCollapsed"
            x-transition:enter="transition-transform ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="pkg-sidebar fixed inset-y-0 left-0 z-50 hidden w-64 flex-col border-r will-change-transform lg:relative lg:flex">
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700">
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    @if(!empty($siteSettings['site_logo']))
                        <img src="{{ Storage::url($siteSettings['site_logo']) }}" alt="Logo" width="32" height="32" class="h-8 w-8 flex-shrink-0 object-contain" style="width:2rem;height:2rem;object-fit:contain;" decoding="async" fetchpriority="high">
                    @else
                        <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-bold text-sm">P</span>
                        </div>
                    @endif
                    <span class="logo-text ml-3 text-lg font-bold text-gray-900 dark:text-white truncate">{{ $siteSettings['site_title'] ?? 'PKG' }}</span>
                </a>
                <!-- Collapse/Hide button - same icon for mobile & desktop -->
                <button @click="sidebarCollapsed = true" class="p-2 rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4 px-2" @click="if($event.target.closest('a[href]') && window.innerWidth < 1024) sidebarCollapsed = true">
                <div class="space-y-1">
                    @php
                        $pendingLaporanPenyaksianCountForSidebar = (int) ($pendingLaporanPenyaksianCount ?? 0);
                        $canAccessScheduleMenu = auth()->user()->isAdmin() || auth()->user()->hasPamongMenuAccess('jadwal');
                        $canAccessCalendarMenu = auth()->user()->isAdmin()
                            || auth()->user()->usesPamongPermissionSystem()
                            || auth()->user()->hasPamongMenuAccess('calendar');
                        $canAccessManualAttendanceMenu = auth()->user()->isAdmin()
                            || auth()->user()->hasPamongMenuAccess('manual_attendance');
                        $canAccessGeneralPresensi = auth()->user()->isAdmin()
                            || auth()->user()->isPengurusPkg()
                            || auth()->user()->hasPamongMenuAccess('presensi');
                        $presensiGroupVisible = auth()->user()->hasPamongMenuAccess('presensi')
                            || $canAccessManualAttendanceMenu
                            || auth()->user()->hasPamongMenuAccess('cek_kehadiran')
                            || auth()->user()->hasPamongMenuAccess('pamong_presensi')
                            || $canAccessScheduleMenu
                            || auth()->user()->hasPamongMenuAccess('qr_generate')
                            || $canAccessCalendarMenu;
                        $presensiGroupActive = request()->routeIs('presensi.*')
                            || request()->routeIs('manual-attendance.*')
                            || request()->routeIs('cek-kehadiran.*')
                            || request()->routeIs('pamong-presensi.*')
                            || request()->routeIs('attendance-schedule.*')
                            || request()->routeIs('qr.*')
                            || request()->routeIs('calendar.*');
                        $tugasPkgGroupVisible = auth()->user()->hasPamongMenuAccess('pr')
                            || auth()->user()->hasPamongMenuAccess('tracer_karakter')
                            || auth()->user()->hasPamongMenuAccess('tracer_bacaan_quran')
                            || auth()->user()->hasPamongMenuAccess('tugas_pkg')
                            || auth()->user()->hasPamongMenuAccess('laporan_penyaksian');
                        $tugasPkgGroupActive = request()->routeIs('tugas-pkg.*')
                            || request()->routeIs('tracer-karakter.*')
                            || request()->routeIs('quran.*')
                            || request()->routeIs('karakter.*')
                            || request()->routeIs('laporan-penyaksian.*')
                            || request()->routeIs('pr.*');
                        // Verifikasi memakai sidebar utama yang ringkas seperti Dashboard.
                        // Badge tetap terlihat, tetapi tidak memaksa submenu terbuka.
                        $tugasPkgGroupExpanded = $tugasPkgGroupActive
                            && ! request()->routeIs('tugas-pkg.verification');
                        $gamificationGroupVisible = auth()->user()->canAccessGamificationAdmin();
                        $gamificationGroupActive = request()->routeIs('admin.gamification.*')
                            || request()->routeIs('admin.rpg.*');
                        $adminToolsGroupVisible = auth()->user()->isAdmin()
                            || auth()->user()->hasPamongMenuAccess('teacher_scheduling');
                        $adminToolsGroupActive = request()->routeIs('settings.*')
                            || request()->routeIs('users.*')
                            || request()->routeIs('pamong.*')
                            || request()->routeIs('teacher-planning.*')
                            || request()->routeIs('admin.data-pull.*')
                            || request()->routeIs('admin.certificate.*');
                        $contentGroupVisible = auth()->user()->hasPamongMenuAccess('berita')
                            || auth()->user()->hasPamongMenuAccess('materi')
                            || auth()->user()->isAdmin()
                            || auth()->user()->isPengurusPkg()
                            || auth()->user()->isTeacher()
                            || auth()->user()->hasPamongMenuAccess('chat')
                            || auth()->user()->hasPamongMenuAccess('group_chat')
                            || auth()->user()->hasPamongMenuAccess('catatan_rapat');
                        $contentGroupActive = request()->routeIs('berita.*')
                            || request()->routeIs('materi.*')
                            || request()->routeIs('materi-rpp-journals.*')
                            || request()->routeIs('pamong.chat.*')
                            || request()->routeIs('group-chat.*')
                            || request()->routeIs('catatan-rapat.*');
                    @endphp
                    <div class="flex items-center gap-2 px-3 pb-1 pt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                            </svg>
                        </span>
                        <span>Data Utama</span>
                    </div>
                    <!-- Dashboard -->
                    @if(auth()->user()->hasPamongMenuAccess('dashboard'))
                    <a href="{{ route('dashboard') }}" class="nav-item @if(request()->routeIs('dashboard')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    @endif
                    
                    @if(auth()->user()->hasPamongMenuAccess('siswa'))
                    <!-- Siswa -->
                    <a href="{{ route('siswa.index') }}" class="nav-item @if(request()->routeIs('siswa.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span class="nav-text">Siswa</span>
                    </a>
                    @endif
                    
                    @if(auth()->user()->hasPamongMenuAccess('siswa'))
                    <!-- Orang Tua -->
                    <a href="{{ route('ortu-management.index') }}" class="nav-item @if(request()->routeIs('ortu-management.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="nav-text">Orang Tua</span>
                    </a>
                    @endif
                    
                    @if(auth()->user()->isAdmin())
                    <!-- Pamong -->
                    <a href="{{ route('pamong.index') }}" class="nav-item @if(request()->routeIs('pamong.*') && !request()->routeIs('pamong-presensi.*') && !request()->routeIs('pamong.chat.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="nav-text">Pamong</span>
                    </a>
                    @endif
                    
                    @if(auth()->user()->hasPamongMenuAccess('siswa'))
                    <!-- Kelas -->
                    <a href="{{ route('kelas.index') }}" class="nav-item @if(request()->routeIs('kelas.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span class="nav-text">Kelas</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasPamongMenuAccess('export'))
                    <a href="{{ route('export.index') }}" class="nav-item @if(request()->routeIs('export.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16"/></svg>
                        <span class="nav-text">Ekspor Data</span>
                    </a>
                    @endif
                    
                    <div class="flex items-center gap-2 px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span>Aktivitas</span>
                    </div>
                    @if($presensiGroupVisible)
                    <div x-data="{ open: {{ $presensiGroupActive ? 'true' : 'false' }} }" class="space-y-1">
                        <button type="button" @click="open = !open" class="nav-item {{ $presensiGroupActive ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} flex w-full items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <span class="flex items-center">
                                <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                <span class="nav-text">Presensi</span>
                            </span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition x-cloak class="space-y-1 pl-3">
                            @if($canAccessGeneralPresensi)
                            <a href="{{ route('presensi.index') }}" class="nav-item @if(request()->routeIs('presensi.index')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Presensi Siswa</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('presensi') || auth()->user()->hasPamongMenuAccess('pamong_presensi'))
                            <a href="{{ route('presensi.recap') }}" class="nav-item @if(request()->routeIs('presensi.recap')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Rekap Presensi</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('presensi'))
                            <a href="{{ route('presensi.generus-recap') }}" class="nav-item @if(request()->routeIs('presensi.generus-recap')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Rekap Generus</span>
                            </a>
                            @endif
                            @if($canAccessManualAttendanceMenu && ! $canAccessGeneralPresensi)
                            <a href="{{ route('presensi.index', ['tab' => 'input']) }}#input" class="nav-item @if(request()->routeIs('manual-attendance.*') || (request()->routeIs('presensi.index') && request('tab') === 'input')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Input Manual</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('cek_kehadiran'))
                            <a href="{{ route('cek-kehadiran.index') }}" class="nav-item @if(request()->routeIs('cek-kehadiran.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Poin Kehadiran</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('pamong_presensi'))
                            <a href="{{ route('pamong-presensi.index') }}" class="nav-item @if(request()->routeIs('pamong-presensi.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Presensi Pamong</span>
                            </a>
                            @endif
                            @if($canAccessScheduleMenu && ! $canAccessGeneralPresensi)
                            <a href="{{ route('attendance-schedule.index') }}" class="nav-item @if(request()->routeIs('attendance-schedule.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Jadwal Presensi</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('qr_generate'))
                            <a href="{{ route('qr.generate') }}" class="nav-item @if(request()->routeIs('qr.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">QR Code</span>
                            </a>
                            @endif
                            @if($canAccessCalendarMenu)
                            <a href="{{ route('calendar.index') }}" class="nav-item @if(request()->routeIs('calendar.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Kalender</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    <div class="border-t border-gray-200 dark:border-gray-700 my-3"></div>
                    
                    @if($contentGroupVisible)
                    <div x-data="{ open: {{ $contentGroupActive ? 'true' : 'false' }} }" class="space-y-1">
                        <button type="button" @click="open = !open" class="nav-item {{ $contentGroupActive ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} flex w-full items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <span class="flex items-center">
                                <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                <span class="nav-text">Konten & Komunikasi</span>
                            </span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition x-cloak class="space-y-1 pl-3">
                            @if(auth()->user()->hasPamongMenuAccess('berita'))
                            <a href="{{ route('berita.index') }}" class="nav-item @if(request()->routeIs('berita.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Berita</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('materi'))
                            <a href="{{ route('materi.index') }}" class="nav-item @if(request()->routeIs('materi.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Materi</span>
                            </a>
                            <a href="{{ route('presentations.index') }}" class="nav-item @if(request()->routeIs('presentations.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Presentasi</span>
                            </a>
                            @endif
                            @if(auth()->user()->isAdmin() || auth()->user()->isPengurusPkg() || auth()->user()->isTeacher())
                            <a href="{{ route('materi-rpp-journals.index') }}" class="nav-item @if(request()->routeIs('materi-rpp-journals.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Jurnal RPP</span>
                                @if(($appSidebarPendingJournalCount ?? 0) > 0)
                                    <span class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                                        {{ ($appSidebarPendingJournalCount ?? 0) > 99 ? '99+' : $appSidebarPendingJournalCount }}
                                    </span>
                                @endif
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('chat'))
                            <a href="{{ route('pamong.chat.index') }}" class="nav-item @if(request()->routeIs('pamong.chat.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Chat</span>
                                @if(($appSidebarUnreadChatCount ?? 0) > 0)
                                    <span class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                                        {{ ($appSidebarUnreadChatCount ?? 0) > 99 ? '99+' : $appSidebarUnreadChatCount }}
                                    </span>
                                @endif
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('group_chat'))
                            <a href="{{ route('group-chat.index') }}" class="nav-item @if(request()->routeIs('group-chat.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Grup Chat</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('catatan_rapat'))
                            <a href="{{ route('catatan-rapat.index') }}" class="nav-item @if(request()->routeIs('catatan-rapat.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Catatan Rapat</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 dark:border-gray-700 my-3"></div>
                    @endif
                    
                    @if($tugasPkgGroupVisible)
                    <div x-data="{ open: {{ $tugasPkgGroupExpanded ? 'true' : 'false' }} }" class="space-y-1">
                        <button type="button" @click="open = !open" class="nav-item {{ $tugasPkgGroupActive ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} flex w-full items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <span class="flex items-center">
                                <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                <span class="nav-text">Tugas PKG</span>
                                @if($pendingLaporanPenyaksianCountForSidebar > 0)
                                    <span class="nav-text ml-2 inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                                        {{ $pendingLaporanPenyaksianCountForSidebar > 99 ? '99+' : $pendingLaporanPenyaksianCountForSidebar }}
                                    </span>
                                @endif
                            </span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition x-cloak class="space-y-1 pl-3">
                            @if(auth()->user()->hasPamongMenuAccess('pr'))
                            <a href="{{ route('tugas-pkg.index') }}" class="nav-item @if(request()->routeIs('tugas-pkg.index') || request()->routeIs('pr.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Daftar Tugas Aktif</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('tracer_karakter'))
                            <a href="{{ route('tugas-pkg.verification') }}" class="nav-item @if(request()->routeIs('tugas-pkg.verification') || request()->routeIs('tracer-karakter.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Verifikasi Tugas PKG</span>
                                @if(($pendingPkgVerificationCount ?? 0) > 0)
                                    <span class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $pendingPkgVerificationCount }}</span>
                                @endif
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('tracer_bacaan_quran'))
                            <a href="{{ route('quran.index') }}" class="nav-item @if(request()->routeIs('quran.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Tracer Bacaan Al-Qur'an</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('tugas_pkg'))
                            <a href="{{ route('tugas-pkg.master') }}" class="nav-item @if(request()->routeIs('tugas-pkg.master') || (request()->routeIs('karakter.*') && !request()->routeIs('karakter.verification.*'))) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Buat Tugas PKG</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('laporan_penyaksian'))
                            <a href="{{ route('laporan-penyaksian.index') }}" class="nav-item @if(request()->routeIs('laporan-penyaksian.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Info Lapor PKG</span>
                                @if($pendingLaporanPenyaksianCountForSidebar > 0)
                                    <span class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                                        {{ $pendingLaporanPenyaksianCountForSidebar > 99 ? '99+' : $pendingLaporanPenyaksianCountForSidebar }}
                                    </span>
                                @endif
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    @if(auth()->user()->isAdmin() || $gamificationGroupVisible)
                    <div class="border-t border-gray-200 dark:border-gray-700 my-3"></div>
                    <div class="flex items-center gap-2 px-3 pb-1 pt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 12v-2m8-4a8 8 0 11-16 0 8 8 0 0116 0z"/>
                            </svg>
                        </span>
                        <span>{{ auth()->user()->isAdmin() ? 'Admin' : 'Operasional' }}</span>
                    </div>
                    
                    @if($gamificationGroupVisible)
                    <div x-data="{ open: {{ $gamificationGroupActive ? 'true' : 'false' }} }" class="space-y-1">
                        <button type="button" @click="open = !open" class="nav-item {{ $gamificationGroupActive ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} flex w-full items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <span class="flex items-center">
                                <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21h8m-7-4h6a2 2 0 002-2v-3.382a1 1 0 01.553-.894A4 4 0 0019 7a4 4 0 00-4-4 3.98 3.98 0 00-3 1.354A3.98 3.98 0 009 3a4 4 0 00-4 4 4 4 0 001.447 3.724 1 1 0 01.553.894V15a2 2 0 002 2z"/></svg>
                                <span class="nav-text">Gamifikasi & Game</span>
                            </span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition x-cloak class="space-y-1 pl-3">
                            @if(auth()->user()->hasPamongMenuAccess('gamification'))
                            <a href="{{ route('admin.gamification.badges') }}" class="nav-item @if(request()->routeIs('admin.gamification.*') && !request()->routeIs('admin.gamification.transactions')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Gamifikasi</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('gamification'))
                            <a href="{{ route('admin.gamification.transactions') }}" class="nav-item @if(request()->routeIs('admin.gamification.transactions')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Riwayat Transaksi</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasPamongMenuAccess('game'))
                            <a href="{{ route('admin.rpg.index') }}" class="nav-item @if(request()->routeIs('admin.rpg.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Game 29 Karakter</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    @if($adminToolsGroupVisible)
                    <div x-data="{ open: {{ $adminToolsGroupActive ? 'true' : 'false' }} }" class="space-y-1">
                        <button type="button" @click="open = !open" class="nav-item {{ $adminToolsGroupActive ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} flex w-full items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <span class="flex items-center">
                                <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span class="nav-text">Administrasi</span>
                            </span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition x-cloak class="space-y-1 pl-3">
                            @if(auth()->user()->hasPamongMenuAccess('teacher_scheduling'))
                            <a href="{{ route('teacher-planning.index') }}" class="nav-item @if(request()->routeIs('teacher-planning.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Pendataan & Jadwal Guru</span>
                            </a>
                            @endif
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('settings.index') }}" class="nav-item @if(request()->routeIs('settings.*') || request()->routeIs('users.*') || request()->routeIs('pamong.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Pengaturan</span>
                            </a>
                            <a href="{{ route('admin.data-pull.index') }}" class="nav-item @if(request()->routeIs('admin.data-pull.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Tarik Data</span>
                            </a>
                            <a href="{{ route('admin.certificate.settings', 1) }}" class="nav-item @if(request()->routeIs('admin.certificate.*')) bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @else text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <span class="nav-text">Sertifikat Level</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    <div class="border-t border-gray-200 dark:border-gray-700 my-3"></div>
                    
                    <!-- Lihat Halaman Publik -->
                    <a href="{{ route('public.index') }}" target="_blank" class="nav-item text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        <span class="nav-text">Halaman Publik</span>
                    </a>
                    @endif
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-3 hidden lg:hidden">
                <!-- Collapse button moved to header -->
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            @if(!empty($mobilePortal))
                @include('layouts.partials.portal-mobile-navigation')
            @endif

            <!-- Top Header -->
            <header class="pkg-topbar relative z-[80] hidden h-16 shrink-0 items-center justify-between border-b px-4 lg:flex lg:px-6">
                <!-- Left: Menu Button -->
                <div class="flex items-center">
                    <button @click="sidebarCollapsed = !sidebarCollapsed" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
                
                <!-- Right: User Menu -->
                <div class="flex items-center space-x-3">
                    <x-pwa-push-control
                        :subscribe-url="route('pwa.push-subscriptions.store')"
                        :unsubscribe-url="route('pwa.push-subscriptions.destroy')"
                        :badge-count="$pendingPkgVerificationCount ?? 0"
                    />

                    <!-- Dark Mode Toggle -->
                    <button @click="toggleDarkMode()" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" title="Toggle Dark Mode">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                    
                    <!-- User Dropdown -->
                    <div class="relative z-[90]" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700" aria-haspopup="menu" :aria-expanded="open.toString()">
                            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center">
                                <span class="text-white text-sm font-medium">{{ substr(auth()->user()->username, 0, 1) }}</span>
                            </div>
                            <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()->username }}</span>
                            <svg class="hidden sm:block w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute right-0 top-full z-[100] mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-2xl ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900 dark:ring-white/10" role="menu">
                            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ auth()->user()->username }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->role->display_name ?? 'User' }}</p>
                            </div>
                            <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700" role="menuitem">Profil</a>
                            @if($teacherPortalAvailable ?? false)
                                <a href="{{ route('guru.dashboard') }}" class="block px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/40" role="menuitem">Buka Portal Guru</a>
                            @endif
                            @if(auth()->user()->hasAnyRole(\App\Models\User::attendanceRoleNames()))
                                <a href="{{ route('profile.id-card') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700" role="menuitem">ID Card Saya</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-200 dark:border-gray-700">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 text-left text-sm font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20" role="menuitem">Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="pkg-portal-mobile-main min-w-0 max-w-full flex-1 overflow-x-hidden overflow-y-auto">
                @if(session('success'))
                <div class="mx-4 mt-4 lg:mx-6">
                    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <p class="ml-3 text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
                @endif
                
                @if(session('error'))
                <div class="mx-4 mt-4 lg:mx-6">
                    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            <p class="ml-3 text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
                @endif
                
                <div class="p-4 lg:p-6">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @else
    <!-- Guest Content -->
    <div class="min-h-screen">
        @yield('content')
    </div>
    @endauth
    
    <!-- PWA Install Prompt -->
    <div id="pwa-install" class="fixed bottom-4 left-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg transform translate-y-full transition-transform duration-300 z-40" style="display: none;">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-medium">Install PKG Presensi</h3>
                <p class="text-sm text-blue-100">Install aplikasi untuk akses lebih cepat</p>
            </div>
            <div class="flex space-x-2">
                <button id="pwa-install-btn" class="bg-white text-blue-600 px-3 py-1 rounded text-sm font-medium">Install</button>
                <button id="pwa-dismiss" class="text-blue-100 hover:text-white text-xl" aria-label="Tutup prompt instalasi">&times;</button>
            </div>
        </div>
    </div>
    
    @stack('scripts')
    
    <script>
        let deferredPrompt;
        const installButton = document.getElementById('pwa-install-btn');
        const installPrompt = document.getElementById('pwa-install');
        const dismissButton = document.getElementById('pwa-dismiss');
        const isLocalHost = ['127.0.0.1', 'localhost', '::1'].includes(window.location.hostname);
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            if (isLocalHost || localStorage.getItem('pwaInstallDismissed') === 'true') {
                return;
            }
            deferredPrompt = e;
            installPrompt.style.display = 'block';
            setTimeout(() => installPrompt.classList.remove('translate-y-full'), 100);
        });
        
        installButton?.addEventListener('click', () => {
            installPrompt.classList.add('translate-y-full');
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(() => deferredPrompt = null);
        });
        
        dismissButton?.addEventListener('click', () => {
            localStorage.setItem('pwaInstallDismissed', 'true');
            installPrompt.classList.add('translate-y-full');
            setTimeout(() => installPrompt.style.display = 'none', 300);
        });
        
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            window.axios = window.axios || {};
            window.axios.defaults = window.axios.defaults || {};
            window.axios.defaults.headers = window.axios.defaults.headers || {};
            window.axios.defaults.headers.common = window.axios.defaults.headers.common || {};
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
            window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        }
    </script>
    
    <!-- CSRF Token Handler -->
    @php
        $csrfHandlerVersion = is_file(public_path('js/csrf-handler.js'))
            ? filemtime(public_path('js/csrf-handler.js'))
            : 1;
    @endphp
    <script src="{{ asset('js/csrf-handler.js') }}?v={{ $csrfHandlerVersion }}"></script>
    
    @include('components.profile-assignment-prompt')
    @include('components.face-enrollment-prompt')
    @include('components.biometric-prompt')
</body>
</html>

