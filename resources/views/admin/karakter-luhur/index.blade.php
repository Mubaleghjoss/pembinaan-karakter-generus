@extends('layouts.app')

@section('title', 'Bank 29 Karakter Luhur')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Bank 29 Karakter Luhur</h1>
            <p class="pkg-page-subheading">Sumber data untuk game Rangkai Kata &amp; Tebak Karakter. Semakin lengkap studi kasusnya, makin seru gamenya.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.karakter-luhur.create') }}" class="btn-primary px-5 py-2.5 font-bold">+ Tambah Karakter</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="pkg-card-soft rounded-2xl p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Karakter</p>
            <p class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ $total }}</p>
        </div>
        <div class="pkg-card-soft rounded-2xl p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">Aktif (dipakai game)</p>
            <p class="mt-1 text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $totalActive }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.karakter-luhur.index') }}" class="mb-5 flex flex-wrap gap-3">
        <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama / arab / kategori..." class="pkg-field w-full max-w-md">
        <button type="submit" class="btn-primary px-5 py-2.5 font-bold">Cari</button>
        @if($search !== '')
            <a href="{{ route('admin.karakter-luhur.index') }}" class="btn-secondary px-5 py-2.5 font-bold">Reset</a>
        @endif
    </form>

    <div class="pkg-panel-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr class="text-left text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3 w-12">No</th>
                        <th class="px-4 py-3">Karakter</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3 text-center">Studi Kasus</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 font-bold text-gray-500">{{ $item->nomor }}</td>
                            <td class="px-4 py-3">
                                <p class="font-bold text-gray-900 dark:text-white">{{ $item->nama }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" dir="rtl" lang="ar">{{ $item->nama_arab }}</p>
                                @if($item->ringkas)<p class="text-xs text-gray-400">{{ $item->ringkas }}</p>@endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $item->kategori ?: '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-xs font-bold text-sky-700 dark:bg-sky-900/50 dark:text-sky-200">{{ count($item->studiKasusList()) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200">Aktif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.karakter-luhur.edit', $item) }}" class="btn-primary !px-2.5 !py-1.5 text-xs">Edit</a>
                                    <form method="POST" action="{{ route('admin.karakter-luhur.toggle', $item) }}" onsubmit="return true;">
                                        @csrf
                                        <button type="submit" class="btn-secondary !px-2.5 !py-1.5 text-xs">{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.karakter-luhur.destroy', $item) }}" onsubmit="return confirm('Hapus karakter {{ $item->nama }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger !px-2.5 !py-1.5 text-xs">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada karakter. Klik "Tambah Karakter" atau jalankan seeder.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
