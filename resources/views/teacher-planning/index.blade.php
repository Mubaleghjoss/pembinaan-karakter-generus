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
            <a href="{{ route('teacher-materials.index') }}" class="btn-secondary">Pustaka Materi</a>
            <a href="{{ route('public.teacher-availability.index') }}" target="_blank" rel="noopener" class="btn-secondary">Buka Formulir</a>
        </div>
    </header>

    @if(session('success'))
        <div data-page-feedback="success" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div data-page-feedback="error" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('teacher_access_code'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/40">
            <p class="text-sm font-bold text-emerald-800 dark:text-emerald-200">Kode akses baru—salin sekarang karena tidak dapat ditampilkan kembali:</p>
            <code class="mt-2 inline-block rounded-lg bg-white px-4 py-2 text-lg font-black tracking-[0.18em] text-emerald-800 dark:bg-gray-900 dark:text-emerald-200">{{ session('teacher_access_code') }}</code>
        </div>
    @endif
    @if(session('teacher_credentials'))
        @php
            $credentials = session('teacher_credentials');
        @endphp
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
            <p class="font-black">Kredensial awal {{ $credentials['name'] }}</p>
            <p class="mt-1 text-sm">Password hanya ditampilkan sekali. Guru wajib menggantinya saat login pertama.</p>
            <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                <div class="rounded-lg bg-white/80 p-3 dark:bg-slate-950/50"><dt class="text-xs font-bold uppercase tracking-wide">Username</dt><dd class="mt-1 select-all font-mono text-lg font-black">{{ $credentials['username'] }}</dd></div>
                <div class="rounded-lg bg-white/80 p-3 dark:bg-slate-950/50"><dt class="text-xs font-bold uppercase tracking-wide">Password</dt><dd class="mt-1 select-all font-mono text-lg font-black">{{ $credentials['password'] }}</dd></div>
            </dl>
        </div>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan pengisi formulir guru">
        @foreach([
            ['Total Pengisi', $stats['total'], null, null],
            ['Siap Dijadwalkan', $stats['eligible'], 'status', 'eligible'],
            ['Senin', $stats['monday'], 'night', 'monday'],
            ['Selasa', $stats['tuesday'], 'night', 'tuesday'],
            ['Jumat', $stats['friday'], 'night', 'friday'],
        ] as [$label, $value, $filterType, $filterValue])
            @php
                $isActiveStat = $filterType
                    ? (($activeProfileFilter['type'] ?? null) === $filterType && ($activeProfileFilter['value'] ?? null) === $filterValue)
                    : ! $activeProfileFilter;
                $statUrl = route('teacher-planning.index', array_filter([
                    'month' => $selectedMonth->format('Y-m'),
                    'profile_filter' => $filterType,
                    'profile_value' => $filterValue,
                ]));
            @endphp
            <a href="{{ $statUrl }}#data-kesediaan"
               class="pkg-card group p-4 transition hover:-translate-y-0.5 hover:border-emerald-400 hover:shadow-md {{ $isActiveStat ? 'ring-2 ring-emerald-500/60' : '' }}">
                <p class="text-sm text-gray-500 group-hover:text-emerald-700 dark:text-gray-400 dark:group-hover:text-emerald-300">{{ $label }}</p>
                <div class="mt-2 flex items-end justify-between gap-3">
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $value }}</p>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Lihat nama</span>
                </div>
            </a>
        @endforeach
    </section>

    <section class="pkg-panel p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-black text-gray-900 dark:text-white">Analisis Pengisi Formulir</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Klik kategori untuk melihat siapa saja yang termasuk di dalamnya.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-amber-100 px-3 py-1.5 font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Konfirmasi H-3: {{ $confirmationDue }}</span>
                <span class="rounded-full bg-emerald-100 px-3 py-1.5 font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Pengingat H-1: {{ $reminderDue }}</span>
            </div>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([
                ['title' => 'Berdasarkan Kelompok', 'type' => 'group', 'items' => $groupStats],
                ['title' => 'Jenis Kesediaan', 'type' => 'role', 'items' => $roleStats],
                ['title' => 'Rombel Pilihan', 'type' => 'rombel', 'items' => $rombelStats],
                ['title' => 'Malam Tersedia', 'type' => 'night', 'items' => $nightStats],
                ['title' => 'Kemampuan Materi', 'type' => 'competency', 'items' => $competencyStats],
            ] as $analysis)
                <article class="pkg-card-soft p-4">
                    <h3 class="text-sm font-black text-gray-900 dark:text-white">{{ $analysis['title'] }}</h3>
                    <div class="mt-3 space-y-1.5">
                        @foreach($analysis['items'] as $item)
                            @php
                                $isActiveItem = ($activeProfileFilter['type'] ?? null) === $analysis['type']
                                    && ($activeProfileFilter['value'] ?? null) === $item['value'];
                            @endphp
                            <a href="{{ route('teacher-planning.index', [
                                    'month' => $selectedMonth->format('Y-m'),
                                    'profile_filter' => $analysis['type'],
                                    'profile_value' => $item['value'],
                                ]) }}#data-kesediaan"
                               class="flex min-h-10 items-center justify-between gap-3 rounded-xl border px-3 py-2 text-sm transition
                                      {{ $isActiveItem
                                          ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'
                                          : 'border-transparent text-gray-600 hover:border-emerald-300 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <span class="min-w-0 leading-5">{{ $item['label'] }}</span>
                                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 font-black text-gray-900 dark:bg-gray-700 dark:text-white">{{ $item['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </article>
            @endforeach
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

        @if(auth()->user()->isAdmin())
        <details class="pkg-panel p-5" @if(blank($adminWhatsapp)) open @endif>
            <summary class="cursor-pointer text-lg font-bold text-gray-900 dark:text-white">Kontak Admin untuk Guru</summary>
            <p class="mt-3 text-sm leading-6 text-gray-500 dark:text-gray-400">Nomor ini dipakai tombol WhatsApp dan pengajuan perubahan jadwal di Portal Guru.</p>
            <form method="POST" action="{{ route('teacher-planning.admin-contact.update') }}" class="mt-5 space-y-4">
                @csrf @method('PUT')
                <div>
                    <label for="admin_whatsapp" class="form-label">Nomor WhatsApp Admin</label>
                    <input id="admin_whatsapp" name="admin_whatsapp" value="{{ old('admin_whatsapp', $adminWhatsapp) }}" class="pkg-field w-full" inputmode="tel" placeholder="Contoh: 081234567890" maxlength="24" required>
                </div>
                <button class="btn-primary w-full justify-center">Simpan Kontak Admin</button>
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
            <div x-data="{ generateConfirmOpen: false, generating: false }">
                <form
                    x-ref="generateScheduleForm"
                    method="POST"
                    action="{{ route('teacher-planning.generate') }}"
                    data-generate-schedule-form
                >
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                    <button
                        type="button"
                        class="btn-success"
                        data-generate-schedule-trigger
                        @click="generateConfirmOpen = true"
                        @disabled(! $hasActiveTemplates)
                    >
                        Buat Jadwal Bulanan
                    </button>

                    <div
                        x-cloak
                        x-show="generateConfirmOpen"
                        x-transition.opacity
                        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/55 p-4"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="generate-schedule-title"
                        data-generate-schedule-confirm
                        @keydown.escape.window="if (!generating) generateConfirmOpen = false"
                        @click.self="if (!generating) generateConfirmOpen = false"
                    >
                        <div class="pkg-modal w-full max-w-md p-6 shadow-2xl">
                            <div class="flex items-start gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.29 3.86l-7.19 12.47A2 2 0 004.81 19h14.38a2 2 0 001.71-2.67L13.71 3.86a2 2 0 00-3.42 0z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 id="generate-schedule-title" class="text-lg font-semibold text-slate-900 dark:text-white">Konfirmasi tindakan</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                        Buat atau hitung ulang draft otomatis untuk {{ $selectedMonth->translatedFormat('F Y') }}? Penugasan manual tetap dipertahankan.
                                    </p>
                                </div>
                            </div>
                            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    class="btn-secondary !justify-center !px-4 !py-2 text-sm"
                                    @click="generateConfirmOpen = false"
                                    :disabled="generating"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70 dark:focus:ring-offset-slate-900"
                                    data-generate-schedule-submit
                                    :disabled="generating"
                                    @click="
                                        if (generating) return;
                                        generating = true;
                                        generateConfirmOpen = false;
                                        $nextTick(() => $refs.generateScheduleForm.submit());
                                    "
                                >
                                    <span x-show="!generating">Lanjutkan</span>
                                    <span x-show="generating">Memproses...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
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
                @if(auth()->user()->isAdmin())
                    <div x-data="{ deleteScheduleConfirmOpen: false, deletingSchedule: false }">
                        <form
                            x-ref="deleteScheduleForm"
                            method="POST"
                            action="{{ route('teacher-planning.periods.destroy', $period) }}"
                            data-delete-schedule-form
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="button"
                                class="btn-danger"
                                data-delete-schedule-trigger
                                @click="deleteScheduleConfirmOpen = true"
                            >
                                Hapus Jadwal
                            </button>

                            <div
                                x-cloak
                                x-show="deleteScheduleConfirmOpen"
                                x-transition.opacity
                                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/55 p-4"
                                role="dialog"
                                aria-modal="true"
                                aria-labelledby="delete-schedule-title"
                                data-delete-schedule-confirm
                                @keydown.escape.window="if (!deletingSchedule) deleteScheduleConfirmOpen = false"
                                @click.self="if (!deletingSchedule) deleteScheduleConfirmOpen = false"
                            >
                                <div class="pkg-modal w-full max-w-md p-6 text-left shadow-2xl">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-300">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.29 3.86l-7.19 12.47A2 2 0 004.81 19h14.38a2 2 0 001.71-2.67L13.71 3.86a2 2 0 00-3.42 0z"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h3 id="delete-schedule-title" class="text-lg font-semibold text-slate-900 dark:text-white">Hapus jadwal bulanan</h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                                Hapus jadwal {{ $period->month->translatedFormat('F Y') }} beserta seluruh sesi dan penugasannya? Data guru dan Template Slot Mingguan tidak akan dihapus.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                        <button
                                            type="button"
                                            class="btn-secondary !justify-center !px-4 !py-2 text-sm"
                                            @click="deleteScheduleConfirmOpen = false"
                                            :disabled="deletingSchedule"
                                        >
                                            Batal
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70 dark:focus:ring-offset-slate-900"
                                            data-delete-schedule-submit
                                            :disabled="deletingSchedule"
                                            @click="
                                                if (deletingSchedule) return;
                                                deletingSchedule = true;
                                                deleteScheduleConfirmOpen = false;
                                                $nextTick(() => $refs.deleteScheduleForm.submit());
                                            "
                                        >
                                            <span x-show="!deletingSchedule">Hapus Jadwal</span>
                                            <span x-show="deletingSchedule">Menghapus...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
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
                <article id="sesi-{{ $session->id }}" class="scroll-mt-24 p-4 sm:p-5">
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-black text-gray-900 dark:text-white">{{ $session->session_date->translatedFormat('l, d F Y') }} · {{ strtoupper($session->rombel) }}</p>
                            <p class="text-sm text-gray-500">{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }} WIB{{ $session->location ? ' · '.$session->location : '' }}</p>
                        </div>
                        @if($main && $backup)
                            <form method="POST" action="{{ route('teacher-planning.sessions.swap', $session) }}" data-stay-submit data-confirm="Tukar pengajar utama dan cadangan pada sesi ini?">@csrf @method('PATCH')<button class="btn-secondary !px-3 !py-2 text-sm">Tukar Utama/Cadangan</button></form>
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
                                <form method="POST" action="{{ route('teacher-planning.sessions.assign', [$session, $role]) }}" class="space-y-3" data-stay-submit>
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
                                            @php($sentAt = $assignment->getAttribute("{$stage}_whatsapp_sent_at"))
                                            <form method="POST" action="{{ route('teacher-planning.assignments.sent', [$assignment, $stage]) }}" data-stay-submit>
                                                @csrf @method('PATCH')
                                                <button class="btn-secondary w-full justify-center !px-2 !py-2 text-xs">
                                                    {{ $sentAt ? strtoupper($stage).' terkirim '.$sentAt->format('d/m H:i') : $label }}
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                    <form method="POST" action="{{ route('teacher-planning.assignments.status', $assignment) }}" class="mt-3 space-y-2 rounded-xl border border-gray-200 p-3 dark:border-gray-700" data-stay-submit>
                                        @csrf @method('PATCH')
                                        <label class="form-label">Status konfirmasi</label>
                                        <select name="confirmation_status" class="pkg-field w-full">
                                            <option value="pending" @selected($assignment->confirmation_status === 'pending')>Menunggu</option>
                                            <option value="confirmed" @selected($assignment->confirmation_status === 'confirmed')>Bersedia</option>
                                            <option value="declined" @selected($assignment->confirmation_status === 'declined')>Berhalangan</option>
                                        </select>
                                        <input name="confirmation_note" value="{{ $assignment->confirmation_note }}" class="pkg-field w-full" maxlength="500" placeholder="Catatan status (opsional)">
                                        <button class="btn-secondary w-full justify-center !py-2 text-xs">Simpan Status</button>
                                    </form>
                                    @foreach($assignment->requests->take(3) as $scheduleRequest)
                                        <form method="POST" action="{{ route('teacher-planning.requests.status', $scheduleRequest) }}" class="mt-3 space-y-2 rounded-xl border border-sky-200 bg-sky-50 p-3 dark:border-sky-900/60 dark:bg-sky-950/30" data-stay-submit>
                                            @csrf @method('PATCH')
                                            <div class="flex items-start justify-between gap-2">
                                                <div>
                                                    <p class="text-xs font-black uppercase tracking-wide text-sky-700 dark:text-sky-300">
                                                        {{ $scheduleRequest->request_type === 'reschedule' ? 'Pengajuan jadwal ulang' : 'Tidak bisa mengajar' }}
                                                    </p>
                                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $scheduleRequest->reason }}</p>
                                                </div>
                                                <span class="rounded-full bg-white px-2 py-1 text-[10px] font-bold text-sky-700 dark:bg-slate-900 dark:text-sky-300">#{{ $scheduleRequest->id }}</span>
                                            </div>
                                            <select name="status" class="pkg-field w-full">
                                                <option value="pending" @selected($scheduleRequest->status === 'pending')>Menunggu</option>
                                                <option value="approved" @selected($scheduleRequest->status === 'approved')>Disetujui</option>
                                                <option value="rejected" @selected($scheduleRequest->status === 'rejected')>Ditolak</option>
                                            </select>
                                            <input name="admin_note" value="{{ $scheduleRequest->admin_note }}" class="pkg-field w-full" maxlength="500" placeholder="Catatan Admin (opsional)">
                                            <button class="btn-secondary w-full justify-center !py-2 text-xs">Simpan Permohonan</button>
                                        </form>
                                    @endforeach
                                    @if($assignment->confirmation_note)<p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Catatan: {{ $assignment->confirmation_note }}</p>@endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('teacher-planning.sessions.materials.sync', $session) }}" class="mt-4 rounded-2xl border border-gray-200 p-4 dark:border-gray-700" data-stay-submit>
                        @csrf @method('PUT')
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">Materi sesi</p>
                                <p class="text-sm text-gray-500">Materi ini selalu tampil kepada pengajar utama dan cadangan pada sesi tersebut.</p>
                            </div>
                            <a href="{{ route('teacher-materials.index') }}" class="text-sm font-bold text-emerald-600">Kelola pustaka</a>
                        </div>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @forelse($teacherMaterials as $material)
                                <label class="pkg-check"><input type="checkbox" name="material_ids[]" value="{{ $material->id }}" @checked($session->materials->contains($material->id))><span>{{ $material->title }}</span></label>
                            @empty
                                <p class="text-sm text-gray-500">Belum ada materi aktif.</p>
                            @endforelse
                        </div>
                        <button class="btn-secondary mt-4 w-full justify-center !py-2 text-sm">Simpan Materi Sesi</button>
                    </form>
                </article>
            @endforeach
        </div>

        <div class="border-t border-gray-200 p-5 dark:border-gray-700">
            <form method="POST" action="{{ route('teacher-planning.periods.publish', $period) }}" class="space-y-3" data-stay-submit>
                @csrf @method('PATCH')
                @if($warnings)<textarea name="warning_acknowledgement" class="pkg-field w-full" rows="2" maxlength="1000" placeholder="Tuliskan catatan persetujuan untuk menerbitkan jadwal yang masih memiliki peringatan" required></textarea>@endif
                <button class="btn-success">{{ $period->status === 'published' ? 'Terbitkan Ulang Perubahan' : 'Terbitkan ke Kalender' }}</button>
            </form>
        </div>
    </section>
    @else
        <section class="pkg-empty-state"><div class="pkg-empty-icon"></div><h2 class="pkg-empty-title">Belum ada jadwal bulan ini</h2><p class="pkg-empty-copy">Atur template lalu pilih “Buat Jadwal Bulanan”.</p></section>
    @endif

    <section id="data-kesediaan" class="pkg-panel scroll-mt-24 overflow-hidden">
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-white">Data Kesediaan Guru</h2>
                    @if($activeProfileFilter)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Menampilkan {{ $profiles->total() }} nama untuk kategori
                            <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ $activeProfileFilter['label'] }}</span>.
                        </p>
                    @else
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Semua pengisi formulir. Klik analitik di atas untuk menyaring daftar nama.</p>
                    @endif
                </div>
                @if($activeProfileFilter)
                    <a href="{{ route('teacher-planning.index', ['month' => $selectedMonth->format('Y-m')]) }}#data-kesediaan" class="btn-secondary">Tampilkan Semua</a>
                @endif
            </div>
        </div>
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
                        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900/60 dark:bg-sky-950/30 sm:col-span-2">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">Akun Portal Guru</p>
                                    @if($profile->user)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Tertaut ke <span class="font-bold">{{ $profile->user->username }}</span>. Role akun tidak diubah oleh penyuntingan profil.</p>
                                    @else
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Belum memiliki akun. Admin dapat membuat akun Guru dengan password awal sekali tampil.</p>
                                    @endif
                                </div>
                                @if(auth()->user()->isAdmin())
                                    @if(!$profile->user_id)
                                        <button type="submit" form="create-teacher-account-{{ $profile->id }}" class="btn-primary whitespace-nowrap">Buat Akun Guru</button>
                                    @elseif($profile->user?->isGuru())
                                        <button type="submit" form="reset-teacher-account-{{ $profile->id }}" class="btn-secondary whitespace-nowrap">Reset Password Awal</button>
                                    @endif
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
                        @if(!$profile->user_id)
                            <form id="create-teacher-account-{{ $profile->id }}" method="POST" action="{{ route('teacher-planning.profiles.account.store', $profile) }}" class="hidden" data-confirm="Buat akun Portal Guru untuk {{ $profile->name }}?">@csrf</form>
                        @elseif($profile->user?->isGuru())
                            <form id="reset-teacher-account-{{ $profile->id }}" method="POST" action="{{ route('teacher-planning.profiles.account.reset', $profile) }}" class="hidden" data-confirm="Buat ulang password awal akun {{ $profile->user->username }}?">@csrf</form>
                        @endif
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
                <div class="pkg-empty-state">
                    <h3 class="pkg-empty-title">{{ $activeProfileFilter ? 'Tidak ada nama pada kategori ini' : 'Belum ada pengisi' }}</h3>
                    <p class="pkg-empty-copy">
                        {{ $activeProfileFilter ? 'Pilih kategori analitik lain atau tampilkan kembali semua pengisi.' : 'Bagikan tautan `/pendataanguru` beserta kode akses.' }}
                    </p>
                    @if($activeProfileFilter)
                        <a href="{{ route('teacher-planning.index', ['month' => $selectedMonth->format('Y-m')]) }}#data-kesediaan" class="btn-secondary mt-4">Tampilkan Semua</a>
                    @endif
                </div>
            @endforelse
        </div>
        @if($profiles->hasPages())<div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $profiles->links() }}</div>@endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const storageKey = 'teacher-planning:stay';

        document.querySelectorAll('form[data-stay-submit]').forEach((form) => {
            form.addEventListener('submit', () => {
                sessionStorage.setItem(storageKey, JSON.stringify({
                    y: window.scrollY,
                    at: Date.now()
                }));
            });
        });

        const savedRaw = sessionStorage.getItem(storageKey);
        if (!savedRaw) return;

        sessionStorage.removeItem(storageKey);
        let saved;
        try {
            saved = JSON.parse(savedRaw);
        } catch (error) {
            return;
        }
        if (!saved || Date.now() - Number(saved.at || 0) > 120000) return;

        const restore = () => window.scrollTo({ top: Number(saved.y || 0), behavior: 'auto' });
        restore();
        window.requestAnimationFrame(() => {
            restore();
            window.setTimeout(restore, 120);
        });

        const feedback = document.querySelector('[data-page-feedback]');
        if (!feedback) return;
        const tone = feedback.dataset.pageFeedback === 'error' ? 'error' : 'success';
        const toast = document.createElement('div');
        toast.setAttribute('role', 'status');
        toast.className = 'fixed right-4 top-20 z-[120] max-w-sm rounded-2xl border px-4 py-3 text-sm font-bold shadow-2xl transition duration-300 '
            + (tone === 'error'
                ? 'border-red-200 bg-red-600 text-white'
                : 'border-emerald-200 bg-emerald-600 text-white');
        toast.textContent = feedback.innerText.trim();
        document.body.appendChild(toast);
        feedback.remove();
        window.setTimeout(() => {
            toast.classList.add('translate-x-4', 'opacity-0');
            window.setTimeout(() => toast.remove(), 320);
        }, 3200);
    })();
</script>
@endpush
