@extends('layouts.app')

@section('title', 'Pendataan & Jadwal Guru')

@section('content')
<div class="space-y-6">
    <header class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Pendataan & Jadwal Guru</h1>
            <p class="pkg-page-subheading">Kelola kesediaan MT/MS, pemerataan tugas, konfirmasi, dan jadwal bulanan.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('public.teacher-availability.index') }}" target="_blank" rel="noopener" class="btn-secondary">Buka Formulir</a>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('teacher_access_code'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/40">
            <p class="text-sm font-bold text-emerald-800 dark:text-emerald-200">Kode akses baru—salin sekarang karena tidak dapat ditampilkan kembali:</p>
            <code class="mt-2 inline-block rounded-lg bg-white px-4 py-2 text-lg font-black tracking-[0.18em] text-emerald-800 dark:bg-gray-900 dark:text-emerald-200">{{ session('teacher_access_code') }}</code>
        </div>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            ['Total Pengisi', $stats['total']],
            ['Siap Dijadwalkan', $stats['eligible']],
            ['Senin', $stats['monday']],
            ['Selasa', $stats['tuesday']],
            ['Jumat', $stats['friday']],
        ] as [$label, $value])
            <div class="pkg-card p-4"><p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p><p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ $value }}</p></div>
        @endforeach
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <div class="pkg-card p-5">
            <h2 class="font-bold text-gray-900 dark:text-white">Kesediaan Peran</h2>
            <dl class="mt-4 space-y-2">@foreach($roleStats as $item)<div class="flex justify-between gap-3 text-sm"><dt class="text-gray-500">{{ $item['label'] }}</dt><dd class="font-bold text-gray-900 dark:text-white">{{ $item['count'] }}</dd></div>@endforeach</dl>
        </div>
        <div class="pkg-card p-5">
            <h2 class="font-bold text-gray-900 dark:text-white">Kesediaan Rombel</h2>
            <dl class="mt-4 space-y-2">@foreach($rombelStats as $item)<div class="flex justify-between gap-3 text-sm"><dt class="text-gray-500">{{ $item['label'] }}</dt><dd class="font-bold text-gray-900 dark:text-white">{{ $item['count'] }}</dd></div>@endforeach</dl>
        </div>
        <div class="pkg-card p-5">
            <h2 class="font-bold text-gray-900 dark:text-white">Tindakan Hari Ini</h2>
            <dl class="mt-4 space-y-2">
                <div class="flex justify-between gap-3 text-sm"><dt class="text-gray-500">Konfirmasi H-3 menunggu</dt><dd class="font-bold text-amber-600">{{ $confirmationDue }}</dd></div>
                <div class="flex justify-between gap-3 text-sm"><dt class="text-gray-500">Pengingat H-1</dt><dd class="font-bold text-emerald-600">{{ $reminderDue }}</dd></div>
            </dl>
            <p class="mt-4 text-xs leading-5 text-gray-500">Buka jadwal tanggal terkait lalu gunakan tombol WhatsApp. Sistem hanya mencatat tombol dibuka sampai admin menandai pesan terkirim.</p>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        @if(auth()->user()->isAdmin())
        <details class="pkg-panel p-5" @if(! $invite) open @endif>
            <summary class="cursor-pointer text-lg font-bold text-gray-900 dark:text-white">Kode Akses Formulir</summary>
            <form method="POST" action="{{ route('teacher-planning.invite.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                <div class="sm:col-span-2"><label class="form-label">Label</label><input name="label" value="{{ old('label', $invite?->label ?? 'Pendataan Guru MT/MS') }}" class="pkg-field w-full" required></div>
                <div class="sm:col-span-2"><label class="form-label">Kode baru {{ $invite ? '(opsional)' : '' }}</label><input name="access_code" class="pkg-field w-full uppercase" minlength="6" maxlength="32" @required(! $invite)></div>
                <div><label class="form-label">Berlaku (hari)</label><input name="valid_days" type="number" min="1" max="3650" value="180" class="pkg-field w-full" required></div>
                <div><label class="form-label">Kuota pengisi</label><input name="max_uses" type="number" min="{{ max(1, $invite?->used_count ?? 0) }}" value="{{ old('max_uses', $invite?->max_uses ?? 100) }}" class="pkg-field w-full" required></div>
                <label class="pkg-check sm:col-span-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $invite?->is_active ?? true))><span>Formulir aktif</span></label>
                @if($invite)<p class="text-sm text-gray-500 sm:col-span-2">Digunakan {{ $invite->used_count }}/{{ $invite->max_uses }}. Berlaku hingga {{ $invite->expires_at?->translatedFormat('d M Y H:i') ?? '-' }}.</p>@endif
                <button class="btn-primary justify-center sm:col-span-2">Simpan Akses</button>
            </form>
        </details>
        @endif

        @if(auth()->user()->isAdmin())
        <details class="pkg-panel p-5">
            <summary class="cursor-pointer text-lg font-bold text-gray-900 dark:text-white">Pesan Setelah Formulir Terkirim</summary>
            <p class="mt-3 text-sm leading-6 text-gray-500 dark:text-gray-400">Teks ini ditampilkan pada halaman selesai setelah calon guru mengirim formulir.</p>
            <form method="POST" action="{{ route('teacher-planning.success-message.update') }}" class="mt-5 space-y-4">
                @csrf @method('PUT')
                <div>
                    <label for="success_title" class="form-label">Judul</label>
                    <input id="success_title" name="success_title" value="{{ old('success_title', $successMessageSettings['title']) }}" class="pkg-field w-full" maxlength="120" required>
                </div>
                <div>
                    <label for="success_message" class="form-label">Isi pesan</label>
                    <textarea id="success_message" name="success_message" rows="4" class="pkg-field w-full" maxlength="500" required>{{ old('success_message', $successMessageSettings['message']) }}</textarea>
                </div>
                <button class="btn-primary w-full justify-center">Simpan Pesan</button>
            </form>
        </details>
        @endif

        <details class="pkg-panel p-5" @if($templates->isEmpty()) open @endif>
            <summary class="cursor-pointer text-lg font-bold text-gray-900 dark:text-white">Template Slot Mingguan</summary>
            <p class="mt-3 text-sm leading-6 text-gray-500 dark:text-gray-400">Generator jadwal hanya memakai template aktif. Tambahkan kombinasi malam dan rombel yang memang akan belajar, misalnya Senin malam untuk SMP.</p>
            <form method="POST" action="{{ route('teacher-planning.templates.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf
                <div><label class="form-label">Malam</label><select name="weekday" class="pkg-field w-full" required>@foreach($nights as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                <div><label class="form-label">Rombel</label><select name="rombel" class="pkg-field w-full" required>@foreach($rombels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                <div><label class="form-label">Mulai</label><input name="start_time" type="time" value="20:00" class="pkg-field w-full" required></div>
                <div><label class="form-label">Selesai</label><input name="end_time" type="time" value="21:30" class="pkg-field w-full" required></div>
                <div class="sm:col-span-2"><label class="form-label">Lokasi (opsional)</label><input name="location" class="pkg-field w-full" maxlength="120"></div>
                <button class="btn-primary justify-center sm:col-span-2">Simpan Template</button>
            </form>
            <div class="mt-5 space-y-2">
                @forelse($templates as $template)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <div><p class="font-semibold text-gray-900 dark:text-white">{{ $nights[$template->weekday] }} · {{ $rombels[$template->rombel] }}</p><p class="text-sm text-gray-500">{{ substr($template->start_time, 0, 5) }}–{{ substr($template->end_time, 0, 5) }} · {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</p></div>
                        <form method="POST" action="{{ route('teacher-planning.templates.toggle', $template) }}">@csrf @method('PATCH')<button class="btn-secondary !px-3 !py-2 text-sm">{{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                    </div>
                @empty
                    <p class="pkg-empty-copy">Belum ada template slot.</p>
                @endforelse
            </div>
        </details>
    </div>

    <section class="pkg-panel p-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <form method="GET" action="{{ route('teacher-planning.index') }}" class="flex flex-wrap items-end gap-3">
                <div><label class="form-label">Bulan jadwal</label><input name="month" type="month" value="{{ $selectedMonth->format('Y-m') }}" class="pkg-field"></div>
                <button class="btn-secondary">Tampilkan</button>
            </form>
            <form method="POST" action="{{ route('teacher-planning.generate') }}"
                  data-confirm="Buat atau hitung ulang draft otomatis untuk bulan ini? Penugasan manual tetap dipertahankan."
                  data-confirm-title="Konfirmasi tindakan"
                  data-confirm-button="Lanjutkan"
                  data-confirm-tone="primary">
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                <button class="btn-success" @disabled(! $hasActiveTemplates)>Buat Jadwal Bulanan</button>
            </form>
        </div>
        @if(! $hasActiveTemplates)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                Jadwal belum bisa dibuat karena belum ada Template Slot Mingguan aktif. Buka bagian <strong>Template Slot Mingguan</strong>, tambahkan minimal satu malam dan rombel, lalu coba kembali.
            </div>
        @endif
    </section>

    @if($period)
    <section class="pkg-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
            <div>
                <h2 class="text-xl font-black text-gray-900 dark:text-white">Jadwal {{ $period->month->translatedFormat('F Y') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Status: {{ $period->status === 'published' ? 'Diterbitkan' : 'Draft' }} · {{ $period->sessions->count() }} sesi</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('teacher-planning.export.excel', $period) }}" class="btn-secondary">Excel</a>
                <a href="{{ route('teacher-planning.export.pdf', $period) }}" class="btn-secondary">PDF</a>
                <a href="{{ route('teacher-planning.export.image', $period) }}" target="_blank" class="btn-secondary">Gambar</a>
            </div>
        </div>

        @if($warnings)
            <div class="border-b border-amber-200 bg-amber-50 p-5 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                <p class="font-bold">{{ count($warnings) }} peringatan jadwal</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">@foreach($warnings as $warning)<li>{{ $warning }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($period->sessions->sortBy(fn($session) => $session->session_date->format('Y-m-d').' '.$session->start_time.' '.$session->rombel) as $session)
                @php
                    $main = $session->assignments->firstWhere('role', 'main');
                    $backup = $session->assignments->firstWhere('role', 'backup');
                @endphp
                <article class="p-4 sm:p-5">
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-black text-gray-900 dark:text-white">{{ $session->session_date->translatedFormat('l, d F Y') }} · {{ strtoupper($session->rombel) }}</p>
                            <p class="text-sm text-gray-500">{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }} WIB{{ $session->location ? ' · '.$session->location : '' }}</p>
                        </div>
                        @if($main && $backup)
                            <form method="POST" action="{{ route('teacher-planning.sessions.swap', $session) }}" data-confirm="Tukar pengajar utama dan cadangan pada sesi ini?">@csrf @method('PATCH')<button class="btn-secondary !px-3 !py-2 text-sm">Tukar Utama/Cadangan</button></form>
                        @endif
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach(['main' => ['Utama', $main], 'backup' => ['Cadangan', $backup]] as $role => [$roleLabel, $assignment])
                            <div class="pkg-card-soft p-4">
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <p class="font-bold text-gray-900 dark:text-white">Pengajar {{ $roleLabel }}</p>
                                    @if($assignment)
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $assignment->confirmation_status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : ($assignment->confirmation_status === 'declined' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                            {{ match($assignment->confirmation_status) { 'confirmed' => 'Bersedia', 'declined' => 'Berhalangan', default => 'Menunggu' } }}
                                        </span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('teacher-planning.sessions.assign', [$session, $role]) }}" class="space-y-3">
                                    @csrf @method('PUT')
                                    <select name="teacher_profile_id" class="pkg-field w-full">
                                        <option value="">Belum diisi</option>
                                        @foreach($eligibleProfiles as $teacher)
                                            <option value="{{ $teacher->id }}" @selected($assignment?->teacher_profile_id === $teacher->id)>{{ $teacher->name }} · {{ $teacher->monthly_limit ?: '4+' }} maks.</option>
                                        @endforeach
                                    </select>
                                    <input name="overload_reason" class="pkg-field w-full" maxlength="500" placeholder="Alasan override jika melebihi batas">
                                    <button class="btn-primary w-full justify-center !py-2 text-sm">Simpan {{ $roleLabel }}</button>
                                </form>
                                @if($assignment)
                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        <form method="POST" action="{{ route('teacher-planning.assignments.whatsapp', [$assignment, 'h3']) }}" target="_blank">@csrf<button class="btn-success w-full justify-center !px-2 !py-2 text-xs">WA H-3</button></form>
                                        <form method="POST" action="{{ route('teacher-planning.assignments.whatsapp', [$assignment, 'h1']) }}" target="_blank">@csrf<button class="btn-success w-full justify-center !px-2 !py-2 text-xs">WA H-1</button></form>
                                        @foreach(['h3' => 'Tandai H-3 terkirim', 'h1' => 'Tandai H-1 terkirim'] as $stage => $label)
                                            <form method="POST" action="{{ route('teacher-planning.assignments.sent', [$assignment, $stage]) }}">@csrf @method('PATCH')<button class="btn-secondary w-full justify-center !px-2 !py-2 text-xs">{{ $label }}</button></form>
                                        @endforeach
                                    </div>
                                    @if($assignment->confirmation_note)<p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Catatan: {{ $assignment->confirmation_note }}</p>@endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>

        <div class="border-t border-gray-200 p-5 dark:border-gray-700">
            <form method="POST" action="{{ route('teacher-planning.periods.publish', $period) }}" class="space-y-3">
                @csrf @method('PATCH')
                @if($warnings)<textarea name="warning_acknowledgement" class="pkg-field w-full" rows="2" maxlength="1000" placeholder="Tuliskan catatan persetujuan untuk menerbitkan jadwal yang masih memiliki peringatan" required></textarea>@endif
                <button class="btn-success">{{ $period->status === 'published' ? 'Terbitkan Ulang Perubahan' : 'Terbitkan ke Kalender' }}</button>
            </form>
        </div>
    </section>
    @else
        <section class="pkg-empty-state"><div class="pkg-empty-icon"></div><h2 class="pkg-empty-title">Belum ada jadwal bulan ini</h2><p class="pkg-empty-copy">Atur template lalu pilih “Buat Jadwal Bulanan”.</p></section>
    @endif

    <section class="pkg-panel overflow-hidden">
        <div class="border-b border-gray-200 p-5 dark:border-gray-700"><h2 class="text-xl font-black text-gray-900 dark:text-white">Data Kesediaan Guru</h2><p class="mt-1 text-sm text-gray-500">Nama diisi manual. Periksa ejaan sebelum menautkan akun.</p></div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($profiles as $profile)
                <details class="p-4 sm:p-5">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $profile->name }}</p>
                            <p class="text-sm text-gray-500">{{ $profile->kelompokLabel() }} · {{ $profile->whatsapp }} · {{ $profile->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ match($profile->participation_role) { 'main_backup' => 'Utama & cadangan', 'main' => 'Utama', 'backup' => 'Cadangan', 'as_needed' => 'Sesuai kebutuhan', default => 'Belum memungkinkan' } }}
                                · Rombel {{ collect($profile->rombels ?? [])->map(fn($item) => strtoupper($item))->join(', ') ?: '-' }}
                                @if($period) · Bulan ini {{ $profile->current_assignments_count ?? 0 }} tugas ({{ $profile->current_main_count ?? 0 }} utama, {{ $profile->current_backup_count ?? 0 }} cadangan) @endif
                            </p>
                        </div>
                        <span class="text-sm font-semibold text-emerald-600">Edit data</span>
                    </summary>
                    <form method="POST" action="{{ route('teacher-planning.profiles.update', $profile) }}" class="mt-5 grid gap-4 border-t border-gray-200 pt-5 dark:border-gray-700 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <div class="flex flex-col gap-3 rounded-2xl border p-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between {{ $profile->signature_path ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/60 dark:bg-emerald-950/30' : 'border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/30' }}">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">Surat Pernyataan Kesediaan</p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    Dikirim {{ $profile->submitted_at->translatedFormat('d F Y H:i') }} WIB.
                                    {{ $profile->signature_path ? 'Sudah ditandatangani.' : 'Data lama ini belum memiliki tanda tangan.' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('teacher-planning.profiles.statement.preview', $profile) }}" target="_blank" rel="noopener" class="btn-secondary">Lihat PDF</a>
                                @if(auth()->user()->isAdmin() || auth()->user()->hasPamongCrudPermission('teacher_scheduling', 'export'))
                                    <a href="{{ route('teacher-planning.profiles.statement.download', $profile) }}" class="btn-success">Unduh PDF</a>
                                @endif
                            </div>
                        </div>
                        <div><label class="form-label">Nama lengkap</label><input name="name" value="{{ $profile->name }}" class="pkg-field w-full" required></div>
                        <div><label class="form-label">Nama publik/panggilan</label><input name="public_name" value="{{ $profile->public_name }}" class="pkg-field w-full"></div>
                        <div><label class="form-label">Kelompok</label><select name="kelompok" class="pkg-field w-full">@foreach($groups as $value => $label)<option value="{{ $value }}" @selected($profile->kelompok === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div><label class="form-label">WhatsApp</label><input name="whatsapp" value="{{ $profile->whatsapp }}" class="pkg-field w-full" required></div>
                        <div><label class="form-label">Tautkan akun</label><select name="user_id" class="pkg-field w-full"><option value="">Tanpa akun</option>@foreach($linkableUsers as $user)<option value="{{ $user->id }}" @selected($profile->user_id === $user->id)>{{ $user->name ?: $user->username }}</option>@endforeach</select></div>
                        <div><label class="form-label">Jenis kesediaan</label><select name="participation_role" class="pkg-field w-full">@foreach(['main_backup'=>'Utama & cadangan','main'=>'Utama','backup'=>'Cadangan','as_needed'=>'Sesuai kebutuhan','unavailable'=>'Belum memungkinkan'] as $value=>$label)<option value="{{ $value }}" @selected($profile->participation_role === $value)>{{ $label }}</option>@endforeach</select></div>
                        <fieldset><legend class="form-label">Rombel</legend><div class="space-y-2">@foreach($rombels as $value=>$label)<label class="pkg-check"><input type="checkbox" name="rombels[]" value="{{ $value }}" @checked(in_array($value, $profile->rombels ?? [], true))><span>{{ $label }}</span></label>@endforeach</div></fieldset>
                        <fieldset><legend class="form-label">Malam</legend><div class="space-y-2">@foreach($nights as $value=>$label)<label class="pkg-check"><input type="checkbox" name="available_nights[]" value="{{ $value }}" @checked(in_array($value, $profile->available_nights ?? [], true))><span>{{ $label }}</span></label>@endforeach</div></fieldset>
                        <fieldset class="sm:col-span-2">
                            <legend class="form-label">Kemampuan/materi</legend>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach(['quran'=>"Makna Al-Qur'an",'hadith'=>'Makna Al-Hadits','memorization'=>'Hafalan','practice'=>'Praktik','class_support'=>'Pendampingan kelas','all_materials'=>'Bersedia seluruh materi'] as $value=>$label)
                                    <label class="pkg-check"><input type="checkbox" name="competencies[]" value="{{ $value }}" @checked(in_array($value, $profile->competencies ?? [], true))><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div><label class="form-label">Batas bulanan (kosong = 4+)</label><input name="monthly_limit" type="number" min="1" max="3" value="{{ $profile->monthly_limit }}" class="pkg-field w-full"></div>
                        <div><label class="form-label">Kesiapan bahan ajar</label><select name="material_readiness" class="pkg-field w-full"><option value="">-</option><option value="ready" @selected($profile->material_readiness === 'ready')>Bersedia</option><option value="needs_support" @selected($profile->material_readiness === 'needs_support')>Perlu pendampingan</option></select></div>
                        <div><label class="form-label">Dihubungi sebagai cadangan</label><select name="backup_contact_preference" class="pkg-field w-full"><option value="">-</option><option value="ready" @selected($profile->backup_contact_preference === 'ready')>Bersedia</option><option value="one_day_notice" @selected($profile->backup_contact_preference === 'one_day_notice')>Minimal satu hari</option><option value="unavailable" @selected($profile->backup_contact_preference === 'unavailable')>Belum memungkinkan</option></select></div>
                        <div><label class="form-label">Kendala</label><textarea name="constraints" class="pkg-field w-full" rows="3">{{ $profile->constraints }}</textarea></div>
                        <label class="pkg-check sm:col-span-2"><input type="checkbox" name="is_active" value="1" @checked($profile->is_active)><span>Profil aktif</span></label>
                        <button class="btn-primary justify-center sm:col-span-2">Simpan Profil</button>
                    </form>
                    @if(auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('teacher-planning.profiles.destroy', $profile) }}" class="mt-4 border-t border-red-200 pt-4 dark:border-red-900/50"
                              data-confirm="Hapus data kesediaan {{ $profile->name }}? Tindakan ini tidak dapat dibatalkan."
                              data-confirm-title="Hapus data guru"
                              data-confirm-button="Hapus"
                              data-confirm-tone="danger">
                            @csrf @method('DELETE')
                            <button class="btn-danger w-full justify-center">Hapus Data Kesediaan Guru</button>
                        </form>
                    @endif
                </details>
            @empty
                <div class="pkg-empty-state"><h3 class="pkg-empty-title">Belum ada pengisi</h3><p class="pkg-empty-copy">Bagikan tautan `/pendataanguru` beserta kode akses.</p></div>
            @endforelse
        </div>
        @if($profiles->hasPages())<div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $profiles->links() }}</div>@endif
    </section>
</div>
@endsection
