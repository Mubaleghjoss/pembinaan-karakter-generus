<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <script>
        (function () {
            var preference = @json(auth()->user()?->theme_preference ?? 'system');
            if (preference === 'system') localStorage.removeItem('darkMode');
            else localStorage.setItem('darkMode', preference === 'dark' ? 'true' : 'false');
        })();
    </script>
    @include('layouts.partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#047857">
    <title>@yield('title', 'Portal Guru') - {{ $siteSettings['site_title'] ?? 'PKG' }}</title>
    @include('layouts.partials.favicons')
    <link rel="manifest" href="{{ route('guru.manifest') }}">
    @viteReactRefresh
    @vite(['resources/js/app.js'])
    @include('layouts.partials.theme-styles')
    @stack('styles')
</head>
<body class="min-h-full overflow-x-hidden bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100"
      x-data="{ moreOpen: false }"
      x-effect="document.documentElement.classList.toggle('overflow-hidden', moreOpen)">
@php
    $guruUser = auth()->user();
@endphp
<div class="min-h-screen">
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/90" style="padding-top: env(safe-area-inset-top);">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-3 px-4">
            <a href="{{ route('guru.dashboard') }}" class="flex min-w-0 items-center gap-3">
                @if(!empty($siteSettings['site_logo']))
                    <img src="{{ Storage::url($siteSettings['site_logo']) }}" alt="Logo PKG" width="40" height="40" class="h-10 w-10 flex-none object-contain">
                @else
                    <img src="{{ asset('images/icons/pkg-logo-192.png') }}" alt="Logo PKG" width="40" height="40" class="h-10 w-10 flex-none object-contain">
                @endif
                <span class="min-w-0">
                    <span class="block truncate text-sm font-black">{{ $siteSettings['site_title'] ?? 'PKG Panunggangan' }}</span>
                    <span class="block text-xs font-semibold text-emerald-600 dark:text-emerald-400">Portal Guru</span>
                </span>
            </a>
            <a href="{{ route('guru.profile') }}" class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-emerald-600 text-sm font-black text-white ring-4 ring-emerald-100 dark:ring-emerald-950">
                @if($guruUser?->avatar_path)
                    <img src="{{ Storage::url($guruUser->avatar_path) }}" alt="{{ $guruUser->name }}" class="h-full w-full object-cover">
                @else
                    {{ strtoupper(substr($guruUser?->name ?: 'G', 0, 1)) }}
                @endif
            </a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-5xl px-4 pb-28 pt-5 sm:px-6 sm:pt-8">
        @if(session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/95" style="padding-bottom: env(safe-area-inset-bottom);" aria-label="Navigasi Portal Guru">
        <div class="mx-auto grid h-16 max-w-xl grid-cols-5">
            @foreach([
                ['guru.dashboard', 'Beranda', 'home'],
                ['guru.schedule', 'Jadwal', 'calendar'],
                ['guru.materials', 'Materi', 'book'],
                ['guru.profile', 'Profil', 'user'],
            ] as [$routeName, $label, $icon])
                @php
                    $active = request()->routeIs($routeName)
                        || ($routeName === 'guru.schedule' && request()->routeIs('guru.schedule.*'));
                @endphp
                <a href="{{ route($routeName) }}" class="flex flex-col items-center justify-center gap-1 text-[11px] font-bold transition {{ $active ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                    @if($icon === 'home')<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 11l9-8 9 8v9a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1v-9z"/></svg>@endif
                    @if($icon === 'calendar')<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3v4m8-4v4M4 9h16M5 5h14a1 1 0 011 1v14H4V6a1 1 0 011-1z"/></svg>@endif
                    @if($icon === 'book')<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a3 3 0 013-3h5v18H7a3 3 0 00-3 2V5zm16 0a3 3 0 00-3-3h-5v18h5a3 3 0 013 2V5z"/></svg>@endif
                    @if($icon === 'user')<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0"/></svg>@endif
                    <span>{{ $label }}</span>
                </a>
            @endforeach
            <button type="button" @click="moreOpen = true" class="flex flex-col items-center justify-center gap-1 text-[11px] font-bold text-slate-500 dark:text-slate-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"/></svg>
                <span>Lainnya</span>
            </button>
        </div>
    </nav>

    <div x-cloak x-show="moreOpen" class="fixed inset-0 z-50" @keydown.escape.window="moreOpen = false">
        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="moreOpen = false" x-transition.opacity></div>
        <section class="absolute inset-x-0 bottom-0 mx-auto max-h-[88vh] max-w-xl overflow-y-auto rounded-t-[2rem] bg-white p-5 shadow-2xl dark:bg-slate-900" style="padding-bottom: calc(1.25rem + env(safe-area-inset-bottom));" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
            <div class="mx-auto mb-5 h-1.5 w-12 rounded-full bg-slate-300 dark:bg-slate-700"></div>
            <div class="flex items-center justify-between">
                <div><h2 class="text-xl font-black">Menu lainnya</h2><p class="text-sm text-slate-500">Pengaturan dan layanan akun Guru.</p></div>
                <button type="button" @click="moreOpen = false" class="rounded-full bg-slate-100 p-2 dark:bg-slate-800" aria-label="Tutup"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg></button>
            </div>
            <div class="mt-5 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                <p class="mb-3 text-sm font-black">Tema aplikasi</p>
                <form method="POST" action="{{ route('guru.theme.update') }}" class="grid grid-cols-3 gap-2">
                    @csrf @method('PUT')
                    @foreach(['light' => 'Terang', 'dark' => 'Gelap', 'system' => 'Sistem'] as $value => $label)
                        <button name="theme_preference" value="{{ $value }}" class="rounded-xl border px-2 py-2 text-xs font-bold {{ $guruUser->theme_preference === $value ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'border-slate-200 dark:border-slate-700' }}">{{ $label }}</button>
                    @endforeach
                </form>
            </div>
            <div class="mt-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                <x-pwa-push-control :subscribe-url="route('pwa.push-subscriptions.store')" :unsubscribe-url="route('pwa.push-subscriptions.destroy')" :badge-count="0" />
            </div>
            <div class="mt-3 grid gap-2">
                <a href="{{ route('guru.id-card') }}" class="rounded-2xl border border-slate-200 p-4 font-bold dark:border-slate-700">Kartu ID dan QR</a>
                <a href="{{ route('guru.statement') }}" class="rounded-2xl border border-slate-200 p-4 font-bold dark:border-slate-700">Unduh surat kesediaan</a>
                <a href="{{ route('guru.password.edit') }}" class="rounded-2xl border border-slate-200 p-4 font-bold dark:border-slate-700">Ubah password</a>
                <a href="{{ route('biometrik') }}" class="rounded-2xl border border-slate-200 p-4 font-bold dark:border-slate-700">Login biometrik</a>
                <a href="{{ route('public.index') }}" class="rounded-2xl border border-slate-200 p-4 font-bold dark:border-slate-700">Bantuan dan informasi</a>
                @if($guruUser->usesPamongPermissionSystem())
                    <a href="{{ route('dashboard') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">Buka Portal Operasional</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-2xl border border-red-200 bg-red-50 p-4 text-left font-bold text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">Keluar</button></form>
            </div>
        </section>
    </div>
</div>
@stack('scripts')
</body>
</html>
