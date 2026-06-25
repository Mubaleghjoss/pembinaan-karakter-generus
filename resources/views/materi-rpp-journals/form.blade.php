@extends('layouts.app')

@section('title', ($isNew ? 'Isi' : 'Lihat') . ' Jurnal RPP')

@section('content')
@php
    $workflowState = $journal->exists ? $journal->workflow_status : 'pending';
    $workflowLabel = $journal->exists ? $journal->workflow_label : 'Belum Diisi';
@endphp
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">{{ $isNew ? 'Tugas Jurnal RPP' : 'Jurnal RPP' }}</h1>
            <p class="pkg-page-subheading">Catat realisasi materi dan kelola petugas per pertemuan.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('calendar.index') }}" class="btn-secondary text-sm">Kalender</a>
            <a href="{{ route('materi-rpp-journals.index') }}" class="btn-secondary text-sm">Rekap Jurnal</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($journal->exists && $workflowState === 'pending_review')
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200">
            Jurnal ini dikirim siswa dan menunggu konfirmasi pamong atau admin.
        </div>
    @elseif($journal->exists && $workflowState === 'needs_revision')
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
            Jurnal dikembalikan kepada siswa untuk diperbaiki.
        </div>
    @elseif(! $scheduleReminder->isJournalAvailable())
        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
            Form jurnal dapat diisi setelah acara selesai pada {{ $scheduleReminder->journalAvailableAt()->format('d M Y H:i') }}.
        </div>
    @endif

    @if($canAssign)
        <section class="pkg-panel mb-6 p-5">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Petugas Jurnal</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tambahkan beberapa pamong, admin, atau siswa yang boleh mengisi jurnal yang sama.</p>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">Petugas terkunci setelah jurnal dikirim.</span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @forelse($scheduleReminder->journalAssignees as $assignedPerson)
                    @php
                        $isMainTeacher = $assignedPerson->user_id
                            && (int) $assignedPerson->user_id === (int) data_get($scheduleReminder->source_payload, 'teacher_user_id');
                    @endphp
                    <div class="pkg-card-soft flex items-center justify-between gap-3 p-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $assignedPerson->label }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $assignedPerson->type_label }}{{ $isMainTeacher ? ' · Pengajar utama' : '' }}
                            </p>
                        </div>
                        @unless($isMainTeacher)
                            <form method="POST" action="{{ route('materi-rpp-journals.schedule.assignees.destroy', [$scheduleReminder, $assignedPerson]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger px-3 py-2 text-xs" data-confirm="Hapus petugas jurnal ini?" data-confirm-title="Hapus Petugas" data-confirm-button="Hapus" data-confirm-tone="danger">Hapus</button>
                            </form>
                        @endunless
                    </div>
                @empty
                    <div class="pkg-empty-state sm:col-span-2">
                        <p class="pkg-empty-title">Belum ada petugas</p>
                        <p class="pkg-empty-copy">Cari dan tambahkan petugas jurnal di bawah ini.</p>
                    </div>
                @endforelse
            </div>

            <form method="GET" action="{{ route('materi-rpp-journals.schedule', $scheduleReminder) }}" class="pkg-filter-grid mt-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Petugas</label>
                    <select name="assignee_type" class="w-full pkg-field">
                        <option value="user" @selected($assigneeType === 'user')>Admin / Pamong</option>
                        <option value="siswa" @selected($assigneeType === 'siswa')>Siswa</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Nama atau Akun</label>
                    <input name="assignee_q" value="{{ $assigneeQuery }}" class="w-full pkg-field" placeholder="Ketik nama, username, atau NIS">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-secondary w-full justify-center">Cari</button>
                </div>
            </form>

            <form method="POST" action="{{ route('materi-rpp-journals.schedule.assignees.store', $scheduleReminder) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <input type="hidden" name="assignee_type" value="{{ $assigneeType }}">
                <div class="min-w-0 flex-1">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Petugas</label>
                    <select name="assignee_id" class="w-full pkg-field" required>
                        <option value="">Pilih hasil pencarian</option>
                        @foreach($assigneeOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('assignee_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-primary">Tambah Petugas</button>
            </form>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <form method="POST" action="{{ $isNew ? route('materi-rpp-journals.schedule.store', $scheduleReminder) : route('materi-rpp-journals.update', $journal) }}" class="pkg-panel space-y-5 p-5">
            @csrf
            @if(! $isNew)
                @method('PATCH')
            @endif

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
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Halaman Awal Realisasi</label>
                    <input type="number" name="actual_page_start" min="1" value="{{ old('actual_page_start', $journal->actual_page_start) }}" class="w-full pkg-field" placeholder="Opsional" @disabled(! $canSubmit)>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Halaman Akhir Realisasi</label>
                    <input type="number" name="actual_page_end" min="1" value="{{ old('actual_page_end', $journal->actual_page_end) }}" class="w-full pkg-field" placeholder="Opsional" @disabled(! $canSubmit)>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan Umum</label>
                <textarea name="notes" rows="5" class="w-full pkg-field" placeholder="Catatan pelaksanaan materi" @disabled(! $canSubmit)>{{ old('notes', $journal->notes) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Kendala</label>
                <textarea name="obstacles" rows="3" class="w-full pkg-field" placeholder="Opsional" @disabled(! $canSubmit)>{{ old('obstacles', $journal->obstacles) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tindak Lanjut</label>
                <textarea name="follow_up" rows="3" class="w-full pkg-field" placeholder="Opsional" @disabled(! $canSubmit)>{{ old('follow_up', $journal->follow_up) }}</textarea>
            </div>

            @if($canSubmit)
                <div class="flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                    <a href="{{ route('materi-rpp-journals.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary">Simpan dan Sahkan</button>
                </div>
            @endif
        </form>

        <div class="space-y-6">
            <aside class="pkg-panel h-fit p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Ringkasan RPP</h2>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $workflowLabel }}</span>
                </div>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">Materi</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $scheduleReminder->sourceMateri?->judul ?? $journal->materi_title }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Tanggal</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $journal->journal_date?->format('d M Y') }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Pertemuan</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $journal->session_number ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Target Halaman</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $journal->target_page_range ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Pengajar</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $journal->teacher_name ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Petugas Jurnal</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $scheduleReminder->journal_assignee_label }}</dd></div>
                    @if($journal->submittedBySiswa)
                        <div><dt class="text-gray-500 dark:text-gray-400">Dikirim Siswa</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $journal->submittedBySiswa->nama }}</dd></div>
                    @elseif($journal->creator)
                        <div><dt class="text-gray-500 dark:text-gray-400">Pengisi</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ $journal->creator->display_name }}</dd></div>
                    @endif
                </dl>
            </aside>

            @if($canReview && $workflowState === 'pending_review')
                <section class="pkg-panel p-5">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Konfirmasi Jurnal Siswa</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sahkan jika isi sudah benar, atau kembalikan dengan catatan perbaikan.</p>

                    <form method="POST" action="{{ route('materi-rpp-journals.review', $journal) }}" class="mt-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="review_action" value="approve">
                        <button type="submit" class="btn-success w-full justify-center">Sahkan Jurnal</button>
                    </form>

                    <form method="POST" action="{{ route('materi-rpp-journals.review', $journal) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="review_action" value="revise">
                        <textarea name="review_note" rows="3" class="w-full pkg-field" placeholder="Catatan yang harus diperbaiki" required></textarea>
                        <button type="submit" class="btn-secondary w-full justify-center">Kembalikan untuk Perbaikan</button>
                    </form>
                </section>
            @elseif($journal->review_note)
                <section class="pkg-card-soft p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Catatan Peninjau</h2>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $journal->review_note }}</p>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection
