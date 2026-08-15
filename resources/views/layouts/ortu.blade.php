<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('layouts.partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Orang Tua') - {{ $siteSettings['site_title'] ?? 'PKG' }}</title>
    @include('layouts.partials.favicons')
    @php
        $manifestVersion = is_file(public_path('manifest.json'))
            ? filemtime(public_path('manifest.json'))
            : null;
    @endphp
    <link rel="manifest" href="{{ asset('manifest.json') }}{{ $manifestVersion ? '?v=' . $manifestVersion : '' }}">
    
    @viteReactRefresh
    @vite(['resources/js/app.js'])
    @include('layouts.partials.theme-styles')
    
    @stack('styles')
    @stack('head-scripts')

    <script>
        (function () {
            try {
                var shouldCollapse = window.innerWidth < 1024 || localStorage.getItem('ortuSidebarCollapsed') === 'true';
                document.documentElement.classList.toggle('sidebar-preload-closed', shouldCollapse);
            } catch (error) {
                document.documentElement.classList.remove('sidebar-preload-closed');
            }
        })();
    </script>
    
    <style>
        .sidebar-collapsed { width: 64px; }
        .sidebar-collapsed .nav-text { display: none; }
        .sidebar-collapsed .logo-text { display: none; }
        .sidebar-collapsed .nav-icon { margin-right: 0 !important; }
        @media (min-width: 1024px) {
            aside .nav-text { display: inline; }
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
    sidebarCollapsed: window.innerWidth < 1024 ? true : (localStorage.getItem('ortuSidebarCollapsed') === 'true'),
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
x-effect="localStorage.setItem('ortuSidebarCollapsed', sidebarCollapsed); document.documentElement.classList.toggle('overflow-hidden', mobileMenuOpen || (!sidebarCollapsed && window.innerWidth < 1024))"
@resize.window="if (window.innerWidth < 1024) sidebarCollapsed = true; else mobileMenuOpen = false">
    @php
        $currentOrtu = Auth::guard('ortu')->user();
        $siswa = $currentOrtu;
        $ortuTaskBadge = (int) ($ortuSidebarPendingTaskCount ?? 0);
        $ortuChatBadge = (int) ($ortuSidebarUnreadChatCount ?? 0);
        $mobilePortal = [
            'tone' => 'teal',
            'portal_label' => $currentOrtu?->isGraduated() ? 'Portal Orang Tua Alumni' : 'Portal Orang Tua',
            'home_url' => route('ortu.dashboard'),
            'profile_url' => route('ortu.settings'),
            'profile_label' => 'pengaturan orang tua',
            'user_name' => $currentOrtu?->nama ?? 'Orang Tua',
            'user_meta' => trim(($currentOrtu?->kelas?->nama ?? '') . ' - ' . ($currentOrtu?->nis ?? ''), ' -'),
            'photo_url' => $currentOrtu?->foto_path ? asset('storage/' . $currentOrtu->foto_path) : null,
            'bottom_items' => [
                ['label' => 'Beranda', 'icon' => 'home', 'url' => route('ortu.dashboard'), 'active' => request()->routeIs('ortu.dashboard')],
                ['label' => 'Kalender', 'icon' => 'calendar', 'url' => route('ortu.jadwal'), 'active' => request()->routeIs('ortu.jadwal*')],
                ['label' => 'Tugas', 'icon' => 'check', 'url' => route('ortu.tugas'), 'active' => request()->routeIs('ortu.tugas*'), 'badge' => $ortuTaskBadge],
                ['label' => 'Materi', 'icon' => 'book', 'url' => route('ortu.materi.index'), 'active' => request()->routeIs('ortu.materi.*')],
                ['label' => 'Chat', 'icon' => 'chat', 'url' => route('ortu.chat'), 'active' => request()->routeIs('ortu.chat*'), 'badge' => $ortuChatBadge],
            ],
            'more_active' => request()->routeIs('ortu.kehadiran')
                || request()->routeIs('ortu.quran.*')
                || request()->routeIs('ortu.settings*')
                || request()->routeIs('ortu.biometrik'),
            'sheet_sections' => [
                [
                    'label' => 'Menu utama',
                    'items' => [
                        ['label' => 'Beranda', 'icon' => 'home', 'url' => route('ortu.dashboard'), 'active' => request()->routeIs('ortu.dashboard')],
                        ['label' => 'Kalender', 'icon' => 'calendar', 'url' => route('ortu.jadwal'), 'active' => request()->routeIs('ortu.jadwal*')],
                        ['label' => 'Tugas PKG', 'icon' => 'check', 'url' => route('ortu.tugas'), 'active' => request()->routeIs('ortu.tugas*'), 'badge' => $ortuTaskBadge],
                        ['label' => 'Materi', 'icon' => 'book', 'url' => route('ortu.materi.index'), 'active' => request()->routeIs('ortu.materi.*')],
                        ['label' => 'Chat Pamong', 'icon' => 'chat', 'url' => route('ortu.chat'), 'active' => request()->routeIs('ortu.chat*'), 'badge' => $ortuChatBadge],
                    ],
                ],
                [
                    'label' => 'Aktivitas Generus',
                    'items' => [
                        ['label' => 'Kehadiran PKG', 'icon' => 'attendance', 'url' => route('ortu.kehadiran'), 'active' => request()->routeIs('ortu.kehadiran')],
                        ['label' => "Bacaan Al-Qur'an", 'icon' => 'book', 'url' => route('ortu.quran.index'), 'active' => request()->routeIs('ortu.quran.*')],
                    ],
                ],
                [
                    'label' => 'Akun orang tua',
                    'items' => [
                        ['label' => 'Pengaturan', 'icon' => 'settings', 'url' => route('ortu.settings'), 'active' => request()->routeIs('ortu.settings*')],
                        ['label' => 'Biometrik', 'icon' => 'fingerprint', 'url' => route('ortu.biometrik'), 'active' => request()->routeIs('ortu.biometrik')],
                    ],
                ],
            ],
            'logout_url' => route('ortu.logout'),
        ];
    @endphp
    <div class="pkg-portal-shell flex overflow-hidden">
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
                <a href="{{ route('ortu.dashboard') }}" class="flex items-center">
                    @if(!empty($siteSettings['site_logo']))
                        <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo" width="32" height="32" class="h-8 w-8 flex-shrink-0 object-contain" style="width:2rem;height:2rem;object-fit:contain;" decoding="async" fetchpriority="high">
                    @else
                        <div class="h-8 w-8 bg-teal-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-bold text-sm">O</span>
                        </div>
                    @endif
                    <span class="logo-text ml-3 text-lg font-bold text-gray-900 dark:text-white truncate">Portal Ortu</span>
                </a>
                <button @click="sidebarCollapsed = true" class="p-2 rounded-md text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
            </div>

            <!-- Student Info -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-teal-50 dark:bg-teal-900/30">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-teal-600 dark:bg-teal-700 flex items-center justify-center flex-shrink-0">
                        @if($siswa->foto_path)
                            <img src="{{ asset('storage/' . $siswa->foto_path) }}" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <span class="text-white dark:text-teal-200 font-bold text-sm">{{ substr($siswa->nama, 0, 1) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $siswa->nama }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $siswa->school_grade_label }} | {{ $siswa->nis }}</p>
                        @if($siswa->isGraduated())
                            <span class="mt-1 inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800 dark:bg-sky-950/60 dark:text-sky-200">Alumni</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4 px-2" @click="if($event.target.closest('a[href]') && window.innerWidth < 1024) sidebarCollapsed = true">
                <div class="space-y-1">
                    <!-- Dashboard -->
                    <a href="{{ route('ortu.dashboard') }}" class="nav-item @if(request()->routeIs('ortu.dashboard')) bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span class="nav-text">Dashboard</span>
                    </a>

                    <!-- Kalender -->
                    <a href="{{ route('ortu.jadwal') }}" class="nav-item @if(request()->routeIs('ortu.jadwal*')) bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="nav-text">Kalender</span>
                    </a>

                    <!-- Tugas PKG -->
                    <a href="{{ route('ortu.tugas') }}" class="nav-item @if(request()->routeIs('ortu.tugas*')) bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="nav-text">Tugas PKG</span>
                        @if(($ortuSidebarPendingTaskCount ?? 0) > 0)
                            <span class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                                {{ ($ortuSidebarPendingTaskCount ?? 0) > 99 ? '99+' : $ortuSidebarPendingTaskCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Materi -->
                    <a href="{{ route('ortu.materi.index') }}" class="nav-item @if(request()->routeIs('ortu.materi.*')) bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <span class="nav-text">Materi</span>
                    </a>

                    <!-- Kehadiran PKG -->
                    <a href="{{ route('ortu.kehadiran') }}" class="nav-item @if(request()->routeIs('ortu.kehadiran')) bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="nav-text">Kehadiran PKG</span>
                    </a>

                    <!-- Chat Pamong -->
                    <a href="{{ route('ortu.chat') }}" class="nav-item @if(request()->routeIs('ortu.chat*')) bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <span class="nav-text">Chat Pamong</span>
                        @if(($ortuSidebarUnreadChatCount ?? 0) > 0)
                            <span class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                                {{ ($ortuSidebarUnreadChatCount ?? 0) > 99 ? '99+' : $ortuSidebarUnreadChatCount }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('ortu.quran.index') }}" class="nav-item @if(request()->routeIs('ortu.quran.*')) bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <span class="nav-text">Bacaan Al-Qur'an</span>
                    </a>

                    <div class="border-t border-gray-200 dark:border-gray-700 my-3"></div>

                    <!-- Settings -->
                    <a href="{{ route('ortu.settings') }}" class="nav-item @if(request()->routeIs('ortu.settings')) bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 @else text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @endif flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="nav-text">Pengaturan</span>
                    </a>
                </div>
            </nav>

            <!-- Logout -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-3">
                <form method="POST" action="{{ route('ortu.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-3 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span class="nav-text">Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            @include('layouts.partials.portal-mobile-navigation')

            <!-- Top Bar -->
            <header class="pkg-topbar sticky top-0 z-30 hidden h-16 items-center justify-between border-b px-4 sm:px-6 lg:flex">
                <div class="flex items-center">
                    <button @click="sidebarCollapsed = !sidebarCollapsed" class="p-2 rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors mr-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">@yield('title', 'Portal Orang Tua')</h2>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="toggleDarkMode()" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Mode Terang/Gelap">
                        <template x-if="!darkMode">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        </template>
                        <template x-if="darkMode">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </template>
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <main class="pkg-portal-mobile-main min-w-0 max-w-full flex-1 overflow-x-hidden overflow-y-auto">
                @if(session('success'))
                <div class="mx-4 sm:mx-6 mt-4">
                    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg text-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                        {{ session('success') }}
                    </div>
                </div>
                @endif
                @if(session('error'))
                <div class="mx-4 sm:mx-6 mt-4">
                    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm">
                        {{ session('error') }}
                    </div>
                </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
    @include('components.biometric-prompt')
</body>
</html>
