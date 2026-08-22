@php
    $authPublicNavigationItems = [
        ['label' => 'Beranda', 'route' => 'public.index', 'active' => 'public.index'],
        ['label' => 'Game', 'route' => 'public.rpg.index', 'active' => 'public.rpg.*'],
        ['label' => '29 Karakter', 'route' => 'public.karakter.index', 'active' => 'public.karakter.*'],
        ['label' => 'Kalender', 'route' => 'public.calendar.index', 'active' => 'public.calendar.*'],
        ['label' => 'Materi', 'route' => 'materi.index', 'active' => 'materi.*'],
        ['label' => 'Scan Presensi', 'route' => 'public.scanner', 'active' => 'public.scanner'],
        ['label' => 'Lapor PKG', 'route' => 'laporan-penyaksian.create', 'active' => 'laporan-penyaksian.*'],
    ];
@endphp

<nav id="auth-public-navigation" class="pkg-public-nav-theme sticky top-0 z-50 min-h-[4.5rem] backdrop-blur-xl" aria-label="Navigasi publik">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-[4.5rem] items-center justify-between gap-3">
            <a href="{{ route('public.index') }}" class="pkg-nav-brand flex min-w-0 items-center gap-3" aria-label="Kembali ke beranda">
                @if(!empty($siteSettings['site_logo']))
                    <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo" width="44" height="44" class="h-10 w-10 shrink-0 object-contain sm:h-11 sm:w-11" style="width:2.75rem;height:2.75rem;object-fit:contain;" decoding="async">
                @endif
                <div class="min-w-0">
                    <p class="pkg-nav-brand-title truncate text-sm font-extrabold sm:text-base">{{ $siteSettings['site_title'] ?? 'PKG Presensi' }}</p>
                    <p class="pkg-public-nav-copy hidden truncate text-xs sm:block">Pembinaan Karakter Generus</p>
                </div>
            </a>

            <div class="hidden items-center gap-1 xl:flex">
                @foreach($authPublicNavigationItems as $item)
                    @php $isActive = request()->routeIs($item['active']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="pkg-public-nav-link {{ $isActive ? 'is-active' : '' }} whitespace-nowrap rounded-full px-3 py-2 text-sm font-semibold transition-colors"
                       @if($isActive) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <span class="pkg-public-nav-sep mx-1" aria-hidden="true"></span>

                <button type="button" data-auth-public-theme-toggle class="pkg-theme-toggle inline-flex h-9 w-9 items-center justify-center rounded-full transition" aria-pressed="false" aria-label="Ganti mode terang/gelap" title="Ganti mode">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
                    </svg>
                    <span class="sr-only" data-auth-public-theme-label>Mode Gelap</span>
                </button>

                <div class="relative" x-data="{ open: false }" @keydown.escape="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open"
                            class="pkg-public-nav-login ml-1 inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-bold transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h6a3 3 0 013 3v1"/>
                        </svg>
                        Login
                        <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         class="pkg-public-login-menu absolute right-0 mt-2 w-48 overflow-hidden rounded-2xl p-1.5 shadow-xl">
                        <a href="{{ route('siswa.login') }}" class="pkg-public-login-item flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold">Siswa</a>
                        <a href="{{ route('ortu.login') }}" class="pkg-public-login-item flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold">Orang Tua</a>
                        <a href="{{ route('login') }}" class="pkg-public-login-item flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold">Pamong &amp; Guru</a>
                    </div>
                </div>
            </div>

            <button id="auth-mobile-menu-toggle" type="button" class="pkg-mobile-menu-toggle xl:hidden" aria-expanded="false" aria-controls="auth-mobile-menu" aria-label="Buka menu navigasi">
                <svg class="pkg-menu-open-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg class="pkg-menu-close-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span class="pkg-menu-label">Menu</span>
            </button>
        </div>
    </div>

    <button id="auth-mobile-menu-overlay" type="button" class="pkg-mobile-menu-overlay xl:hidden" aria-label="Tutup menu navigasi" aria-hidden="true" tabindex="-1"></button>
    <aside id="auth-mobile-menu" class="pkg-mobile-menu-shell xl:hidden" aria-hidden="true" aria-label="Navigasi mobile" tabindex="-1" inert>
        <div class="pkg-mobile-menu-panel-header">
            <div class="pkg-mobile-menu-panel-brand">
                @if(!empty($siteSettings['site_logo']))
                    <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="" width="44" height="44" class="pkg-mobile-menu-panel-logo">
                @endif
                <div class="min-w-0">
                    <p class="pkg-mobile-menu-eyebrow text-[10px] font-bold uppercase tracking-[0.2em]">Menu Utama</p>
                    <h2 class="truncate text-base font-extrabold text-slate-900 dark:text-white">{{ $siteSettings['site_title'] ?? 'PKG Presensi' }}</h2>
                </div>
            </div>
            <button id="auth-mobile-menu-close" type="button" class="pkg-mobile-menu-close" aria-label="Tutup menu navigasi">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="pkg-mobile-menu-scroll">
            <div class="pkg-mobile-menu-card space-y-2 p-3">
                @foreach($authPublicNavigationItems as $item)
                    <a href="{{ route($item['route']) }}" class="pkg-mobile-menu-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}" @if(request()->routeIs($item['active'])) aria-current="page" @endif>
                        <span class="pkg-mobile-menu-icon">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-5-5 5 5-5 5"/>
                            </svg>
                        </span>
                        <span class="pkg-mobile-menu-text">{{ $item['label'] }}</span>
                    </a>
                @endforeach

                <div class="pkg-mobile-menu-divider space-y-2 border-t pt-3">
                    <p class="pkg-mobile-menu-eyebrow px-2 text-xs font-bold uppercase tracking-wider">Pilih Login</p>
                    <div class="grid grid-cols-1 gap-2">
                        <a href="{{ route('siswa.login') }}" class="pkg-mobile-login-link rounded-xl px-3 py-2.5 text-center text-sm font-semibold {{ request()->routeIs('siswa.login') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-200' : '' }}">Siswa</a>
                        <a href="{{ route('ortu.login') }}" class="pkg-mobile-login-link rounded-xl px-3 py-2.5 text-center text-sm font-semibold {{ request()->routeIs('ortu.login') ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-200' : '' }}">Orang Tua</a>
                        <a href="{{ route('login') }}" class="pkg-mobile-login-link rounded-xl px-3 py-2.5 text-center text-sm font-semibold {{ request()->routeIs('login') ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-200' : '' }}">Pamong dan Guru</a>
                    </div>
                </div>

                <button type="button" data-auth-public-theme-toggle class="pkg-theme-toggle flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition" aria-pressed="false">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
                    </svg>
                    <span data-auth-public-theme-label>Mode Gelap</span>
                </button>
            </div>
        </div>
    </aside>
</nav>

@push('scripts')
    <script>
        (function () {
            const menu = document.getElementById('auth-mobile-menu');
            const toggle = document.getElementById('auth-mobile-menu-toggle');
            const close = document.getElementById('auth-mobile-menu-close');
            const overlay = document.getElementById('auth-mobile-menu-overlay');

            function setMenu(open) {
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

                if (open) window.requestAnimationFrame(() => close?.focus());
            }

            toggle?.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
            close?.addEventListener('click', () => {
                setMenu(false);
                toggle?.focus();
            });
            overlay?.addEventListener('click', () => setMenu(false));
            menu?.querySelectorAll('a[href]').forEach((link) => link.addEventListener('click', () => setMenu(false)));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setMenu(false);
            });
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1280) setMenu(false);
            });

            function syncThemeButtons() {
                const isDark = window.pkgTheme?.get ? window.pkgTheme.get() : document.documentElement.classList.contains('dark');
                document.querySelectorAll('[data-auth-public-theme-toggle]').forEach((button) => {
                    button.setAttribute('aria-pressed', String(isDark));
                    const label = button.querySelector('[data-auth-public-theme-label]');
                    if (label) label.textContent = isDark ? 'Mode Terang' : 'Mode Gelap';
                });
            }

            document.querySelectorAll('[data-auth-public-theme-toggle]').forEach((button) => {
                button.addEventListener('click', () => window.pkgTheme?.toggle?.());
            });
            window.addEventListener('pkg:theme-change', syncThemeButtons);
            syncThemeButtons();
        })();
    </script>
@endpush
