@extends('layouts.app')

@section('title', 'Daftar Ulang Generus')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Daftar Ulang Generus</h1>
            <p class="pkg-page-subheading">Bagikan tautan daftar-ulang ke Orang Tua, pantau status tanda tangan surat pernyataan, dan unduh dokumennya.</p>
        </div>
    </div>

    @include('settings.partials.admin-tabs', ['tab' => 'daftarulang'])

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- Pengaturan Template Pesan WhatsApp -->
    <section x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }" class="mb-6 pkg-panel-lg p-4 sm:p-5">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left">
            <span>
                <span class="block font-bold text-gray-900 dark:text-white">Template Pesan WhatsApp</span>
                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">Atur teks yang otomatis terisi saat menekan tombol "Kirim WA".</span>
            </span>
            <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak class="mt-4">
            <form method="POST" action="{{ route('admin.generus-registration.wa-template') }}" class="space-y-3">
                @csrf
                @method('PUT')
                <textarea name="wa_template" rows="5" class="pkg-field w-full font-mono text-sm" maxlength="2000" required>{{ old('wa_template', $waTemplate) }}</textarea>
                <div class="flex flex-wrap items-center gap-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Gunakan <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-gray-800">{nama}</code> untuk nama Generus dan
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-gray-800">{link}</code> untuk tautan daftar ulang.
                    </p>
                    <button type="submit" class="btn-primary ml-auto px-4 py-2 text-sm font-bold">Simpan Template</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Pengaturan Template Pesan Informasi Akun -->
    <section x-data="{ open: false }" class="mb-6 pkg-panel-lg p-4 sm:p-5">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left">
            <span>
                <span class="block font-bold text-gray-900 dark:text-white">Template Pesan Informasi Akun</span>
                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">Teks yang terisi otomatis saat menekan "Kirim Akun ke WA Anak" / "Kirim Akun ke WA Orang Tua" di halaman hasil daftar ulang.</span>
            </span>
            <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak class="mt-4 space-y-4">
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 p-3 text-xs leading-6 text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-100">
                <p class="font-bold">Kata kunci yang bisa dipakai (otomatis diganti):</p>
                <p class="mt-1 flex flex-wrap gap-1.5">
                    @foreach(['{nama}' => 'nama Generus', '{nama_ortu}' => 'nama Orang Tua', '{nis}' => 'NIS / login anak', '{password}' => 'password (NIS)', '{username_ortu}' => 'username Orang Tua', '{link_siswa}' => 'link login siswa', '{link_ortu}' => 'link login ortu'] as $ph => $desc)
                        <span class="rounded bg-white px-1.5 py-0.5 font-mono dark:bg-gray-800">{{ $ph }}</span><span class="mr-2 text-indigo-700/80 dark:text-indigo-200/80">{{ $desc }}</span>
                    @endforeach
                </p>
                <p class="mt-2">Teks di luar kata kunci bebas Anda tulis/tambah sendiri. Baris baru akan ikut terkirim.</p>
            </div>

            <form method="POST" action="{{ route('admin.generus-registration.account-template') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">Pesan untuk WA Anak (Generus)</label>
                    <textarea name="account_wa_student" rows="10" class="pkg-field w-full font-mono text-xs sm:text-sm" maxlength="4000" required>{{ old('account_wa_student', $accountWaStudentTemplate) }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">Pesan untuk WA Orang Tua</label>
                    <textarea name="account_wa_parent" rows="10" class="pkg-field w-full font-mono text-xs sm:text-sm" maxlength="4000" required>{{ old('account_wa_parent', $accountWaParentTemplate) }}</textarea>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="btn-primary px-4 py-2 text-sm font-bold">Simpan Template Akun</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.generus-registration.account-template.reset') }}" onsubmit="return confirm('Kembalikan kedua template pesan akun ke teks bawaan?');">
                @csrf
                <button type="submit" class="btn-secondary px-4 py-2 text-sm font-semibold">Kembalikan ke Teks Bawaan</button>
            </form>
        </div>
    </section>

    {{-- Statistik: fokus Generus AKTIF (bukan alumni) --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="pkg-card-soft rounded-2xl p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Generus Aktif</p>
            <p class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ $stats['active_total'] }}</p>
            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">tidak termasuk alumni</p>
        </div>
        <div class="pkg-card-soft rounded-2xl p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">Sudah Isi Surat</p>
            <p class="mt-1 text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $stats['active_signed'] }}</p>
            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                {{ $stats['active_total'] > 0 ? round($stats['active_signed'] / $stats['active_total'] * 100) : 0 }}% dari Generus aktif
            </p>
        </div>
        <a href="{{ route('admin.generus-registration.index', ['status' => 'aktif', 'surat' => 'belum']) }}"
           class="pkg-card-soft rounded-2xl p-4 ring-amber-300 transition hover:ring-2 dark:ring-amber-700">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-300">Belum Isi Surat</p>
            <p class="mt-1 text-2xl font-black text-amber-700 dark:text-amber-300">{{ $stats['active_unsigned'] }}</p>
            <p class="mt-0.5 text-[11px] font-semibold text-amber-700/80 dark:text-amber-300/80">Klik untuk lihat daftarnya →</p>
        </a>
        <div class="pkg-card-soft rounded-2xl p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-sky-600 dark:text-sky-300">Alumni</p>
            <p class="mt-1 text-2xl font-black text-sky-700 dark:text-sky-300">{{ $stats['alumni_total'] }}</p>
            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">tidak dihitung di angka atas</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.generus-registration.index') }}" class="mb-5 pkg-panel p-3 sm:p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">Cari</label>
                <input type="search" name="q" value="{{ $search }}" placeholder="Nama Generus, Orang Tua, atau NIS..." class="pkg-field w-full">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">Status Generus</label>
                <select name="status" class="pkg-field w-full">
                    <option value="aktif" @selected($statusFilter === 'aktif')>Aktif (bukan alumni)</option>
                    <option value="alumni" @selected($statusFilter === 'alumni')>Alumni saja</option>
                    <option value="semua" @selected($statusFilter === 'semua')>Semua</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">Surat Pernyataan</label>
                <select name="surat" class="pkg-field w-full">
                    <option value="semua" @selected($suratFilter === 'semua')>Semua</option>
                    <option value="belum" @selected($suratFilter === 'belum')>Belum isi / update</option>
                    <option value="sudah" @selected($suratFilter === 'sudah')>Sudah isi</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">Kelompok</label>
                <select name="kelompok" class="pkg-field w-full">
                    <option value="">Semua kelompok</option>
                    @foreach($kelompokOptions as $value => $label)
                        <option value="{{ $value }}" @selected($kelompokFilter === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3">
                <button type="submit" class="btn-primary px-5 py-2.5 font-bold">Terapkan Filter</button>
                <a href="{{ route('admin.generus-registration.index') }}" class="btn-secondary px-5 py-2.5 font-bold">Reset</a>
                <span class="ml-auto self-center text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan <span class="font-bold text-gray-900 dark:text-white">{{ $totalCount }}</span> data
                    @if($totalCount > 0) · sudah TTD {{ $signedCount }} · belum {{ $totalCount - $signedCount }} @endif
                </span>
            </div>
        </div>
    </form>

    {{-- Kartu (mobile) --}}
    <div class="pkg-cards-mobile">
        @forelse($rows as $row)
            @php $s = $row['siswa']; $waTarget = $row['parent_wa'] ?: $row['student_wa']; $waMsg = rawurlencode(str_replace(['{nama}', '{link}'], [$s->nama, $row['direct_url']], $waTemplate)); @endphp
            <div class="pkg-data-card">
                <div class="pkg-data-card-head">
                    <div class="min-w-0">
                        <p class="pkg-data-card-title">{{ $s->nama }}</p>
                        <p class="pkg-data-card-sub">NIS {{ $s->nis }}@if($s->kelompok) · {{ \App\Models\Siswa::kelompokOptions()[$s->kelompok] ?? $s->kelompok }}@endif</p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        @if($row['signed'])
                            <span class="pkg-status-badge pkg-status-success">Sudah TTD</span>
                        @else
                            <span class="pkg-status-badge pkg-status-warning">Belum</span>
                        @endif
                        @if($s->status === 'graduated')
                            <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700 dark:bg-sky-900/50 dark:text-sky-200">Alumni</span>
                        @endif
                    </div>
                </div>
                <div class="pkg-data-card-meta">
                    <div class="pkg-data-card-row"><span class="k">Orang Tua</span><span class="v">{{ $s->nama_wali ?: '—' }}</span></div>
                    @if($row['signed'])
                        <div class="pkg-data-card-row"><span class="k">Tgl TTD</span><span class="v">{{ optional($row['registration']->statement_accepted_at)->translatedFormat('d M Y H:i') }}</span></div>
                        <div class="pkg-data-card-row"><span class="k">Update terakhir</span><span class="v">{{ optional($row['registration']->updated_at)->translatedFormat('d M Y H:i') }}</span></div>
                    @endif
                </div>
                <div class="pkg-data-card-actions flex-wrap">
                    <button type="button" class="btn-secondary" data-copy-link="{{ $row['direct_url'] }}" data-mark-shared="{{ $row['mark_shared_url'] }}" data-channel="link" data-share-target="share-{{ $s->id }}">Salin Link</button>
                    @if($waTarget)
                        <a href="{{ $waTarget }}?text={{ $waMsg }}" target="_blank" rel="noopener" class="btn-success" data-mark-shared="{{ $row['mark_shared_url'] }}" data-channel="wa" data-share-target="share-{{ $s->id }}">Kirim WA</a>
                    @endif
                    @if($row['preview_url'])
                        <a href="{{ $row['preview_url'] }}" target="_blank" rel="noopener" class="btn-primary">Lihat Surat</a>
                        <a href="{{ $row['download_url'] }}" class="btn-secondary">Unduh</a>
                    @endif
                    @if($row['signed'])
                        <form method="POST" action="{{ route('admin.generus-registration.reset', ['siswa' => $s->id]) }}" onsubmit="return confirm('Reset daftar ulang {{ addslashes($s->nama) }}? Data pernyataan &amp; tanda tangan akan dihapus, status kembali Belum. Biodata siswa tetap.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger">Reset</button>
                        </form>
                    @endif
                </div>
                <p id="share-{{ $s->id }}" class="mt-2 text-[11px] {{ $row['shared_at'] ? 'text-gray-500 dark:text-gray-400' : 'text-amber-600 dark:text-amber-400' }}">
                    @if($row['shared_at'])
                        Terakhir dibagikan: {{ $row['shared_at']->translatedFormat('d M Y H:i') }}
                        ({{ $row['shared_channel'] === 'wa' ? 'WA' : 'Salin link' }}@if($row['shared_by']) · {{ $row['shared_by'] }}@endif)
                    @else
                        Belum pernah dibagikan
                    @endif
                </p>
            </div>
        @empty
            <div class="pkg-empty-state pkg-card">
                <p class="pkg-empty-title">Tidak ada data</p>
                <p class="pkg-empty-copy">Tidak ada Generus yang cocok.</p>
            </div>
        @endforelse
    </div>

    {{-- Tabel (desktop) --}}
    <div class="pkg-table-desktop pkg-panel-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr class="text-left text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3">Generus</th>
                        <th class="px-4 py-3">WA Generus</th>
                        <th class="px-4 py-3">Orang Tua</th>
                        <th class="px-4 py-3">WA Orang Tua</th>
                        <th class="px-4 py-3">Status Surat</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($rows as $row)
                        @php $s = $row['siswa']; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3">
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ $s->nama }}
                                    @if($s->status === 'graduated')
                                        <span class="ml-1 inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700 dark:bg-sky-900/50 dark:text-sky-200">Alumni</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">NIS {{ $s->nis }}@if($s->kelompok) · {{ \App\Models\Siswa::kelompokOptions()[$s->kelompok] ?? $s->kelompok }}@endif</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($row['student_wa'])
                                    <a href="{{ $row['student_wa'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-green-600 hover:text-green-700 dark:text-green-400">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        {{ $s->phone }}
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-800 dark:text-gray-200">{{ $s->nama_wali ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($row['parent_wa'])
                                    <a href="{{ $row['parent_wa'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-green-600 hover:text-green-700 dark:text-green-400">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        {{ $s->phone_wali }}
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($row['signed'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 13 4 4L19 7"/></svg>
                                        Sudah TTD
                                    </span>
                                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">TTD: {{ optional($row['registration']->statement_accepted_at)->translatedFormat('d M Y H:i') }}</p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Update: {{ optional($row['registration']->updated_at)->translatedFormat('d M Y H:i') }}</p>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/50 dark:text-amber-200">Belum</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="btn-secondary !px-2.5 !py-1.5 text-xs" data-copy-link="{{ $row['direct_url'] }}" data-mark-shared="{{ $row['mark_shared_url'] }}" data-channel="link" data-share-target="share-row-{{ $s->id }}">Salin Link</button>
                                    @php
                                        $waTarget = $row['parent_wa'] ?: $row['student_wa'];
                                        $waMsg = rawurlencode(str_replace(
                                            ['{nama}', '{link}'],
                                            [$s->nama, $row['direct_url']],
                                            $waTemplate
                                        ));
                                    @endphp
                                    @if($waTarget)
                                        <a href="{{ $waTarget }}?text={{ $waMsg }}" target="_blank" rel="noopener" class="btn-success !px-2.5 !py-1.5 text-xs" data-mark-shared="{{ $row['mark_shared_url'] }}" data-channel="wa" data-share-target="share-row-{{ $s->id }}">Kirim WA</a>
                                    @endif
                                    @if($row['preview_url'])
                                        <a href="{{ $row['preview_url'] }}" target="_blank" rel="noopener" class="btn-primary !px-2.5 !py-1.5 text-xs">Lihat Surat</a>
                                        <a href="{{ $row['download_url'] }}" class="btn-secondary !px-2.5 !py-1.5 text-xs">Unduh</a>
                                    @endif
                                    @if($row['signed'])
                                        <form method="POST" action="{{ route('admin.generus-registration.reset', ['siswa' => $s->id]) }}" onsubmit="return confirm('Reset daftar ulang {{ addslashes($s->nama) }}? Data pernyataan &amp; tanda tangan akan dihapus, status kembali Belum. Biodata siswa tetap.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger !px-2.5 !py-1.5 text-xs">Reset</button>
                                        </form>
                                    @endif
                                </div>
                                <p id="share-row-{{ $s->id }}" class="mt-1.5 text-[11px] {{ $row['shared_at'] ? 'text-gray-500 dark:text-gray-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    @if($row['shared_at'])
                                        Terakhir dibagikan: {{ $row['shared_at']->translatedFormat('d M Y H:i') }}
                                        ({{ $row['shared_channel'] === 'wa' ? 'WA' : 'Salin link' }}@if($row['shared_by']) · {{ $row['shared_by'] }}@endif)
                                    @else
                                        Belum pernah dibagikan
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Tidak ada data Generus yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Catat waktu terakhir tautan daftar ulang dibagikan (WA / salin link).
    async function pkgMarkShared(el) {
        const url = el.dataset.markShared;
        const channel = el.dataset.channel || 'link';
        const targetId = el.dataset.shareTarget;
        if (!url) return;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ channel }),
            });
            if (!res.ok) return;
            const data = await res.json();
            const note = targetId ? document.getElementById(targetId) : null;
            if (note && data.shared_at_label) {
                note.textContent = `Terakhir dibagikan: ${data.shared_at_label} (${data.channel})`;
                note.classList.remove('text-amber-600', 'dark:text-amber-400');
                note.classList.add('text-gray-500', 'dark:text-gray-400');
            }
        } catch (e) {
            // diamkan; pencatatan bersifat pelengkap
        }
    }

    document.addEventListener('click', async (event) => {
        const shareLink = event.target.closest('a[data-mark-shared]');
        if (shareLink) {
            pkgMarkShared(shareLink);
        }

        const button = event.target.closest('[data-copy-link]');
        if (!button) return;
        try {
            await navigator.clipboard.writeText(button.dataset.copyLink);
            const original = button.textContent;
            button.textContent = 'Tersalin';
            window.setTimeout(() => { button.textContent = original; }, 1500);
        } catch (e) {
            window.prompt('Salin tautan:', button.dataset.copyLink);
        }
        pkgMarkShared(button);
    });
</script>
@endpush
@endsection
