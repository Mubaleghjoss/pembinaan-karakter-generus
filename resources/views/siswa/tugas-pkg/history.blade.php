@extends('layouts.siswa')

@section('title', 'Riwayat Tugas PKG')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Riwayat Tugas PKG</h1>
            <p class="text-gray-600 dark:text-gray-400">Catatan karakter yang sudah kamu praktikkan</p>
        </div>
        <a href="{{ route('siswa.tugas-pkg.index') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            Kembali
        </a>
    </div>

    @if($history->isEmpty())
    <div class="pkg-card p-8 text-center">
        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-gray-500">Belum ada riwayat karakter</p>
    </div>
    @else
    <div class="space-y-8">
        @foreach($historyByMonth as $month => $items)
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->isoFormat('MMMM YYYY') }}
            </h2>
            <div class="space-y-4">
                @foreach($items as $item)
                <div class="pkg-card p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $item->karakter->nama }}
                                </h3>
                                @if($item->isVerified())
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded">
                                    Terverifikasi
                                </span>
                                @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 rounded">
                                    Menunggu Verifikasi
                                </span>
                                @endif
                                <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded">
                                    +{{ $item->awarded_points ?? ($item->karakter->poin ?? 0) }} poin
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                {{ $item->karakter->deskripsi }}
                            </p>
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $item->checked_at->isoFormat('dddd, D MMMM YYYY HH:mm') }}
                            </div>
                            @if($item->isVerified())
                            <div class="mt-2 flex items-center text-sm text-green-600 dark:text-green-400">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Diverifikasi oleh {{ $item->verifier->username }} pada {{ $item->verified_at->isoFormat('D MMM YYYY HH:mm') }}
                            </div>
                            @endif
                            @if($item->notes)
                            <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300">
                                <strong>Catatan verifikator:</strong> {{ $item->notes }}
                            </div>
                            @endif
                            @if($item->student_note)
                            <div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800">
                                <strong>Catatan siswa:</strong> {{ $item->student_note }}
                            </div>
                            @endif
                            @if($item->hasil_teks)
                            <div class="mt-2 p-3 bg-purple-50 dark:bg-purple-900/20 rounded text-sm text-purple-800 dark:text-purple-200 border border-purple-200 dark:border-purple-800">
                                <strong>Jawaban teks:</strong> {{ $item->hasil_teks }}
                            </div>
                            @endif
                            @if($item->has_photo_proof)
                            <div class="mt-3 flex flex-wrap items-start gap-3">
                                <a href="{{ $item->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $item->proof_url }}" data-preview-alt="Bukti foto {{ $item->karakter->nama }}" data-preview-title="Bukti foto - {{ $item->karakter->nama }}" data-preview-filename="{{ basename($item->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $item->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="block overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow dark:border-blue-800 dark:bg-slate-900">
                                    <img
                                        src="{{ $item->proof_url }}"
                                        alt="Bukti foto {{ $item->karakter->nama }}"
                                        loading="lazy"
                                        class="h-16 w-16 object-cover"
                                    >
                                </a>
                                <div>
                                    <a href="{{ $item->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $item->proof_url }}" data-preview-alt="Bukti foto {{ $item->karakter->nama }}" data-preview-title="Bukti foto - {{ $item->karakter->nama }}" data-preview-filename="{{ basename($item->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $item->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                        Lihat bukti foto
                                    </a>
                                    <span class="mt-2 block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->proof_compressed_size_kb ?? 0 }} KB
                                    </span>
                                </div>
                            </div>
                            @endif
                            @if($item->has_voice_note)
                            <div class="mt-3 rounded-xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-800 dark:bg-violet-900/20">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-violet-700 dark:text-violet-300">Voice note bukti</p>
                                    <span class="text-xs text-violet-600 dark:text-violet-400">
                                        {{ $item->voice_note_size_kb ?? 0 }} KB
                                        @if($item->voice_note_duration_label)
                                            | {{ $item->voice_note_duration_label }}
                                        @endif
                                    </span>
                                </div>
                                <audio controls preload="none" class="mt-2 w-full">
                                    <source src="{{ $item->voice_note_url }}">
                                </audio>
                                <a href="{{ $item->voice_note_url }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 rounded-lg border border-violet-200 bg-white px-3 py-2 text-sm font-medium text-violet-700 transition hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                                    Unduh voice note
                                </a>
                            </div>
                            @endif
                            @if(($item->proof_bonus_points ?? 0) > 0)
                            <div class="mt-2 text-xs text-blue-600 dark:text-blue-400">Bonus bukti +{{ $item->proof_bonus_points }} poin</div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $history->links() }}
    </div>
    @endif
</div>
@include('components.image-preview-modal')
@endsection
