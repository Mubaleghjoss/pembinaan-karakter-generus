@extends('layouts.ortu')

@section('title', 'Kehadiran PKG')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kehadiran PKG</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Riwayat poin kehadiran anak</p>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Total Poin</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalPoints) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Total Hadir</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $totalHadir }} hari</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction List --}}
    <div class="pkg-panel overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white">Riwayat Poin Kehadiran</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($transactions as $t)
                <div class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <div class="flex items-center">
                        <span class="text-lg mr-3">📅</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $t->description }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $t->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                    <span class="text-sm font-bold {{ $t->points > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $t->points > 0 ? '+' : '' }}{{ $t->points }}
                    </span>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-gray-600 dark:text-gray-400">
                    Belum ada riwayat poin kehadiran
                </div>
            @endforelse
        </div>
        @if($transactions->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

