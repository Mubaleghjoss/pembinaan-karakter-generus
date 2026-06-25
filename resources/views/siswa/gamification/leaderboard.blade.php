@extends('layouts.siswa')

@section('title', 'Leaderboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Leaderboard</h1>
            <p class="text-gray-600">Peringkat siswa berdasarkan total poin</p>
        </div>
        <a href="{{ route('siswa.gamification.dashboard') }}" class="text-indigo-600 hover:underline">
            Kembali
        </a>
    </div>

    <!-- Period Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('siswa.gamification.leaderboard', ['period' => 'all']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ $period === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Semua Waktu
            </a>
            <a href="{{ route('siswa.gamification.leaderboard', ['period' => 'monthly']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ $period === 'monthly' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Bulan Ini
            </a>
            <a href="{{ route('siswa.gamification.leaderboard', ['period' => 'weekly']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ $period === 'weekly' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Minggu Ini
            </a>
            <a href="{{ route('siswa.gamification.leaderboard', ['period' => 'daily']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ $period === 'daily' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Hari Ini
            </a>
        </div>
    </div>

    <!-- My Rank Card -->
    @if($myRank)
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-4 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                    <span class="text-2xl font-bold">#{{ $myRank }}</span>
                </div>
                <div>
                    <p class="font-semibold">Peringkat Kamu</p>
                    <p class="text-indigo-200 text-sm">{{ auth()->guard('siswa')->user()->nama }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold">{{ number_format(auth()->guard('siswa')->user()->siswaPoint?->total_points ?? 0) }}</p>
                <p class="text-indigo-200 text-sm">poin</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Top 3 Podium -->
    @if(count($leaderboard) >= 3)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-end justify-center gap-4">
            <!-- 2nd Place -->
            <div class="text-center">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-2">
                    @if(isset($leaderboard[1]['siswa']['foto']) && $leaderboard[1]['siswa']['foto'])
                    <img src="{{ asset('storage/' . $leaderboard[1]['siswa']['foto']) }}" 
                         class="w-16 h-16 rounded-full object-cover">
                    @else
                    <span class="text-2xl">S</span>
                    @endif
                </div>
                <div class="bg-gray-200 rounded-t-lg px-4 py-6">
                    <span class="text-3xl">#2</span>
                    <p class="font-semibold text-gray-800 mt-2 truncate max-w-[100px]">{{ $leaderboard[1]['siswa']['nama'] ?? 'Unknown' }}</p>
                    <p class="text-sm text-gray-600">{{ number_format($leaderboard[1]['total_points']) }} poin</p>
                </div>
            </div>
            
            <!-- 1st Place -->
            <div class="text-center -mt-4">
                <div class="w-20 h-20 mx-auto bg-yellow-100 rounded-full flex items-center justify-center mb-2 ring-4 ring-yellow-400">
                    @if(isset($leaderboard[0]['siswa']['foto']) && $leaderboard[0]['siswa']['foto'])
                    <img src="{{ asset('storage/' . $leaderboard[0]['siswa']['foto']) }}" 
                         class="w-20 h-20 rounded-full object-cover">
                    @else
                    <span class="text-3xl">S</span>
                    @endif
                </div>
                <div class="bg-yellow-100 rounded-t-lg px-6 py-8">
                    <span class="text-4xl">#1</span>
                    <p class="font-bold text-gray-800 mt-2 truncate max-w-[120px]">{{ $leaderboard[0]['siswa']['nama'] ?? 'Unknown' }}</p>
                    <p class="text-sm text-yellow-700 font-semibold">{{ number_format($leaderboard[0]['total_points']) }} poin</p>
                </div>
            </div>
            
            <!-- 3rd Place -->
            <div class="text-center">
                <div class="w-16 h-16 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-2">
                    @if(isset($leaderboard[2]['siswa']['foto']) && $leaderboard[2]['siswa']['foto'])
                    <img src="{{ asset('storage/' . $leaderboard[2]['siswa']['foto']) }}" 
                         class="w-16 h-16 rounded-full object-cover">
                    @else
                    <span class="text-2xl">S</span>
                    @endif
                </div>
                <div class="bg-orange-100 rounded-t-lg px-4 py-4">
                    <span class="text-3xl">#3</span>
                    <p class="font-semibold text-gray-800 mt-2 truncate max-w-[100px]">{{ $leaderboard[2]['siswa']['nama'] ?? 'Unknown' }}</p>
                    <p class="text-sm text-gray-600">{{ number_format($leaderboard[2]['total_points']) }} poin</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Full Leaderboard -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Peringkat Lengkap</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($leaderboard as $index => $item)
            <div class="p-4 flex items-center gap-4 hover:bg-gray-50 transition-colors
                        {{ $item['siswa_id'] === auth()->guard('siswa')->id() ? 'bg-indigo-50' : '' }}">
                <!-- Rank -->
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold
                            {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : 
                               ($index === 1 ? 'bg-gray-200 text-gray-700' : 
                               ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600')) }}">
                    {{ $index + 1 }}
                </div>
                
                <!-- Avatar -->
                <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                    @if(isset($item['siswa']['foto']) && $item['siswa']['foto'])
                    <img src="{{ asset('storage/' . $item['siswa']['foto']) }}" 
                         class="w-12 h-12 object-cover">
                    @else
                    <span class="text-xl">S</span>
                    @endif
                </div>
                
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 truncate">{{ $item['siswa']['nama'] ?? 'Unknown' }}</p>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <span>Level {{ $item['level'] }}</span>
                        <span>|</span>
                        <span>{{ $item['siswa']['kelas']['nama'] ?? '-' }}</span>
                    </div>
                </div>
                
                <!-- Points -->
                <div class="text-right">
                    <p class="text-xl font-bold text-indigo-600">{{ number_format($item['total_points']) }}</p>
                    <p class="text-xs text-gray-500">poin</p>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <span class="text-4xl">LB</span>
                <p class="mt-2">Belum ada data leaderboard</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
