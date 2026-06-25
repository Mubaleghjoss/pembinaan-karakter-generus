@extends('layouts.siswa')

@section('title', 'Gamifikasi')

@section('content')
<div class="space-y-6">
    @if(!empty($stats['last_period_reset']))
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800">
        <p class="font-semibold">
            {{ number_format($stats['last_period_reset']['points']) }} poin sebelumnya sudah terekam
            @if(!empty($stats['last_period_reset']['period_name']))
            di periode {{ $stats['last_period_reset']['period_name'] }}
            @endif
            .
        </p>
        <p class="mt-1">
            {{ $stats['last_period_reset']['message'] ?? 'Yuk semangat kumpulkan poin lagi untuk benefit yang ada.' }}
        </p>
    </div>
    @endif

    <!-- Header Stats -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-indigo-200 text-sm">Level {{ $stats['current_level']->level ?? 1 }}</p>
                <h1 class="text-2xl font-bold">{{ $stats['current_level']->nama ?? 'Pemula' }}</h1>
                <p class="text-indigo-200 mt-1">{{ $stats['current_level']->badge_icon_url ?? 'LVL' }}</p>
                @if(!empty($stats['active_period']))
                <p class="mt-2 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100">
                    Periode aktif: {{ $stats['active_period']['name'] }}
                </p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-4xl font-bold">{{ number_format($stats['points']->total_points ?? 0) }}</p>
                <p class="text-indigo-200">Total Poin</p>
            </div>
        </div>
        
        <!-- Progress Bar -->
        @if($stats['next_level'])
        <div class="mt-4">
            <div class="flex justify-between text-sm mb-1">
                <span>Progress ke {{ $stats['next_level']->nama }}</span>
                <span>{{ $stats['points_to_next'] }} poin lagi</span>
            </div>
            <div class="w-full bg-indigo-400/30 rounded-full h-3">
                <div class="bg-white rounded-full h-3 transition-all duration-500" 
                     style="width: {{ $stats['progress_to_next'] }}%"></div>
            </div>
        </div>
        @else
        <div class="mt-4 text-center">
            <span class="text-yellow-300">Level maksimum tercapai!</span>
        </div>
        @endif
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-100 rounded-lg flex items-center justify-center">
                    <span class="text-sm font-semibold text-sky-700">PRD</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['active_period_points'] ?? ($stats['points']->total_points ?? 0)) }}</p>
                    <p class="text-xs text-gray-500">{{ !empty($stats['active_period']) ? $stats['active_period']['name'] : 'Periode aktif' }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-sm font-semibold text-green-700">RK</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['rank'] }}</p>
                    <p class="text-xs text-gray-500">Peringkat</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <span class="text-sm font-semibold text-yellow-700">PIN</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_badges'] }}</p>
                    <p class="text-xs text-gray-500">Pin</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <span class="text-sm font-semibold text-orange-700">STR</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['attendance_streak'] }}</p>
                    <p class="text-xs text-gray-500">Streak Hadir</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <span class="text-sm font-semibold text-purple-700">KAR</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['character_streak'] }}</p>
                    <p class="text-xs text-gray-500">Streak Karakter</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Benefit & Penghargaan Level -->
    @if($reachedLevels->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100" x-data="{ showBenefits: false }">
        <div class="p-4 border-b border-gray-100 cursor-pointer select-none flex items-center justify-between" @click="showBenefits = !showBenefits">
            <div>
                <h2 class="font-semibold text-gray-800">Benefit & Penghargaan Level</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $reachedLevels->count() }} level tercapai | {{ $totalRewards }} reward</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400" x-text="showBenefits ? 'Tutup' : 'Lihat'"></span>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="showBenefits && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>
        <div x-show="showBenefits" x-collapse x-cloak class="p-4 space-y-3">
            @foreach($reachedLevels as $reachedLevel)
            <div class="rounded-lg border {{ $reachedLevel->level === $currentLevel ? 'border-indigo-300 bg-indigo-50/50' : 'border-gray-200 bg-gray-50/50' }} overflow-hidden"
                 x-data="{ open: {{ $reachedLevel->level === $currentLevel ? 'true' : 'false' }} }">
                {{-- Level Header (clickable) --}}
                <div class="flex items-center justify-between px-4 py-2.5 cursor-pointer select-none {{ $reachedLevel->level === $currentLevel ? 'bg-indigo-100/50' : 'bg-gray-100/50' }}"
                     @click="open = !open">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-sm" style="background-color: {{ $reachedLevel->warna }}20">
                            {{ $reachedLevel->badge_icon_url ?? 'LVL' }}
                        </div>
                        <p class="font-bold text-sm text-gray-800">Level {{ $reachedLevel->level }} - {{ $reachedLevel->nama }}</p>
                        @if($reachedLevel->level === $currentLevel)
                        <span class="text-[10px] px-2 py-0.5 bg-indigo-600 text-white rounded-full font-medium">Saat Ini</span>
                        @endif
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

                {{-- Benefits (collapsible) --}}
                <div x-show="open" x-collapse x-cloak class="px-4 py-2.5 space-y-1.5">
                    @php $benefits = $reachedLevel->benefits ?? []; @endphp
                    @if(count($benefits) > 0)
                        @foreach($benefits as $benefit)
                        @php
                            $benefitLower = strtolower($benefit);
                            $rewardType = null;
                            $rewardIcon = 'OK';
                            if (str_contains($benefitLower, 'sertifikat')) { $rewardType = 'sertifikat'; $rewardIcon = 'CERT'; }
                            elseif (str_contains($benefitLower, 'pin')) { $rewardType = 'pin'; $rewardIcon = 'PIN'; }
                            elseif (str_contains($benefitLower, 'nominasi')) { $rewardType = 'nominasi'; $rewardIcon = 'NOM'; }
                            elseif (str_contains($benefitLower, 'piagam')) { $rewardType = 'piagam'; $rewardIcon = 'PIA'; }
                            elseif (str_contains($benefitLower, 'apresiasi')) { $rewardType = 'apresiasi'; $rewardIcon = 'APR'; }
                            elseif (str_contains($benefitLower, 'piala')) { $rewardType = 'piala'; $rewardIcon = 'TROFI'; }
                            $hasTemplate = false;
                            if ($rewardType) {
                                $tmpl = $reachedLevel->rewardTemplates->where('reward_type', $rewardType)->first();
                                $hasTemplate = $tmpl && $tmpl->hasTemplate();
                            }
                        @endphp
                        <div class="flex items-center justify-between p-2 bg-white rounded-lg border border-gray-100">
                            <div class="flex items-center gap-2">
                                <span class="text-sm">{{ $rewardIcon }}</span>
                                <span class="text-xs font-medium text-gray-700">{{ $benefit }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                @if($rewardType && $hasTemplate)
                                    <a href="{{ route('siswa.gamification.certificate.download', ['level' => $reachedLevel->id, 'format' => 'png', 'type' => $rewardType]) }}&view=1" 
                                       target="_blank"
                                       class="px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-[10px] font-medium rounded transition-colors">
                                        Lihat
                                    </a>
                                    <a href="{{ route('siswa.gamification.certificate.download', ['level' => $reachedLevel->id, 'format' => 'png', 'type' => $rewardType]) }}" 
                                       class="px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-medium rounded transition-colors">
                                        PNG
                                    </a>
                                    <a href="{{ route('siswa.gamification.certificate.download', ['level' => $reachedLevel->id, 'format' => 'pdf', 'type' => $rewardType]) }}" 
                                       class="px-2 py-0.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-medium rounded transition-colors">
                                        PDF
                                    </a>
                                @elseif($rewardType)
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-medium rounded">
                                        Sedang diproses
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-medium rounded">
                                        Tercapai
                                    </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-xs text-gray-400 italic py-1">Belum ada benefit untuk level ini</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Recent Badges -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="font-semibold text-gray-800">Pin Penghargaan</h2>
            </div>
            <div class="p-4">
                @if(count($allBadges) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-96 overflow-y-auto pr-1">
                    @foreach($allBadges as $data)
                    @php $badge = $data['badge']; @endphp
                    <div class="p-3 border rounded-lg {{ $data['earned'] ? 'bg-white border-green-100 shadow-sm' : 'bg-gray-50 border-gray-100' }}">
                        <div class="flex items-start gap-3 mb-2">
                            <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-xl transition-all"
                                 style="background-color: {{ $data['earned'] ? $badge->warna.'20' : '#f3f4f6' }};
                                        filter: {{ $data['earned'] ? 'none' : 'grayscale(100%) opacity(0.7)' }}">
                                {{ $badge->icon_url }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-sm truncate {{ $data['earned'] ? 'text-gray-800' : 'text-gray-500' }}">
                                    {{ $badge->nama }}
                                </h3>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium {{ $data['earned'] ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $data['earned'] ? 'Tercapai' : 'Belum Tercapai' }}
                                    </span>
                                    @if($badge->poin_reward > 0)
                                        <span class="text-[10px] font-bold text-orange-500">+{{ $badge->poin_reward }} Poin</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        @if(!$data['earned'])
                            @if($data['current'] >= $data['target'])
                            {{-- Target sudah tercapai tapi belum di-award --}}
                            <div class="mt-2 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-[10px] font-medium rounded-full">
                                    Syarat Tercapai
                                </span>
                            </div>
                            @else
                            <div class="mt-2">
                                <div class="flex justify-between text-[10px] text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $data['current'] }} / {{ $data['target'] }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ $data['progress'] }}%"></div>
                                </div>
                                <p class="text-[10px] text-gray-500 mt-1 italic">
                                    @php
                                        $remaining = $data['target'] - $data['current'];
                                        $criteriaType = $badge->kriteria['type'] ?? '';
                                    @endphp
                                    @if($criteriaType === 'level_reached')
                                        {{ $remaining }} level lagi untuk membuka
                                    @elseif($criteriaType === 'verified_character_count')
                                        {{ $remaining }} tugas lagi untuk membuka
                                    @elseif($criteriaType === 'attendance_count')
                                        {{ $remaining }}x hadir lagi untuk membuka
                                    @else
                                        {{ $remaining }} lagi untuk membuka
                                    @endif
                                </p>
                                <a href="{{ route('siswa.tugas-pkg.index') }}" class="mt-2 block w-full py-1.5 text-center text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition-colors shadow-sm">
                                    Kerjakan Tugas
                                </a>
                            </div>
                            @endif
                        @else
                            <div class="mt-2 text-[10px] text-gray-400 flex justify-between items-center">
                                <span>Diraih: {{ \Carbon\Carbon::parse($data['earned_at'])->format('d M Y') }}</span>
                                <span class="text-green-600">Selesai</span>
                            </div>
                        @endif

                        <div class="mt-2 pt-2 border-t border-gray-100/50">
                            <p class="text-[10px] text-gray-500 leading-tight">
                                {{ $badge->criteria_description }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-gray-500">
                    <span class="text-4xl">PIN</span>
                    <p class="mt-2">Belum ada pin</p>
                    <p class="text-sm">Terus semangat untuk mendapatkan pin!</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Mini Leaderboard -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="font-semibold text-gray-800">Top 5 Leaderboard</h2>
                <a href="{{ route('siswa.gamification.leaderboard') }}" class="text-sm text-indigo-600 hover:underline">
                    Lihat Semua
                </a>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($leaderboard as $index => $item)
                <div class="p-3 flex items-center gap-3 {{ $item['siswa_id'] === auth()->guard('siswa')->id() ? 'bg-indigo-50' : '' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
                                {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : 
                                   ($index === 1 ? 'bg-gray-100 text-gray-700' : 
                                   ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-50 text-gray-600')) }}">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 truncate">{{ $item['siswa']['nama'] ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-500">Level {{ $item['level'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-indigo-600">{{ number_format($item['total_points']) }}</p>
                        <p class="text-xs text-gray-500">poin</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Aktivitas Poin Terbaru</h2>
            <a href="{{ route('siswa.gamification.history') }}" class="text-sm text-indigo-600 hover:underline">
                Lihat Semua
            </a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($stats['recent_transactions'] as $transaction)
            <div class="p-3 flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-xl">
                    {{ $transaction->icon }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-gray-800">{{ $transaction->description }}</p>
                    <p class="text-xs text-gray-500">{{ $transaction->created_at->diffForHumans() }}</p>
                </div>
                <div class="font-bold {{ $transaction->color }}">
                    {{ $transaction->formatted_points }}
                </div>
                @if(auth()->guard('web')->check())
                <form action="{{ route('admin.gamification.transactions.destroy', $transaction->id) }}" method="POST" data-confirm="Hapus log ini? Poin akan dihitung ulang otomatis." data-confirm-title="Hapus log transaksi" data-confirm-button="Hapus" data-confirm-tone="danger">
                    @csrf @method('DELETE')
                    <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 transition" title="Hapus log">
                        <svg class="w-4 h-4 text-red-400 hover:text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
                @endif
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <span class="text-4xl">LOG</span>
                <p class="mt-2">Belum ada aktivitas poin</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Point Breakdown -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Breakdown Poin</h2>
        <div class="grid grid-cols-3 gap-4">
            <div class="text-center p-4 bg-green-50 rounded-xl">
                <p class="text-2xl font-bold text-green-600">{{ number_format($stats['points']->attendance_points ?? 0) }}</p>
                <p class="text-sm text-green-700">Poin Kehadiran</p>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-xl">
                <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['points']->character_points ?? 0) }}</p>
                <p class="text-sm text-purple-700">Poin Karakter</p>
            </div>
            <div class="text-center p-4 bg-orange-50 rounded-xl">
                <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['points']->bonus_points ?? 0) }}</p>
                <p class="text-sm text-orange-700">Poin Bonus</p>
            </div>
        </div>
    </div>
</div>
@endsection
