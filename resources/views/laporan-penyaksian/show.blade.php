@extends('layouts.app')

@section('title', 'Detail Laporan - PKG')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Detail Laporan Penyaksian</h1>
            <p class="pkg-page-subheading">Dilaporkan pada {{ $laporanPenyaksian->created_at->format('d M Y H:i') }}.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('laporan-penyaksian.index') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="pkg-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Informasi Pelapor</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Nama Pelapor</dt>
                        <dd class="text-gray-900 dark:text-white font-medium">{{ $laporanPenyaksian->nama_pelapor }}</dd>
                    </div>
                    @if($laporanPenyaksian->email_pelapor)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $laporanPenyaksian->email_pelapor }}</dd>
                    </div>
                    @endif
                    @if($laporanPenyaksian->phone_pelapor)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">No. HP</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $laporanPenyaksian->phone_pelapor }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <div class="pkg-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Informasi {{ $laporanPenyaksian->siswa_id ? 'Generus' : 'Pamong' }}
                </h2>
                <div class="flex items-start gap-4">
                    @if($laporanPenyaksian->siswa)
                    <div class="flex-shrink-0 w-16 h-16 rounded-full overflow-hidden bg-gray-200">
                        @if($laporanPenyaksian->siswa->foto_path)
                        <img src="{{ asset('storage/' . $laporanPenyaksian->siswa->foto_path) }}" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center bg-emerald-500 text-white font-bold text-xl">
                            {{ substr($laporanPenyaksian->siswa->nama, 0, 1) }}
                        </div>
                        @endif
                    </div>
                    @elseif($laporanPenyaksian->pamong)
                    <div class="flex-shrink-0 w-16 h-16 rounded-full overflow-hidden bg-gray-200">
                        @if($laporanPenyaksian->pamong->avatar_path)
                        <img src="{{ asset('storage/' . $laporanPenyaksian->pamong->avatar_path) }}" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center bg-blue-500 text-white font-bold text-xl">
                            {{ substr($laporanPenyaksian->pamong->name ?? $laporanPenyaksian->pamong->username, 0, 1) }}
                        </div>
                        @endif
                    </div>
                    @endif
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="text-lg font-medium text-gray-900 dark:text-white">{{ $laporanPenyaksian->nama_generus }}</div>
                            @if($laporanPenyaksian->siswa_id)
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">Siswa</span>
                            @elseif($laporanPenyaksian->pamong_id)
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">Pamong</span>
                            @endif
                        </div>
                        @if($laporanPenyaksian->siswa)
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            NIS: {{ $laporanPenyaksian->siswa->nis }} | Kelas Sekolah: {{ $laporanPenyaksian->siswa->school_grade_label }} | Level PKG: {{ $laporanPenyaksian->siswa->target_grade_label }}
                        </div>
                        @if($laporanPenyaksian->siswa->pamongAssignments->count() > 0)
                        <div class="mt-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Pamong:</span>
                            @foreach($laporanPenyaksian->siswa->pamongAssignments as $pa)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-1">
                                {{ $pa->pamong?->username }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                        @elseif($laporanPenyaksian->pamong)
                        <div class="text-sm text-gray-500 dark:text-gray-400">Username: {{ $laporanPenyaksian->pamong->username }}</div>
                        @if($laporanPenyaksian->pamong->email)
                        <div class="text-sm text-gray-500 dark:text-gray-400">Email: {{ $laporanPenyaksian->pamong->email }}</div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="pkg-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Detail Laporan</h2>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Tanggal Kejadian</dt>
                        <dd class="text-gray-900 dark:text-white font-medium">{{ $laporanPenyaksian->tanggal_kejadian->format('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Karakter yang Belum Optimal</dt>
                        <dd class="text-gray-900 dark:text-white mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">{{ $laporanPenyaksian->karakter_belum_optimal }}</dd>
                    </div>
                    @if($laporanPenyaksian->deskripsi_kejadian)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Deskripsi Tambahan</dt>
                        <dd class="text-gray-900 dark:text-white mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">{{ $laporanPenyaksian->deskripsi_kejadian }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="space-y-6">
            <div class="pkg-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Status dan Tindak Lanjut</h2>

                <div class="mb-4">
                    <span class="px-3 py-1 text-sm font-medium rounded-full 
                        @if($laporanPenyaksian->status == 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @elseif($laporanPenyaksian->status == 'ditindaklanjuti') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @endif">
                        {{ $laporanPenyaksian->status_label }}
                    </span>
                </div>

                @if($laporanPenyaksian->penindak)
                <div class="mb-4 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Ditindaklanjuti oleh:</span>
                    <span class="text-gray-900 dark:text-white font-medium">{{ $laporanPenyaksian->penindak->username }}</span>
                    <div class="text-gray-500 dark:text-gray-400 text-xs">{{ $laporanPenyaksian->ditindaklanjuti_at?->format('d M Y H:i') }}</div>
                </div>
                @endif

                @if($laporanPenyaksian->catatan_tindak_lanjut)
                <div class="mb-4">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Catatan:</span>
                    <p class="text-gray-900 dark:text-white mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded text-sm">{{ $laporanPenyaksian->catatan_tindak_lanjut }}</p>
                </div>
                @endif

                <form action="{{ route('laporan-penyaksian.update', $laporanPenyaksian) }}" method="POST" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Update Status</label>
                        <select name="status" class="w-full pkg-field">
                            <option value="pending" {{ $laporanPenyaksian->status == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="ditindaklanjuti" {{ $laporanPenyaksian->status == 'ditindaklanjuti' ? 'selected' : '' }}>Ditindaklanjuti</option>
                            <option value="selesai" {{ $laporanPenyaksian->status == 'selesai' ? 'selected' : '' }}>Selesai</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Catatan Tindak Lanjut</label>
                        <textarea name="catatan_tindak_lanjut" rows="3" class="w-full pkg-field" placeholder="Tuliskan catatan tindak lanjut...">{{ $laporanPenyaksian->catatan_tindak_lanjut }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary w-full">Simpan Perubahan</button>
                </form>
            </div>

            @if(auth()->user()->isAdmin())
            <div class="pkg-card p-6">
                <h2 class="text-lg font-medium text-red-600 dark:text-red-400 mb-4">Zona Bahaya</h2>
                <form action="{{ route('laporan-penyaksian.destroy', $laporanPenyaksian) }}" method="POST" data-no-csrf-handler data-confirm="Yakin ingin menghapus laporan ini?" data-confirm-title="Hapus laporan" data-confirm-button="Hapus" data-confirm-tone="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger w-full">Hapus Laporan</button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
