@extends('layouts.app')

@section('title', 'Poin Kehadiran')

@section('content')
<div x-data="{ showDeleteModal: false, deleteId: null, deleteDesc: '', reason: '' }">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Poin Kehadiran</h1>
            <p class="pkg-page-subheading">Kelola poin kehadiran siswa yang Anda bimbing.</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="pkg-card p-5">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Poin Kehadiran</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalAttendancePoints) }}</p>
                </div>
            </div>
        </div>
        <div class="pkg-card p-5">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Siswa</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalStudents }}</p>
                </div>
            </div>
        </div>
        <div class="pkg-card p-5">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Rata-rata / Siswa</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalStudents > 0 ? number_format($totalAttendancePoints / $totalStudents, 0) : 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="pkg-filter-bar mb-6">
        <form method="GET" action="{{ route('cek-kehadiran.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Siswa</label>
                <select name="siswa_id" class="pkg-field text-sm">
                    <option value="">Semua Siswa</option>
                    @foreach($siswaList as $s)
                        <option value="{{ $s->id }}" @selected(request('siswa_id') == $s->id)>{{ $s->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="pkg-field text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="pkg-field text-sm">
            </div>
            <button type="submit" class="btn-primary text-sm !px-5 !py-2.5">
                Filter
            </button>
        </form>
    </div>

    {{-- Transactions Table --}}
    <div class="pkg-panel overflow-hidden">
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Siswa</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Poin</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($transactions as $t)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3 pkg-mobile-main" data-label="Siswa">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $t->siswa->nama ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $t->siswa->school_grade_label ?? 'Kelas belum dikonfirmasi' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300" data-label="Deskripsi">{{ $t->description }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400" data-label="Tanggal">{{ $t->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 text-center" data-label="Poin">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-bold {{ $t->points > 0 ? 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30' : 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30' }}">
                                    {{ $t->points > 0 ? '+' : '' }}{{ $t->points }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center pkg-mobile-actions" data-label="Aksi">
                                @if($t->points > 0)
                                <button @click="showDeleteModal = true; deleteId = {{ $t->id }}; deleteDesc = '{{ addslashes($t->description) }}'; reason = ''" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors" title="Hapus Poin">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @else
                                <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 pkg-mobile-empty" data-label="">
                                <div class="pkg-empty-state py-8">
                                    <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v8m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v12a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <p class="pkg-empty-title">Tidak ada data poin kehadiran</p>
                                    <p class="pkg-empty-copy">Coba ubah filter atau tunggu data kehadiran baru masuk.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @keydown.escape.window="showDeleteModal = false">
        <div class="pkg-modal max-w-md w-full mx-4 p-6" @click.outside="showDeleteModal = false">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Hapus Poin Kehadiran?</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Data poin akan dihapus dan poin dikurangi dari leaderboard.</p>
            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-4" x-text="deleteDesc"></p>
            <form :action="'{{ route('cek-kehadiran.index') }}/' + deleteId" method="POST">
                @csrf
                @method('DELETE')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alasan Penghapusan <span class="text-red-500">*</span></label>
                    <textarea name="reason" x-model="reason" required minlength="5" maxlength="500" rows="2" class="w-full pkg-field text-sm" placeholder="Masukkan alasan penghapusan (min 5 karakter)..."></textarea>
                </div>
                <div class="pkg-page-actions justify-end">
                    <button type="button" @click="showDeleteModal = false" class="btn-secondary text-sm !px-4 !py-2">Batal</button>
                    <button type="submit" class="btn-danger text-sm !px-4 !py-2">Hapus & Kurangi Poin</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


