@extends('layouts.app')

@section('title', $materi->judul)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">{{ $materi->judul }}</h1>
            <p class="pkg-page-subheading">{{ $materi->bulan ? $materi->bulan->format('F Y') : 'Periode belum diatur' }}</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('materi.index') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    <div class="pkg-panel">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Ringkasan Materi</h2>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">{{ $materi->bulan ? $materi->bulan->format('F Y') : '-' }}</p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full {{ $materi->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                    {{ $materi->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
        </div>

        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Deskripsi</h2>
            <div class="prose dark:prose-invert max-w-none">
                {!! nl2br(e($materi->deskripsi)) !!}
            </div>
        </div>

        @include('materi.partials.rpp-summary', ['materi' => $materi, 'showStatus' => true])

        @if(($rppJournals ?? collect())->isNotEmpty())
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Jurnal RPP</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Catatan realisasi dan tindak lanjut per tanggal RPP.</p>
                </div>
                <a href="{{ route('materi-rpp-journals.index', ['materi_id' => $materi->id]) }}" class="btn-secondary px-3 py-2 text-xs">Lihat Rekap</a>
            </div>
            <div class="space-y-3">
                @foreach($rppJournals as $journal)
                    <div class="pkg-card-soft p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $journal->journal_date?->format('d M Y') }} - Pertemuan {{ $journal->session_number ?? '-' }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Target {{ $journal->target_page_range ?? '-' }}; realisasi {{ $journal->actual_page_range }}</p>
                                @if($journal->notes)
                                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($journal->notes, 140) }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ $journal->status_label }}</span>
                                <a href="{{ route('materi-rpp-journals.edit', $journal) }}" class="btn-secondary px-3 py-2 text-xs">Lihat</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        @include('materi.partials.pdf-viewer', [
            'wrapperClass' => 'border-b border-gray-200 p-6 dark:border-gray-700',
            'heading' => 'File PDF',
        ])

        @include('materi.partials.video-list', ['materi' => $materi])

        @if(($canEditMateri ?? false) || ($canDeleteMateri ?? false))
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
            @if(($canEditMateri ?? false) && $materi->hasRpp() && ! $materi->isRppPublished())
            <form action="{{ route('materi.publish-rpp', $materi) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-success">Publikasikan RPP</button>
            </form>
            @endif
            @if($canEditMateri ?? false)
            <a href="{{ route('materi.edit', $materi) }}" class="btn-primary">Edit</a>
            @endif
            @if($canDeleteMateri ?? false)
            <form action="{{ route('materi.destroy', $materi) }}" method="POST" class="inline" data-confirm="Yakin ingin menghapus materi ini?" data-confirm-title="Hapus materi" data-confirm-button="Hapus" data-confirm-tone="danger">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Hapus</button>
            </form>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
