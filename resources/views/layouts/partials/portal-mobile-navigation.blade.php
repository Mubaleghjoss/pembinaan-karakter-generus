@php
    $portalTone = ($mobilePortal['tone'] ?? 'blue') === 'teal' ? 'teal' : 'blue';
    $activeTextClass = $portalTone === 'teal'
        ? 'text-teal-600 dark:text-teal-400'
        : 'text-blue-600 dark:text-blue-400';
    $activeSurfaceClass = $portalTone === 'teal'
        ? 'border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-900 dark:bg-teal-950/40 dark:text-teal-300'
        : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300';
    $avatarClass = $portalTone === 'teal'
        ? 'bg-teal-600 ring-teal-100 dark:ring-teal-950'
        : 'bg-blue-600 ring-blue-100 dark:ring-blue-950';
@endphp

<header class="pkg-portal-mobile-header lg:hidden">
    <div class="flex h-16 min-w-0 items-center justify-between gap-3 px-4">
        <a href="{{ $mobilePortal['home_url'] }}" class="flex min-w-0 items-center gap-3">
            @if(!empty($siteSettings['site_logo']))
                <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo PKG" width="40" height="40" class="h-10 w-10 flex-none object-contain">
            @else
                <img src="{{ asset('images/icons/pkg-logo-192.png') }}" alt="Logo PKG" width="40" height="40" class="h-10 w-10 flex-none object-contain">
            @endif
            <span class="min-w-0">
                <span class="block truncate text-sm font-black text-slate-900 dark:text-white">{{ $siteSettings['site_title'] ?? 'PKG Panunggangan' }}</span>
                <span class="block truncate text-xs font-semibold {{ $activeTextClass }}">{{ $mobilePortal['portal_label'] }}</span>
            </span>
        </a>
        <a href="{{ $mobilePortal['profile_url'] }}" class="flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-full text-sm font-black text-white ring-4 {{ $avatarClass }}" aria-label="Buka {{ $mobilePortal['profile_label'] }}">
            @if(!empty($mobilePortal['photo_url']))
                <img src="{{ $mobilePortal['photo_url'] }}" alt="{{ $mobilePortal['user_name'] }}" class="h-full w-full object-cover">
            @else
                {{ strtoupper(substr($mobilePortal['user_name'] ?: 'P', 0, 1)) }}
            @endif
        </a>
    </div>
</header>

<nav class="pkg-portal-mobile-bottom lg:hidden" aria-label="Navigasi {{ $mobilePortal['portal_label'] }}">
    <div class="grid h-16 grid-cols-6">
        @foreach($mobilePortal['bottom_items'] as $item)
            <a href="{{ $item['url'] }}"
               class="relative flex min-w-0 flex-col items-center justify-center gap-1 px-0.5 text-[10px] font-bold transition {{ $item['active'] ? $activeTextClass : 'text-slate-500 dark:text-slate-400' }}"
               @if($item['active']) aria-current="page" @endif>
                @include('layouts.partials.portal-mobile-icon', ['icon' => $item['icon'], 'iconClass' => 'h-5 w-5'])
                <span class="max-w-full truncate">{{ $item['label'] }}</span>
                @if(($item['badge'] ?? 0) > 0)
                    <span class="absolute right-1 top-1 inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-red-500 px-1 py-0.5 text-[9px] font-black leading-none text-white">
                        {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach
        <button type="button" @click="mobileMenuOpen = true" class="flex min-w-0 flex-col items-center justify-center gap-1 px-0.5 text-[10px] font-bold transition {{ ($mobilePortal['more_active'] ?? false) ? $activeTextClass : 'text-slate-500 dark:text-slate-400' }}" :aria-expanded="mobileMenuOpen.toString()" aria-controls="portal-mobile-sheet">
            @include('layouts.partials.portal-mobile-icon', ['icon' => 'more', 'iconClass' => 'h-5 w-5'])
            <span>Lainnya</span>
        </button>
    </div>
</nav>

<div x-cloak x-show="mobileMenuOpen" class="fixed inset-0 z-[70] lg:hidden" @keydown.escape.window="mobileMenuOpen = false">
    <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="mobileMenuOpen = false" x-transition.opacity></div>
    <section id="portal-mobile-sheet"
             role="dialog"
             aria-modal="true"
             aria-labelledby="portal-mobile-sheet-title"
             class="pkg-portal-mobile-sheet"
             x-transition:enter="transition duration-200 ease-out"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition duration-150 ease-in"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
        <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-slate-300 dark:bg-slate-700"></div>
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 id="portal-mobile-sheet-title" class="text-xl font-black text-slate-900 dark:text-white">Menu lainnya</h2>
                <p class="truncate text-sm text-slate-500 dark:text-slate-400">{{ $mobilePortal['user_name'] }}{{ !empty($mobilePortal['user_meta']) ? ' - ' . $mobilePortal['user_meta'] : '' }}</p>
            </div>
            <button type="button" @click="mobileMenuOpen = false" class="rounded-full bg-slate-100 p-2 text-slate-700 dark:bg-slate-800 dark:text-slate-200" aria-label="Tutup menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="mt-5 space-y-4">
            @foreach($mobilePortal['sheet_sections'] as $section)
                <section>
                    <h3 class="mb-2 px-1 text-xs font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ $section['label'] }}</h3>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($section['items'] as $item)
                            <a href="{{ $item['url'] }}"
                               @if(!empty($item['target'])) target="{{ $item['target'] }}" rel="noopener" @endif
                               class="relative flex min-w-0 items-center gap-3 rounded-2xl border p-3 text-sm font-bold transition {{ $item['active'] ? $activeSurfaceClass : 'border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800' }}"
                               @click="mobileMenuOpen = false">
                                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
                                    @include('layouts.partials.portal-mobile-icon', ['icon' => $item['icon'], 'iconClass' => 'h-5 w-5'])
                                </span>
                                <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                                @if(($item['badge'] ?? 0) > 0)
                                    <span class="absolute right-2 top-2 inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-red-500 px-1 py-0.5 text-[9px] font-black text-white">
                                        {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <section class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-black text-slate-900 dark:text-white">Tema aplikasi</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="darkMode ? 'Mode gelap aktif' : 'Mode terang aktif'"></p>
                    </div>
                    <button type="button" @click="toggleDarkMode()" class="btn-secondary !h-10 !px-3">Ganti tema</button>
                </div>
            </section>

            @if(!empty($mobilePortal['push']))
                <section class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <x-pwa-push-control
                        :subscribe-url="$mobilePortal['push']['subscribe_url']"
                        :unsubscribe-url="$mobilePortal['push']['unsubscribe_url']"
                        :badge-count="$mobilePortal['push']['badge_count']" />
                </section>
            @endif

            <form method="POST" action="{{ $mobilePortal['logout_url'] }}">
                @csrf
                <button type="submit" class="w-full rounded-2xl border border-red-200 bg-red-50 p-4 text-left text-sm font-black text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">Keluar</button>
            </form>
        </div>
    </section>
</div>
