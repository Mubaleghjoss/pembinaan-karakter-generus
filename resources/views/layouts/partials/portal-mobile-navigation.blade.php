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
    $bottomColumnClass = match (min(6, max(2, count($mobilePortal['bottom_items']) + 1))) {
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
        4 => 'grid-cols-4',
        5 => 'grid-cols-5',
        default => 'grid-cols-6',
    };
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
    <div class="grid h-16 {{ $bottomColumnClass }}">
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
            @if(!empty($mobilePortal['favorites']))
                @php
                    $favoriteConfig = $mobilePortal['favorites'];
                @endphp
                <section
                    class="rounded-2xl border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-900 dark:bg-amber-950/20"
                    x-data="{
                        editing: false,
                        original: @js($favoriteConfig['selected_keys']),
                        selected: @js($favoriteConfig['selected_keys']),
                        available: @js($favoriteConfig['available_items']),
                        max: {{ (int) $favoriteConfig['max'] }},
                        has(key) {
                            return this.selected.includes(key);
                        },
                        toggle(key) {
                            if (this.has(key)) {
                                this.selected = this.selected.filter((item) => item !== key);
                                return;
                            }
                            if (this.selected.length < this.max) {
                                this.selected.push(key);
                            }
                        },
                        move(index, offset) {
                            const target = index + offset;
                            if (target < 0 || target >= this.selected.length) return;
                            const copy = [...this.selected];
                            [copy[index], copy[target]] = [copy[target], copy[index]];
                            this.selected = copy;
                        },
                        item(key) {
                            return this.available.find((entry) => entry.key === key) || { label: key };
                        },
                        cancel() {
                            this.selected = [...this.original];
                            this.editing = false;
                        }
                    }">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-[0.14em] text-amber-800 dark:text-amber-300">Favorit Admin</h3>
                            <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-400/80">Maksimal {{ $favoriteConfig['max'] }} menu akses cepat.</p>
                        </div>
                        <button type="button" class="btn-secondary !h-9 !px-3 text-xs" @click="editing ? cancel() : editing = true">
                            <span x-text="editing ? 'Batal' : 'Atur Favorit'"></span>
                        </button>
                    </div>

                    <div x-show="!editing" class="mt-3 grid grid-cols-2 gap-2">
                        @forelse($favoriteConfig['selected_items'] as $item)
                            <a href="{{ $item['url'] }}"
                               class="relative flex min-w-0 items-center gap-2 rounded-xl border border-amber-200 bg-white p-2.5 text-xs font-bold text-slate-700 dark:border-amber-900 dark:bg-slate-900 dark:text-slate-200"
                               @click="mobileMenuOpen = false">
                                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                    @include('layouts.partials.portal-mobile-icon', ['icon' => $item['icon'], 'iconClass' => 'h-4 w-4'])
                                </span>
                                <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                                @if(($item['badge'] ?? 0) > 0)
                                    <span class="absolute right-1.5 top-1.5 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-red-500 px-1 py-0.5 text-[8px] font-black text-white">
                                        {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                                    </span>
                                @endif
                            </a>
                        @empty
                            <p class="col-span-2 rounded-xl border border-dashed border-amber-300 p-3 text-center text-xs text-amber-700 dark:border-amber-800 dark:text-amber-300">Belum ada menu favorit.</p>
                        @endforelse
                    </div>

                    <form x-show="editing" x-cloak method="POST" action="{{ $favoriteConfig['update_url'] }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="favorites_present" value="1">
                        <template x-for="key in selected" :key="`favorite-input-${key}`">
                            <input type="hidden" name="favorites[]" :value="key">
                        </template>

                        <div class="rounded-xl border border-amber-200 bg-white p-2 dark:border-amber-900 dark:bg-slate-900">
                            <p class="px-1 pb-2 text-xs font-bold text-slate-700 dark:text-slate-200">Urutan favorit</p>
                            <template x-if="selected.length === 0">
                                <p class="px-1 py-2 text-xs text-slate-500 dark:text-slate-400">Pilih menu dari daftar di bawah.</p>
                            </template>
                            <div class="space-y-1">
                                <template x-for="(key, index) in selected" :key="`favorite-order-${key}`">
                                    <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-2 py-1.5 dark:bg-slate-800">
                                        <span class="flex h-6 w-6 flex-none items-center justify-center rounded-md bg-amber-100 text-[10px] font-black text-amber-700 dark:bg-amber-900/50 dark:text-amber-300" x-text="index + 1"></span>
                                        <span class="min-w-0 flex-1 truncate text-xs font-bold text-slate-700 dark:text-slate-200" x-text="item(key).label"></span>
                                        <button type="button" class="rounded-md border border-slate-200 p-1 text-slate-600 disabled:opacity-30 dark:border-slate-700 dark:text-slate-300" @click="move(index, -1)" :disabled="index === 0" aria-label="Naikkan urutan">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button type="button" class="rounded-md border border-slate-200 p-1 text-slate-600 disabled:opacity-30 dark:border-slate-700 dark:text-slate-300" @click="move(index, 1)" :disabled="index === selected.length - 1" aria-label="Turunkan urutan">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="entry in available" :key="`favorite-choice-${entry.key}`">
                                <button type="button"
                                        class="flex min-w-0 items-center gap-2 rounded-xl border p-2.5 text-left text-xs font-bold transition"
                                        :class="has(entry.key)
                                            ? 'border-amber-400 bg-amber-100 text-amber-800 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200'
                                            : 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200'"
                                        @click="toggle(entry.key)">
                                    <span class="flex h-5 w-5 flex-none items-center justify-center rounded-full border text-[9px] font-black" x-text="has(entry.key) ? 'OK' : '+'"></span>
                                    <span class="min-w-0 truncate" x-text="entry.label"></span>
                                </button>
                            </template>
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400"><span x-text="selected.length"></span>/{{ $favoriteConfig['max'] }} dipilih</span>
                            <button type="submit" class="btn-primary !h-10 !px-4 text-xs">Simpan Favorit</button>
                        </div>
                    </form>

                    <form x-show="editing" x-cloak method="POST" action="{{ $favoriteConfig['update_url'] }}" class="mt-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="reset" value="1">
                        <button type="submit" class="w-full rounded-xl border border-amber-300 px-3 py-2 text-xs font-bold text-amber-800 dark:border-amber-800 dark:text-amber-300">Kembalikan ke Favorit Awal</button>
                    </form>
                </section>
            @endif

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
