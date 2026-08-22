@extends('layouts.app')

@section('title', 'Boss Online')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Boss Online</h1>
            <p class="pkg-page-subheading">Mulai pertarungan Boss (sifat buruk) yang dikeroyok semua siswa online. Jawaban benar mengurangi HP boss.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Form mulai boss --}}
    <div class="mb-6 pkg-panel-lg p-4 sm:p-6">
        <h2 class="mb-3 font-bold text-gray-900 dark:text-white">Mulai Boss Baru</h2>
        <form method="POST" action="{{ route('admin.boss.store') }}" class="grid gap-3 sm:grid-cols-4">
            @csrf
            <div class="sm:col-span-2">
                <label class="pkg-label">Nama Boss</label>
                <input type="text" name="nama" class="pkg-field w-full" placeholder="mis. Sifat Malas" required>
            </div>
            <div>
                <label class="pkg-label">Mode Soal</label>
                <select name="mode" class="pkg-field w-full">
                    <option value="tebak">Tebak Karakter</option>
                    <option value="rangkai">Rangkai Kata</option>
                </select>
            </div>
            <div>
                <label class="pkg-label">Total HP</label>
                <input type="number" name="max_hp" class="pkg-field w-full" value="500" min="50" max="100000" required>
            </div>
            <div class="sm:col-span-4">
                <label class="pkg-label">Deskripsi (opsional)</label>
                <input type="text" name="deskripsi" class="pkg-field w-full" placeholder="Kalahkan dengan menerapkan karakter luhur!">
            </div>
            <div class="sm:col-span-4">
                <button type="submit" class="btn-primary px-5 py-2.5 font-bold">Mulai Boss</button>
                <span class="ml-2 text-xs text-gray-500">Memulai boss baru akan mengakhiri boss aktif sebelumnya.</span>
            </div>
        </form>
    </div>

    {{-- Riwayat boss: kartu (mobile) --}}
    <div class="pkg-cards-mobile">
        @forelse($battles as $b)
            <div class="pkg-data-card">
                <div class="pkg-data-card-head">
                    <div class="min-w-0">
                        <p class="pkg-data-card-title">{{ $b->nama }}</p>
                        <p class="pkg-data-card-sub">{{ $b->mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata' }}</p>
                    </div>
                    @if($b->status === 'active')
                        <span class="pkg-status-badge pkg-status-success shrink-0">Aktif</span>
                    @elseif($b->status === 'defeated')
                        <span class="pkg-status-badge pkg-status-info shrink-0">Dikalahkan</span>
                    @else
                        <span class="pkg-status-badge pkg-status-neutral shrink-0">Selesai</span>
                    @endif
                </div>
                <div class="pkg-data-card-meta">
                    <div class="pkg-data-card-row"><span class="k">HP</span><span class="v">{{ max(0,$b->current_hp) }} / {{ $b->max_hp }}</span></div>
                    <div class="pkg-data-card-row"><span class="k">Peserta</span><span class="v">{{ $b->hits_count }}</span></div>
                </div>
                @if($b->status === 'active')
                    <div class="pkg-data-card-actions">
                        <form method="POST" action="{{ route('admin.boss.end', $b) }}" onsubmit="return confirm('Hentikan boss ini?');">
                            @csrf
                            <button type="submit" class="btn-danger">Hentikan Boss</button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="pkg-empty-state pkg-card">
                <p class="pkg-empty-title">Belum ada Boss</p>
                <p class="pkg-empty-copy">Mulai satu di form atas.</p>
            </div>
        @endforelse
    </div>

    {{-- Riwayat boss: tabel (desktop) --}}
    <div class="pkg-table-desktop pkg-panel-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr class="text-left text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3">Boss</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3">HP</th>
                        <th class="px-4 py-3 text-center">Peserta</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($battles as $b)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">{{ $b->nama }}</td>
                            <td class="px-4 py-3">{{ $b->mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata' }}</td>
                            <td class="px-4 py-3">{{ max(0,$b->current_hp) }} / {{ $b->max_hp }}</td>
                            <td class="px-4 py-3 text-center">{{ $b->hits_count }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($b->status === 'active')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200">Aktif</span>
                                @elseif($b->status === 'defeated')
                                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700 dark:bg-blue-900/50 dark:text-blue-200">Dikalahkan</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Selesai</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($b->status === 'active')
                                    <form method="POST" action="{{ route('admin.boss.end', $b) }}" onsubmit="return confirm('Hentikan boss ini?');">
                                        @csrf
                                        <button type="submit" class="btn-danger !px-2.5 !py-1.5 text-xs">Hentikan</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada Boss. Mulai satu di atas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
