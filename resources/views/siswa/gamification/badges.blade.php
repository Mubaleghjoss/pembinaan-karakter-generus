@extends('layouts.siswa')

@section('title', 'Koleksi Pin Penghargaan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pin Penghargaan Saya</h1>
            <p class="text-gray-600">{{ $earnedCount }} dari {{ $totalCount }} pin terkumpul</p>
        </div>
        <a href="{{ route('siswa.gamification.dashboard') }}" class="text-indigo-600 hover:underline">
            Kembali
        </a>
    </div>

    <!-- Progress -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-600">Progress Koleksi</span>
            <span class="text-sm font-semibold text-indigo-600">{{ round(($earnedCount / max($totalCount, 1)) * 100) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-indigo-600 rounded-full h-3 transition-all duration-500" 
                 style="width: {{ ($earnedCount / max($totalCount, 1)) * 100 }}%"></div>
        </div>
    </div>

    <!-- Pin Categories -->
    @php
        $categories = [
            'attendance' => ['name' => 'Kehadiran', 'icon' => 'HDR', 'color' => 'green'],
            'character' => ['name' => 'Karakter / Tugas PKG', 'icon' => 'KAR', 'color' => 'purple'],
        ];
        $groupedBadges = collect($badges)->groupBy(fn($b) => $b['badge']->kategori);
    @endphp

    @foreach($categories as $catKey => $catInfo)
    @if(isset($groupedBadges[$catKey]))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-{{ $catInfo['color'] }}-50">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                <span>{{ $catInfo['icon'] }}</span>
                Pin {{ $catInfo['name'] }}
                <span class="text-sm font-normal text-gray-500">
                    ({{ collect($groupedBadges[$catKey])->where('earned', true)->count() }}/{{ count($groupedBadges[$catKey]) }})
                </span>
            </h2>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($groupedBadges[$catKey] as $item)
                <div class="relative p-4 rounded-xl border-2 transition-all cursor-pointer hover:shadow-md
                            {{ $item['earned'] ? 'border-' . $catInfo['color'] . '-300 bg-' . $catInfo['color'] . '-50' : 'border-gray-200 bg-gray-50' }}"
                     onclick="showPinDetail({{ json_encode($item) }})">
                    
                    <!-- Pin Icon -->
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-3
                                    {{ $item['earned'] ? '' : 'grayscale opacity-50' }}"
                             style="background-color: {{ $item['badge']->warna }}20">
                            {{ $item['badge']->icon_url }}
                        </div>
                        
                        <h3 class="font-semibold text-gray-800 text-sm">{{ $item['badge']->nama }}</h3>
                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $item['badge']->deskripsi }}</p>
                        
                        <!-- Clear Target Rule -->
                        <div class="mt-2 px-2 py-1 bg-indigo-50 rounded-lg">
                            <p class="text-xs font-medium text-indigo-700">Target: {{ $item['badge']->criteria_description }}</p>
                        </div>

                        <!-- Progress or Earned Date -->
                        @if($item['earned'])
                        <div class="mt-2 text-xs text-{{ $catInfo['color'] }}-600 font-medium">
                            Diperoleh {{ \Carbon\Carbon::parse($item['earned_at'])->format('d M Y') }}
                        </div>
                        @else
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-{{ $catInfo['color'] }}-500 rounded-full h-2" 
                                     style="width: {{ $item['progress'] }}%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1 font-medium">{{ $item['current'] }}/{{ $item['target'] }} ({{ $item['progress'] }}%)</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @endforeach

    @if($groupedBadges->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
        <div class="text-4xl mb-3">PIN</div>
        <p class="text-gray-500">Belum ada pin penghargaan yang tersedia.</p>
    </div>
    @endif
</div>

<!-- Pin Detail Modal -->
<div id="pinDetailModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 relative">
        <button onclick="closePinModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        
        <div id="pinModalContent" class="text-center">
            <!-- Content will be injected by JS -->
        </div>
    </div>
</div>

@push('scripts')
<script>
function showPinDetail(item) {
    const modal = document.getElementById('pinDetailModal');
    const content = document.getElementById('pinModalContent');
    
    const badge = item.badge;
    const earned = item.earned;
    const progress = item.progress;
    const current = item.current;
    const target = item.target;
    const earnedAt = item.earned_at;
    
    content.innerHTML = `
        <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center text-5xl mb-4 ${earned ? '' : 'grayscale opacity-50'}"
             style="background-color: ${badge.warna}20">
            ${badge.icon_url}
        </div>
        
        <h2 class="text-xl font-bold text-gray-800">${badge.nama}</h2>
        <p class="text-gray-600 mt-2">${badge.deskripsi}</p>
        
        <div class="mt-4 p-3 bg-indigo-50 rounded-lg">
            <span class="text-indigo-700 font-semibold">Target: ${badge.criteria_description}</span>
        </div>
        
        ${earned ? `
            <div class="mt-4 p-3 bg-green-50 rounded-lg">
                <span class="text-green-700">Diperoleh pada ${new Date(earnedAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</span>
            </div>
        ` : `
            <div class="mt-4">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">Progress</span>
                    <span class="font-semibold">${current}/${target}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-indigo-600 rounded-full h-3" style="width: ${progress}%"></div>
                </div>
                <p class="text-sm text-gray-500 mt-2">Masih butuh <strong>${target - current}</strong> lagi untuk mendapatkan pin ini!</p>
            </div>
        `}
    `;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePinModal() {
    const modal = document.getElementById('pinDetailModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close modal on backdrop click
document.getElementById('pinDetailModal').addEventListener('click', function(e) {
    if (e.target === this) closePinModal();
});
</script>
@endpush
@endsection
