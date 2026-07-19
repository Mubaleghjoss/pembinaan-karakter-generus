@php
    $authAccent = trim($__env->yieldContent('auth_accent', '#0f766e'));
    $authAccentSecondary = trim($__env->yieldContent('auth_accent_secondary', '#0369a1'));
    $authCardTitle = trim($__env->yieldContent('auth_card_title', 'Masuk'));
    $authCardCopy = trim($__env->yieldContent('auth_card_copy', 'Gunakan akun Anda untuk melanjutkan.'));
    $showPublicNavigation = trim($__env->yieldContent('auth_public_navigation', 'false')) === 'true';
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
<body class="min-h-screen overflow-x-hidden text-slate-900 dark:text-slate-100" style="--auth-accent: {{ $authAccent }}; --auth-accent-secondary: {{ $authAccentSecondary }};">
    @if($showPublicNavigation)
        @include('layouts.partials.auth-public-navigation')
    @endif

    <div class="pkg-auth-page {{ $showPublicNavigation ? 'pkg-auth-page-with-nav' : '' }}">
        <div class="pkg-auth-backdrop"></div>

        <div class="pkg-auth-shell" data-reveal="right">
            <div class="pkg-auth-panel">
                <div class="pkg-auth-panel-heading border-b px-4 py-3.5 sm:px-6 sm:py-4" style="background: linear-gradient(135deg, color-mix(in srgb, var(--auth-accent) 14%, var(--pkg-surface, #ffffff)), color-mix(in srgb, var(--auth-accent-secondary) 10%, var(--pkg-surface, #ffffff))); border-color: var(--pkg-border);">
                    <h1 class="pkg-page-title text-lg font-bold sm:text-xl">{{ $authCardTitle }}</h1>
                    <p class="pkg-page-copy mt-0.5 text-xs sm:text-sm">{{ $authCardCopy }}</p>
                </div>
                <div class="pkg-auth-panel-content px-4 py-4 sm:px-6 sm:py-5">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
