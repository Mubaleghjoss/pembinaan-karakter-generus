@php
    $authAccent = trim($__env->yieldContent('auth_accent', '#0f766e'));
    $authAccentSecondary = trim($__env->yieldContent('auth_accent_secondary', '#0369a1'));
    $authBadge = trim($__env->yieldContent('auth_badge', 'Portal Masuk'));
    $authHeading = trim($__env->yieldContent('auth_heading', $siteSettings['site_title'] ?? 'PKG Presensi'));
    $authSubheading = trim($__env->yieldContent('auth_subheading', $siteSettings['site_name'] ?? 'Pembinaan Karakter Generus'));
    $authCardTitle = trim($__env->yieldContent('auth_card_title', 'Masuk'));
    $authCardCopy = trim($__env->yieldContent('auth_card_copy', 'Gunakan akun Anda untuk melanjutkan.'));
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

        <button id="auth-theme-toggle" type="button" class="pkg-btn-secondary fixed right-4 top-4 z-30 rounded-full px-3 py-2 text-sm font-semibold">
            Mode
        </button>

        <div class="pkg-auth-shell">
            <div class="mb-8 text-center">
                <div class="pkg-auth-mark">
                    @if(!empty($siteSettings['site_logo']))
                        <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo" width="56" height="56" class="h-14 w-14 object-contain" style="width:3.5rem;height:3.5rem;object-fit:contain;" decoding="async" fetchpriority="high">
                    @else
                        @yield('auth_mark')
                    @endif
                </div>

                <div class="mb-3 inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] pkg-chip">
                    {{ $authBadge }}
                </div>

                <h1 class="pkg-page-title text-3xl font-black sm:text-4xl">{{ $authHeading }}</h1>
                <p class="pkg-page-copy mx-auto mt-2 max-w-md text-sm sm:text-base">{{ $authSubheading }}</p>
            </div>

            <div class="pkg-auth-panel">
                <div class="border-b px-6 py-5 sm:px-8" style="background: linear-gradient(135deg, color-mix(in srgb, var(--auth-accent) 14%, var(--pkg-surface, #ffffff)), color-mix(in srgb, var(--auth-accent-secondary) 10%, var(--pkg-surface, #ffffff))); border-color: var(--pkg-border);">
                    <h2 class="pkg-page-title text-2xl font-bold">{{ $authCardTitle }}</h2>
                    <p class="pkg-page-copy mt-1 text-sm">{{ $authCardCopy }}</p>
                </div>
                <div class="px-6 py-6 sm:px-8 sm:py-8">
                    @yield('content')
                </div>
            </div>

            <div class="mt-6 text-center">
                @yield('auth_footer')
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
