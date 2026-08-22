<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('layouts.partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Siswa') - {{ $siteSettings['site_title'] ?? 'PKG' }}</title>
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
                var shouldCollapse = window.innerWidth < 1024 || localStorage.getItem('siswaSidebarCollapsed') === 'true';
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
<body class="h-full font-sans antialiased"
      x-data="{
        darkMode: localStorage.getItem('darkMode') !== null ? localStorage.getItem('darkMode') === 'true' : window.matchMedia('(prefers-color-scheme: dark)').matches,
        sidebarCollapsed: window.innerWidth < 1024 ? true : (localStorage.getItem('siswaSidebarCollapsed') === 'true'),
        mobileMenuOpen: false,
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            document.documentElement.classList.toggle('dark', this.darkMode);
        }
      }"
      :class="{ 'dark': darkMode }"
      x-init="document.documentElement.classList.remove('sidebar-preload-closed')"
      x-effect="localStorage.setItem('siswaSidebarCollapsed', sidebarCollapsed); document.documentElement.classList.toggle('overflow-hidden', mobileMenuOpen || (!sidebarCollapsed && window.innerWidth < 1024))"
      @resize.window="if (window.innerWidth < 1024) sidebarCollapsed = true; else mobileMenuOpen = false">
@php
    $currentSiswa = Auth::guard('siswa')->user();
    $siswaTaskBadge = (int) ($siswaSidebarPendingTaskCount ?? 0);
    $siswaJournalBadge = (int) ($siswaSidebarPendingJournalCount ?? 0);
    $siswaChatBadge = (int) ($siswaSidebarUnreadChatCount ?? 0);
    $mobilePortal = [
        'tone' => 'blue',
        'portal_label' => $currentSiswa?->isGraduated() ? 'Portal Alumni' : 'Portal Siswa',
        'home_url' => route('siswa.dashboard'),
        'profile_url' => route('siswa.profile'),
        'profile_label' => 'profil siswa',
        'user_name' => $currentSiswa?->nama ?? 'Siswa',
        'user_meta' => trim(($currentSiswa?->kelas?->nama ?? '') . ' - ' . ($currentSiswa?->nis ?? ''), ' -'),
        'photo_url' => $currentSiswa?->foto_path ? asset('storage/' . $currentSiswa->foto_path) : null,
        'bottom_items' => [
            ['label' => 'Beranda', 'icon' => 'home', 'url' => route('siswa.dashboard'), 'active' => request()->routeIs('siswa.dashboard')],
            ['label' => 'Kalender', 'icon' => 'calendar', 'url' => route('siswa.calendar.index'), 'active' => request()->routeIs('siswa.calendar.*')],
            ['label' => 'Tugas', 'icon' => 'check', 'url' => route('siswa.tugas-pkg.index'), 'active' => request()->routeIs('siswa.tugas-pkg.*') || request()->routeIs('siswa.karakter.*'), 'badge' => $siswaTaskBadge],
            ['label' => 'Materi', 'icon' => 'book', 'url' => route('siswa.materi.index'), 'active' => request()->routeIs('siswa.materi.*') || request()->routeIs('siswa.materi-rpp-journals.*'), 'badge' => $siswaJournalBadge],
            ['label' => 'Chat', 'icon' => 'chat', 'url' => route('siswa.chat.index'), 'active' => request()->routeIs('siswa.chat.*') || request()->routeIs('siswa.group-chat.*'), 'badge' => $siswaChatBadge],
        ],
        'more_active' => request()->routeIs('siswa.kehadiran.*')
            || request()->routeIs('siswa.quran.*')
            || request()->routeIs('siswa.gamification.*')
            || request()->routeIs('siswa.rpg.*')
            || request()->routeIs('siswa.profile')
            || request()->routeIs('siswa.kartu*')
            || request()->routeIs('siswa.biometrik'),
        'sheet_sections' => [
            [
                'label' => 'Menu utama',
                'items' => [
                    ['label' => 'Beranda', 'icon' => 'home', 'url' => route('siswa.dashboard'), 'active' => request()->routeIs('siswa.dashboard')],
                    ['label' => 'Kalender', 'icon' => 'calendar', 'url' => route('siswa.calendar.index'), 'active' => request()->routeIs('siswa.calendar.*')],
                    ['label' => 'Tugas PKG', 'icon' => 'check', 'url' => route('siswa.tugas-pkg.index'), 'active' => request()->routeIs('siswa.tugas-pkg.*') || request()->routeIs('siswa.karakter.*'), 'badge' => $siswaTaskBadge],
                    ['label' => 'Materi', 'icon' => 'book', 'url' => route('siswa.materi.index'), 'active' => request()->routeIs('siswa.materi.*')],
                    ['label' => 'Chat', 'icon' => 'chat', 'url' => route('siswa.chat.index'), 'active' => request()->routeIs('siswa.chat.*') || request()->routeIs('siswa.group-chat.*'), 'badge' => $siswaChatBadge],
                ],
            ],
            [
                'label' => 'Belajar dan aktivitas',
                'items' => [
                    ['label' => 'Jurnal RPP', 'icon' => 'journal', 'url' => route('siswa.materi-rpp-journals.index'), 'active' => request()->routeIs('siswa.materi-rpp-journals.*'), 'badge' => $siswaJournalBadge],
                    ['label' => 'Kehadiran', 'icon' => 'attendance', 'url' => route('siswa.kehadiran.index'), 'active' => request()->routeIs('siswa.kehadiran.*')],
                    ['label' => "Bacaan Al-Qur'an", 'icon' => 'book', 'url' => route('siswa.quran.index'), 'active' => request()->routeIs('siswa.quran.*')],
                    ['label' => 'Game 29 Karakter', 'icon' => 'game', 'url' => route('siswa.game.index'), 'active' => request()->routeIs('siswa.game.*')],
                    ['label' => 'Poin & Peringkat', 'icon' => 'game', 'url' => route('siswa.gamification.dashboard'), 'active' => request()->routeIs('siswa.gamification.*')],
                    ['label' => 'Petualangan', 'icon' => 'rpg', 'url' => route('siswa.rpg.index'), 'active' => request()->routeIs('siswa.rpg.*')],
                ],
            ],
            [
                'label' => 'Akun siswa',
                'items' => [
                    ['label' => 'Profil dan Foto', 'icon' => 'user', 'url' => route('siswa.profile'), 'active' => request()->routeIs('siswa.profile')],
                    ['label' => 'Kartu Siswa', 'icon' => 'card', 'url' => route('siswa.kartu'), 'active' => request()->routeIs('siswa.kartu')],
                    ['label' => 'Cetak Kartu', 'icon' => 'print', 'url' => route('siswa.kartu.print'), 'active' => request()->routeIs('siswa.kartu.print'), 'target' => '_blank'],
                    ['label' => 'Biometrik', 'icon' => 'fingerprint', 'url' => route('siswa.biometrik'), 'active' => request()->routeIs('siswa.biometrik')],
                ],
            ],
        ],
        'push' => [
            'subscribe_url' => route('siswa.pwa.push-subscriptions.store'),
            'unsubscribe_url' => route('siswa.pwa.push-subscriptions.destroy'),
            'badge_count' => $siswaTaskBadge,
        ],
        'logout_url' => route('siswa.logout'),
    ];
@endphp

<div class="pkg-portal-shell flex overflow-hidden">
    <div x-show="!sidebarCollapsed" @click="sidebarCollapsed = true" class="pkg-sidebar-overlay fixed inset-0 z-40 bg-black/50 lg:hidden" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <aside x-show="!sidebarCollapsed"
           x-transition:enter="transition-transform ease-out duration-200"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition-transform ease-in duration-150"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="pkg-sidebar fixed inset-y-0 left-0 z-50 hidden w-64 flex-col border-r will-change-transform lg:relative lg:flex">
        <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4 dark:border-gray-700">
            <a href="{{ route('siswa.dashboard') }}" class="flex min-w-0 items-center">
                @if(!empty($siteSettings['site_logo']))
                    <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo" width="32" height="32" class="h-8 w-8 flex-shrink-0 object-contain" style="width:2rem;height:2rem;object-fit:contain;" decoding="async" fetchpriority="high">
                @else
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white">P</span>
                @endif
                <span class="logo-text ml-3 truncate text-lg font-bold text-gray-900 dark:text-white">{{ $siteSettings['site_title'] ?? 'PKG' }}</span>
            </a>
            <button type="button" @click="sidebarCollapsed = true" class="rounded-md p-2 text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
            </button>
        </div>

        @if($currentSiswa)
            <div class="border-b border-gray-200 bg-blue-50 px-4 py-3 dark:border-gray-700 dark:bg-blue-900/30">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-600 text-sm font-bold text-white">
                        @if($currentSiswa->foto_path)
                            <img src="{{ asset('storage/' . $currentSiswa->foto_path) }}" alt="{{ $currentSiswa->nama }}" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr($currentSiswa->nama ?? 'S', 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $currentSiswa->nama }}</p>
                        @if($currentSiswa->isGraduated())<span class="mt-1 inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800 dark:bg-sky-950/60 dark:text-sky-200">Alumni</span>@endif
                        <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ $currentSiswa->school_grade_label }} | {{ $currentSiswa->nis }}</p>
                    </div>
                </div>
            </div>
        @endif

        <nav class="flex-1 overflow-y-auto px-2 py-4" @click="if($event.target.closest('a[href]') && window.innerWidth < 1024) sidebarCollapsed = true">
            <div class="space-y-1">
                <a href="{{ route('siswa.dashboard') }}" class="nav-item @if(request()->routeIs('siswa.dashboard')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="{{ route('siswa.tugas-pkg.index') }}" class="nav-item @if(request()->routeIs('siswa.tugas-pkg.*') || request()->routeIs('siswa.karakter.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="nav-text">Tugas PKG</span>
                    @if(($siswaSidebarPendingTaskCount ?? 0) > 0)
                        <span class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                            {{ ($siswaSidebarPendingTaskCount ?? 0) > 99 ? '99+' : $siswaSidebarPendingTaskCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('siswa.materi.index') }}" class="nav-item @if(request()->routeIs('siswa.materi.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span class="nav-text">Materi</span>
                </a>
                <a href="{{ route('siswa.materi-rpp-journals.index') }}" class="nav-item @if(request()->routeIs('siswa.materi-rpp-journals.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z"/></svg>
                    <span class="nav-text">Jurnal RPP</span>
                    @if(($siswaSidebarPendingJournalCount ?? 0) > 0)
                        <span class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                            {{ ($siswaSidebarPendingJournalCount ?? 0) > 99 ? '99+' : $siswaSidebarPendingJournalCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('siswa.calendar.index') }}" class="nav-item @if(request()->routeIs('siswa.calendar.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="nav-text">Kalender</span>
                </a>
                <a href="{{ route('siswa.kehadiran.index') }}" class="nav-item @if(request()->routeIs('siswa.kehadiran.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h13M9 7h13M5 7h.01M5 17h.01"/></svg>
                    <span class="nav-text">Kehadiran</span>
                </a>
                <a href="{{ route('siswa.quran.index') }}" class="nav-item @if(request()->routeIs('siswa.quran.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span class="nav-text">Bacaan Al-Qur'an</span>
                </a>
                <a href="{{ route('siswa.chat.index') }}" class="nav-item @if(request()->routeIs('siswa.chat.*') || request()->routeIs('siswa.group-chat.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span class="nav-text">Chat</span>
                </a>

                <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                @php
                    $siswaExtrasActive = request()->routeIs('siswa.gamification.*')
                        || request()->routeIs('siswa.rpg.*')
                        || request()->routeIs('siswa.biometrik');
                @endphp
                <!-- Lainnya (opsional): Poin, Game, Biometrik -->
                <div x-data="{ open: {{ $siswaExtrasActive ? 'true' : 'false' }} }" class="space-y-1">
                    <button type="button" @click="open = !open" class="nav-item {{ $siswaExtrasActive ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }} flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                        <span class="flex items-center">
                            <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                            <span class="nav-text">Lainnya</span>
                        </span>
                        <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-transition x-cloak class="space-y-1 pl-3">
                        <a href="{{ route('siswa.game.index') }}" class="nav-item @if(request()->routeIs('siswa.game.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                            <span class="nav-text">Game 29 Karakter</span>
                        </a>
                        <a href="{{ route('siswa.gamification.dashboard') }}" class="nav-item @if(request()->routeIs('siswa.gamification.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                            <span class="nav-text">Poin &amp; Peringkat</span>
                        </a>
                        <a href="{{ route('siswa.rpg.index') }}" class="nav-item @if(request()->routeIs('siswa.rpg.*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                            <span class="nav-text">Petualangan</span>
                        </a>
                        <a href="{{ route('siswa.biometrik') }}" class="nav-item @if(request()->routeIs('siswa.biometrik')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                            <span class="nav-text">Biometrik</span>
                        </a>
                    </div>
                </div>

                <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                <a href="{{ route('siswa.profile') }}" class="nav-item @if(request()->routeIs('siswa.profile') || request()->routeIs('siswa.kartu*')) bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 @else text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 @endif flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="nav-text">Profil</span>
                </a>
            </div>
        </nav>

        <div class="border-t border-gray-200 p-3 dark:border-gray-700">
            <form method="POST" action="{{ route('siswa.logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span class="nav-text">Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        @include('layouts.partials.portal-mobile-navigation')

        <header class="pkg-topbar sticky top-0 z-30 hidden h-16 items-center justify-between border-b px-4 sm:px-6 lg:flex">
            <div class="flex min-w-0 items-center">
                <button type="button" @click="sidebarCollapsed = !sidebarCollapsed" class="mr-3 rounded-md p-2 text-gray-500 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="truncate text-lg font-semibold text-gray-900 dark:text-white">@yield('title', 'Portal Siswa')</h2>
            </div>
            <div class="flex items-center gap-2">
                @if($currentSiswa)
                    <x-pwa-push-control
                        :subscribe-url="route('siswa.pwa.push-subscriptions.store')"
                        :unsubscribe-url="route('siswa.pwa.push-subscriptions.destroy')"
                        :badge-count="$siswaSidebarPendingTaskCount ?? 0"
                    />
                @endif

                <button type="button" @click="toggleDarkMode()" class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700" title="Mode Terang/Gelap">
                    <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="darkMode" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                @if($currentSiswa)
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-lg p-1.5 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700" aria-haspopup="true" :aria-expanded="open.toString()">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-600 text-sm font-semibold text-white">
                                @if($currentSiswa->foto_path)
                                    <img src="{{ asset('storage/' . $currentSiswa->foto_path) }}" alt="{{ $currentSiswa->nama }}" class="h-full w-full object-cover">
                                @else
                                    {{ strtoupper(substr($currentSiswa->nama ?? 'S', 0, 1)) }}
                                @endif
                            </span>
                            <span class="hidden max-w-[10rem] truncate text-sm font-medium text-gray-700 dark:text-gray-300 sm:block">{{ $currentSiswa->nama }}</span>
                            <svg class="hidden h-4 w-4 text-gray-400 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition x-cloak class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $currentSiswa->nama }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $currentSiswa->school_grade_label }} | {{ $currentSiswa->nis }}</p>
                            </div>
                            <a href="{{ route('siswa.profile') }}" class="block px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">Profil dan Foto</a>
                            <a href="{{ route('siswa.kartu') }}" class="block px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">Kartu Siswa dan Unduh</a>
                            <a href="{{ route('siswa.kartu.print') }}" target="_blank" rel="noopener" class="block px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">Mode Print KTP</a>
                            <form method="POST" action="{{ route('siswa.logout') }}" class="border-t border-gray-200 dark:border-gray-700">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">Keluar</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </header>

        <main class="pkg-portal-mobile-main min-w-0 max-w-full flex-1 overflow-x-hidden overflow-y-auto">
            @if(session('success'))
                <div class="mx-4 mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300 sm:mx-6">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 sm:mx-6">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
@include('components.profile-assignment-prompt')
@include('components.face-enrollment-prompt')
@include('components.biometric-prompt')
</body>
</html>
