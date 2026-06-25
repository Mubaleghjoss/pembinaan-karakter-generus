@extends('layouts.app')

@section('title', 'Pamong Bantu Ceklis - ' . $siswa->nama)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('tugas-pkg.verification') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pamong Bantu Ceklis</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-300">Bantu siswa menceklis kegiatan PKG yang sudah dilaporkan</p>
    </div>

    <div class="pkg-card-soft mb-6 rounded-2xl border border-blue-200 p-4 dark:border-blue-700">
        <div class="flex items-start gap-3">
            <span class="text-sm font-semibold text-blue-700 dark:text-blue-300 mt-0.5">Info</span>
            <div>
                <h3 class="font-semibold text-blue-800 dark:text-blue-200 text-sm">Fitur Bantu Ceklis oleh Pamong</h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                    Fitur ini digunakan untuk membantu siswa PKG yang <strong>terkendala</strong>, misalnya:
                </p>
                <ul class="text-sm text-blue-700 dark:text-blue-300 mt-1 list-disc list-inside space-y-0.5">
                    <li>Tidak punya HP / tidak bisa mengakses aplikasi</li>
                    <li>HP rusak atau sedang bermasalah</li>
                    <li>Tidak bisa menceklis sendiri karena kendala teknis</li>
                </ul>
                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                    Siswa sudah <strong>melaporkan langsung ke pamong</strong> bahwa kegiatan telah dilaksanakan, maka pamong dapat membantu menceklis <strong>dengan wajib memberikan catatan/alasan</strong>.
                </p>
            </div>
        </div>
    </div>

    <div class="pkg-panel p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                @if($siswa->foto)
                    <img class="h-16 w-16 rounded-full object-cover" src="{{ asset('storage/' . $siswa->foto) }}" alt="">
                @else
                    <div class="h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                        <span class="text-2xl text-blue-600 dark:text-blue-300 font-medium">{{ substr($siswa->nama, 0, 1) }}</span>
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $siswa->nama }}</h2>
                    <p class="text-gray-600 dark:text-gray-400">NIS: {{ $siswa->nis }} | Kelas: {{ $siswa->kelas->nama ?? '-' }}</p>
                </div>
            </div>
            <a href="{{ route('tugas-pkg.history', $siswa) }}" class="pkg-btn-secondary inline-flex items-center gap-1 px-3 py-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Riwayat Ceklis
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('tugas-pkg.store-check', $siswa) }}" method="POST">
        @csrf
        <div class="pkg-panel">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pilih Karakter yang Sudah Dilaksanakan</h3>
                <p class="text-sm text-gray-500 dark:text-gray-300">Centang karakter yang sudah dilaporkan siswa ke pamong ({{ now()->format('d M Y') }})</p>
            </div>

            <div class="p-6">
                @if($karakterList->isEmpty())
                    <div class="pkg-empty-state py-8">
                        <h3 class="pkg-empty-title">Belum ada tugas PKG</h3>
                        <p class="pkg-empty-copy">Tambahkan tugas PKG terlebih dahulu agar pamong bisa membantu ceklis siswa dari halaman ini.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($karakterList as $karakter)
                        <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ in_array($karakter->id, $todayChecked) ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : '' }}">
                            <input type="checkbox" name="karakter_ids[]" value="{{ $karakter->id }}"
                                class="mt-1 h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                {{ in_array($karakter->id, $todayChecked) ? 'checked disabled' : '' }}>
                            <div class="flex-1">
                                <span class="block font-medium text-gray-900 dark:text-white">
                                    {{ $karakter->nama }}
                                    @if($karakter->jenis_penyelesaian === 'teks')
                                        <span class="pkg-status-badge pkg-status-neutral ml-1 whitespace-nowrap">Teks</span>
                                    @elseif($karakter->jenis_penyelesaian === 'klik')
                                        <span class="pkg-status-badge pkg-status-info ml-1 whitespace-nowrap">Zikir</span>
                                    @endif
                                </span>
                                @if($karakter->deskripsi)
                                    <span class="block text-sm text-gray-500 dark:text-gray-300 mt-1">{{ $karakter->deskripsi }}</span>
                                @endif
                                @if(in_array($karakter->id, $todayChecked))
                                    <span class="pkg-status-badge pkg-status-success mt-2 inline-flex">
                                        Sudah diceklis hari ini
                                    </span>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                <label for="catatan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Alasan / Catatan <span class="text-red-500">*</span>
                </label>
                <textarea name="catatan" id="catatan" rows="3" required
                    class="w-full px-3 py-2 pkg-field"
                    placeholder="Wajib isi alasan, misal: Siswa sudah lapor ke pamong, HP rusak / tidak punya HP ...">{{ old('catatan') }}</textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Catatan wajib diisi sebagai bukti bahwa siswa sudah melaporkan ke pamong</p>
            </div>

            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <a href="{{ route('tugas-pkg.verification') }}" class="pkg-btn-secondary px-4 py-2">
                    Batal
                </a>
                <button type="submit" class="pkg-btn-primary px-6 py-2 font-medium">
                    Simpan Ceklis
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
