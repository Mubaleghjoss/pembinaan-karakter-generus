@extends('layouts.siswa')

@section('title', 'Tugas Terverifikasi')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tugas Terverifikasi</h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Daftar tugas PKG yang sudah diverifikasi beserta detail poin</p>
            </div>
            <a href="{{ route('siswa.tugas-pkg.index') }}" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totalVerified }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Terverifikasi</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">+{{ $totalPoints }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Poin Diperoleh</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        @forelse($history as $item)
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->karakter->nama ?? 'Tugas dihapus' }}</h3>
                            @if($item->karakter)
                                @php
                                    $catColors = [
                                        'harian' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'mingguan' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                        'bulanan' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                    ];
                                @endphp
                                <span class="px-1.5 py-0.5 text-xs font-medium rounded {{ $catColors[$item->karakter->kategori] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $item->karakter->kategori_label }}
                                </span>
                            @endif
                        </div>
                        @if($item->karakter?->deskripsi)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $item->karakter->deskripsi }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <span>Dikerjakan: {{ $item->checked_at->format('d M Y H:i') }}</span>
                            <span>Diverifikasi: {{ $item->verified_at->format('d M Y H:i') }}</span>
                            @if($item->verifier)
                                <span>Oleh: {{ $item->verifier->name ?? $item->verifier->username }}</span>
                            @endif
                        </div>
                        @if($item->student_note)
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1 italic">Catatan siswa: {{ $item->student_note }}</p>
                        @endif
                        @if($item->notes)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">Catatan: {{ $item->notes }}</p>
                        @endif
                        @if($item->hasil_teks)
                            <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">Jawaban teks: {{ $item->hasil_teks }}</p>
                        @endif
                        @if($item->has_photo_proof)
                            <div class="mt-2 flex flex-wrap items-start gap-3">
                                <a href="{{ $item->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $item->proof_url }}" data-preview-alt="Bukti foto {{ $item->karakter->nama ?? 'tugas' }}" data-preview-title="Bukti foto - {{ $item->karakter->nama ?? 'Tugas PKG' }}" data-preview-filename="{{ basename($item->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $item->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="block overflow-hidden rounded-lg border border-blue-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow dark:border-blue-800 dark:bg-slate-900">
                                    <img
                                        src="{{ $item->proof_url }}"
                                        alt="Bukti foto {{ $item->karakter->nama ?? 'tugas' }}"
                                        loading="lazy"
                                        class="h-14 w-14 object-cover"
                                    >
                                </a>
                                <div>
                                    <a href="{{ $item->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $item->proof_url }}" data-preview-alt="Bukti foto {{ $item->karakter->nama ?? 'tugas' }}" data-preview-title="Bukti foto - {{ $item->karakter->nama ?? 'Tugas PKG' }}" data-preview-filename="{{ basename($item->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $item->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                        Lihat bukti foto
                                    </a>
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->proof_compressed_size_kb ?? 0 }} KB
                                    </span>
                                </div>
                            </div>
                        @endif
                        @if($item->has_voice_note)
                            <div class="mt-2 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 dark:border-violet-800 dark:bg-violet-900/20">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-medium text-violet-700 dark:text-violet-300">Voice note bukti</p>
                                    <span class="text-xs text-violet-600 dark:text-violet-400">
                                        {{ $item->voice_note_size_kb ?? 0 }} KB
                                        @if($item->voice_note_duration_label)
                                            | {{ $item->voice_note_duration_label }}
                                        @endif
                                    </span>
                                </div>
                                <audio controls preload="none" class="mt-2 w-full max-w-sm">
                                    <source src="{{ $item->voice_note_url }}">
                                </audio>
                                <a href="{{ $item->voice_note_url }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 rounded-lg border border-violet-200 bg-white px-2.5 py-1.5 text-xs font-medium text-violet-700 transition hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                                    Unduh voice note
                                </a>
                            </div>
                        @endif
                        @if(($item->proof_bonus_points ?? 0) > 0)
                            <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">Bonus bukti +{{ $item->proof_bonus_points }} poin</p>
                        @endif
                    </div>
                    <div class="flex-shrink-0">
                        <span class="px-2.5 py-1.5 text-sm font-bold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg">
                            +{{ $item->awarded_points ?? ($item->karakter->poin ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                <p>Belum ada tugas yang terverifikasi.</p>
                <a href="{{ route('siswa.tugas-pkg.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm mt-2 inline-block">Kerjakan tugas PKG</a>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $history->links() }}
    </div>
</div>

@include('components.image-preview-modal')
@endsection
