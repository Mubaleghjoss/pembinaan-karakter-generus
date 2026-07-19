<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('layouts.partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $theme->app_name ?? 'PKG Presensi')</title>
    <meta name="description" content="{{ $theme->app_description ?? 'Sistem Presensi QR Code - Pembinaan Karakter Generus' }}">
    
    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', $theme->app_name ?? 'PKG Presensi')">
    <meta property="og:description" content="@yield('og_description', $theme->app_description ?? 'Sistem Presensi QR Code - Pembinaan Karakter Generus')">
    @if($theme->logo_path)
    <meta property="og:image" content="{{ asset('storage/' . $theme->logo_path) }}">
    @else
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    @endif
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="{{ $theme->app_name ?? 'PKG Presensi' }}">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', $theme->app_name ?? 'PKG Presensi')">
    <meta name="twitter:description" content="@yield('og_description', $theme->app_description ?? 'Sistem Presensi QR Code - Pembinaan Karakter Generus')">
    @if($theme->logo_path)
    <meta name="twitter:image" content="{{ asset('storage/' . $theme->logo_path) }}">
    @endif
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="{{ $theme->primary_color ?? '#667EEA' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $theme->app_name ?? 'PKG Presensi' }}">
    @php
        $manifestVersion = is_file(public_path('manifest.json')) ? filemtime(public_path('manifest.json')) : null;
    @endphp
    <link rel="manifest" href="{{ asset('manifest.json') }}{{ $manifestVersion ? '?v=' . $manifestVersion : '' }}">
    @include('layouts.partials.favicons')
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Styles & Scripts -->
    @viteReactRefresh
    @vite(['resources/js/app.js'])
    @include('layouts.partials.theme-styles')
    
    <style>
        html {
            color-scheme: light;
        }

        html.dark {
            color-scheme: dark;
        }

        :root {
            @foreach($theme->getCssVariables() as $key => $value)
            {{ $key }}: {{ $value }};
            @endforeach
        }
        
        .gradient-primary {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
        }
        
        .gradient-accent {
            background: linear-gradient(135deg, var(--color-accent) 0%, var(--color-warning) 100%);
        }
        
        .text-primary { color: var(--color-primary); }
        .text-secondary { color: var(--color-secondary); }
        .bg-primary { background-color: var(--color-primary); }
        .bg-secondary { background-color: var(--color-secondary); }
        
        .hover-lift {
            transition: transform var(--pkg-motion-fast, 160ms) var(--pkg-motion-enter, cubic-bezier(0.2, 0, 0, 1)), box-shadow var(--pkg-motion-fast, 160ms) var(--pkg-motion-enter, cubic-bezier(0.2, 0, 0, 1));
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
        }

        .pkg-public-shell {
            position: relative;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            background:
                radial-gradient(circle at top left, color-mix(in srgb, var(--color-primary, #0f766e) 16%, transparent), transparent 26%),
                radial-gradient(circle at top right, color-mix(in srgb, var(--color-secondary, #0369a1) 14%, transparent), transparent 22%),
                linear-gradient(180deg, color-mix(in srgb, var(--pkg-bg-top, #f8fafc) 92%, white), var(--pkg-bg-base, #f8fafc) 42%, var(--pkg-bg-bottom, #edf5ff) 100%);
        }

        .pkg-public-shell::before,
        .pkg-public-shell::after {
            content: "";
            position: fixed;
            inset: auto;
            width: 18rem;
            height: 18rem;
            border-radius: 9999px;
            filter: blur(80px);
            opacity: 0.22;
            pointer-events: none;
            z-index: 0;
        }

        .pkg-public-shell::before {
            top: 3rem;
            left: -4rem;
            background: var(--color-primary, #0f766e);
        }

        .pkg-public-shell::after {
            right: -5rem;
            bottom: 8rem;
            background: var(--color-secondary, #0369a1);
        }

        .pkg-public-nav {
            background: var(--pkg-public-nav-bg, color-mix(in srgb, var(--color-primary, #0f766e) 78%, rgba(7, 13, 31, 0.92)));
            backdrop-filter: blur(18px);
            border-bottom: 1px solid color-mix(in srgb, var(--pkg-border, rgba(148, 163, 184, 0.24)) 82%, transparent);
        }

        .pkg-public-footer {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(13, 148, 136, 0.18), transparent 24%),
                radial-gradient(circle at right center, rgba(14, 165, 233, 0.16), transparent 20%),
                linear-gradient(180deg, rgba(2, 6, 23, 0.98), rgba(15, 23, 42, 1));
        }

        .pkg-theme-toggle {
            border: 1px solid color-mix(in srgb, var(--pkg-border, rgba(148, 163, 184, 0.24)) 90%, transparent);
            background: color-mix(in srgb, var(--pkg-shell, rgba(255, 255, 255, 0.84)) 92%, transparent);
            color: var(--pkg-public-nav-text, #0f172a);
            box-shadow: var(--pkg-shadow-soft, 0 18px 46px rgba(15, 23, 42, 0.08));
        }

        .pkg-theme-toggle:hover {
            color: var(--pkg-brand, #0f766e);
            border-color: color-mix(in srgb, var(--pkg-brand, #0f766e) 28%, transparent);
            transform: translateY(-1px);
        }

        .pkg-brand-logo-motion {
            animation: pkgLogoFloat 6.8s ease-in-out infinite;
            transform-origin: center;
            will-change: transform;
        }

        .pkg-brand-title-motion {
            animation: pkgTitleGlow 6s ease-in-out infinite;
            text-shadow: 0 0 0 rgba(255, 255, 255, 0);
        }

        .pkg-pwa-launch-splash {
            align-items: center;
            background:
                radial-gradient(circle at 50% 24%, rgba(217, 180, 90, 0.25), transparent 18rem),
                linear-gradient(135deg, #0b4229, #10643a);
            display: none;
            inset: 0;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            position: fixed;
            transition: opacity 280ms ease;
            z-index: 9999;
        }

        .pkg-pwa-launch-splash.is-active {
            display: flex;
            opacity: 1;
        }

        .pkg-pwa-launch-card {
            align-items: center;
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding: 24px;
            text-align: center;
            width: min(100%, 22rem);
        }

        .pkg-pwa-launch-logo {
            animation: pkgSplashLogo 1.35s cubic-bezier(0.2, 0.8, 0.2, 1) infinite;
            background: #f8f6e8;
            border-radius: 9999px;
            box-shadow: 0 22px 58px rgba(0, 0, 0, 0.28);
            height: 124px;
            object-fit: contain;
            padding: 0;
            width: 124px;
        }

        .pkg-pwa-launch-title {
            color: #fff7db;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        @keyframes pkgLogoFloat {
            0%, 100% {
                transform: translateY(0) rotate(0deg) scale(1);
            }
            50% {
                transform: translateY(-2px) rotate(-0.8deg) scale(1.01);
            }
        }

        @keyframes pkgTitleGlow {
            0%, 100% {
                text-shadow: 0 0 0 rgba(255, 255, 255, 0);
            }
            50% {
                text-shadow: 0 0 8px rgba(255, 255, 255, 0.14);
            }
        }

        @keyframes pkgSplashLogo {
            0%, 100% {
                transform: scale(0.98);
            }
            50% {
                transform: scale(1.06);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .pkg-brand-logo-motion,
            .pkg-brand-title-motion,
            .pkg-pwa-launch-logo {
                animation: none;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="pkg-public-shell h-full text-slate-900 dark:text-slate-100">
    <div id="pkg-pwa-launch-splash" class="pkg-pwa-launch-splash" aria-hidden="true">
        <div class="pkg-pwa-launch-card">
            <img class="pkg-pwa-launch-logo" src="{{ asset('images/icons/pkg-logo-192.png') }}" alt="Logo PKG" width="124" height="124">
            <div class="pkg-pwa-launch-title">Pembinaan Karakter Generus Panunggangan</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav id="public-navigation" class="pkg-public-nav sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex min-h-[4.5rem] items-center justify-between gap-3 py-3 sm:h-20 sm:py-0">
                <div class="pkg-nav-brand flex min-w-0 items-center gap-3 sm:gap-4">
                    @if($theme->logo_path)
                        <img src="{{ asset('storage/' . $theme->logo_path) }}" alt="Logo" width="48" height="48" class="pkg-brand-logo-motion h-10 w-10 shrink-0 object-contain sm:h-12 sm:w-12" style="width:3rem;height:3rem;object-fit:contain;" decoding="async" fetchpriority="high">
                    @endif
                    <div class="min-w-0">
                        <h1 class="pkg-brand-title-motion pkg-nav-brand-title truncate text-base font-bold leading-tight sm:text-lg lg:text-2xl" style="color: var(--pkg-public-nav-text, #0f172a);">{{ $theme->app_name }}</h1>
                        <p class="pkg-nav-brand-copy pkg-public-nav-copy mt-1 max-w-[14rem] text-[11px] leading-tight sm:max-w-[20rem] sm:text-xs lg:max-w-[28rem] lg:text-sm">{{ $theme->app_description }}</p>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-4 lg:space-x-6">
                    <a href="{{ route('public.index') }}" class="pkg-public-nav-link font-medium transition-colors">
                        Beranda
                    </a>
                    <a href="{{ route('public.rpg.index') }}" class="pkg-public-nav-link font-medium transition-colors">
                        Game 29 Karakter
                    </a>
                    <a href="{{ route('public.calendar.index') }}" class="pkg-public-nav-link font-medium transition-colors">
                        Kalender
                    </a>
                    <a href="{{ route('materi.index') }}" class="pkg-public-nav-link font-medium transition-colors">
                        Materi
                    </a>
                    <a href="{{ route('public.scanner') }}" class="pkg-public-nav-link font-medium transition-colors">
                        Scan Presensi
                    </a>
                    <a href="{{ route('laporan-penyaksian.create') }}" class="pkg-public-nav-link flex items-center font-medium transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Lapor PKG
                    </a>
                    <button id="public-theme-toggle" type="button" class="pkg-theme-toggle inline-flex items-center justify-center gap-2 rounded-full px-3 py-2 text-sm font-semibold transition" aria-pressed="false">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
                        </svg>
                        <span class="pkg-theme-toggle-label">Mode Gelap</span>
                    </button>
                    
                    {{-- PWA Install Button (Desktop) --}}
                    <button id="pwa-install-btn" style="display: none;" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full font-bold transition-all shadow-lg flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Install App
                    </button>

                    @if(Auth::guard('siswa')->check())
                        <a href="{{ route('siswa.dashboard') }}" class="bg-white text-primary px-6 py-2 rounded-full font-bold hover:bg-white/90 transition-all shadow-lg flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Kembali ke Dashboard
                        </a>
                    @elseif(Auth::guard('web')->check())
                        <a href="{{ route('dashboard') }}" class="bg-white text-primary px-6 py-2 rounded-full font-bold hover:bg-white/90 transition-all shadow-lg flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Kembali ke Dashboard
                        </a>
                    @elseif(Auth::guard('ortu')->check())
                        <a href="{{ route('ortu.dashboard') }}" class="bg-white text-primary px-6 py-2 rounded-full font-bold hover:bg-white/90 transition-all shadow-lg flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Kembali ke Dashboard
                        </a>
                    @else
                        <div class="relative" id="login-dropdown">
                            <button id="login-btn" class="bg-white text-primary px-6 py-2.5 rounded-full font-bold hover:bg-white/90 transition-all shadow-lg flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                Login
                                <svg class="w-3 h-3 transition-transform duration-200" id="login-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="login-menu" class="absolute right-0 mt-2 w-56 rounded-xl shadow-2xl border overflow-hidden opacity-0 invisible transition-all duration-200 transform -translate-y-2 bg-white border-gray-100 dark:bg-slate-950 dark:border-slate-800" style="z-index:99">
                                <a href="{{ route('ortu.login') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-teal-50 dark:hover:bg-teal-950/40 transition-colors group">
                                    <span class="w-9 h-9 rounded-lg bg-teal-100 text-teal-600 flex items-center justify-center group-hover:bg-teal-200 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-800 dark:text-slate-100 text-sm">Orang Tua</div>
                                        <div class="text-xs text-gray-400 dark:text-slate-400">Pantau anak Anda</div>
                                    </div>
                                </a>
                                <a href="{{ route('siswa.login') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition-colors group border-t border-gray-50 dark:border-slate-900">
                                    <span class="w-9 h-9 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-800 dark:text-slate-100 text-sm">Siswa</div>
                                        <div class="text-xs text-gray-400 dark:text-slate-400">Dashboard siswa</div>
                                    </div>
                                </a>
                                <a href="{{ route('login') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition-colors group border-t border-gray-50 dark:border-slate-900">
                                    <span class="w-9 h-9 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-800 dark:text-slate-100 text-sm">Pamong / Admin</div>
                                        <div class="text-xs text-gray-400 dark:text-slate-400">Panel pengelola</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-toggle" type="button" class="pkg-mobile-menu-toggle md:hidden" aria-expanded="false" aria-controls="mobile-menu" aria-label="Buka menu navigasi">
                    <svg class="pkg-menu-open-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg class="pkg-menu-close-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span class="pkg-menu-label">Menu</span>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <button id="mobile-menu-overlay" type="button" class="pkg-mobile-menu-overlay md:hidden" aria-label="Tutup menu navigasi" aria-hidden="true" tabindex="-1"></button>
        <aside id="mobile-menu" class="pkg-mobile-menu-shell md:hidden" aria-hidden="true" aria-label="Navigasi mobile" tabindex="-1" inert>
            <div class="pkg-mobile-menu-panel-header">
                <div class="pkg-mobile-menu-panel-brand">
                    @if($theme->logo_path)
                        <img src="{{ asset('storage/' . $theme->logo_path) }}" alt="" width="44" height="44" class="pkg-mobile-menu-panel-logo">
                    @endif
                    <div class="min-w-0">
                        <p class="pkg-mobile-menu-eyebrow text-[10px] font-bold uppercase tracking-[0.2em]">Menu Utama</p>
                        <h2 class="truncate text-base font-extrabold text-slate-900 dark:text-white">{{ $theme->app_name }}</h2>
                    </div>
                </div>
                <button id="mobile-menu-close" type="button" class="pkg-mobile-menu-close" aria-label="Tutup menu navigasi">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="pkg-mobile-menu-scroll">
                <div class="pkg-mobile-menu-card space-y-3 p-3">
                    <a href="{{ route('public.index') }}" class="pkg-mobile-menu-link {{ request()->routeIs('public.index') ? 'is-active' : '' }}" @if(request()->routeIs('public.index')) aria-current="page" @endif>
                        <span class="pkg-mobile-menu-icon">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/></svg>
                        </span>
                        <span class="pkg-mobile-menu-text">Beranda</span>
                    </a>
                    <a href="{{ route('public.rpg.index') }}" class="pkg-mobile-menu-link {{ request()->routeIs('public.rpg.*') ? 'is-active' : '' }}" @if(request()->routeIs('public.rpg.*')) aria-current="page" @endif>
                        <span class="pkg-mobile-menu-icon">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.75h4.5l1.5 3H18a2.25 2.25 0 012.25 2.25v6A2.25 2.25 0 0118 17.25h-1.5l-1.5 3h-6l-1.5-3H6A2.25 2.25 0 013.75 15v-6A2.25 2.25 0 016 6.75h2.25l1.5-3z"/></svg>
                        </span>
                        <span class="pkg-mobile-menu-text">Game 29 Karakter</span>
                    </a>
                    <a href="{{ route('public.calendar.index') }}" class="pkg-mobile-menu-link {{ request()->routeIs('public.calendar.*') ? 'is-active' : '' }}" @if(request()->routeIs('public.calendar.*')) aria-current="page" @endif>
                        <span class="pkg-mobile-menu-icon">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                        <span class="pkg-mobile-menu-text">Kalender</span>
                    </a>
                    <a href="{{ route('materi.index') }}" class="pkg-mobile-menu-link {{ request()->routeIs('materi.*') ? 'is-active' : '' }}" @if(request()->routeIs('materi.*')) aria-current="page" @endif>
                        <span class="pkg-mobile-menu-icon">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/></svg>
                        </span>
                        <span class="pkg-mobile-menu-text">Materi</span>
                    </a>
                    <a href="{{ route('public.scanner') }}" class="pkg-mobile-menu-link {{ request()->routeIs('public.scanner') ? 'is-active' : '' }}" @if(request()->routeIs('public.scanner')) aria-current="page" @endif>
                        <span class="pkg-mobile-menu-icon">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        </span>
                        <span class="pkg-mobile-menu-text">Scan Presensi</span>
                    </a>
                    <a href="{{ route('laporan-penyaksian.create') }}" class="pkg-mobile-menu-link {{ request()->routeIs('laporan-penyaksian.*') ? 'is-active' : '' }}" @if(request()->routeIs('laporan-penyaksian.*')) aria-current="page" @endif>
                        <span class="pkg-mobile-menu-icon">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <span class="pkg-mobile-menu-text">Lapor PKG</span>
                    </a>
                    <button id="public-theme-toggle-mobile" type="button" class="pkg-theme-toggle flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition" aria-pressed="false">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
                        </svg>
                        <span class="pkg-theme-toggle-label">Mode Gelap</span>
                    </button>
                @if(Auth::guard('siswa')->check())
                    <a href="{{ route('siswa.dashboard') }}" class="block bg-white text-primary px-4 py-2 rounded-lg font-bold text-center flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali ke Dashboard
                    </a>
                @elseif(Auth::guard('web')->check())
                    <a href="{{ route('dashboard') }}" class="block bg-white text-primary px-4 py-2 rounded-lg font-bold text-center flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali ke Dashboard
                    </a>
                @elseif(Auth::guard('ortu')->check())
                    <a href="{{ route('ortu.dashboard') }}" class="block bg-white text-primary px-4 py-2 rounded-lg font-bold text-center flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali ke Dashboard
                    </a>
                @else
                    <div class="pkg-mobile-menu-divider space-y-2 border-t pt-2">
                        <p class="pkg-mobile-menu-eyebrow text-xs font-medium uppercase tracking-wider">Login Sebagai</p>
                        <a href="{{ route('ortu.login') }}" class="pkg-mobile-login-link flex items-center gap-3 rounded-lg px-3 py-2 transition-colors">
                            <span class="w-8 h-8 rounded-lg bg-teal-500/30 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                            <span class="font-medium">Orang Tua</span>
                        </a>
                        <a href="{{ route('siswa.login') }}" class="pkg-mobile-login-link flex items-center gap-3 rounded-lg px-3 py-2 transition-colors">
                            <span class="w-8 h-8 rounded-lg bg-blue-500/30 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <span class="font-medium">Siswa</span>
                        </a>
                        <a href="{{ route('login') }}" class="pkg-mobile-login-link flex items-center gap-3 rounded-lg px-3 py-2 transition-colors">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/30 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </span>
                            <span class="font-medium">Pamong / Admin</span>
                        </a>
                    </div>
                @endif
                
                {{-- PWA Install Button (Mobile) --}}
                <button id="pwa-install-btn-mobile" style="display: none;" class="block w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2.5 rounded-lg font-bold transition-all shadow-lg flex items-center justify-center gap-2 text-sm mt-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Install Aplikasi
                </button>
                </div>
            </div>
        </aside>
    </nav>

    <!-- Main Content -->
    <main class="relative z-10 min-h-screen min-w-0 w-full max-w-full">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="pkg-public-footer text-white py-12 mt-10 md:mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">{{ $theme->app_name }}</h3>
                    <p class="text-gray-400">{{ $theme->footer_text ?? $theme->app_description }}</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Menu</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('public.index') }}" class="text-gray-400 hover:text-white transition-colors">Beranda</a></li>
                        <li><a href="{{ route('public.rpg.index') }}" class="text-gray-400 hover:text-white transition-colors">Game 29 Karakter</a></li>
                        <li><a href="{{ route('public.calendar.index') }}" class="text-gray-400 hover:text-white transition-colors">Kalender</a></li>
                        <li><a href="{{ route('public.scanner') }}" class="text-gray-400 hover:text-white transition-colors">Scan Presensi</a></li>
                        <li><a href="{{ route('laporan-penyaksian.create') }}" class="text-gray-400 hover:text-white transition-colors">Lapor PKG</a></li>
                        @if(Auth::guard('siswa')->check())
                            <li><a href="{{ route('siswa.dashboard') }}" class="text-gray-400 hover:text-white transition-colors">Dashboard Siswa</a></li>
                        @elseif(Auth::guard('web')->check())
                            <li><a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white transition-colors">Dashboard Pamong</a></li>
                        @elseif(Auth::guard('ortu')->check())
                            <li><a href="{{ route('ortu.dashboard') }}" class="text-gray-400 hover:text-white transition-colors">Dashboard Ortu</a></li>
                        @else
                            <li><a href="{{ route('ortu.login') }}" class="text-gray-400 hover:text-white transition-colors">Login Orang Tua</a></li>
                            <li><a href="{{ route('siswa.login') }}" class="text-gray-400 hover:text-white transition-colors">Login Siswa</a></li>
                            <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition-colors">Login Pamong</a></li>
                        @endif
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Kontak</h3>
                    <p class="text-gray-400">{{ $theme->footer_organization ?? 'SMA AFBS' }}</p>
                    @if($theme->footer_address)
                    <p class="text-gray-400 text-sm mt-1">{{ $theme->footer_address }}</p>
                    @endif
                    @if($theme->footer_phone)
                    <p class="text-gray-400 text-sm mt-2 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $theme->footer_phone }}
                    </p>
                    @endif
                    @if($theme->footer_email)
                    <p class="text-gray-400 text-sm mt-1 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ $theme->footer_email }}
                    </p>
                    @endif
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} {{ $theme->app_name }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    {{-- Floating PWA Install Banner (Mobile) --}}
    <div id="pwa-install-banner" style="display: none;" class="fixed bottom-0 left-0 right-0 z-50 md:hidden">
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-4 py-3 shadow-2xl">
            <div class="flex items-center justify-between max-w-lg mx-auto">
                <div class="flex items-center gap-3">
                    @if($theme->logo_path)
                    <img src="{{ asset('storage/' . $theme->logo_path) }}" alt="Logo" width="40" height="40" class="w-10 h-10 rounded-xl shadow object-contain" style="width:2.5rem;height:2.5rem;object-fit:contain;">
                    @else
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    @endif
                    <div>
                        <p class="font-bold text-sm">Install {{ $theme->app_name ?? 'PKG' }}</p>
                        <p class="text-xs text-white/80">Akses lebih cepat dari homescreen</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="pwa-banner-install" class="bg-white text-green-700 px-4 py-1.5 rounded-full text-xs font-bold hover:bg-green-50 transition-colors shadow">
                        Install
                    </button>
                    <button id="pwa-banner-close" class="text-white/70 hover:text-white p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setMobileMenu(open) {
            const menu = document.getElementById('mobile-menu');
            const toggle = document.getElementById('mobile-menu-toggle');
            const overlay = document.getElementById('mobile-menu-overlay');
            if (!menu || !toggle || !overlay) return;

            menu.classList.toggle('is-open', open);
            overlay.classList.toggle('is-open', open);
            toggle.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
            menu.setAttribute('aria-hidden', String(!open));
            overlay.setAttribute('aria-hidden', String(!open));
            menu.inert = !open;
            document.documentElement.classList.toggle('overflow-hidden', open);

            if (open) {
                window.requestAnimationFrame(() => document.getElementById('mobile-menu-close')?.focus());
            }
        }

        function toggleMobileMenu() {
            const toggle = document.getElementById('mobile-menu-toggle');
            setMobileMenu(toggle?.getAttribute('aria-expanded') !== 'true');
        }

        document.getElementById('mobile-menu-toggle')?.addEventListener('click', toggleMobileMenu);
        document.getElementById('mobile-menu-close')?.addEventListener('click', () => {
            setMobileMenu(false);
            document.getElementById('mobile-menu-toggle')?.focus();
        });
        document.getElementById('mobile-menu-overlay')?.addEventListener('click', () => setMobileMenu(false));
        document.getElementById('mobile-menu')?.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', () => setMobileMenu(false));
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setMobileMenu(false);
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) setMobileMenu(false);
        });

        const publicNavigation = document.getElementById('public-navigation');
        const syncPublicNavigation = () => publicNavigation?.classList.toggle('is-scrolled', window.scrollY > 12);
        window.addEventListener('scroll', syncPublicNavigation, { passive: true });
        syncPublicNavigation();

        function syncPublicThemeToggles() {
            const isDark = window.pkgTheme && window.pkgTheme.get ? window.pkgTheme.get() : document.documentElement.classList.contains('dark');
            document.querySelectorAll('#public-theme-toggle, #public-theme-toggle-mobile').forEach((button) => {
                const label = button.querySelector('.pkg-theme-toggle-label');
                if (label) {
                    label.textContent = isDark ? 'Mode Terang' : 'Mode Gelap';
                }
                button.setAttribute('aria-pressed', String(isDark));
            });
        }

        document.querySelectorAll('#public-theme-toggle, #public-theme-toggle-mobile').forEach((button) => {
            button.addEventListener('click', function () {
                if (window.pkgTheme && typeof window.pkgTheme.toggle === 'function') {
                    window.pkgTheme.toggle();
                }
            });
        });

        window.addEventListener('pkg:theme-change', syncPublicThemeToggles);
        syncPublicThemeToggles();

        // PWA launch splash shown after opening installed app.
        (function () {
            var splash = document.getElementById('pkg-pwa-launch-splash');
            if (!splash) return;

            var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            var isHomeLaunch = window.location.pathname === '/' || window.location.pathname === '/index.php';
            var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!isStandalone || !isHomeLaunch) {
                return;
            }

            splash.style.display = 'flex';

            requestAnimationFrame(function () {
                splash.classList.add('is-active');
            });

            window.setTimeout(function () {
                splash.classList.remove('is-active');
                window.setTimeout(function () {
                    splash.style.display = 'none';
                }, 300);
            }, reducedMotion ? 700 : 1500);
        })();

        // Login dropdown toggle
        (function() {
            var btn = document.getElementById('login-btn');
            if (!btn) return;
            var menu = document.getElementById('login-menu');
            var chevron = document.getElementById('login-chevron');
            var isOpen = false;

            function openMenu() {
                isOpen = true;
                menu.classList.remove('opacity-0', 'invisible', '-translate-y-2');
                menu.classList.add('opacity-100', 'visible', 'translate-y-0');
                chevron.classList.add('rotate-180');
            }

            function closeMenu() {
                isOpen = false;
                menu.classList.add('opacity-0', 'invisible', '-translate-y-2');
                menu.classList.remove('opacity-100', 'visible', 'translate-y-0');
                chevron.classList.remove('rotate-180');
            }

            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (isOpen) { closeMenu(); } else { openMenu(); }
            });

            document.addEventListener('click', function(e) {
                if (isOpen && !menu.contains(e.target)) {
                    closeMenu();
                }
            });
        })();
        
        // PWA Install Prompt
        let deferredPrompt;
        
        function triggerInstall() {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted install');
                }
                deferredPrompt = null;
                hideAllInstallButtons();
            });
        }
        
        function hideAllInstallButtons() {
            ['pwa-install-btn', 'pwa-install-btn-mobile', 'pwa-install-banner'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
        }
        
        function showAllInstallButtons() {
            const desktopBtn = document.getElementById('pwa-install-btn');
            const mobileBtn = document.getElementById('pwa-install-btn-mobile');
            const banner = document.getElementById('pwa-install-banner');
            
            if (desktopBtn) desktopBtn.style.display = 'flex';
            if (mobileBtn) mobileBtn.style.display = 'flex';
            
            // Show floating banner on mobile after 2 seconds
            if (banner && !sessionStorage.getItem('pwa-banner-dismissed')) {
                setTimeout(() => {
                    banner.style.display = 'block';
                }, 2000);
            }
        }
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showAllInstallButtons();
        });
        
        // Bind click events to all install buttons
        ['pwa-install-btn', 'pwa-install-btn-mobile', 'pwa-banner-install'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', triggerInstall);
        });
        
        // Close banner button
        const bannerClose = document.getElementById('pwa-banner-close');
        if (bannerClose) {
            bannerClose.addEventListener('click', () => {
                const banner = document.getElementById('pwa-install-banner');
                if (banner) banner.style.display = 'none';
                sessionStorage.setItem('pwa-banner-dismissed', '1');
            });
        }
        
        // Hide install buttons if app is already installed
        window.addEventListener('appinstalled', () => {
            console.log('App installed successfully');
            hideAllInstallButtons();
        });
    </script>
    
    @stack('scripts')
</body>
</html>
