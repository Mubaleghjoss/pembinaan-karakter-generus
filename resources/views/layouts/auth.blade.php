@php
    $authAccent = trim($__env->yieldContent('auth_accent', '#0f766e'));
    $authAccentSecondary = trim($__env->yieldContent('auth_accent_secondary', '#0369a1'));
    $authBadge = trim($__env->yieldContent('auth_badge', 'Portal Masuk'));
    $authHeading = trim($__env->yieldContent('auth_heading', $siteSettings['site_title'] ?? 'PKG Presensi'));
    $authSubheading = trim($__env->yieldContent('auth_subheading', $siteSettings['site_name'] ?? 'Pembinaan Karakter Generus'));
    $authCardTitle = trim($__env->yieldContent('auth_card_title', 'Masuk'));
    $authCardCopy = trim($__env->yieldContent('auth_card_copy', 'Gunakan akun Anda untuk melanjutkan.'));
    $authQuickStats = [
        ['label' => 'Akses', 'value' => 'Cepat'],
        ['label' => 'Tema', 'value' => 'Seragam'],
        ['label' => 'Login', 'value' => 'Aman'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('layouts.partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ($siteSettings['site_title'] ?? 'PKG Presensi') . ' - Login')</title>

    @include('layouts.partials.favicons')
    @php($manifestVersion = is_file(public_path('manifest.json')) ? filemtime(public_path('manifest.json')) : null)
    <link rel="manifest" href="{{ asset('manifest.json') }}{{ $manifestVersion ? '?v=' . $manifestVersion : '' }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @viteReactRefresh
    @vite(['resources/js/app.js'])
    @include('layouts.partials.theme-styles')
    @stack('styles')
</head>
<body class="min-h-screen text-slate-900 dark:text-slate-100" style="--auth-accent: {{ $authAccent }}; --auth-accent-secondary: {{ $authAccentSecondary }};">
    <div class="pkg-auth-page">
        <div class="pkg-auth-backdrop"></div>

        <button id="auth-theme-toggle" type="button" class="pkg-btn-secondary fixed right-3 top-3 z-30 rounded-full px-3 py-2 text-xs font-semibold sm:right-4 sm:top-4 sm:text-sm">
            Mode
        </button>

        <div class="pkg-auth-shell max-w-6xl">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,0.95fr)_minmax(380px,0.8fr)] lg:items-center">
                <div class="hidden space-y-6 lg:block" data-reveal="left">
                    <div class="text-center lg:text-left">
                        <div class="mb-3 inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] pkg-chip">
                            {{ $authBadge }}
                        </div>
                        <h1 class="pkg-page-title text-3xl font-black sm:text-4xl lg:text-5xl">{{ $authHeading }}</h1>
                        <p class="pkg-page-copy mx-auto mt-3 max-w-xl text-sm sm:text-base lg:mx-0">{{ $authSubheading }}</p>
                    </div>

                    <div class="pkg-auth-sidecard p-5 lg:p-6">
                        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            @foreach($authQuickStats as $item)
                                <div class="pkg-auth-stat">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-white" style="background: linear-gradient(135deg, var(--auth-accent), var(--auth-accent-secondary)); box-shadow: 0 18px 34px color-mix(in srgb, var(--auth-accent) 24%, transparent);">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $item['label'] }}</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $item['value'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div data-reveal="right" class="pt-12 sm:pt-10 lg:pt-0">
                    <div class="mb-3 text-center sm:mb-5 lg:mb-8">
                        <div class="pkg-auth-mark">
                            @if(!empty($siteSettings['site_logo']))
                                <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo" width="56" height="56" class="h-14 w-14 object-contain" style="width:3.5rem;height:3.5rem;object-fit:contain;" decoding="async" fetchpriority="high">
                            @else
                                @yield('auth_mark')
                            @endif
                        </div>
                        <div class="mt-2 lg:hidden">
                            <div class="inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] pkg-chip">
                                {{ $authBadge }}
                            </div>
                            <h1 class="pkg-page-title mt-2 text-xl font-black sm:text-2xl">{{ $authHeading }}</h1>
                            <p class="pkg-page-copy mx-auto mt-1 max-w-md text-xs sm:text-sm">{{ $authSubheading }}</p>
                        </div>
                    </div>

                    <div class="pkg-auth-panel">
                        <div class="border-b px-4 py-4 sm:px-7 sm:py-5" style="background: linear-gradient(135deg, color-mix(in srgb, var(--auth-accent) 14%, var(--pkg-surface, #ffffff)), color-mix(in srgb, var(--auth-accent-secondary) 10%, var(--pkg-surface, #ffffff))); border-color: var(--pkg-border);">
                            <h2 class="pkg-page-title text-xl font-bold sm:text-2xl">{{ $authCardTitle }}</h2>
                            <p class="pkg-page-copy mt-1 text-xs sm:text-sm">{{ $authCardCopy }}</p>
                        </div>
                        <div class="px-4 py-5 sm:px-7 sm:py-7">
                            @yield('content')
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        @yield('auth_footer')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var root = document.documentElement;
            var button = document.getElementById('auth-theme-toggle');

            function syncLabel() {
                if (!button) return;
                button.textContent = root.classList.contains('dark') ? 'Mode Terang' : 'Mode Gelap';
            }

            syncLabel();

            if (button) {
                button.addEventListener('click', function () {
                    var nextDark = !root.classList.contains('dark');
                    root.classList.toggle('dark', nextDark);
                    localStorage.setItem('darkMode', nextDark ? 'true' : 'false');
                    syncLabel();
                });
            }
        })();
    </script>

    @stack('scripts')
</body>
</html>
