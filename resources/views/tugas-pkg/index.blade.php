@extends('layouts.app')

@section('title', 'Daftar Tugas PKG Aktif')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Daftar Tugas PKG Aktif</h1>
            <p class="pkg-page-subheading">Pantau tugas yang sedang bisa dikerjakan siswa dan antrean verifikasinya.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('tugas-pkg.verification') }}" class="btn-success text-sm !px-4 !py-2">Verifikasi Tugas</a>
            <a href="{{ route('tugas-pkg.master') }}" class="btn-primary text-sm !px-4 !py-2">Buat Tugas</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <div class="pkg-card-soft p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Total aktif</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $activeSummary['total'] ?? 0 }}</p>
        </div>
        <div class="pkg-card-soft p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Dengan bukti</p>
            <p class="mt-2 text-2xl font-bold text-sky-600 dark:text-sky-300">{{ $activeSummary['with_proof'] ?? 0 }}</p>
        </div>
        <div class="pkg-card-soft p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Input teks</p>
            <p class="mt-2 text-2xl font-bold text-violet-600 dark:text-violet-300">{{ $activeSummary['teks'] ?? 0 }}</p>
        </div>
        <div class="pkg-card-soft p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Hitungan</p>
            <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-300">{{ $activeSummary['klik'] ?? 0 }}</p>
        </div>
    </div>

    <x-collapsible-section
        title="Filter daftar tugas"
        description="Cari tugas aktif berdasarkan nama, kategori, atau jenis penyelesaian."
        :open="request()->filled('search') || request()->filled('karakter_id') || request()->filled('kategori') || request()->filled('jenis_penyelesaian')"
        :compact="true"
        class="mb-6"
    >
        <form method="GET" class="pkg-filter-grid sm:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto_auto]">
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Cari</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama tugas atau deskripsi" class="pkg-field w-full">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tugas</span>
                <select name="karakter_id" class="pkg-field w-full">
                    <option value="">Semua tugas</option>
                    @foreach($karakterOptions as $karakter)
                        <option value="{{ $karakter->id }}" {{ (string) request('karakter_id') === (string) $karakter->id ? 'selected' : '' }}>
                            {{ $karakter->nama }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Kategori</span>
                <select name="kategori" class="pkg-field w-full">
                    <option value="">Semua kategori</option>
                    <option value="harian" {{ request('kategori') === 'harian' ? 'selected' : '' }}>Harian</option>
                    <option value="mingguan" {{ request('kategori') === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                    <option value="bulanan" {{ request('kategori') === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Jenis</span>
                <select name="jenis_penyelesaian" class="pkg-field w-full">
                    <option value="">Semua jenis</option>
                    <option value="checklist" {{ request('jenis_penyelesaian') === 'checklist' ? 'selected' : '' }}>Checklist</option>
                    <option value="teks" {{ request('jenis_penyelesaian') === 'teks' ? 'selected' : '' }}>Input teks</option>
                    <option value="klik" {{ request('jenis_penyelesaian') === 'klik' ? 'selected' : '' }}>Hitungan</option>
                </select>
            </label>
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full justify-center text-sm !px-4 !py-2">Filter</button>
            </div>
            <div class="flex items-end">
                <a href="{{ route('tugas-pkg.index') }}" class="btn-secondary w-full justify-center text-sm !px-4 !py-2">Reset</a>
            </div>
        </form>
    </x-collapsible-section>

    @php
        $categoryColors = [
            'harian' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            'mingguan' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
            'bulanan' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        ];
    @endphp

    @if($taskList->isEmpty())
        <div class="pkg-card">
            <div class="pkg-empty-state">
                <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="pkg-empty-title">Belum ada tugas PKG aktif</p>
                <p class="pkg-empty-copy">Aktifkan tugas di menu master tugas agar daftar aktif muncul di halaman ini.</p>
            </div>
        </div>
    @else
        <div class="space-y-3 lg:hidden">
            @foreach($taskList as $task)
                <article class="pkg-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $task->nama }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-300">{{ Str::limit($task->deskripsi ?: 'Tanpa deskripsi.', 110) }}</p>
                        </div>
                        <div class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            +{{ $task->poin }}
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $categoryColors[$task->kategori] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                            {{ $task->kategori_label }}
                        </span>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            @if($task->jenis_penyelesaian === 'teks')
                                Input teks
                            @elseif($task->jenis_penyelesaian === 'klik')
                                Hitungan{{ $task->target_klik ? ' ' . $task->target_klik : '' }}
                            @else
                                Checklist
                            @endif
                        </span>
                        @if($task->allows_photo_proof || $task->allows_voice_note_proof)
                            <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                Ada bukti
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-800 dark:bg-slate-950/40">
                        <p class="font-medium text-slate-700 dark:text-slate-200">{{ $task->formatted_period ?: 'Tanpa batas tanggal' }}</p>
                        @if(($task->photo_proof_bonus_points ?? 0) > 0 || ($task->voice_note_bonus_points ?? 0) > 0)
                            <p class="mt-1 text-xs text-sky-600 dark:text-sky-300">Bonus bukti +{{ ($task->photo_proof_bonus_points ?? 0) + ($task->voice_note_bonus_points ?? 0) }} poin</p>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg bg-slate-50 p-2 dark:bg-slate-950/40">
                            <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $task->total_dikerjakan_count }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Kiriman</p>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-2 dark:bg-amber-950/30">
                            <p class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ $task->pending_verification_count }}</p>
                            <p class="text-xs text-amber-700 dark:text-amber-300">Menunggu</p>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-2 dark:bg-emerald-950/30">
                            <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $task->verified_count }}</p>
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">Selesai</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @if($task->pending_verification_count > 0)
                            <a href="{{ route('tugas-pkg.verification', ['status' => 'unverified', 'karakter_id' => $task->id]) }}" class="btn-success justify-center text-xs !px-3 !py-2">
                                Verifikasi {{ $task->pending_verification_count }}
                            </a>
                        @endif
                        @if($task->total_dikerjakan_count > 0)
                            <a href="{{ route('tugas-pkg.verification', ['status' => 'all', 'karakter_id' => $task->id]) }}" class="btn-secondary justify-center text-xs !px-3 !py-2">
                                Lihat Riwayat
                            </a>
                        @else
                            <span class="rounded-lg bg-slate-50 px-3 py-2 text-center text-xs text-slate-500 dark:bg-slate-900 dark:text-slate-400">Belum ada kiriman</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pkg-card hidden overflow-hidden lg:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Tugas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Detail</th>
                            <th class="px-6 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Poin</th>
                            <th class="px-6 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Progress</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($taskList as $task)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $task->nama }}</div>
                                    <div class="mt-1 max-w-xl text-sm leading-6 text-gray-500 dark:text-gray-300">{{ Str::limit($task->deskripsi ?: 'Tanpa deskripsi.', 130) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $categoryColors[$task->kategori] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                            {{ $task->kategori_label }}
                                        </span>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            @if($task->jenis_penyelesaian === 'teks')
                                                Input teks
                                            @elseif($task->jenis_penyelesaian === 'klik')
                                                Hitungan{{ $task->target_klik ? ' ' . $task->target_klik : '' }}
                                            @else
                                                Checklist
                                            @endif
                                        </span>
                                        @if($task->allows_photo_proof || $task->allows_voice_note_proof)
                                            <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">Bukti aktif</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $task->formatted_period ?: 'Tanpa batas tanggal' }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="text-sm font-semibold text-emerald-600 dark:text-emerald-300">+{{ $task->poin }}</div>
                                    @if(($task->photo_proof_bonus_points ?? 0) > 0 || ($task->voice_note_bonus_points ?? 0) > 0)
                                        <div class="mt-1 text-xs text-sky-600 dark:text-sky-300">Bonus +{{ ($task->photo_proof_bonus_points ?? 0) + ($task->voice_note_bonus_points ?? 0) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center text-sm">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $task->total_dikerjakan_count }} kiriman</div>
                                    <div class="mt-1 text-xs text-amber-600 dark:text-amber-300">Menunggu {{ $task->pending_verification_count }}</div>
                                    <div class="mt-1 text-xs text-emerald-600 dark:text-emerald-300">Terverifikasi {{ $task->verified_count }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-col items-end gap-2">
                                        @if($task->pending_verification_count > 0)
                                            <a href="{{ route('tugas-pkg.verification', ['status' => 'unverified', 'karakter_id' => $task->id]) }}" class="btn-success text-xs !px-3 !py-2">
                                                Verifikasi {{ $task->pending_verification_count }}
                                            </a>
                                        @endif
                                        @if($task->total_dikerjakan_count > 0)
                                            <a href="{{ route('tugas-pkg.verification', ['status' => 'all', 'karakter_id' => $task->id]) }}" class="btn-secondary text-xs !px-3 !py-2">
                                                Lihat Riwayat
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400">Belum ada kiriman</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $taskList->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
