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
    };

    return {
        // Common state
        schoolGrades: @json($schoolGradeOptions),
        pamongOptions: @json($pamongOptions->map(fn ($pamong) => ['id' => $pamong->id, 'name' => $pamong->name ?: $pamong->username])->values()),
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
        filters: {
            tanggal: initialDate,
            school_grade: '',
            pamong_id: '',
            status: '',
            verified: ''
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
            await this.loadStats();
        },
        
        async loadPresensi() {
            this.loading = true;
            
            try {
                const params = new URLSearchParams({
                    tanggal: this.filters.tanggal,
                    school_grade: this.filters.school_grade,
                    pamong_id: this.filters.pamong_id,
                    status: this.filters.status,
                    verified: this.filters.verified
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
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.verified !== '') params.set('verified', this.filters.verified);

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
                alpha: 'Tidak Hadir',
                tidak_hadir: 'Tidak Hadir'
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
                    pamong_id: this.filters.pamong_id
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
                    this.loadStats();
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
                        status: this.filters.status || null,
                        verified: this.filters.verified === '' ? null : this.filters.verified
                    })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    window.showNotification(data.message || 'Data presensi berhasil diverifikasi', 'success');
                    await this.loadPresensi();
                    await this.loadStats();
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
                await this.loadStats();
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
            this.selectSiswaForManual(student);
            this.manualInput.tanggal = this.filters.tanggal || this.manualInput.tanggal;
            this.manualInput.status = '';
            this.manualInput.jam_masuk = '';
            this.manualInput.keterangan = '';

            if (typeof this.setActiveTab === 'function') {
                this.setActiveTab('input');
            } else {
                window.location.hash = 'input';
            }

            window.dispatchEvent(new CustomEvent('pkg:open-section', {
                detail: { id: 'manual-attendance' }
            }));

            this.$nextTick(() => {
                const manualPanel = document.querySelector('[data-manual-input-panel]');
                manualPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                manualPanel?.querySelector('[data-manual-status]')?.focus();
            });
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
                    this.loadStats();
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
                    this.loadStats();
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
