@extends('layouts.app')

@section('title', 'Data Presensi - PKG Presensi')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-5 sm:px-6 lg:px-8"
     x-data="presensiManager()"
     x-init="init()">
    
    <!-- Breadcrumb -->
    <x-breadcrumb :items="[
        ['title' => 'Presensi', 'url' => route('presensi.index')]
    ]" />
    
    <!-- Page Header -->
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Presensi Siswa</h1>
            <p class="pkg-page-subheading">
                Kelola rekap, input manual, dan jadwal presensi siswa dalam satu alur.
            </p>
        </div>
        <div class="pkg-page-actions mt-4 sm:mt-0">
            <div class="hidden sm:flex items-center gap-2 rounded-2xl pkg-card-soft px-3 py-2 {{ $isOpen ? 'text-green-700 dark:text-green-300' : '' }}">
                <div class="w-2 h-2 rounded-full {{ $isOpen ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }}"></div>
                <span id="realtime-clock" class="text-sm font-mono {{ $isOpen ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-300' }}">--:--:--</span>
            </div>
        </div>
    </div>

    @if(($autoAlphaCount ?? 0) > 0)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
            {{ $autoAlphaCount }} data tidak hadir otomatis dibuat untuk siswa yang belum mengisi presensi setelah jadwal ditutup.
        </div>
    @endif

    @php
        $presensiTabs = [];

        if ($canCreateManualAttendance ?? false) {
            $presensiTabs[] = ['id' => 'input', 'label' => 'Input Manual', 'icon' => ''];
        }

        $presensiTabs[] = ['id' => 'rekap', 'label' => 'Rekap Presensi', 'icon' => ''];
        $presensiTabs[] = ['id' => 'jadwal', 'label' => 'Jadwal', 'icon' => ''];
        $presensiDefaultTab = request('tab') ?: (($canCreateManualAttendance ?? false) ? 'input' : 'rekap');

        if (! collect($presensiTabs)->pluck('id')->contains($presensiDefaultTab)) {
            $presensiDefaultTab = 'rekap';
        }
    @endphp

    <!-- Tabs Component -->
    <x-tabs 
        :tabs="$presensiTabs"
        :default-tab="$presensiDefaultTab"
    >
        <!-- Tab: Rekap Presensi -->
        <x-tab-panel id="rekap">
            @include('presensi.partials.rekap')
        </x-tab-panel>

        <!-- Tab: Input Manual -->
        <x-tab-panel id="input" :lazy="true">
            @include('presensi.partials.input')
        </x-tab-panel>

        <!-- Tab: Jadwal -->
        <x-tab-panel id="jadwal" :lazy="true">
            @include('presensi.partials.jadwal')
        </x-tab-panel>
    </x-tabs>

    @include('presensi.partials.edit-modal')

    @if($canCreateManualAttendance ?? false)
    <div x-cloak x-show="quickModal.open" class="fixed inset-0 z-[80] flex items-end justify-center bg-black/50 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:items-center sm:p-6" @keydown.escape.window="closeQuickModal()">
        <section x-show="quickModal.open" x-transition.opacity.duration.150ms role="dialog" aria-modal="true" aria-labelledby="quick-status-title" class="pkg-modal w-full max-w-md p-5" @click.outside="closeQuickModal()">
            <div class="flex min-w-0 items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 id="quick-status-title" class="text-balance text-lg font-bold text-gray-900 dark:text-white">Input Presensi</h2>
                    <p class="mt-1 truncate text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="quickModal.student?.nama"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><span x-text="quickModal.student?.nis"></span> &middot; <span x-text="quickModal.student?.kelas"></span></p>
                </div>
                <button type="button" @click="closeQuickModal()" class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Tutup modal input presensi">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <fieldset class="mt-5">
                <legend class="text-sm font-semibold text-gray-900 dark:text-white">Pilih status</legend>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <template x-for="option in [
                        { value: 'hadir', label: 'Hadir' },
                        { value: 'sakit', label: 'Sakit' },
                        { value: 'izin', label: 'Izin' },
                        { value: 'alpha', label: 'Alpa', copy: 'Tanpa Keterangan' }
                    ]" :key="option.value">
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700" :class="quickModal.status === option.value ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-500 dark:bg-emerald-950/30' : ''">
                            <input data-quick-status-option type="radio" class="pkg-check" x-model="quickModal.status" :value="option.value">
                            <span class="min-w-0"><strong class="block text-sm" x-text="option.label"></strong><span x-show="option.copy" class="block text-xs text-gray-500 dark:text-gray-400" x-text="option.copy"></span></span>
                        </label>
                    </template>
                </div>
            </fieldset>

            <p x-show="quickModal.error" x-text="quickModal.error" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert"></p>
            <div class="mt-5 grid grid-cols-2 gap-2">
                <button type="button" class="btn-secondary min-h-11 justify-center" @click="closeQuickModal()">Batal</button>
                <button type="button" class="btn-primary min-h-11 justify-center disabled:opacity-60" :disabled="quickModal.saving" @click="saveQuickStatus()" x-text="quickModal.saving ? 'Menyimpan...' : 'Simpan'"></button>
            </div>
        </section>
    </div>
    @endif
</div>

<!-- QR Scanner Modal -->
@include('presensi.partials.qr-scanner-modal')

@endsection

@push('scripts')
<script>
// Realtime clock
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: false 
    });
    const clockEl = document.getElementById('realtime-clock');
    if (clockEl) {
        clockEl.textContent = timeStr;
    }
}
setInterval(updateClock, 1000);
updateClock();

function presensiManager() {
    const initialDate = @json(request('date', now()->toDateString()));
    const endpoints = {
        presensiData: @json(route('presensi.data')),
        presensiStats: @json(route('presensi.stats')),
        presensiExport: @json(route('presensi.export')),
        manualStudentSearch: @json(route('presensi.students')),
        presensiStore: @json(route('presensi.store')),
        presensiBulkStore: @json(route('presensi.bulk')),
        presensiBulkVerify: @json(route('presensi.bulk-verify')),
        presensiVerify: @json(route('presensi.verify', ['presensi' => '__ID__'])),
        presensiUpdate: @json(route('presensi.update', ['presensi' => '__ID__'])),
        quickStatus: @json(route('presensi.quick-status')),
        shareSummary: @json(route('presensi.share-summary')),
        periodPanel: @json(route('presensi.panel.period')),
        generusPanel: @json(route('presensi.panel.generus')),
    };

    return {
        // Common state
        schoolGrades: @json($schoolGradeOptions),
        kelompokOptions: @json($kelompokOptions),
        pamongOptions: @json($pamongOptions->map(fn ($pamong) => ['id' => $pamong->id, 'name' => $pamong->name ?: $pamong->username])->values()),
        attendanceCategories: [
            { key: 'hadir', label: 'Hadir', count: 'hadir_count', panelClass: 'border-green-200 bg-green-50/80 dark:border-green-900/50 dark:bg-green-950/20', textClass: 'text-green-800 dark:text-green-300' },
            { key: 'sakit', label: 'Sakit', count: 'sakit_count', panelClass: 'border-amber-200 bg-amber-50/80 dark:border-amber-900/50 dark:bg-amber-950/20', textClass: 'text-amber-800 dark:text-amber-300' },
            { key: 'izin', label: 'Izin', count: 'izin_count', panelClass: 'border-blue-200 bg-blue-50/80 dark:border-blue-900/50 dark:bg-blue-950/20', textClass: 'text-blue-800 dark:text-blue-300' },
            { key: 'alpha', label: 'Alpa (Tanpa Keterangan)', count: 'alpha_count', panelClass: 'border-red-200 bg-red-50/80 dark:border-red-900/50 dark:bg-red-950/20', textClass: 'text-red-800 dark:text-red-300' },
            { key: 'belum_hadir', label: 'Belum Presensi', count: 'belum_hadir_count', panelClass: 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/50', textClass: 'text-slate-700 dark:text-slate-200' }
        ],
        loading: false,
        bulkVerifying: false,
        editModal: {
            open: false,
            saving: false,
            id: null,
            siswa_nama: '',
            tanggal: '',
            status: '',
            jam_masuk: '',
            jam_keluar: '',
            keterangan: ''
        },
        
        // Rekap tab state
        presensi: [],
        groupSummary: [],
        stats: { total: 0, hadir: 0, terlambat: 0, tidak_hadir: 0, verified: 0 },
        allDates: @json(request()->boolean('all_dates')),
        filters: {
            tanggal: initialDate,
            school_grade: @json(request('school_grade', '')),
            pamong_id: @json(request('pamong_id', '')),
            kelompok: @json(request('kelompok', '')),
            status: @json(request('status', '')),
            verified: @json(request('verified', ''))
        },
        quickModal: {
            open: false,
            saving: false,
            student: null,
            status: '',
            error: ''
        },
        reportPanels: {
            period: { loading: false, loaded: false, html: '', error: '' },
            generus: { loading: false, loaded: false, html: '', error: '' }
        },
        
        // Manual input state
        manualInput: {
            searchSiswa: '',
            searchResults: [],
            selectedSiswa: null,
            tanggal: initialDate,
            status: '',
            jam_masuk: '',
            keterangan: ''
        },
        
        // Bulk input state
        bulkInput: {
            school_grade: '',
            pamong_id: '',
            tanggal: initialDate,
            status: 'hadir'
        },
        
        async init() {
            await this.loadPresensi();
            const requestedPanel = @json(request('panel'));
            if (requestedPanel === 'laporan-periode') await this.loadReportPanel('period');
            if (requestedPanel === 'rekap-generus') await this.loadReportPanel('generus');
        },

        async loadReportPanel(key) {
            const panel = this.reportPanels[key];
            if (!panel || panel.loading || panel.loaded) return;
            panel.loading = true;
            panel.error = '';
            try {
                const params = new URLSearchParams(window.location.search);
                params.delete('tab');
                params.delete('panel');
                const endpoint = key === 'period' ? endpoints.periodPanel : endpoints.generusPanel;
                const response = await fetch(`${endpoint}?${params.toString()}`, { headers: { 'Accept': 'text/html' } });
                if (!response.ok) throw new Error('Panel laporan belum dapat dimuat.');
                panel.html = await response.text();
                panel.loaded = true;
                this.$nextTick(() => window.Alpine?.initTree?.(document.querySelector(`[data-report-panel="${key}"]`)));
            } catch (error) {
                panel.error = error.message || 'Panel laporan belum dapat dimuat.';
            } finally {
                panel.loading = false;
            }
        },
        
        async loadPresensi() {
            this.loading = true;
            
            try {
                const params = new URLSearchParams({
                    tanggal: this.filters.tanggal,
                    school_grade: this.filters.school_grade,
                    pamong_id: this.filters.pamong_id,
                    kelompok: this.filters.kelompok,
                    status: this.filters.status,
                    verified: this.filters.verified,
                    all_dates: this.allDates ? '1' : '0'
                });
                
                const response = await fetch(`${endpoints.presensiData}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.presensi = data.data || [];
                this.groupSummary = data.group_summary || [];
            } catch (error) {
                console.error('Error loading presensi:', error);
            } finally {
                this.loading = false;
            }
        },

        currentFilterParams() {
            const params = new URLSearchParams();
            params.set('tanggal', this.filters.tanggal);

            if (this.filters.school_grade) params.set('school_grade', this.filters.school_grade);
            if (this.filters.pamong_id) params.set('pamong_id', this.filters.pamong_id);
            if (this.filters.kelompok) params.set('kelompok', this.filters.kelompok);
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.verified !== '') params.set('verified', this.filters.verified);
            if (this.allDates) params.set('all_dates', '1');

            return params;
        },

        exportUrl() {
            return `${endpoints.presensiExport}?${this.currentFilterParams().toString()}`;
        },

        statusLabel(status) {
            const labels = {
                hadir: 'Hadir',
                terlambat: 'Terlambat',
                izin: 'Izin',
                sakit: 'Sakit',
                alpha: 'Alpa (Tanpa Keterangan)',
                tidak_hadir: 'Alpa (Tanpa Keterangan)'
            };

            return labels[status] || '-';
        },

        statusBadgeClass(status) {
            return {
                'pkg-status-success': status === 'hadir',
                'pkg-status-warning': status === 'terlambat' || status === 'sakit',
                'pkg-status-danger': status === 'alpha' || status === 'tidak_hadir',
                'pkg-status-info': status === 'izin'
            };
        },
        
        async loadStats() {
            try {
                const params = new URLSearchParams({
                    tanggal: this.filters.tanggal,
                    school_grade: this.filters.school_grade,
                    pamong_id: this.filters.pamong_id,
                    kelompok: this.filters.kelompok
                });
                
                const response = await fetch(`${endpoints.presensiStats}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.stats = data.data;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },
        
        async verifyAttendance(item) {
            const confirmed = await window.showConfirmation('Verifikasi presensi ini?', {
                title: 'Verifikasi presensi',
                confirmText: 'Verifikasi',
                tone: 'primary'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(endpoints.presensiVerify.replace('__ID__', item.id), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                
                if (response.ok) {
                    item.is_verified = true;
                    window.showNotification('Presensi berhasil diverifikasi', 'success');
                }
            } catch (error) {
                console.error('Error verifying:', error);
            }
        },

        async bulkVerifyAttendance() {
            const confirmed = await window.showConfirmation('Verifikasi semua data presensi sesuai filter saat ini?', {
                title: 'Verifikasi semua',
                confirmText: 'Verifikasi semua',
                tone: 'primary'
            });
            if (!confirmed) return;

            this.bulkVerifying = true;

            try {
                const response = await fetch(endpoints.presensiBulkVerify, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        tanggal: this.filters.tanggal,
                        school_grade: this.filters.school_grade || null,
                        pamong_id: this.filters.pamong_id || null,
                        kelompok: this.filters.kelompok || null,
                        status: this.filters.status || null,
                        verified: this.filters.verified === '' ? null : this.filters.verified,
                        all_dates: this.allDates
                    })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    window.showNotification(data.message || 'Data presensi berhasil diverifikasi', 'success');
                    await this.loadPresensi();
                } else {
                    window.showNotification(data.message || 'Gagal verifikasi data presensi', 'error');
                }
            } catch (error) {
                console.error('Error bulk verifying:', error);
                window.showNotification('Terjadi kesalahan saat verifikasi semua data', 'error');
            } finally {
                this.bulkVerifying = false;
            }
        },
        
        editPresensi(item) {
            this.editModal = {
                open: true,
                saving: false,
                id: item.id,
                siswa_nama: item.siswa?.nama || 'Siswa',
                tanggal: item.tanggal || '',
                status: item.status || '',
                jam_masuk: item.jam_masuk ? item.jam_masuk.slice(0, 5) : '',
                jam_keluar: item.jam_keluar ? item.jam_keluar.slice(0, 5) : '',
                keterangan: item.keterangan || ''
            };

            this.$nextTick(() => document.querySelector('[data-edit-presensi-status]')?.focus());
        },

        closeEditPresensi() {
            if (this.editModal.saving) return;
            this.editModal.open = false;
        },

        async updatePresensi() {
            if (!this.editModal.id || !this.editModal.status) {
                window.showNotification('Status kehadiran wajib dipilih', 'warning');
                return;
            }

            this.editModal.saving = true;

            try {
                const response = await fetch(endpoints.presensiUpdate.replace('__ID__', this.editModal.id), {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        tanggal: this.editModal.tanggal,
                        status: this.editModal.status,
                        jam_masuk: this.editModal.jam_masuk || null,
                        jam_keluar: this.editModal.jam_keluar || null,
                        keterangan: this.editModal.keterangan || null
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const validationMessage = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : null;
                    window.showNotification(validationMessage || data.message || 'Gagal memperbarui presensi', 'error');
                    return;
                }

                this.editModal.open = false;
                await this.loadPresensi();
                window.showNotification('Presensi berhasil diperbarui', 'success');
            } catch (error) {
                console.error('Error updating presensi:', error);
                window.showNotification('Terjadi kesalahan saat memperbarui presensi', 'error');
            } finally {
                this.editModal.saving = false;
            }
        },
        
        // Manual Input Functions
        async searchSiswaForManual() {
            if (this.manualInput.searchSiswa.length < 2) {
                this.manualInput.searchResults = [];
                return;
            }
            
            try {
                const params = new URLSearchParams({
                    search: this.manualInput.searchSiswa,
                    per_page: 50
                });
                
                const response = await fetch(`${endpoints.manualStudentSearch}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.manualInput.searchResults = data.data || [];
            } catch (error) {
                console.error('Error searching siswa:', error);
            }
        },
        
        selectSiswaForManual(siswa) {
            const normalized = this.normalizeSiswaForManual(siswa);
            this.manualInput.selectedSiswa = normalized;
            this.manualInput.searchSiswa = normalized.nama;
            this.manualInput.searchResults = [];
        },

        normalizeSiswaForManual(siswa) {
            return {
                id: siswa.id,
                nis: siswa.nis,
                nama: siswa.nama,
                school_grade: siswa.school_grade || null,
                school_grade_label: siswa.school_grade_label || 'Belum dikonfirmasi',
                foto_url: siswa.foto_url || null
            };
        },

        selectSiswaFromRekap(student) {
            this.quickModal = {
                open: true,
                saving: false,
                student,
                status: ['hadir', 'sakit', 'izin', 'alpha'].includes(student.status) ? student.status : '',
                error: ''
            };
            this.$nextTick(() => document.querySelector('[data-quick-status-option]')?.focus());
        },

        closeQuickModal() {
            if (this.quickModal.saving) return;
            this.quickModal.open = false;
            this.quickModal.error = '';
        },

        async saveQuickStatus() {
            if (!this.quickModal.student || !this.quickModal.status) {
                this.quickModal.error = 'Pilih status presensi terlebih dahulu.';
                return;
            }

            this.quickModal.saving = true;
            this.quickModal.error = '';
            try {
                const response = await fetch(endpoints.quickStatus, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        siswa_id: this.quickModal.student.id,
                        tanggal: this.filters.tanggal,
                        status: this.quickModal.status
                    })
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Presensi belum dapat disimpan.');
                this.quickModal.open = false;
                await this.loadPresensi();
                window.showNotification(data.message || 'Presensi berhasil diperbarui', 'success');
            } catch (error) {
                this.quickModal.error = error.message || 'Presensi belum dapat disimpan.';
            } finally {
                this.quickModal.saving = false;
            }
        },

        shareFilterParams(groupKey = '') {
            const params = new URLSearchParams({ tanggal: this.filters.tanggal });
            if (this.filters.school_grade) params.set('school_grade', this.filters.school_grade);
            if (this.filters.pamong_id) params.set('pamong_id', this.filters.pamong_id);
            if (this.filters.kelompok) params.set('kelompok', this.filters.kelompok);
            if (groupKey) params.set('group', groupKey);
            return params;
        },

        async attendanceShareText(groupKey = '') {
            const response = await fetch(`${endpoints.shareSummary}?${this.shareFilterParams(groupKey)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Ringkasan belum dapat dibuat.');
            return data.data.text;
        },

        async copyAttendanceSummary(groupKey = '') {
            try {
                const text = await this.attendanceShareText(groupKey);
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.setAttribute('readonly', '');
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    textarea.remove();
                }
                window.showNotification('Teks rekap berhasil disalin', 'success');
            } catch (error) {
                window.showNotification(error.message || 'Teks belum dapat disalin', 'error');
            }
        },

        async shareAttendanceWhatsApp(groupKey = '') {
            const target = window.open('about:blank', '_blank');
            try {
                const text = await this.attendanceShareText(groupKey);
                const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
                if (target) target.location.href = url;
                else window.location.href = url;
            } catch (error) {
                target?.close();
                window.showNotification(error.message || 'WhatsApp belum dapat dibuka', 'error');
            }
        },
        
        resetManualInput() {
            this.manualInput = {
                searchSiswa: '',
                searchResults: [],
                selectedSiswa: null,
                tanggal: new Date().toISOString().split('T')[0],
                status: '',
                jam_masuk: '',
                keterangan: ''
            };
        },
        
        async submitManualPresensi() {
            if (!this.manualInput.selectedSiswa || !this.manualInput.status) {
                window.showNotification('Pilih siswa dan status kehadiran', 'warning');
                return;
            }
            
            try {
                const response = await fetch(endpoints.presensiStore, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        siswa_id: this.manualInput.selectedSiswa.id,
                        tanggal: this.manualInput.tanggal,
                        status: this.manualInput.status,
                        jam_masuk: this.manualInput.jam_masuk || null,
                        keterangan: this.manualInput.keterangan || null,
                        is_manual: true
                    })
                });
                
                const data = await response.json();
                if (response.ok) {
                    window.showNotification('Presensi berhasil disimpan', 'success');
                    this.resetManualInput();
                    this.loadPresensi();
                } else {
                    window.showNotification(data.message || 'Gagal menyimpan presensi', 'error');
                }
            } catch (error) {
                console.error('Error submitting presensi:', error);
                window.showNotification('Terjadi kesalahan saat menyimpan presensi', 'error');
            }
        },
        
        async submitBulkPresensi() {
            if (!this.bulkInput.school_grade && !this.bulkInput.pamong_id) {
                window.showNotification('Pilih Pamong atau kelas sekolah terlebih dahulu', 'warning');
                return;
            }
            
            const confirmed = await window.showConfirmation('Input presensi untuk seluruh Generus sesuai filter ini?', {
                title: 'Presensi massal',
                confirmText: 'Simpan semua',
                tone: 'primary'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(endpoints.presensiBulkStore, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        school_grade: this.bulkInput.school_grade || null,
                        pamong_id: this.bulkInput.pamong_id || null,
                        tanggal: this.bulkInput.tanggal,
                        status: this.bulkInput.status
                    })
                });
                
                const data = await response.json();
                if (response.ok) {
                    window.showNotification(data.message || 'Presensi massal berhasil disimpan', 'success');
                    this.loadPresensi();
                } else {
                    window.showNotification(data.message || 'Gagal menyimpan presensi', 'error');
                }
            } catch (error) {
                console.error('Error submitting bulk presensi:', error);
                window.showNotification('Terjadi kesalahan saat menyimpan presensi', 'error');
            }
        },
        
        openQrScanner() {
            if (typeof window.openQrScanner === 'function') {
                window.openQrScanner();
            }
        }
    };
}
</script>
@endpush
