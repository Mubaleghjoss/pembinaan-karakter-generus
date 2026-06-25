@extends('layouts.siswa')

@section('title', 'Isi Jurnal RPP')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Isi Jurnal RPP</h1>
            <p class="pkg-page-subheading">Catat hasil pelaksanaan materi sesuai event yang ditugaskan.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('siswa.materi-rpp-journals.index') }}" class="btn-secondary">Kembali</a>
        </div>
    </div>

    @if($journal->workflow_status === 'pending_review')
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200">
            Jurnal sudah dikirim dan sedang menunggu konfirmasi pamong.
        </div>
    @elseif($journal->workflow_status === 'needs_revision')
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
            <p class="font-semibold">Jurnal perlu diperbaiki.</p>
            @if($journal->review_note)<p class="mt-1 text-sm">{{ $journal->review_note }}</p>@endif
        </div>
    @elseif($journal->workflow_status === 'approved')
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
            Jurnal sudah disahkan dan tidak dapat diubah lagi.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
        <form method="POST" action="{{ route('siswa.materi-rpp-journals.store', $scheduleReminder) }}" class="pkg-panel space-y-5 p-5">
            @csrf
            <div class="pkg-filter-grid">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status Realisasi</label>
                    <select name="realization_status" class="w-full pkg-field" @disabled(! $canSubmit)>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('realization_status', $journal->realization_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Halaman Awal</label>
                    <input type="number" name="actual_page_start" min="1" value="{{ old('actual_page_start', $journal->actual_page_start) }}" class="w-full pkg-field" @disabled(! $canSubmit)>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Halaman Akhir</label>
                    <input type="number" name="actual_page_end" min="1" value="{{ old('actual_page_end', $journal->actual_page_end) }}" class="w-full pkg-field" @disabled(! $canSubmit)>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan Pelaksanaan</label>
                <textarea name="notes" rows="5" class="w-full pkg-field" required @disabled(! $canSubmit)>{{ old('notes', $journal->notes) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Kendala</label>
                <textarea name="obstacles" rows="3" class="w-full pkg-field" @disabled(! $canSubmit)>{{ old('obstacles', $journal->obstacles) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tindak Lanjut</label>
                <textarea name="follow_up" rows="3" class="w-full pkg-field" @disabled(! $canSubmit)>{{ old('follow_up', $journal->follow_up) }}</textarea>
            </div>
            @if($canSubmit)
                <div class="flex justify-end border-t border-gray-200 pt-5 dark:border-gray-700">
                    <button type="submit" class="btn-primary">Kirim untuk Konfirmasi</button>
                </div>
            @endif
        </form>

        <aside class="pkg-panel h-fit p-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Target Materi</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400">Materi</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $scheduleReminder->sourceMateri?->judul ?? $scheduleReminder->title }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Tanggal</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $scheduleReminder->start_date?->format('d M Y') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Pertemuan</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ data_get($scheduleReminder->source_payload, 'number', '-') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Target Halaman</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ data_get($scheduleReminder->source_payload, 'page_range', '-') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Pengajar</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ data_get($scheduleReminder->source_payload, 'teacher_name', '-') }}</dd></div>
            </dl>
        </aside>
    </div>
</div>
@endsection
