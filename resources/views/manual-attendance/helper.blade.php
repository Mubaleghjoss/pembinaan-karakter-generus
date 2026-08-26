@extends('layouts.app')

@section('title', 'Bantu Isi Presensi')

@section('content')
@php
    // Data untuk Alpine: dipakai filter, isi cepat, dan salin teks WA.
    $alpineGroups = collect($groups)->map(fn ($g) => [
        'pamong_id' => $g['pamong_id'],
        'pamong_nama' => $g['pamong_nama'],
        'is_mine' => $g['is_mine'],
        'sudah' => $g['sudah'],
        'belum' => $g['belum'],
        'students' => $g['students'],
    ])->values();
@endphp
<div class="mx-auto max-w-5xl px-4 py-5 sm:px-6 sm:py-6"
     x-data="presensiHelper(@js($alpineGroups), @js($today))">

    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bantu Isi Presensi</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Daftar generus per pamong pembimbing beserta status presensi hari ini. Semua pamong boleh membantu mengisi.
        </p>
    </div>

    {{-- Status jadwal --}}
    @if($schedule && $scheduleOpen)
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/30">
            <p class="font-bold text-emerald-900 dark:text-emerald-100">Presensi sedang dibuka</p>
            <p class="mt-0.5 text-sm text-emerald-800 dark:text-emerald-100">
                {{ $schedule->nama ?? 'Jadwal presensi' }} ·
                {{ $schedule->open_time ? \Illuminate\Support\Carbon::parse($schedule->open_time)->format('H:i') : '—' }}
                s.d. {{ $schedule->close_time ? \Illuminate\Support\Carbon::parse($schedule->close_time)->format('H:i') : '—' }}
            </p>
        </div>
    @elseif($schedule)
        <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/30">
            <p class="font-bold text-amber-900 dark:text-amber-100">Jadwal presensi belum/sudah tidak terbuka</p>
            <p class="mt-0.5 text-sm text-amber-800 dark:text-amber-100">Pengisian manual tetap bisa dilakukan untuk tanggal hari ini.</p>
        </div>
    @else
        <div class="mb-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
            <p class="font-bold text-gray-800 dark:text-gray-100">Belum ada jadwal presensi aktif</p>
            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">Daftar di bawah tetap menampilkan status presensi hari ini.</p>
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="mb-4 grid grid-cols-3 gap-3">
        <div class="pkg-panel p-3 text-center">
            <p class="text-xl font-black text-gray-900 dark:text-white">{{ $totalSudah + $totalBelum }}</p>
            <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Total Generus</p>
        </div>
        <div class="pkg-panel p-3 text-center">
            <p class="text-xl font-black text-emerald-600 dark:text-emerald-300">{{ $totalSudah }}</p>
            <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Sudah Absen</p>
        </div>
        <div class="pkg-panel p-3 text-center">
            <p class="text-xl font-black text-rose-600 dark:text-rose-300">{{ $totalBelum }}</p>
            <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Belum Absen</p>
        </div>
    </div>

    {{-- Filter + salin WA --}}
    <div class="pkg-panel mb-4 p-4">
        <div class="grid gap-2 sm:grid-cols-3">
            <input type="search" x-model="search" placeholder="Cari nama / NIS…"
                   class="form-input w-full text-sm" aria-label="Cari generus">
            <select x-model="kelompok" class="form-select w-full text-sm" aria-label="Filter kelompok">
                <option value="">Semua kelompok</option>
                @foreach($kelompokOptions as $key => $label)
                    <option value="{{ $label }}">{{ $label }}</option>
                @endforeach
            </select>
            <select x-model="statusFilter" class="form-select w-full text-sm" aria-label="Filter status">
                <option value="all">Semua status</option>
                <option value="belum">Hanya belum absen</option>
                <option value="sudah">Hanya sudah absen</option>
            </select>
        </div>

        <label class="mt-3 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" x-model="onlyMine" class="form-checkbox">
            Hanya binaan saya
        </label>

        <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
            <button type="button" @click="copyText('belum')" class="btn-secondary text-sm">
                Salin Daftar BELUM Absen
            </button>
            <button type="button" @click="copyText('sudah')" class="btn-secondary text-sm">
                Salin Daftar SUDAH Absen
            </button>
            <button type="button" @click="copyText('all')" class="btn-secondary text-sm">
                Salin Semua
            </button>
            <span x-show="copied" x-transition class="self-center text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                Teks disalin, siap ditempel ke WhatsApp.
            </span>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Teks salinan berisi nama, kelompok, dan pamong — mengikuti filter yang sedang aktif.
        </p>
    </div>

    {{-- Daftar per pamong --}}
    <template x-for="group in visibleGroups()" :key="group.pamong_nama">
        <div class="pkg-panel mb-3 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                        <span x-text="group.pamong_nama"></span>
                        <template x-if="group.is_mine">
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200">Saya</span>
                        </template>
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="group.sudah"></span> dari <span x-text="group.sudah + group.belum"></span> binaan sudah absen
                    </p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                      :class="group.belum === 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200'"
                      x-text="group.belum === 0 ? 'Lengkap' : group.belum + ' belum'"></span>
            </div>

            <div class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="s in filteredStudents(group)" :key="s.id">
                    <div class="py-2.5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="flex items-center gap-1.5 font-semibold text-gray-900 dark:text-white">
                                    <span class="truncate" x-text="s.nama"></span>
                                    <template x-if="s.is_mine">
                                        <span class="shrink-0 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">binaan saya</span>
                                    </template>
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    NIS <span x-text="s.nis"></span>
                                    <template x-if="s.kelompok"><span> · <span x-text="s.kelompok"></span></span></template>
                                    <template x-if="s.kelas"><span> · <span x-text="s.kelas"></span></span></template>
                                </p>
                            </div>

                            <div class="text-right">
                                <template x-if="s.sudah">
                                    <div>
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200"
                                              x-text="statusLabel(s.status)"></span>
                                        <p class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400" x-text="s.jam_masuk ? 'Jam ' + s.jam_masuk : ''"></p>
                                    </div>
                                </template>
                                <template x-if="!s.sudah">
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700 dark:bg-rose-900/50 dark:text-rose-200">Belum absen</span>
                                </template>
                            </div>
                        </div>

                        {{-- Tombol isi cepat --}}
                        <template x-if="!s.sudah">
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <template x-for="opt in statusOptions" :key="opt.value">
                                    <button type="button" @click="submit(s, opt.value)"
                                            :disabled="saving === s.id"
                                            class="rounded-lg px-2.5 py-1.5 text-[11px] font-bold text-white transition disabled:opacity-50"
                                            :class="opt.classes"
                                            x-text="saving === s.id ? '…' : opt.label"></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="filteredStudents(group).length === 0">
                    <p class="py-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada generus yang cocok dengan filter.</p>
                </template>
            </div>
        </div>
    </template>

    <template x-if="visibleGroups().length === 0">
        <div class="pkg-panel p-8 text-center text-gray-500 dark:text-gray-400">
            Tidak ada data yang cocok dengan filter.
        </div>
    </template>
</div>

@push('scripts')
<script>
function presensiHelper(groups, today) {
    return {
        groups: groups,
        today: today,
        search: '',
        kelompok: '',
        statusFilter: 'all',
        onlyMine: false,
        saving: null,
        copied: false,

        statusOptions: [
            { value: 'hadir', label: 'Hadir', classes: 'bg-emerald-600 hover:bg-emerald-700' },
            { value: 'terlambat', label: 'Terlambat', classes: 'bg-amber-600 hover:bg-amber-700' },
            { value: 'izin', label: 'Izin', classes: 'bg-sky-600 hover:bg-sky-700' },
            { value: 'sakit', label: 'Sakit', classes: 'bg-rose-600 hover:bg-rose-700' },
        ],

        statusLabel(status) {
            return ({
                hadir: 'Hadir', terlambat: 'Terlambat', izin: 'Izin',
                sakit: 'Sakit', alpha: 'Tidak Hadir',
            })[status] || 'Tercatat';
        },

        filteredStudents(group) {
            const q = this.search.trim().toLowerCase();
            return group.students.filter((s) => {
                if (this.onlyMine && !s.is_mine) return false;
                if (this.kelompok && s.kelompok !== this.kelompok) return false;
                if (this.statusFilter === 'belum' && s.sudah) return false;
                if (this.statusFilter === 'sudah' && !s.sudah) return false;
                if (q && !(String(s.nama).toLowerCase().includes(q) || String(s.nis).toLowerCase().includes(q))) return false;
                return true;
            });
        },

        visibleGroups() {
            return this.groups.filter((g) => this.filteredStudents(g).length > 0);
        },

        async submit(student, status) {
            this.saving = student.id;
            try {
                const res = await fetch('{{ route('manual-attendance.siswa.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        siswa_id: student.id,
                        tanggal: this.today,
                        status: status,
                    }),
                });
                const data = await res.json();

                if (data.success) {
                    student.sudah = true;
                    student.status = status;
                    student.jam_masuk = data.data?.jam_masuk || null;
                    // Perbarui hitungan grup terkait.
                    this.groups.forEach((g) => {
                        if (g.students.some((s) => s.id === student.id)) {
                            g.sudah += 1;
                            g.belum = Math.max(0, g.belum - 1);
                        }
                    });
                    window.showNotification?.(data.message || 'Presensi tersimpan.', 'success');
                } else {
                    window.showNotification?.(data.message || 'Gagal menyimpan presensi.', 'error');
                }
            } catch (e) {
                window.showNotification?.('Gagal menyimpan presensi.', 'error');
            } finally {
                this.saving = null;
            }
        },

        /**
         * Bangun teks terstruktur untuk dikirim ke WhatsApp:
         * nama, kelompok, dan pamong, dikelompokkan per pamong.
         */
        buildText(mode) {
            const tgl = new Date(this.today + 'T00:00:00').toLocaleDateString('id-ID', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            });

            const judul = mode === 'belum' ? 'BELUM ABSEN'
                : (mode === 'sudah' ? 'SUDAH ABSEN' : 'REKAP PRESENSI');

            let lines = [`*${judul} PKG*`, tgl, ''];
            let total = 0;

            this.groups.forEach((g) => {
                const list = this.filteredStudents(g).filter((s) => {
                    if (mode === 'belum') return !s.sudah;
                    if (mode === 'sudah') return s.sudah;
                    return true;
                });
                if (list.length === 0) return;

                lines.push(`*Pamong: ${g.pamong_nama}*`);
                list.forEach((s, i) => {
                    const kel = s.kelompok ? ` (${s.kelompok})` : '';
                    const ket = mode === 'all'
                        ? (s.sudah ? ` — ${this.statusLabel(s.status)}${s.jam_masuk ? ' ' + s.jam_masuk : ''}` : ' — Belum absen')
                        : (mode === 'sudah' && s.jam_masuk ? ` — ${this.statusLabel(s.status)} ${s.jam_masuk}` : '');
                    lines.push(`${i + 1}. ${s.nama}${kel}${ket}`);
                    total++;
                });
                lines.push('');
            });

            lines.push(`Total: ${total} generus`);
            return lines.join('\n');
        },

        async copyText(mode) {
            const text = this.buildText(mode);
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
                // Fallback untuk browser/HTTP tanpa Clipboard API.
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2500);
        },
    };
}
</script>
@endpush
@endsection
