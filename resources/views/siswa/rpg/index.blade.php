@extends('layouts.siswa')

@section('title', 'Petualangan')

@section('content')
<div class="p-4 lg:p-6 max-w-6xl mx-auto" x-data="rpgIndex()">
    {{-- Header --}}
    <div class="pkg-page-header mb-6">
        <div>
            <h1 class="pkg-page-heading">Petualangan 29 Karakter</h1>
            <p class="pkg-page-subheading">Jelajahi peta 3D, temui NPC, dan jawab pertanyaan untuk mendapatkan poin. Tersedia juga mode 2D ringan.</p>
        </div>
    </div>

    {{-- Character Card --}}
    <div class="pkg-panel p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl" 
                     :style="'background:' + character.warna + '22; border: 2px solid ' + character.warna">
                    <span x-text="character.avatar"></span>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white" x-text="character.nama_karakter"></h3>
                    <p class="text-sm text-gray-500">Karakter Kamu</p>
                </div>
            </div>
            <button @click="showCharacterModal = true" 
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                Ubah Karakter
            </button>
        </div>
    </div>

    {{-- Maps Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($maps as $map)
        <div class="pkg-panel overflow-hidden hover:shadow-md transition-shadow group">
            {{-- Map Theme Header --}}
            <div class="h-32 relative overflow-hidden
                {{ $map->background_theme === 'grass' ? 'bg-gradient-to-br from-green-400 to-emerald-600' : '' }}
                {{ $map->background_theme === 'desert' ? 'bg-gradient-to-br from-yellow-400 to-orange-500' : '' }}
                {{ $map->background_theme === 'castle' ? 'bg-gradient-to-br from-gray-400 to-slate-600' : '' }}
                {{ $map->background_theme === 'forest' ? 'bg-gradient-to-br from-green-600 to-green-900' : '' }}
                {{ $map->background_theme === 'snow' ? 'bg-gradient-to-br from-blue-100 to-cyan-300' : '' }}
                {{ !in_array($map->background_theme, ['grass','desert','castle','forest','snow']) ? 'bg-gradient-to-br from-indigo-400 to-purple-600' : '' }}
            ">
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-5xl opacity-30 group-hover:opacity-50 transition-opacity">
                        {{ $map->background_theme === 'grass' ? '🌿' : '' }}
                        {{ $map->background_theme === 'desert' ? '🏜️' : '' }}
                        {{ $map->background_theme === 'castle' ? '🏰' : '' }}
                        {{ $map->background_theme === 'forest' ? '🌲' : '' }}
                        {{ $map->background_theme === 'snow' ? '❄️' : '' }}
                        {{ !in_array($map->background_theme, ['grass','desert','castle','forest','snow']) ? '🗺️' : '' }}
                    </span>
                </div>
                <div class="absolute top-3 right-3">
                    <span class="px-2 py-1 bg-white/90 dark:bg-gray-900/90 rounded-full text-xs font-medium">
                        {{ $map->grid_size }}×{{ $map->grid_size }}
                    </span>
                </div>
                @if(in_array($map->id, $completedMaps))
                <div class="absolute top-3 left-3">
                    <span class="px-2 py-1 bg-green-500 text-white rounded-full text-xs font-bold">Selesai</span>
                </div>
                @endif
                @if($map->boss_enabled)
                <div class="absolute bottom-3 left-3">
                    @if(in_array($map->id, $bossDefeatedMaps ?? []))
                        <span class="px-2 py-1 bg-emerald-600 text-white rounded-full text-xs font-bold">Bos Terkalahkan ✓</span>
                    @else
                        <span class="px-2 py-1 bg-rose-600 text-white rounded-full text-xs font-bold animate-pulse">⚔️ ADA BOS</span>
                    @endif
                </div>
                @endif
            </div>
            
            <div class="p-4">
                <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ $map->nama }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $map->deskripsi ?? 'Jelajahi peta dan temui NPC!' }}</p>
                <div class="flex items-center justify-between mt-3">
                    <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                        <span>👤 {{ $map->npc_count }} NPC</span>
                        @if(isset($sessions[$map->id]))
                        <span>⭐ {{ $sessions[$map->id] }} poin</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('siswa.rpg.play', $map) }}?mode=3d"
                           class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                            Main 3D
                        </a>
                        <a href="{{ route('siswa.rpg.play', $map) }}?mode=2d"
                           class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors dark:bg-gray-700 dark:text-gray-200"
                           title="Versi ringan untuk HP lama">
                            2D
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <span class="text-5xl">🗺️</span>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-4">Belum Ada Peta</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Admin belum membuat peta game. Tunggu ya!</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Character Selection Modal --}}
    <div x-show="showCharacterModal" x-cloak
         class="fixed inset-0 z-50 bg-black/50"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-0 sm:p-4">
            <div @click.outside="showCharacterModal = false" 
                 class="pkg-modal sm:rounded-2xl w-full sm:max-w-md h-full sm:h-auto sm:max-h-[90vh] flex flex-col">
                
                {{-- Sticky Header --}}
                <div class="p-4 sm:p-6 pb-0 flex-shrink-0">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pilih Karakter</h3>
                        <button @click="showCharacterModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-lg sm:hidden">X</button>
                    </div>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto px-4 sm:px-6">
                    {{-- Avatar Grid --}}
                    <div class="grid grid-cols-6 gap-2 mb-4">
                        <template x-for="av in avatarOptions" :key="av">
                            <button @click="character.avatar = av"
                                    :class="character.avatar === av ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-xl sm:text-2xl transition-all">
                                <span x-text="av"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Name --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Karakter</label>
                        <input type="text" x-model="character.nama_karakter" maxlength="50" 
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>

                    {{-- Color --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Warna Aksen</label>
                        <div class="flex gap-2 flex-wrap">
                            <template x-for="c in colorOptions" :key="c">
                                <button @click="character.warna = c"
                                        :class="character.warna === c ? 'ring-2 ring-offset-2 ring-gray-400' : ''"
                                        :style="'background:' + c"
                                        class="w-8 h-8 rounded-full transition-all"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl"
                             :style="'background:' + character.warna + '22; border: 2px solid ' + character.warna">
                            <span x-text="character.avatar"></span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="character.nama_karakter || 'Player'"></p>
                            <p class="text-xs text-gray-500">Preview</p>
                        </div>
                    </div>
                </div>

                {{-- Sticky Footer --}}
                <div class="p-4 sm:p-6 pt-3 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <div class="flex gap-2">
                        <button @click="showCharacterModal = false" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">Batal</button>
                        <button @click="saveCharacter()" :disabled="savingChar"
                                class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50">
                            <span x-show="!savingChar">Simpan</span>
                            <span x-show="savingChar">Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function rpgIndex() {
    return {
        showCharacterModal: false,
        savingChar: false,
        character: @json($character),
        avatarOptions: ['🧑‍🎓','👦','👧','🦸‍♂️','🦸‍♀️','🧙‍♂️','🧙‍♀️','🥷','🏃‍♂️','🏃‍♀️','🧑‍🚀','🦊','🐱','🐻','🐼','🐸','🦁','🐲'],
        colorOptions: ['#3B82F6','#EF4444','#10B981','#F59E0B','#8B5CF6','#EC4899','#06B6D4','#F97316'],

        async saveCharacter() {
            this.savingChar = true;
            try {
                const res = await fetch("{{ route('siswa.rpg.character') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.character)
                });
                const data = await res.json();
                if (data.success) {
                    this.showCharacterModal = false;
                }
            } catch (e) {
                console.error(e);
            }
            this.savingChar = false;
        }
    }
}
</script>
@endsection


