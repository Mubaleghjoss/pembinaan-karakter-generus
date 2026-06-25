{{-- Level Tier Info Modal --}}
{{-- Requires Alpine.js and $allLevels, $gamificationStats variables --}}
<div x-data="{ showTierModal: false }" 
     @open-tier-modal.window="showTierModal = true; document.body.style.overflow = 'hidden'"
     x-effect="if(!showTierModal) document.body.style.overflow = ''">
    
    <template x-teleport="body">
        <div x-show="showTierModal" 
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[9999] overflow-y-auto overscroll-contain" style="display: none;">
            
            <div class="fixed inset-0 bg-black/60" @click="showTierModal = false"></div>
            
            <div class="relative min-h-full flex items-start sm:items-center justify-center px-4 py-6 sm:py-10">
                <div x-show="showTierModal" @click.outside="showTierModal = false"
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                     class="relative w-full max-w-md pkg-modal overflow-hidden">
                    
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-1.5">
                                <span>🏆</span> Sistem Level & Tier
                            </h3>
                            <p class="text-indigo-200 text-[11px] sm:text-xs">Kumpulkan poin untuk naik level!</p>
                        </div>
                        <button @click="showTierModal = false" class="text-white/70 hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Level List --}}
                    <div class="px-4 py-4 space-y-3">
                        @foreach($allLevels as $lvl)
                        @php
                            $isCurrentLevel = isset($gamificationStats) && $gamificationStats && 
                                ($gamificationStats['current_level']->level ?? 0) === $lvl->level;
                            $colors = [
                                1 => ['border' => 'border-blue-300', 'bg' => 'bg-blue-50 dark:bg-blue-900/30', 'label' => 'bg-blue-500', 'dot' => 'text-blue-500'],
                                2 => ['border' => 'border-green-300', 'bg' => 'bg-green-50 dark:bg-green-900/30', 'label' => 'bg-green-500', 'dot' => 'text-green-500'],
                                3 => ['border' => 'border-yellow-300', 'bg' => 'bg-yellow-50 dark:bg-yellow-900/30', 'label' => 'bg-yellow-500', 'dot' => 'text-yellow-500'],
                                4 => ['border' => 'border-cyan-300', 'bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'label' => 'bg-cyan-500', 'dot' => 'text-cyan-500'],
                                5 => ['border' => 'border-red-300', 'bg' => 'bg-red-50 dark:bg-red-900/30', 'label' => 'bg-red-500', 'dot' => 'text-red-500'],
                            ];
                            $c = $colors[$lvl->level] ?? ['border' => 'border-gray-300', 'bg' => 'bg-gray-50', 'label' => 'bg-gray-500', 'dot' => 'text-gray-500'];
                        @endphp
                        <div class="rounded-lg border {{ $c['border'] }} {{ $isCurrentLevel ? $c['bg'] . ' ring-2 ring-indigo-400' : 'bg-white dark:bg-gray-800' }} p-3 relative">
                            {{-- Level label + Current indicator --}}
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 {{ $c['label'] }} text-white text-[10px] font-bold rounded-full leading-none">
                                    Level {{ $lvl->level }}
                                </span>
                                @if($isCurrentLevel)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-600 text-white text-[10px] font-bold rounded-full leading-none">
                                    📍 Level kamu
                                </span>
                                @endif
                            </div>

                            {{-- Level info row --}}
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl flex-shrink-0 leading-none">{{ $lvl->badge_icon_url }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $lvl->nama }}</h4>
                                        <span class="text-[11px] text-gray-400 dark:text-gray-500 font-medium">{{ $lvl->points_range }}</span>
                                    </div>
                                    @if($lvl->deskripsi)
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-snug">{{ $lvl->deskripsi }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Benefits --}}
                            @if(!empty($lvl->benefits))
                            <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex flex-wrap gap-x-3 gap-y-0.5">
                                    @foreach($lvl->benefits as $benefit)
                                    <span class="text-[11px] text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                        <span class="{{ $c['dot'] }}">✓</span> {{ $benefit }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    {{-- Footer --}}
                    <div class="px-4 pb-4">
                        <button @click="showTierModal = false" 
                                class="w-full py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

