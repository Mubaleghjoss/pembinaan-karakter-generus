@extends('layouts.app')

@section('title', 'Daftar Akun Siswa')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <x-breadcrumb :items="[
        ['title' => 'Siswa', 'url' => route('siswa.index')],
        ['title' => 'Daftar Akun', 'url' => route('siswa.accounts')]
    ]" />

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Daftar Akun Siswa</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Daftar akun login siswa untuk dicetak atau dikirim ke pamong
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <button onclick="window.print()" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak
            </button>
        </div>
    </div>

    <!-- Filter -->
    <div class="pkg-card mb-6 no-print">
        <div class="p-4">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kelas</label>
                    <select name="kelas_id" onchange="this.form.submit()"
                            class="pkg-field text-sm">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList as $kelas)
                        <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6 no-print">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="font-medium text-yellow-800 dark:text-yellow-200">Informasi Penting</p>
                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    Password default siswa adalah NIS masing-masing. Jika sudah direset, password akan berubah sesuai pengaturan.
                </p>
            </div>
        </div>
    </div>

    <!-- Accounts Table -->
    <div id="print-area" class="pkg-card">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 print-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Daftar Akun Siswa
                @if(request('kelas_id'))
                    - {{ $kelasList->firstWhere('id', request('kelas_id'))?->nama }}
                @endif
            </h2>
            <p class="text-sm text-gray-500">Dicetak: {{ now()->format('d M Y H:i') }}</p>
        </div>

        <div class="pkg-mobile-table overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">NIS (Username)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kelas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Password Default</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($siswaList as $index => $siswa)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white" data-label="No">{{ $index + 1 }}</td>
                        <td class="pkg-mobile-main px-4 py-3 text-sm text-gray-900 dark:text-white" data-label="Siswa">{{ $siswa->nama }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white" data-label="NIS">{{ $siswa->nis }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white" data-label="Kelas">{{ $siswa->kelas->nama ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white" data-label="Password awal">{{ $siswa->nis }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="pkg-mobile-empty px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data siswa
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-700 print-footer">
            <p class="text-sm text-gray-500">Total: {{ $siswaList->count() }} siswa</p>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    #print-area, #print-area * { visibility: visible; }
    #print-area { 
        position: absolute; 
        left: 0; 
        top: 0;
        width: 100%;
        box-shadow: none;
        border: none;
    }
    .no-print { display: none !important; }
    nav { display: none !important; }
    table { font-size: 12px; }
    .print-header, .print-footer { background: white !important; }
}
</style>
@endsection


