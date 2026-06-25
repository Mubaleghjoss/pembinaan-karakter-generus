@extends('layouts.app')

@section('title', 'Absen Manual')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8"
     x-data="manualAttendanceManager()">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Absen Manual</h1>
            <p class="pkg-page-subheading">Catat presensi siswa dan pamong saat scan QR tidak bisa dipakai, izin, sakit, atau koreksi data.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('presensi.index', ['tab' => 'rekap']) }}" class="btn-secondary text-sm !px-4 !py-2">Rekap Siswa</a>
            <a href="{{ route('pamong-presensi.index') }}" class="btn-secondary text-sm !px-4 !py-2">Rekap Pamong</a>
        </div>
    </div>

    <div x-show="notice.message"
         x-transition
         class="rounded-xl border px-4 py-3 text-sm"
         :class="notice.type === 'success'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-100'
            : 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-100'">
        <span x-text="notice.message"></span>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="pkg-panel p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Siswa</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pamong hanya dapat memilih siswa binaannya. Admin dapat memilih semua siswa aktif.</p>
            </div>

            <form @submit.prevent="submitSiswa()" class="space-y-4" data-no-csrf-handler>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Cari Siswa</label>
                    <div class="relative">
                        <input type="text"
                               x-model="siswa.search"
                               @input.debounce.300ms="searchStudents()"
                               class="pkg-field w-full"
                               placeholder="Ketik nama atau NIS">

                        <div x-show="siswa.results.length > 0"
                             x-transition
                             class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                            <template x-for="student in siswa.results" :key="student.id">
                                <button type="button"
                                        @click="selectStudent(student)"
                                        class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <template x-if="student.foto_url">
                                        <img :src="student.foto_url" :alt="student.nama" class="h-9 w-9 rounded-full object-cover">
                                    </template>
                                    <template x-if="!student.foto_url">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100" x-text="student.nama.charAt(0).toUpperCase()"></span>
                                    </template>
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900 dark:text-white" x-text="student.nama"></span>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400" x-text="`${student.nis} - ${student.kelas || '-'}`"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div x-show="siswa.selected"
                         class="mt-3 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm dark:border-blue-800 dark:bg-blue-900/20">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-blue-950 dark:text-blue-100" x-text="siswa.selected?.nama"></p>
                                <p class="text-blue-700 dark:text-blue-300" x-text="`${siswa.selected?.nis} - ${siswa.selected?.kelas || '-'}`"></p>
                            </div>
                            <button type="button" @click="clearStudent()" class="btn-secondary text-xs !px-3 !py-1.5">Ganti</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-filter-grid md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal</label>
                        <input type="date" x-model="siswa.form.tanggal" class="pkg-field w-full">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                        <select x-model="siswa.form.status" class="pkg-field w-full">
                            <option value="hadir">Hadir</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Jam Masuk</label>
                        <input type="time" x-model="siswa.form.jam_masuk" class="pkg-field w-full">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Jam Keluar</label>
                        <input type="time" x-model="siswa.form.jam_keluar" class="pkg-field w-full">
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Keterangan</label>
                    <textarea x-model="siswa.form.keterangan" rows="3" maxlength="500" class="pkg-field w-full" placeholder="Opsional"></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            :disabled="siswa.loading || !siswa.selected"
                            class="btn-primary !px-4 !py-2 disabled:cursor-wait disabled:opacity-70">
                        <span x-text="siswa.loading ? 'Menyimpan...' : 'Simpan Presensi Siswa'"></span>
                    </button>
                </div>
            </form>
        </section>

        <section class="pkg-panel p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pamong</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pamong non-admin hanya dapat mengisi presensi akunnya sendiri.</p>
            </div>

            <form @submit.prevent="submitPamong()" class="space-y-4" data-no-csrf-handler>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Pamong</label>
                    <select x-model="pamong.form.user_id" class="pkg-field w-full">
                        @foreach($pamongUsers as $pamong)
                            <option value="{{ $pamong['id'] }}">{{ $pamong['name'] ?: $pamong['username'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pkg-filter-grid md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal</label>
                        <input type="date" x-model="pamong.form.tanggal" class="pkg-field w-full">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                        <select x-model="pamong.form.status" class="pkg-field w-full">
                            <option value="hadir">Hadir</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Keterangan</label>
                    <textarea x-model="pamong.form.keterangan" rows="3" maxlength="500" class="pkg-field w-full" placeholder="Opsional"></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            :disabled="pamong.loading || !pamong.form.user_id"
                            class="btn-primary !px-4 !py-2 disabled:cursor-wait disabled:opacity-70">
                        <span x-text="pamong.loading ? 'Menyimpan...' : 'Simpan Presensi Pamong'"></span>
                    </button>
                </div>
            </form>
        </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="pkg-card overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                <h2 class="font-semibold text-slate-900 dark:text-white">Terakhir Siswa</h2>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($latestSiswaRecords as $record)
                    <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $record->siswa?->nama ?? '-' }}</p>
                            <p class="text-slate-500 dark:text-slate-400">{{ $record->siswa?->kelas?->nama ?? '-' }} - {{ $record->tanggal?->format('d M Y') }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ ucfirst($record->status) }}</span>
                    </div>
                @empty
                    <div class="pkg-empty-state py-8">
                        <p class="pkg-empty-title">Belum ada data</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="pkg-card overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                <h2 class="font-semibold text-slate-900 dark:text-white">Terakhir Pamong</h2>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($latestPamongRecords as $record)
                    <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $record->user?->name ?: $record->user?->username }}</p>
                            <p class="text-slate-500 dark:text-slate-400">{{ $record->tanggal?->format('d M Y') }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ ucfirst($record->status) }}</span>
                    </div>
                @empty
                    <div class="pkg-empty-state py-8">
                        <p class="pkg-empty-title">Belum ada data</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
function manualAttendanceManager() {
    const today = @json($today);

    return {
        notice: { message: '', type: 'success' },
        siswa: {
            search: '',
            results: [],
            selected: null,
            loading: false,
            form: {
                tanggal: today,
                status: 'hadir',
                jam_masuk: '',
                jam_keluar: '',
                keterangan: '',
            },
        },
        pamong: {
            loading: false,
            form: {
                user_id: @json((string) ($pamongUsers->first()['id'] ?? auth()->id())),
                tanggal: today,
                status: 'hadir',
                keterangan: '',
            },
        },

        showNotice(message, type = 'success') {
            this.notice = { message, type };
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type);
            }
        },

        async searchStudents() {
            const keyword = this.siswa.search.trim();
            if (keyword.length < 2) {
                this.siswa.results = [];
                return;
            }

            const params = new URLSearchParams({ search: keyword });
            const response = await fetch(`${@json(route('manual-attendance.students'))}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await response.json();
            this.siswa.results = data.data || [];
        },

        selectStudent(student) {
            this.siswa.selected = student;
            this.siswa.search = student.nama;
            this.siswa.results = [];
        },

        clearStudent() {
            this.siswa.selected = null;
            this.siswa.search = '';
            this.siswa.results = [];
        },

        async submitSiswa() {
            if (!this.siswa.selected || this.siswa.loading) return;

            this.siswa.loading = true;
            try {
                const response = await fetch(@json(route('manual-attendance.siswa.store')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        siswa_id: this.siswa.selected.id,
                        ...this.siswa.form,
                    }),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Presensi siswa gagal disimpan.');
                }

                this.showNotice(data.message || 'Presensi siswa berhasil disimpan.', 'success');
                this.clearStudent();
                this.siswa.form = {
                    tanggal: today,
                    status: 'hadir',
                    jam_masuk: '',
                    jam_keluar: '',
                    keterangan: '',
                };
            } catch (error) {
                this.showNotice(error.message || 'Presensi siswa gagal disimpan.', 'error');
            } finally {
                this.siswa.loading = false;
            }
        },

        async submitPamong() {
            if (!this.pamong.form.user_id || this.pamong.loading) return;

            this.pamong.loading = true;
            try {
                const response = await fetch(@json(route('manual-attendance.pamong.store')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.pamong.form),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Presensi pamong gagal disimpan.');
                }

                this.showNotice(data.message || 'Presensi pamong berhasil disimpan.', 'success');
                this.pamong.form.tanggal = today;
                this.pamong.form.status = 'hadir';
                this.pamong.form.keterangan = '';
            } catch (error) {
                this.showNotice(error.message || 'Presensi pamong gagal disimpan.', 'error');
            } finally {
                this.pamong.loading = false;
            }
        },
    };
}
</script>
@endpush
