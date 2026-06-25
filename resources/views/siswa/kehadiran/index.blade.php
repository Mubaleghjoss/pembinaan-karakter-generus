@extends('layouts.siswa')

@section('title', 'Kehadiran PKG')

@section('content')
@php
    $statusStyles = [
        'hadir' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200',
        'terlambat' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200',
        'izin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200',
        'sakit' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200',
        'alpha' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
    ];
@endphp

<div class="max-w-6xl mx-auto space-y-6">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Kehadiran PKG</h1>
            <p class="pkg-page-subheading">Riwayat status presensi kamu, termasuk data lama yang diimpor admin.</p>
        </div>
        <div class="pkg-page-actions">
            @if($adminContact)
                <a href="{{ route('siswa.chat.index', ['pamong_id' => $adminContact->id]) }}" class="btn-secondary px-4 py-2">
                    Chat Admin
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="pkg-card p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Data</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total']) }}</p>
        </div>
        <div class="pkg-card p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Hadir</p>
            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($summary['hadir']) }}</p>
        </div>
        <div class="pkg-card p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Terlambat</p>
            <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($summary['terlambat']) }}</p>
        </div>
        <div class="pkg-card p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Izin/Sakit</p>
            <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($summary['izin'] + $summary['sakit']) }}</p>
        </div>
        <div class="pkg-card p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Kehadiran</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['percentage'] }}%</p>
        </div>
    </div>

    <div class="pkg-filter-bar">
        <form method="GET" class="pkg-filter-grid md:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="w-full pkg-field">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="w-full pkg-field">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status" class="w-full pkg-field">
                    <option value="">Semua Status</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary px-4 py-2">Filter</button>
                <a href="{{ route('siswa.kehadiran.index') }}" class="btn-secondary px-4 py-2">Reset</a>
            </div>
        </form>
    </div>

    <div class="pkg-card overflow-hidden">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white">Data Presensi</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jika ada data yang tidak sesuai, gunakan tombol Ajukan Koreksi untuk menghubungi admin.</p>
        </div>
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Jam</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Keterangan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Verifikasi</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($records as $record)
                        @php
                            $label = $statusLabels[$record->status] ?? ucfirst($record->status);
                            $message = "Assalamualaikum Admin, saya ingin mengajukan koreksi data presensi.\nTanggal: ".$record->tanggal->format('d M Y')."\nStatus saat ini: ".$label."\nKeterangan: ".($record->keterangan ?: '-')."\nUsulan perubahan: ";
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white" data-label="Tanggal">
                                {{ $record->tanggal->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-5 py-4" data-label="Status">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyles[$record->status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300" data-label="Jam">
                                <div>Masuk: {{ $record->jam_masuk?->format('H:i') ?? '-' }}</div>
                                <div>Keluar: {{ $record->jam_keluar?->format('H:i') ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300" data-label="Keterangan">
                                {{ $record->keterangan ?: '-' }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300" data-label="Verifikasi">
                                @if($record->is_verified)
                                    <span class="font-medium text-green-600 dark:text-green-400">Terverifikasi</span>
                                    @if($record->verified_at)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $record->verified_at->format('d M Y H:i') }}</div>
                                    @endif
                                @else
                                    <span class="font-medium text-amber-600 dark:text-amber-400">Belum diverifikasi</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right text-sm" data-label="Aksi">
                                @if($adminContact)
                                    <a href="{{ route('siswa.chat.index', ['pamong_id' => $adminContact->id, 'message' => $message]) }}" class="btn-secondary inline-flex px-3 py-2 text-xs">
                                        Ajukan Koreksi
                                    </a>
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Admin belum tersedia</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="pkg-empty-state">
                                    <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-6m3 6V7m3 10v-3m5 6H4a1 1 0 01-1-1V5a1 1 0 011-1h16a1 1 0 011 1v14a1 1 0 01-1 1z"/>
                                    </svg>
                                    <h3 class="pkg-empty-title">Belum Ada Data Presensi</h3>
                                    <p class="pkg-empty-copy">Data akan muncul setelah presensi dicatat atau diimpor oleh admin.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                {{ $records->links() }}
            </div>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="pkg-card p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white">Ringkasan Poin Kehadiran</h2>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="pkg-card-soft rounded-xl p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Poin</p>
                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalPoints) }}</p>
                </div>
                <div class="pkg-card-soft rounded-xl p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Hari Berpoin</p>
                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalHadir) }}</p>
                </div>
            </div>
        </div>
        <div class="pkg-card p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white">Poin Terakhir</h2>
            <div class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($pointTransactions as $transaction)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $transaction->description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <span class="font-bold {{ $transaction->points > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $transaction->points > 0 ? '+' : '' }}{{ $transaction->points }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada transaksi poin kehadiran.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
