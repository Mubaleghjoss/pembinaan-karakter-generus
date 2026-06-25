@extends('layouts.siswa')

@section('title', 'Riwayat Poin')

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

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Riwayat Poin</h1>
            <p class="text-gray-600">
                Semua aktivitas poin kamu
                @if(!empty($stats['active_period']))
                <span class="font-medium text-indigo-600">| Periode aktif: {{ $stats['active_period']['name'] }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('siswa.gamification.dashboard') }}" class="text-indigo-600 hover:underline">
            Kembali
        </a>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">{{ !empty($stats['active_period']) ? 'Poin Periode Aktif' : 'Total Poin' }}</p>
            <p class="text-2xl font-bold text-sky-600">{{ number_format($stats['active_period_points'] ?? ($stats['points']->total_points ?? 0)) }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Akumulasi Total</p>
            <p class="text-2xl font-bold text-indigo-600">{{ number_format($stats['points']->total_points ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Poin Kehadiran</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['points']->attendance_points ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Poin Karakter</p>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['points']->character_points ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Poin Bonus</p>
            <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['points']->bonus_points ?? 0) }}</p>
        </div>
    </div>

    @if(isset($periods) && $periods->isNotEmpty())
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter periode poin</label>
                <select name="period_id" class="w-full rounded-lg border border-gray-200 px-3 py-2">
                    <option value="">Semua periode</option>
                    @foreach($periods as $period)
                    <option value="{{ $period->id }}" {{ (int) ($periodId ?? 0) === $period->id ? 'selected' : '' }}>
                        {{ $period->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">
                Tampilkan
            </button>
            <a href="{{ route('siswa.gamification.history') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50">
                Reset
            </a>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Riwayat Transaksi</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($transactions as $transaction)
            <div class="p-4 flex items-center gap-4 hover:bg-gray-50 transition-colors">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl
                            {{ $transaction->type === 'earned' || $transaction->type === 'bonus' ? 'bg-green-100' : 'bg-red-100' }}">
                    {{ $transaction->icon }}
                </div>

                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800">{{ $transaction->description }}</p>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <span class="capitalize">{{ $transaction->source_label }}</span>
                        @if(!empty($transaction->metadata['period_name']))
                        <span>|</span>
                        <span>{{ $transaction->metadata['period_name'] }}</span>
                        @endif
                        <span>|</span>
                        <span>{{ $transaction->created_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-xl font-bold {{ $transaction->color }}">
                        {{ $transaction->formatted_points }}
                    </p>
                    <p class="text-xs text-gray-500">poin</p>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <span class="text-4xl">PTS</span>
                <p class="mt-2">Belum ada riwayat transaksi</p>
                <p class="text-sm">Mulai hadir dan kumpulkan poin.</p>
            </div>
            @endforelse
        </div>

        @if($transactions->hasPages())
        <div class="p-4 border-t border-gray-100">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
