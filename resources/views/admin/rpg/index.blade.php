@extends('layouts.app')

@section('title', 'RPG Quest - Admin')
@section('page-title', 'RPG Quest Management')

@section('content')
<div class="space-y-4 sm:space-y-6" x-data="rpgAdmin()" x-init="init()">
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="pkg-panel p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Peta</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $maps->count() }}</p>
        </div>
        <div class="pkg-panel p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total NPC</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $maps->sum('npcs_count') }}</p>
        </div>
        <div class="pkg-panel p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Sesi Bermain</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $maps->sum('game_sessions_count') }}</p>
        </div>
    </div>

    {{-- Map Management --}}
    <div class="pkg-panel">
        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Peta RPG</h2>
            <button @click="showMapModal = true; resetMapForm()" 
                    class="btn-primary w-full text-sm sm:w-auto">
                Tambah Peta
            </button>
        </div>

        <div class="border-b border-gray-200 dark:border-gray-700 p-4">
            <div class="grid gap-3 md:grid-cols-5">
                <input
                    type="text"
                    x-model.trim="mapFilters.search"
                    placeholder="Cari nama peta..."
                    class="pkg-field rounded-lg px-3 py-2 text-sm"
                >
                <select x-model="mapFilters.difficulty" class="pkg-field rounded-lg px-3 py-2 text-sm">
                    <option value="all">Semua Difficulty</option>
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
                <select x-model="mapFilters.status" class="pkg-field rounded-lg px-3 py-2 text-sm">
                    <option value="all">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
                <select x-model="mapFilters.preset" class="pkg-field rounded-lg px-3 py-2 text-sm">
                    <option value="all">Semua Preset</option>
                    <option value="relaxed">Santai</option>
                    <option value="balanced">Seimbang</option>
                    <option value="challenge">Tantangan</option>
                    <option value="custom">Custom</option>
                </select>
                <select x-model="mapFilters.sort" class="pkg-field rounded-lg px-3 py-2 text-sm">
                    <option value="newest">Urutkan: Terbaru</option>
                    <option value="oldest">Urutkan: Terlama</option>
                    <option value="npc_desc">NPC Terbanyak</option>
                    <option value="sessions_desc">Sesi Terbanyak</option>
                </select>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 text-sm dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-gray-600 dark:text-gray-300">
                Menampilkan <span class="font-semibold text-gray-900 dark:text-white" x-text="countMatchingMaps()"></span> map
                dari <span class="font-semibold text-gray-900 dark:text-white">{{ $maps->count() }}</span> total.
            </p>
            <button
                type="button"
                x-show="hasActiveMapFilters()"
                x-cloak
                @click="resetMapFilters()"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2 font-medium text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                Reset Filter
            </button>
        </div>

        <div class="flex flex-col divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($maps as $map)
            @php
                $presetLabel = null;
                $isRelaxed =
                    ($map->difficulty ?? 'easy') === 'easy' &&
                    (int) ($map->shield_duration_seconds ?? 8) === 12 &&
                    (int) ($map->ammo_per_pickup ?? 3) === 4 &&
                    (int) ($map->shield_pickups_count ?? 1) === 2 &&
                    (int) ($map->ammo_pickups_count ?? 2) === 4;
                $isBalanced =
                    ($map->difficulty ?? 'easy') === 'medium' &&
                    (int) ($map->shield_duration_seconds ?? 8) === 8 &&
                    (int) ($map->ammo_per_pickup ?? 3) === 3 &&
                    (int) ($map->shield_pickups_count ?? 1) === 1 &&
                    (int) ($map->ammo_pickups_count ?? 2) === 2;
                $isChallenge =
                    ($map->difficulty ?? 'easy') === 'hard' &&
                    (int) ($map->shield_duration_seconds ?? 8) === 6 &&
                    (int) ($map->ammo_per_pickup ?? 3) === 2 &&
                    (int) ($map->shield_pickups_count ?? 1) === 1 &&
                    (int) ($map->ammo_pickups_count ?? 2) === 1;

                if ($isRelaxed) {
                    $presetLabel = 'Preset Santai';
                } elseif ($isBalanced) {
                    $presetLabel = 'Preset Seimbang';
                } elseif ($isChallenge) {
                    $presetLabel = 'Preset Tantangan';
                }

                $previewGridSize = max(1, (int) $map->grid_size);
                $previewObstacles = collect($map->obstacles ?? [])
                    ->mapWithKeys(function ($obstacle) {
                        return [((int) ($obstacle['x'] ?? 0)) . ',' . ((int) ($obstacle['y'] ?? 0)) => true];
                    });
                $previewEnemies = collect(\App\Support\RpgCatalog::normalizeEnemies($map->enemies ?? []))
                    ->mapWithKeys(function ($enemy) {
                        return [((int) ($enemy['x'] ?? 0)) . ',' . ((int) ($enemy['y'] ?? 0)) => $enemy];
                    });
                $previewNpcs = $map->activeNpcs
                    ->mapWithKeys(function ($npc) {
                        return [((int) $npc->pos_x) . ',' . ((int) $npc->pos_y) => $npc];
                    });
            @endphp
            <div
                class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                x-show="matchesMap(@js($map), @js($presetLabel ? ($isRelaxed ? 'relaxed' : ($isBalanced ? 'balanced' : 'challenge')) : 'custom'))"
                :style="'order:' + sortOrderForMap(@js($map))"
            >
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(10rem,12rem)] xl:items-start">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-xl
                                {{ $map->background_theme === 'grass' ? 'bg-green-100 dark:bg-green-900/30' : '' }}
                                {{ $map->background_theme === 'desert' ? 'bg-yellow-100 dark:bg-yellow-900/30' : '' }}
                                {{ $map->background_theme === 'castle' ? 'bg-gray-200 dark:bg-gray-600' : '' }}
                                {{ $map->background_theme === 'forest' ? 'bg-green-200 dark:bg-green-800' : '' }}
                                {{ $map->background_theme === 'snow' ? 'bg-blue-100 dark:bg-blue-900/30' : '' }}">
                                {{ $map->background_theme === 'grass' ? 'G' : ($map->background_theme === 'desert' ? 'D' : ($map->background_theme === 'castle' ? 'K' : ($map->background_theme === 'forest' ? 'H' : ($map->background_theme === 'snow' ? 'S' : 'P')))) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="break-words font-semibold text-gray-900 dark:text-white">{{ $map->nama }}</h3>
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $map->grid_size }}x{{ $map->grid_size }}</span>
                                    <span>|</span>
                                    <span>{{ $map->npcs_count }} NPC</span>
                                    <span>|</span>
                                    <span>{{ $map->game_sessions_count }} sesi</span>
                                    <span>|</span>
                                    <span class="{{ $map->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $map->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        {{ strtoupper($map->difficulty ?? 'easy') }}
                                    </span>
                                    @if($presetLabel)
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 font-semibold text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300">
                                        {{ $presetLabel }}
                                    </span>
                                    @endif
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                        Tameng {{ (int) ($map->shield_duration_seconds ?? 8) }}d x{{ (int) ($map->shield_pickups_count ?? 1) }}
                                    </span>
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                        Peluru {{ (int) ($map->ammo_per_pickup ?? 3) }}/pickup x{{ (int) ($map->ammo_pickups_count ?? 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:flex-wrap sm:justify-end">
                            <button @click="editMap({{ $map->id }}, @js($map))" 
                                    class="inline-flex items-center justify-center rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 sm:py-1.5">
                                Edit
                            </button>
                            <button @click="manageNpcs({{ $map->id }}, @js($map))" 
                                    class="inline-flex items-center justify-center rounded-lg bg-purple-50 px-3 py-2 text-sm text-purple-600 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400 dark:hover:bg-purple-900/50 sm:py-1.5" title="Editor Peta & NPC">
                                Editor Peta
                            </button>
                            <button @click="duplicateMap({{ $map->id }})" 
                                    class="inline-flex items-center justify-center rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-900/50 sm:py-1.5">
                                Duplikat
                            </button>
                            <button @click="deleteMap({{ $map->id }})" 
                                    class="inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 sm:py-1.5">
                                Hapus
                            </button>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/40">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">Preview 2D</p>
                            <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-300">{{ $previewGridSize }}x{{ $previewGridSize }}</span>
                        </div>
                        <div class="mt-3 grid gap-px overflow-hidden rounded-lg bg-slate-300 dark:bg-slate-700" style="grid-template-columns: repeat({{ $previewGridSize }}, minmax(0, 1fr));">
                            @for($displayY = $previewGridSize - 1; $displayY >= 0; $displayY--)
                                @for($x = 0; $x < $previewGridSize; $x++)
                                    @php
                                        $cellKey = $x . ',' . $displayY;
                                        $hasObstacle = $previewObstacles->has($cellKey);
                                        $previewNpc = $previewNpcs->get($cellKey);
                                        $previewEnemy = $previewEnemies->get($cellKey);
                                        $isStart = $x === 0 && $displayY === 0;
                                    @endphp
                                    <span class="flex aspect-square min-h-[0.7rem] items-center justify-center text-[10px] leading-none {{ $hasObstacle ? 'bg-stone-700 text-white' : ($previewEnemy ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200' : ($previewNpc ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-200')) }} {{ $isStart ? 'ring-1 ring-inset ring-indigo-500' : '' }}">
                                        @if($hasObstacle)
                                            #
                                        @elseif($previewEnemy)
                                            {{ \App\Support\RpgCatalog::resolveEnemyAvatar($previewEnemy['avatar'] ?? null) }}
                                        @elseif($previewNpc)
                                            {{ \App\Support\RpgCatalog::resolveNpcAvatar($previewNpc->avatar) }}
                                        @elseif($isStart)
                                            S
                                        @endif
                                    </span>
                                @endfor
                            @endfor
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">
                            <span># Tembok</span>
                            <span>S Mulai</span>
                            <span>{{ $previewNpcs->count() }} NPC</span>
                            <span>{{ $previewEnemies->count() }} Musuh</span>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                <span class="text-4xl">P</span>
                <p class="mt-2">Belum ada peta. Klik "Tambah Peta" untuk memulai.</p>
            </div>
            @endforelse

            @if($maps->count() > 0)
            <div x-show="countMatchingMaps() === 0" x-cloak class="p-8">
                <div class="pkg-empty-state">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        0
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Tidak ada map yang cocok</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Ubah kata kunci, preset, difficulty, atau status agar daftar map muncul lagi.
                    </p>
                    <button
                        type="button"
                        @click="resetMapFilters()"
                        class="mt-4 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                    >
                        Tampilkan Semua Map
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Top Players --}}
    @if($topPlayers->count() > 0)
    <div class="pkg-panel p-4">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Top Players RPG</h2>
        <div class="space-y-2">
            @foreach($topPlayers as $i => $player)
            <div class="flex items-center justify-between p-2 rounded-lg {{ $i < 3 ? 'bg-yellow-50 dark:bg-yellow-900/10' : '' }}">
                <div class="flex items-center gap-3">
                    <span class="text-lg font-bold {{ $i === 0 ? 'text-yellow-500' : ($i === 1 ? 'text-gray-400' : ($i === 2 ? 'text-amber-600' : 'text-gray-500')) }}">
                        {{ '#' . ($i + 1) }}
                    </span>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $player->siswa->nama ?? '-' }}</p>
                        <p class="text-xs text-gray-500">{{ $player->siswa->nis ?? '-' }}</p>
                    </div>
                </div>
                <span class="font-bold text-indigo-600">{{ $player->total }} poin</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- MAP CREATE/EDIT MODAL --}}
    <div x-show="showMapModal" x-cloak class="fixed inset-0 z-[110] flex items-start justify-center overflow-y-auto bg-black/50 p-3 sm:items-center sm:p-4" x-transition>
        <div @click.outside="showMapModal = false" class="pkg-modal flex max-h-[calc(100vh-1.5rem)] min-h-0 w-full max-w-lg flex-col overflow-hidden sm:max-h-[92vh]">
            <div class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700 sm:px-6">
                <h3 class="text-base font-bold text-gray-900 dark:text-white sm:text-lg" x-text="editingMapId ? 'Edit Peta' : 'Tambah Peta Baru'"></h3>
                <button type="button" @click="showMapModal = false" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200" aria-label="Tutup modal">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4 sm:px-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Peta</label>
                    <input type="text" x-model="mapForm.nama" class="pkg-field w-full rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                    <textarea x-model="mapForm.deskripsi" rows="2" class="pkg-field w-full rounded-lg px-3 py-2"></textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ukuran Grid</label>
                        <select x-model="mapForm.grid_size" class="pkg-field w-full rounded-lg px-3 py-2">
                            <option value="5">5x5</option>
                            <option value="8">8x8</option>
                            <option value="10">10x10</option>
                            <option value="12">12x12</option>
                            <option value="15">15x15</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tema</label>
                        <select x-model="mapForm.background_theme" class="pkg-field w-full rounded-lg px-3 py-2">
                            <option value="grass">Padang Rumput</option>
                            <option value="desert">Gurun</option>
                            <option value="castle">Kastil</option>
                            <option value="forest">Hutan</option>
                            <option value="snow">Salju</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tingkat Kesulitan Musuh</label>
                    <select x-model="mapForm.difficulty" class="pkg-field w-full rounded-lg px-3 py-2">
                        <option value="easy">Easy (Lambat)</option>
                        <option value="medium">Medium (Sedang)</option>
                        <option value="hard">Hard (Cepat)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preset Cepat</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="preset in mapPresets" :key="preset.key">
                            <button type="button"
                                @click="applyMapPreset(preset.key)"
                                class="rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-indigo-500 dark:hover:bg-indigo-900/20">
                                <span x-text="preset.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durasi Tameng</label>
                        <div class="relative">
                            <input type="number" min="1" max="60" x-model.number="mapForm.shield_duration_seconds" class="pkg-field w-full rounded-lg px-3 py-2 pr-14">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">detik</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Peluru per Pickup</label>
                        <input type="number" min="1" max="999" x-model.number="mapForm.ammo_per_pickup" class="pkg-field w-full rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Pickup Tameng</label>
                        <input type="number" min="0" max="10" x-model.number="mapForm.shield_pickups_count" class="pkg-field w-full rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Pickup Peluru</label>
                        <input type="number" min="0" max="50" x-model.number="mapForm.ammo_pickups_count" class="pkg-field w-full rounded-lg px-3 py-2">
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Saat game dimulai, tameng dan pickup peluru akan muncul acak di jalur yang bisa dilalui pemain.
                </p>
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 p-3 text-xs text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-900/10 dark:text-indigo-100">
                    <p class="font-semibold">Ringkasan Setelan Tempur</p>
                    <p class="mt-1">
                        <span x-text="difficultyLabel(mapForm.difficulty)"></span> |
                        Tameng <span x-text="mapForm.shield_duration_seconds"></span>d |
                        Pickup tameng <span x-text="mapForm.shield_pickups_count"></span> |
                        Pickup peluru <span x-text="mapForm.ammo_pickups_count"></span> |
                        Peluru/pickup <span x-text="mapForm.ammo_per_pickup"></span>
                    </p>
                </div>
                <div x-show="hasBalanceWarning()" class="rounded-xl border border-amber-200 bg-amber-50/90 p-3 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-900/10 dark:text-amber-100">
                    <p class="font-semibold">Perhatian Balancing</p>
                    <p class="mt-1" x-text="balanceWarningMessage()"></p>
                </div>

                {{-- PANEL BOS (Mode Petualangan) --}}
                <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 dark:border-rose-900/40 dark:bg-rose-900/10">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" x-model="mapForm.boss_enabled" class="pkg-check rounded">
                        <span class="text-sm font-bold text-rose-800 dark:text-rose-200">Aktifkan Bos di peta ini</span>
                    </label>
                    <p class="mt-1 text-xs text-rose-700/80 dark:text-rose-300/80">Bos raksasa muncul di peta. Pemain punya peluru tak terbatas &amp; nyawa terbatas; tembak bos sampai HP habis. Poin utama tetap dari menjawab soal NPC.</p>

                    <div x-show="mapForm.boss_enabled" x-cloak class="mt-3 space-y-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Nama Bos</label>
                                <input type="text" maxlength="120" x-model="mapForm.boss.nama" placeholder="mis. Raja Malas" class="pkg-field w-full rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Avatar Bos</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="opt in bossAvatarOptions" :key="'boss-av-'+opt.value">
                                        <button type="button" @click="mapForm.boss.avatar = opt.value"
                                            :class="mapForm.boss.avatar === opt.value ? 'ring-2 ring-rose-500 bg-rose-50 dark:bg-rose-900/30' : 'bg-white dark:bg-gray-700'"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-lg dark:border-gray-600" :title="opt.label">
                                            <span x-text="opt.icon"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">HP Bos</label>
                                <input type="number" min="50" max="5000" x-model.number="mapForm.boss.max_hp" class="pkg-field w-full rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Ukuran Bos</label>
                                <select x-model.number="mapForm.boss.size" class="pkg-field w-full rounded-lg px-3 py-2 text-sm">
                                    <option :value="2">Besar (2)</option>
                                    <option :value="3">Sangat Besar (3)</option>
                                    <option :value="4">Raksasa (4)</option>
                                    <option :value="5">Masif (5)</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kecepatan Bos</label>
                                <select x-model="mapForm.boss.move_speed" class="pkg-field w-full rounded-lg px-3 py-2 text-sm">
                                    <option value="slow">Lambat</option>
                                    <option value="normal">Normal</option>
                                    <option value="fast">Cepat</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Nyawa Pemain</label>
                                <input type="number" min="1" max="9" x-model.number="mapForm.boss.player_lives" class="pkg-field w-full rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Damage Peluru</label>
                                <input type="number" min="1" max="50" x-model.number="mapForm.boss.bullet_damage" class="pkg-field w-full rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Bonus Poin Menang</label>
                                <input type="number" min="0" max="200" x-model.number="mapForm.boss.reward_points" class="pkg-field w-full rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 p-2 dark:border-gray-600">
                                <p class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Titik Spawn Bos (x, y)</p>
                                <div class="flex gap-2">
                                    <input type="number" min="0" :max="mapForm.grid_size - 1" x-model.number="mapForm.boss.spawn.x" class="pkg-field w-full rounded-lg px-2 py-1 text-sm" placeholder="x">
                                    <input type="number" min="0" :max="mapForm.grid_size - 1" x-model.number="mapForm.boss.spawn.y" class="pkg-field w-full rounded-lg px-2 py-1 text-sm" placeholder="y">
                                </div>
                            </div>
                            <div class="rounded-lg border border-emerald-200 p-2 dark:border-emerald-800">
                                <p class="mb-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">Base / Zona Aman (x, y, radius)</p>
                                <div class="flex gap-2">
                                    <input type="number" min="0" :max="mapForm.grid_size - 1" x-model.number="mapForm.boss.safe_zone.x" class="pkg-field w-full rounded-lg px-2 py-1 text-sm" placeholder="x">
                                    <input type="number" min="0" :max="mapForm.grid_size - 1" x-model.number="mapForm.boss.safe_zone.y" class="pkg-field w-full rounded-lg px-2 py-1 text-sm" placeholder="y">
                                    <input type="number" min="0" max="4" x-model.number="mapForm.boss.safe_zone.radius" class="pkg-field w-full rounded-lg px-2 py-1 text-sm" placeholder="r">
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Bos tidak masuk / tidak menyerang di dalam zona aman. Pemain bisa berlindung di sana.</p>

                        <div class="rounded-lg border border-purple-200 bg-purple-50/60 p-2 dark:border-purple-900/40 dark:bg-purple-900/10">
                            <p class="mb-2 text-xs font-bold text-purple-800 dark:text-purple-200">Respawn Bos (biar makin menantang)</p>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Respawn (detik)</label>
                                    <input type="number" min="0" max="60" x-model.number="mapForm.boss.respawn_seconds" class="pkg-field w-full rounded-lg px-2 py-1 text-sm" placeholder="0 = mati">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Maks. Respawn</label>
                                    <input type="number" min="0" max="20" x-model.number="mapForm.boss.respawn_count" class="pkg-field w-full rounded-lg px-2 py-1 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">HP Naik / Respawn (%)</label>
                                    <input type="number" min="0" max="200" x-model.number="mapForm.boss.respawn_hp_growth" class="pkg-field w-full rounded-lg px-2 py-1 text-sm">
                                </div>
                            </div>
                            <p class="mt-1 text-[11px] text-purple-700/80 dark:text-purple-300/80">Respawn detik = 0 berarti bos mati sekali kalah. Tiap bangkit, HP bos bertambah sesuai persen di atas. Bonus poin hanya diberikan sekali (saat pertama tumbang).</p>
                        </div>

                        <div class="rounded-lg border border-orange-200 bg-orange-50/60 p-2 dark:border-orange-900/40 dark:bg-orange-900/10">
                            <p class="mb-2 text-xs font-bold text-orange-800 dark:text-orange-200">Tantangan Lanjutan</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" x-model="mapForm.boss.boss_shoots" class="pkg-check rounded">
                                    Bos menembak proyektil ke pemain
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" x-model="mapForm.boss.shrink_safezone" class="pkg-check rounded">
                                    Zona aman menyusut tiap respawn
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" x-model="mapForm.boss.spawn_minions" class="pkg-check rounded">
                                    Minion muncul saat HP bos &lt; 50%
                                </label>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kecepatan +/Respawn (%)</label>
                                    <input type="number" min="0" max="80" x-model.number="mapForm.boss.respawn_speed_growth" class="pkg-field w-full rounded-lg px-2 py-1 text-sm">
                                </div>
                            </div>
                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Drop Darah (pemulih nyawa)</label>
                                    <input type="number" min="0" max="10" x-model.number="mapForm.boss.health_drops_count" class="pkg-field w-full rounded-lg px-2 py-1 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Drop Energi (untuk skill)</label>
                                    <input type="number" min="0" max="10" x-model.number="mapForm.boss.energy_drops_count" class="pkg-field w-full rounded-lg px-2 py-1 text-sm">
                                </div>
                            </div>
                            <p class="mt-1 text-[11px] text-orange-700/80 dark:text-orange-300/80">Saat lawan bos, pemain punya skill: Lari (dash), Ulti (serangan besar), dan Mode Rage (tanpa cooldown sementara). Energi didapat dari menjawab NPC benar &amp; mengambil drop energi.</p>
                        </div>
                    </div>
                </div>

                <div x-show="editingMapId">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="mapForm.is_active" class="pkg-check rounded">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                    </label>
                </div>
            </div>

            <div class="grid flex-shrink-0 grid-cols-2 gap-2 border-t border-gray-200 px-4 py-3 dark:border-gray-700 sm:px-6">
                <button @click="showMapModal = false" class="btn-secondary">Batal</button>
                <button @click="saveMap()" :disabled="saving" class="btn-primary disabled:opacity-50">
                    <span x-show="!saving">Simpan</span>
                    <span x-show="saving">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- NPC MANAGEMENT MODAL --}}
    <div x-show="showNpcModal" x-cloak class="fixed inset-0 z-[110] bg-black/50" x-transition>
        <div class="fixed inset-0 z-[110] flex items-start justify-center p-0 sm:items-center sm:p-4">
            <div @click.outside="showNpcModal = false" 
                 class="pkg-modal flex h-full min-h-0 w-full flex-col overflow-hidden sm:h-auto sm:max-h-[92vh] sm:max-w-3xl sm:rounded-2xl">
                
                {{-- Sticky Header --}}
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <div class="flex min-w-0 items-center gap-2">
                        <button @click="showNpcModal = false" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400" title="Kembali">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <h3 class="min-w-0 truncate text-base font-bold text-gray-900 dark:text-white sm:text-lg">Editor Peta & NPC - <span x-text="selectedMap?.nama"></span></h3>
                    </div>
                    <button @click="showNpcModal = false" class="hidden rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 sm:inline-flex">Kembali</button>
                </div>

                {{-- Scrollable Body --}}
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-3 sm:p-4">
                    
                    {{-- Visual Grid Preview - Collapsible --}}
                    <div x-data="{ gridOpen: true }" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl overflow-hidden">
                        <button @click="gridOpen = !gridOpen" class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Grid Editor - Preview 2D gameplay</span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="gridOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="gridOpen" x-collapse>
                            <div class="px-3 pb-3 flex flex-col items-center">
                                <div class="flex flex-wrap gap-1.5 sm:gap-2 mb-3 w-full justify-center">
                                    <button @click="editorMode = 'npc'" :class="editorMode === 'npc' ? 'bg-blue-500 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300'" class="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-bold transition">NPC</button>
                                    <button @click="editorMode = 'obstacle'" :class="editorMode === 'obstacle' ? 'bg-orange-500 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300'" class="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-bold transition">Tembok</button>
                                    <button @click="editorMode = 'enemy'" :class="editorMode === 'enemy' ? 'bg-red-500 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300'" class="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-bold transition">Musuh</button>
                                    <button @click="editorMode = 'erase'" :class="editorMode === 'erase' ? 'bg-purple-500 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300'" class="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-bold transition">Hapus</button>
                                </div>
                                
                                <div class="mx-auto grid gap-px overflow-hidden rounded-lg bg-gray-300 dark:bg-gray-600"
                                     :style="editorGridStyle()">
                                    <template x-for="displayY in editorRows()" :key="'gy-'+displayY">
                                        <template x-for="x in editorGridSize()" :key="'gx-'+x+'-'+displayY">
                                            <div @click="handleGridClick(x-1, displayY)" 
                                                 class="aspect-square flex items-center justify-center cursor-pointer transition-colors"
                                                 :class="bgClassForCell(x-1, displayY)"
                                                 :style="'font-size:' + editorCellFontSize() + 'px'">
                                                <span x-text="getContentForCell(x-1, displayY)"></span>
                                            </div>
                                        </template>
                                    </template>
                                </div>

                                <div class="mt-4 w-full flex justify-center">
                                    <button @click="saveEditor()" :disabled="saving" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition disabled:opacity-50">
                                        <span x-show="!saving">Simpan Rintangan & Musuh</span>
                                        <span x-show="saving">Menyimpan...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3D Preview --}}
                    <div x-data="{ previewOpen: false }" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl overflow-hidden">
                        <button @click="previewOpen = !previewOpen; if (previewOpen) { $nextTick(() => window.pkgLoadRpg3dScene?.($refs.adminRpg3dPreview)?.catch(error => console.error('Gagal memuat preview 3D RPG', error))); }" class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Preview 3D - Tampilan first-person dari grid aktif</span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="previewOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="previewOpen" x-collapse>
                            <div class="px-3 pb-3">
                                <div
                                    x-ref="adminRpg3dPreview"
                                    id="admin-rpg-3d-preview"
                                    data-rpg-3d-scene
                                    data-rpg-3d-provider="pkgAdminRpg3dState"
                                    data-rpg-3d-readonly="true"
                                    class="pkg-rpg-3d-scene pkg-rpg-3d-scene--admin"
                                ></div>
                            </div>
                        </div>
                    </div>

                    {{-- NPC List --}}
                    <div class="space-y-2">
                        <template x-for="npc in mapNpcs" :key="npc.id">
                            <div class="flex flex-col gap-2 rounded-xl bg-gray-50 p-2.5 dark:bg-gray-700/50 sm:flex-row sm:items-center sm:justify-between sm:p-3">
                                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                                    <span class="text-xl sm:text-2xl flex-shrink-0" x-text="getNpcAvatarIcon(npc.avatar)"></span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-sm text-gray-900 dark:text-white truncate" x-text="npc.nama"></p>
                                        <p class="text-xs text-gray-500">(<span x-text="npc.pos_x"></span>,<span x-text="npc.pos_y"></span>) | <span x-text="npc.poin"></span> pt</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-1 sm:flex sm:flex-shrink-0">
                                    <button @click="editNpc(npc)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg text-sm">Edit</button>
                                    <button @click="deleteNpc(npc.id)" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg text-sm">Hapus</button>
                                </div>
                            </div>
                        </template>
                        <div x-show="mapNpcs.length === 0" class="text-center text-gray-400 py-4 text-sm">
                            Belum ada NPC. Klik pada grid untuk menambahkan.
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Musuh Aktif</h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Atur avatar, kecepatan, dan kepintaran musuh.</span>
                        </div>
                        <template x-for="(enemy, index) in mapEnemies" :key="'enemy-'+index+'-'+enemy.x+'-'+enemy.y">
                            <div class="rounded-xl bg-red-50/80 p-3 dark:bg-red-900/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl sm:text-2xl" x-text="getEnemyAvatarIcon(enemy.avatar)"></span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Musuh <span x-text="index + 1"></span></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Posisi (<span x-text="enemy.x"></span>, <span x-text="enemy.y"></span>)</p>
                                        </div>
                                    </div>
                                    <button @click="removeEnemy(index)" class="rounded-lg px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30">Hapus</button>
                                </div>
                                <div class="mt-3 grid gap-3 md:grid-cols-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Avatar Musuh</label>
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="option in enemyAvatarOptions" :key="'enemy-av-'+option.value">
                                                <button type="button"
                                                    @click="enemy.avatar = option.value"
                                                    :class="enemy.avatar === option.value ? 'ring-2 ring-red-500 bg-red-50 dark:bg-red-900/30' : 'bg-white dark:bg-gray-700'"
                                                    class="flex h-9 w-9 items-center justify-center rounded-lg border text-base">
                                                    <span x-text="option.icon"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kecepatan</label>
                                        <select x-model="enemy.speed_level" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <template x-for="option in enemySpeedOptions" :key="'speed-'+option.value">
                                                <option :value="option.value" x-text="option.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kepintaran</label>
                                        <select x-model="enemy.intelligence_level" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <template x-for="option in enemyIntelligenceOptions" :key="'intel-'+option.value">
                                                <option :value="option.value" x-text="option.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="mapEnemies.length === 0" class="text-center text-gray-400 py-4 text-sm">
                            Belum ada musuh. Pilih mode Musuh lalu klik grid untuk menambahkan.
                        </div>
                    </div>

                    {{-- NPC Add/Edit Form --}}
                    <div x-show="showNpcForm" x-transition class="border-2 border-indigo-200 dark:border-indigo-800 rounded-xl p-3 sm:p-4 bg-indigo-50/50 dark:bg-indigo-900/10">
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-3" x-text="editingNpcId ? 'Edit NPC' : 'Tambah NPC'"></h4>
                        
                        {{-- Row 1: Name + Avatar --}}
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nama NPC</label>
                                <input type="text" x-model="npcForm.nama" placeholder="Nama NPC..." class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Avatar</label>
                                <div class="flex gap-1.5 flex-wrap">
                                    <template x-for="option in npcAvatarOptions" :key="option.value">
                                        <button @click="npcForm.avatar = option.value" :class="npcForm.avatar === option.value ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'bg-white dark:bg-gray-700'"
                                                class="flex h-10 w-10 items-center justify-center rounded-lg border text-base">
                                            <span x-text="option.icon"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: Position + Points --}}
                        <div class="mt-3 grid gap-2 sm:grid-cols-3 sm:gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pos X</label>
                                <input type="number" x-model.number="npcForm.pos_x" min="0" class="w-full px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pos Y</label>
                                <input type="number" x-model.number="npcForm.pos_y" min="0" class="w-full px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Poin</label>
                                <input type="number" x-model.number="npcForm.poin" min="1" class="w-full px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            </div>
                        </div>

                        {{-- Row 3: Question --}}
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pertanyaan</label>
                            <textarea x-model="npcForm.pertanyaan" rows="2" placeholder="Tulis pertanyaan..." class="w-full px-3 py-1.5 sm:py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm resize-none"></textarea>
                        </div>

                        {{-- Row 4: Answer Choices --}}
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pilihan Jawaban</label>
                            <div class="space-y-1.5">
                                <template x-for="(choice, idx) in npcForm.pilihan_jawaban" :key="'choice-'+idx">
                                    <div class="flex items-center gap-1.5 sm:gap-2">
                                        <button @click="npcForm.jawaban_benar = idx" 
                                                :class="npcForm.jawaban_benar === idx ? 'bg-green-500 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'"
                                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 transition-all"
                                                :title="'Tandai sebagai jawaban benar'"
                                                x-text="['A','B','C','D'][idx]"></button>
                                        <input type="text" x-model="npcForm.pilihan_jawaban[idx]" 
                                               :placeholder="'Jawaban ' + ['A','B','C','D'][idx]"
                                               class="flex-1 px-2 sm:px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                    </div>
                                </template>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Klik huruf untuk menandai jawaban benar</p>
                        </div>

                        {{-- Form Buttons --}}
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <button @click="showNpcForm = false" class="btn-secondary text-sm">Batal</button>
                            <button @click="saveNpc()" :disabled="saving" class="btn-primary text-sm disabled:opacity-50">
                                <span x-show="!saving">Simpan NPC</span>
                                <span x-show="saving">Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>

<script>
function rpgAdmin() {
    return {
        // Map modal
        showMapModal: false,
        editingMapId: null,
        mapForm: {
            nama: '',
            deskripsi: '',
            grid_size: 10,
            background_theme: 'grass',
            difficulty: 'easy',
            shield_duration_seconds: 8,
            ammo_per_pickup: 3,
            shield_pickups_count: 1,
            ammo_pickups_count: 2,
            obstacles: [],
            enemies: [],
            boss_enabled: false,
            boss: {
                nama: 'Bos', avatar: @js(\App\Support\RpgCatalog::bossAvatarOptions()[0]['value']),
                max_hp: 300, size: 3, move_speed: 'normal',
                player_lives: 3, bullet_damage: 10, reward_points: 25,
                respawn_seconds: 0, respawn_count: 3, respawn_hp_growth: 25,
                boss_shoots: true, respawn_speed_growth: 15, shrink_safezone: true,
                spawn_minions: true, health_drops_count: 2, energy_drops_count: 3,
                spawn: { x: 9, y: 0 }, safe_zone: { x: 0, y: 9, radius: 1 },
            },
            is_active: true
        },
        bossAvatarOptions: @json(\App\Support\RpgCatalog::bossAvatarOptions()),
        saving: false,

        // NPC modal
        showNpcModal: false,
        showNpcForm: false,
        selectedMap: null,
        mapNpcs: [],
        editingNpcId: null,
        npcForm: { 
            rpg_map_id: null, nama: '', avatar: @js(\App\Support\RpgCatalog::npcAvatarOptions()[0]['value']), pos_x: 0, pos_y: 0, 
            pertanyaan: '', pilihan_jawaban: ['','','',''], jawaban_benar: 0, poin: 10 
        },
        npcAvatarOptions: @json(\App\Support\RpgCatalog::npcAvatarOptions()),
        enemyAvatarOptions: @json(\App\Support\RpgCatalog::enemyAvatarOptions()),
        enemySpeedOptions: @json(\App\Support\RpgCatalog::enemySpeedOptions()),
        enemyIntelligenceOptions: @json(\App\Support\RpgCatalog::enemyIntelligenceOptions()),
        npcAvatarLookup: @json(\App\Support\RpgCatalog::npcAvatarLookup()),
        enemyAvatarLookup: @json(\App\Support\RpgCatalog::enemyAvatarLookup()),
        mapPresets: [
            { key: 'relaxed', label: 'Santai' },
            { key: 'balanced', label: 'Seimbang' },
            { key: 'challenge', label: 'Tantangan' },
        ],
        mapsIndex: @json($mapsIndex),
        mapFilters: {
            search: '',
            difficulty: 'all',
            status: 'all',
            preset: 'all',
            sort: 'newest',
        },

        // Editor modal mode
        editorMode: 'npc', // npc, obstacle, enemy, erase
        mapObstacles: [],
        mapEnemies: [],

        init() {
            window.pkgAdminRpg3dState = () => this.getThreePreviewState();
        },

        getThreePreviewState() {
            const map = this.selectedMap || this.mapForm;
            const gridSize = parseInt(map?.grid_size || this.mapForm.grid_size || 10);
            const npcs = (this.mapNpcs || []).map(npc => ({
                ...npc,
                is_active: npc.is_active !== false,
            }));

            return {
                map: {
                    grid_size: gridSize,
                    background_theme: map?.background_theme || 'grass',
                    difficulty: map?.difficulty || 'easy',
                },
                session: { pos_x: 0, pos_y: 0, answered_npcs: [] },
                npcs,
                obstacles: this.mapObstacles || [],
                enemies: this.mapEnemies || [],
                pickups: { shield: [], ammo: [] },
                shieldActive: false,
                shieldSecondsLeft: 0,
                ammo: 0,
                answeredCount: 0,
                totalNpcs: npcs.length,
            };
        },

        editorGridSize() {
            return Math.max(1, parseInt(this.selectedMap?.grid_size || this.mapForm.grid_size || 10));
        },

        editorRows() {
            const gridSize = this.editorGridSize();
            return Array.from({ length: gridSize }, (_, index) => gridSize - 1 - index);
        },

        editorGridStyle() {
            const gridSize = this.editorGridSize();
            return `grid-template-columns: repeat(${gridSize}, minmax(0, 1fr)); width: min(100%, 350px); max-width: 350px;`;
        },

        editorCellFontSize() {
            return Math.max(8, Math.min(14, 280 / this.editorGridSize()));
        },

        resetMapForm() {
            this.editingMapId = null;
            this.mapForm = {
                nama: '',
                deskripsi: '',
                grid_size: 10,
                background_theme: 'grass',
                is_active: true,
                difficulty: 'easy',
                shield_duration_seconds: 8,
                ammo_per_pickup: 3,
                shield_pickups_count: 1,
                ammo_pickups_count: 2,
                obstacles: [],
                enemies: [],
                boss_enabled: false,
                boss: this.defaultBoss()
            };
        },

        defaultBoss() {
            return {
                nama: 'Bos', avatar: this.bossAvatarOptions[0]?.value || '👹',
                max_hp: 300, size: 3, move_speed: 'normal',
                player_lives: 3, bullet_damage: 10, reward_points: 25,
                respawn_seconds: 0, respawn_count: 3, respawn_hp_growth: 25,
                boss_shoots: true, respawn_speed_growth: 15, shrink_safezone: true,
                spawn_minions: true, health_drops_count: 2, energy_drops_count: 3,
                spawn: { x: 9, y: 0 }, safe_zone: { x: 0, y: 9, radius: 1 },
            };
        },

        applyMapPreset(key) {
            const preset = {
                relaxed: {
                    difficulty: 'easy',
                    shield_duration_seconds: 12,
                    ammo_per_pickup: 4,
                    shield_pickups_count: 2,
                    ammo_pickups_count: 4,
                },
                balanced: {
                    difficulty: 'medium',
                    shield_duration_seconds: 8,
                    ammo_per_pickup: 3,
                    shield_pickups_count: 1,
                    ammo_pickups_count: 2,
                },
                challenge: {
                    difficulty: 'hard',
                    shield_duration_seconds: 6,
                    ammo_per_pickup: 2,
                    shield_pickups_count: 1,
                    ammo_pickups_count: 1,
                },
            }[key];

            if (!preset) return;

            this.mapForm = {
                ...this.mapForm,
                ...preset,
            };
        },

        difficultyLabel(value) {
            return {
                easy: 'Easy',
                medium: 'Medium',
                hard: 'Hard',
            }[value] || 'Easy';
        },

        matchesMap(map, presetKey) {
            const search = (this.mapFilters.search || '').toLowerCase();
            const matchesSearch = !search || String(map.nama || '').toLowerCase().includes(search);
            const matchesDifficulty = this.mapFilters.difficulty === 'all' || (map.difficulty || 'easy') === this.mapFilters.difficulty;
            const matchesStatus =
                this.mapFilters.status === 'all' ||
                (this.mapFilters.status === 'active' ? !!map.is_active : !map.is_active);
            const matchesPreset = this.mapFilters.preset === 'all' || presetKey === this.mapFilters.preset;

            return matchesSearch && matchesDifficulty && matchesStatus && matchesPreset;
        },

        countMatchingMaps() {
            return this.mapsIndex.filter((map) => this.matchesMap(map, map.preset_key || 'custom')).length;
        },

        hasActiveMapFilters() {
            return this.mapFilters.search !== ''
                || this.mapFilters.difficulty !== 'all'
                || this.mapFilters.status !== 'all'
                || this.mapFilters.preset !== 'all'
                || this.mapFilters.sort !== 'newest';
        },

        resetMapFilters() {
            this.mapFilters = {
                search: '',
                difficulty: 'all',
                status: 'all',
                preset: 'all',
                sort: 'newest',
            };
        },

        sortOrderForMap(map) {
            switch (this.mapFilters.sort) {
                case 'oldest':
                    return 100000 - Number(map.id || 0);
                case 'npc_desc':
                    return 100000 - Number(map.npcs_count || 0);
                case 'sessions_desc':
                    return 100000 - Number(map.game_sessions_count || 0);
                case 'newest':
                default:
                    return Number(map.id || 0) * -1;
            }
        },

        hasBalanceWarning() {
            return this.balanceWarningMessage() !== '';
        },

        balanceWarningMessage() {
            const shieldCount = Number(this.mapForm.shield_pickups_count || 0);
            const ammoCount = Number(this.mapForm.ammo_pickups_count || 0);
            const ammoPerPickup = Number(this.mapForm.ammo_per_pickup || 0);
            const difficulty = this.mapForm.difficulty || 'easy';

            if (difficulty === 'hard' && shieldCount === 0 && ammoCount === 0) {
                return 'Map hard tanpa pickup tameng dan tanpa pickup peluru cenderung terlalu sulit untuk pemain baru.';
            }

            if (difficulty === 'hard' && ammoCount <= 1 && ammoPerPickup <= 2) {
                return 'Map hard dengan pickup peluru yang sangat sedikit bisa membuat auto-tembak jarang aktif.';
            }

            if (difficulty === 'easy' && shieldCount >= 3 && ammoCount >= 4 && ammoPerPickup >= 4) {
                return 'Map easy ini sangat ringan. Cocok untuk demo, tetapi mungkin terlalu mudah untuk permainan reguler.';
            }

            if (shieldCount === 0 && ammoCount > 0) {
                return 'Tidak ada pickup tameng. Pemain hanya mengandalkan peluru dan gerak menghindar.';
            }

            if (ammoCount === 0 && shieldCount > 0) {
                return 'Tidak ada pickup peluru. Auto-tembak tidak akan aktif di map ini.';
            }

            return '';
        },

        editMap(id, map) {
            this.editingMapId = id;
            
            const safeParse = (data) => {
                if (!data) return [];
                if (Array.isArray(data)) return [...data];
                try {
                    const parsed = JSON.parse(data);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    return [];
                }
            };

            this.mapForm = { 
                nama: map.nama, deskripsi: map.deskripsi || '', 
                grid_size: map.grid_size, background_theme: map.background_theme, 
                is_active: map.is_active, difficulty: map.difficulty || 'easy',
                shield_duration_seconds: parseInt(map.shield_duration_seconds || 8),
                ammo_per_pickup: parseInt(map.ammo_per_pickup || 3),
                shield_pickups_count: parseInt(map.shield_pickups_count ?? 1),
                ammo_pickups_count: parseInt(map.ammo_pickups_count ?? 2),
                obstacles: safeParse(map.obstacles),
                enemies: safeParse(map.enemies).map(enemy => this.normalizeEnemy(enemy)),
                boss_enabled: !!(map.boss_enabled),
                boss: this.parseBossConfig(map.boss_config)
            };
            this.showMapModal = true;
        },

        parseBossConfig(data) {
            const fallback = this.defaultBoss();
            if (!data) return fallback;
            let cfg = data;
            if (typeof data === 'string') {
                try { cfg = JSON.parse(data); } catch (e) { return fallback; }
            }
            if (!cfg || typeof cfg !== 'object') return fallback;
            return {
                nama: cfg.nama || fallback.nama,
                avatar: cfg.avatar || fallback.avatar,
                max_hp: parseInt(cfg.max_hp ?? fallback.max_hp),
                size: parseInt(cfg.size ?? fallback.size),
                move_speed: cfg.move_speed || fallback.move_speed,
                player_lives: parseInt(cfg.player_lives ?? fallback.player_lives),
                bullet_damage: parseInt(cfg.bullet_damage ?? fallback.bullet_damage),
                reward_points: parseInt(cfg.reward_points ?? fallback.reward_points),
                respawn_seconds: parseInt(cfg.respawn_seconds ?? fallback.respawn_seconds),
                respawn_count: parseInt(cfg.respawn_count ?? fallback.respawn_count),
                respawn_hp_growth: parseInt(cfg.respawn_hp_growth ?? fallback.respawn_hp_growth),
                boss_shoots: cfg.boss_shoots ?? fallback.boss_shoots,
                respawn_speed_growth: parseInt(cfg.respawn_speed_growth ?? fallback.respawn_speed_growth),
                shrink_safezone: cfg.shrink_safezone ?? fallback.shrink_safezone,
                spawn_minions: cfg.spawn_minions ?? fallback.spawn_minions,
                health_drops_count: parseInt(cfg.health_drops_count ?? fallback.health_drops_count),
                energy_drops_count: parseInt(cfg.energy_drops_count ?? fallback.energy_drops_count),
                spawn: {
                    x: parseInt(cfg.spawn?.x ?? fallback.spawn.x),
                    y: parseInt(cfg.spawn?.y ?? fallback.spawn.y),
                },
                safe_zone: {
                    x: parseInt(cfg.safe_zone?.x ?? fallback.safe_zone.x),
                    y: parseInt(cfg.safe_zone?.y ?? fallback.safe_zone.y),
                    radius: parseInt(cfg.safe_zone?.radius ?? fallback.safe_zone.radius),
                },
            };
        },

        async saveMap() {
            this.saving = true;
            try {
                if (!this.mapForm.nama?.trim()) {
                    window.showNotification('Nama peta wajib diisi.', 'warning');
                    return;
                }

                const url = this.editingMapId 
                    ? `/rpg-admin/maps/${this.editingMapId}`
                    : '/rpg-admin/maps';
                const method = this.editingMapId ? 'PUT' : 'POST';
                
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.mapForm)
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    window.showNotification(firstError || data.message || 'Gagal menyimpan peta', 'error');
                }
            } catch (e) {
                window.showNotification(e.message || 'Terjadi kesalahan saat menyimpan peta', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteMap(id) {
            const confirmed = await window.showConfirmation('Hapus peta ini? Semua NPC dan sesi akan ikut terhapus.', {
                title: 'Hapus peta',
                confirmText: 'Hapus',
                tone: 'danger'
            });
            if (!confirmed) return;
            try {
                const res = await fetch(`/rpg-admin/maps/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) location.reload();
            } catch (e) {
                window.showNotification(e.message || 'Terjadi kesalahan saat menghapus peta', 'error');
            }
        },

        async duplicateMap(id) {
            const confirmed = await window.showConfirmation('Duplikat peta ini sebagai draft baru?', {
                title: 'Duplikat peta',
                confirmText: 'Duplikat',
                tone: 'info'
            });
            if (!confirmed) return;

            try {
                const res = await fetch(`/rpg-admin/maps/${id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    window.showNotification(firstError || data.message || 'Gagal menduplikat peta', 'error');
                }
            } catch (e) {
                window.showNotification(e.message || 'Terjadi kesalahan saat menduplikat peta', 'error');
            }
        },

        async manageNpcs(mapId, map) {
            this.selectedMap = map;
            this.showNpcModal = true;
            this.showNpcForm = false;
            this.editorMode = 'npc';
            
            const safeParse = (data) => {
                if (!data) return [];
                if (Array.isArray(data)) return [...data];
                try {
                    const parsed = JSON.parse(data);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    return [];
                }
            };

            this.mapObstacles = safeParse(map.obstacles);
            this.mapEnemies = safeParse(map.enemies).map(enemy => this.normalizeEnemy(enemy));
            
            // Fetch NPCs
            try {
                const res = await fetch(`/rpg-admin/maps/${mapId}/detail`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedMap = {
                        ...this.selectedMap,
                        ...data.data,
                        shield_duration_seconds: parseInt(data.data.shield_duration_seconds || this.selectedMap.shield_duration_seconds || 8),
                        ammo_per_pickup: parseInt(data.data.ammo_per_pickup || this.selectedMap.ammo_per_pickup || 3),
                        shield_pickups_count: parseInt(data.data.shield_pickups_count ?? this.selectedMap.shield_pickups_count ?? 1),
                        ammo_pickups_count: parseInt(data.data.ammo_pickups_count ?? this.selectedMap.ammo_pickups_count ?? 2),
                    };
                    this.mapNpcs = data.data.npcs || [];
                    this.mapObstacles = safeParse(data.data.obstacles);
                    this.mapEnemies = safeParse(data.data.enemies).map(enemy => this.normalizeEnemy(enemy));
                }
            } catch (e) {
                console.error(e);
            }
        },

        getNpcAtPos(x, y) {
            return this.mapNpcs.find(n => n.pos_x === x && n.pos_y === y);
        },

        handleGridClick(x, y) {
            if (this.editorMode === 'npc') {
                this.startAddNpc(x, y);
            } else {
                this.toggleCell(x, y);
            }
        },

        bgClassForCell(x, y) {
            if (this.getNpcAtPos(x, y)) return 'bg-yellow-200 dark:bg-yellow-900 border border-yellow-400 dark:border-yellow-700';
            if (this.mapObstacles.some(o => o.x === x && o.y === y)) return 'bg-orange-800 dark:bg-orange-900 text-white border border-orange-900';
            if (this.mapEnemies.some(e => e.x === x && e.y === y)) return 'bg-red-200 dark:bg-red-900/50 border border-red-400';
            return 'bg-green-100 dark:bg-green-900/40 hover:bg-green-200 dark:hover:bg-green-800/60 border border-green-200 dark:border-green-800';
        },

        getContentForCell(x, y) {
            const npc = this.getNpcAtPos(x, y);
            if (npc) return this.getNpcAvatarIcon(npc.avatar);
            if (this.mapObstacles.some(o => o.x === x && o.y === y)) return '##';
            const enemy = this.mapEnemies.find(e => e.x === x && e.y === y);
            if (enemy) return this.getEnemyAvatarIcon(enemy.avatar);
            return '';
        },

        getNpcAvatarIcon(avatar) {
            return this.npcAvatarLookup[avatar] || avatar || this.npcAvatarOptions[0].icon;
        },

        getEnemyAvatarIcon(avatar) {
            return this.enemyAvatarLookup[avatar] || avatar || this.enemyAvatarOptions[0].icon;
        },

        normalizeEnemyAvatar(avatar) {
            const resolvedAvatar = this.enemyAvatarLookup[avatar] || avatar;
            const matched = this.enemyAvatarOptions.find(option => option.value === resolvedAvatar || option.icon === resolvedAvatar);
            return matched ? matched.value : this.enemyAvatarOptions[0].value;
        },

        normalizeEnemy(enemy) {
            return {
                x: Number(enemy?.x ?? 0),
                y: Number(enemy?.y ?? 0),
                avatar: this.normalizeEnemyAvatar(enemy?.avatar),
                speed_level: ['slow', 'normal', 'fast'].includes(enemy?.speed_level) ? enemy.speed_level : 'normal',
                intelligence_level: ['low', 'normal', 'high'].includes(enemy?.intelligence_level) ? enemy.intelligence_level : 'normal'
            };
        },

        toggleCell(x, y) {
            // Cek apakah ada NPC di sini, jangan tempel obstacle/enemy kalau ada NPC
            if (this.editorMode !== 'erase' && this.getNpcAtPos(x, y)) {
                window.showNotification('Terdapat NPC pada posisi ini. Hapus NPC terlebih dahulu.', 'warning');
                return;
            }

            // First remove whatever is here
            this.mapObstacles = this.mapObstacles.filter(o => !(o.x === x && o.y === y));
            this.mapEnemies = this.mapEnemies.filter(e => !(e.x === x && e.y === y));

            if (this.editorMode === 'obstacle') {
                this.mapObstacles.push({ x, y });
            } else if (this.editorMode === 'enemy') {
                this.mapEnemies.push({
                    x,
                    y,
                    avatar: this.enemyAvatarOptions[0].value,
                    speed_level: 'normal',
                    intelligence_level: 'normal'
                });
            }
        },

        removeEnemy(index) {
            this.mapEnemies.splice(index, 1);
        },

        async saveEditor() {
            this.saving = true;
            try {
                // Update the mapForm state manually
                this.mapForm = {
                    nama: this.selectedMap.nama,
                    deskripsi: this.selectedMap.deskripsi || '',
                    grid_size: parseInt(this.selectedMap.grid_size),
                    background_theme: this.selectedMap.background_theme,
                    is_active: this.selectedMap.is_active,
                    difficulty: this.selectedMap.difficulty || 'easy',
                    shield_duration_seconds: parseInt(this.selectedMap.shield_duration_seconds || 8),
                    ammo_per_pickup: parseInt(this.selectedMap.ammo_per_pickup || 3),
                    shield_pickups_count: parseInt(this.selectedMap.shield_pickups_count ?? 1),
                    ammo_pickups_count: parseInt(this.selectedMap.ammo_pickups_count ?? 2),
                    obstacles: this.mapObstacles,
                    enemies: this.mapEnemies.map(enemy => this.normalizeEnemy(enemy))
                };

                const res = await fetch(`/rpg-admin/maps/${this.selectedMap.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.mapForm)
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    window.showNotification(firstError || data.message || 'Gagal menyimpan editor peta', 'error');
                }
            } catch (e) {
                window.showNotification(e.message || 'Terjadi kesalahan saat menyimpan editor peta', 'error');
            } finally {
                this.saving = false;
            }
        },

        startAddNpc(x, y) {
            if (this.getNpcAtPos(x, y)) {
                this.editNpc(this.getNpcAtPos(x, y));
                return;
            }
            this.editingNpcId = null;
            this.npcForm = {
                rpg_map_id: this.selectedMap.id, nama: '', avatar: this.npcAvatarOptions[0].value,
                pos_x: x, pos_y: y, pertanyaan: '', pilihan_jawaban: ['','','',''],
                jawaban_benar: 0, poin: 10
            };
            this.showNpcForm = true;
        },

        editNpc(npc) {
            this.editingNpcId = npc.id;
            this.npcForm = {
                rpg_map_id: this.selectedMap.id,
                nama: npc.nama, avatar: npc.avatar, pos_x: npc.pos_x, pos_y: npc.pos_y,
                pertanyaan: npc.pertanyaan,
                pilihan_jawaban: [...(npc.pilihan_jawaban || ['','','',''])],
                jawaban_benar: npc.jawaban_benar, poin: npc.poin
            };
            // Pad to 4 choices
            while (this.npcForm.pilihan_jawaban.length < 4) this.npcForm.pilihan_jawaban.push('');
            this.showNpcForm = true;
        },

        async saveNpc() {
            // Filter empty choices
            const validChoices = this.npcForm.pilihan_jawaban.filter(c => c.trim() !== '');
            if (validChoices.length < 2) {
                window.showNotification('Minimal 2 pilihan jawaban.', 'warning');
                return;
            }
            if (!this.npcForm.nama || !this.npcForm.pertanyaan) {
                window.showNotification('Nama dan pertanyaan wajib diisi.', 'warning');
                return;
            }

            this.saving = true;
            try {
                const payload = { ...this.npcForm, pilihan_jawaban: validChoices };
                if (payload.jawaban_benar >= validChoices.length) payload.jawaban_benar = 0;
                
                const url = this.editingNpcId 
                    ? `/rpg-admin/npcs/${this.editingNpcId}`
                    : '/rpg-admin/npcs';
                const method = this.editingNpcId ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    // Refresh NPC list
                    await this.manageNpcs(this.selectedMap.id, this.selectedMap);
                    this.showNpcForm = false;
                } else {
                    window.showNotification(data.message || 'Gagal menyimpan NPC', 'error');
                }
            } catch (e) {
                window.showNotification(e.message || 'Terjadi kesalahan saat menyimpan NPC', 'error');
            }
            this.saving = false;
        },

        async deleteNpc(id) {
            const confirmed = await window.showConfirmation('Hapus NPC ini?', {
                title: 'Hapus NPC',
                confirmText: 'Hapus',
                tone: 'danger'
            });
            if (!confirmed) return;
            try {
                const res = await fetch(`/rpg-admin/npcs/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    await this.manageNpcs(this.selectedMap.id, this.selectedMap);
                }
            } catch (e) {
                window.showNotification(e.message || 'Terjadi kesalahan saat menghapus NPC', 'error');
            }
        }
    }
}
</script>
@endsection


