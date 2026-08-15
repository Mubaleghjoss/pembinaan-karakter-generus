@extends('layouts.app')

@section('title', 'Data Siswa - PKG Presensi')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8" 
     x-data="siswaManager()"
     x-init="init()">
    
    <!-- Breadcrumb -->
    <x-breadcrumb :items="[
        ['title' => 'Siswa', 'url' => route('siswa.index')]
    ]" />
    
    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Data Siswa</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Kelola data siswa, generate QR code, dan kelola akun.
            </p>
        </div>
    </div>

    <!-- Tabs Component -->
    <x-tabs 
        :tabs="[
            ['id' => 'data', 'label' => 'Data Siswa', 'icon' => ''],
            ['id' => 'qr', 'label' => 'Kartu ID', 'icon' => ''],
            ['id' => 'akun', 'label' => 'Kelola Akun', 'icon' => '']
        ]"
        :default-tab="request('tab', 'data')"
        persist-key="siswa-tab"
    >
        <!-- Tab: Data Siswa -->
        <x-tab-panel id="data">
            @include('siswa.partials.data')
        </x-tab-panel>

        <!-- Tab: Generate QR -->
        <x-tab-panel id="qr" :lazy="true">
            @include('siswa.partials.qr')
        </x-tab-panel>

        <!-- Tab: Kelola Akun -->
        <x-tab-panel id="akun" :lazy="true">
            @include('siswa.partials.akun')
        </x-tab-panel>
    </x-tabs>
    
    <!-- Biodata Modal (inside x-data scope) -->
    @include('siswa.partials.biodata-modal')
</div>

<!-- Modals -->
@include('siswa.partials.modals')

@endsection

@push('scripts')
<script>
function siswaManager() {
    return {
        // Common state
        classes: [],
        loading: false,
        
        // Biodata modal state
        showBiodataModal: false,
        biodataStudent: null,
        biodataEditing: false,
        biodataForm: {
            nama: '',
            nis: '',
            jenis_kelamin: '',
            tanggal_lahir: '',
            kelompok: '',
            kelas_id: '',
            nama_wali: '',
            phone_wali: '',
            email_wali: ''
        },
        
        // Data tab state
        students: [],
        pagination: {},
        currentPage: 1,
        filters: {
            search: '',
            kelas_id: '',
            status: '',
            biodata_status: ''
        },
        
        // QR tab state
        qrStudents: [],
        qrLoading: false,
        qrFilters: {
            search: '',
            kelas_id: ''
        },
        qrSize: '300',
        showQRModal: false,
        selectedStudent: null,
        qrPreviewUrl: '',
        qrPreviewLoading: false,
        
        // Account tab state
        accountStudents: [],
        accountLoading: false,
        accountFilters: {
            search: '',
            kelas_id: '',
            status: ''
        },
        selectedAccounts: [],
        selectAllAccounts: false,
        alumniModalOpen: false,
        alumniSaving: false,
        alumniStudent: null,
        alumniForm: {
            alumni_can_submit: true,
            alumni_reviewer_id: ''
        },
        
        async init() {
            await this.loadClasses();
            await this.loadStudents();
        },
        
        async loadClasses() {
            try {
                const response = await fetch('/kelas-list', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.classes = data.data || data;
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        },

        extractRows(payload) {
            if (Array.isArray(payload?.data)) {
                return payload.data;
            }

            if (Array.isArray(payload?.data?.data)) {
                return payload.data.data;
            }

            return [];
        },

        extractPagination(payload) {
            const meta = payload?.meta || payload?.data?.meta || payload || {};

            return {
                current_page: meta.current_page || 1,
                last_page: meta.last_page || 1,
                from: meta.from || 0,
                to: meta.to || 0,
                total: meta.total || 0
            };
        },
        
        async loadStudents(page = 1) {
            this.loading = true;
            this.currentPage = page;
            
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 'all',
                    search: this.filters.search,
                    kelas_id: this.filters.kelas_id,
                    status: this.filters.status,
                    biodata_status: this.filters.biodata_status || ''
                });
                
                const response = await fetch(`/siswa-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.students = this.extractRows(data);
                this.pagination = this.extractPagination(data);
            } catch (error) {
                console.error('Error loading students:', error);
            } finally {
                this.loading = false;
            }
        },
        
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadStudents(page);
            }
        },
        
        // QR Functions
        async loadStudentsForQR() {
            this.qrLoading = true;
            
            try {
                const params = new URLSearchParams({
                    per_page: 'all',
                    search: this.qrFilters.search,
                    kelas_id: this.qrFilters.kelas_id,
                    status: '1' // Only active students
                });
                
                const response = await fetch(`/siswa-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.qrStudents = this.extractRows(data);
            } catch (error) {
                console.error('Error loading students for QR:', error);
            } finally {
                this.qrLoading = false;
            }
        },
        
        async previewQR(student) {
            this.selectedStudent = student;
            this.showQRModal = true;
            this.qrPreviewLoading = true;
            this.qrPreviewUrl = '';
            
            try {
                const response = await fetch(`/siswa/${student.id}/qr-code?size=${this.qrSize}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                if (data.qr_code) {
                    this.qrPreviewUrl = data.qr_code;
                }
            } catch (error) {
                console.error('Error loading QR preview:', error);
            } finally {
                this.qrPreviewLoading = false;
            }
        },
        
        async downloadQR(student) {
            // Buka halaman kartu siswa untuk print/download
            window.open(`/siswa/${student.id}/card`, '_blank');
        },
        
        async downloadAllQR() {
            const params = new URLSearchParams();

            if (this.qrFilters.kelas_id) {
                params.set('kelas_id', this.qrFilters.kelas_id);
            }

            if (this.qrFilters.search) {
                params.set('search', this.qrFilters.search);
            }

            const query = params.toString();
            window.open(`/siswa/cards/print${query ? `?${query}` : ''}`, '_blank');
        },
        
        // Account Functions
        async loadStudentsForAccount() {
            this.accountLoading = true;
            
            try {
                const params = new URLSearchParams({
                    per_page: 'all',
                    search: this.accountFilters.search,
                    kelas_id: this.accountFilters.kelas_id,
                    status: this.accountFilters.status
                });
                
                const response = await fetch(`/siswa-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.accountStudents = this.extractRows(data);
            } catch (error) {
                console.error('Error loading students for account:', error);
            } finally {
                this.accountLoading = false;
            }
        },
        
        toggleSelectAllAccounts() {
            if (this.selectAllAccounts) {
                this.selectedAccounts = this.accountStudents.map(s => s.id);
            } else {
                this.selectedAccounts = [];
            }
        },
        
        async resetSinglePassword(student) {
            const confirmed = await window.showConfirmation(`Reset password untuk ${student.nama}?`, {
                title: 'Reset password siswa',
                confirmText: 'Reset',
                tone: 'warning'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/siswa/${student.id}/reset-password`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.showNotification(`Password berhasil direset. Password baru: ${data.new_password || student.nis}`, 'success');
                } else {
                    window.showNotification(data.message || 'Gagal reset password', 'error');
                }
            } catch (error) {
                console.error('Error resetting password:', error);
                window.showNotification('Terjadi kesalahan saat reset password', 'error');
            }
        },
        
        async resetSelectedPasswords() {
            if (this.selectedAccounts.length === 0) return;
            const confirmed = await window.showConfirmation(`Reset password untuk ${this.selectedAccounts.length} siswa?`, {
                title: 'Reset password massal',
                confirmText: 'Reset',
                tone: 'warning'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch('/siswa/bulk-reset-password', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ ids: this.selectedAccounts })
                });
                const data = await response.json();
                if (data.success) {
                    window.showNotification(`Password berhasil direset untuk ${this.selectedAccounts.length} siswa`, 'success');
                    this.selectedAccounts = [];
                } else {
                    window.showNotification(data.message || 'Gagal reset password', 'error');
                }
            } catch (error) {
                console.error('Error resetting passwords:', error);
                window.showNotification('Terjadi kesalahan saat reset password', 'error');
            }
        },
        
        async toggleAccountStatus(student) {
            const action = student.is_active ? 'nonaktifkan' : 'aktifkan';
            const confirmed = await window.showConfirmation(`${action.charAt(0).toUpperCase() + action.slice(1)} akun ${student.nama}?`, {
                title: 'Ubah status akun siswa',
                confirmText: action.charAt(0).toUpperCase() + action.slice(1),
                tone: 'warning'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/siswa/${student.id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    student.is_active = !student.is_active;
                } else {
                    window.showNotification(data.message || 'Gagal mengubah status', 'error');
                }
            } catch (error) {
                console.error('Error toggling status:', error);
                window.showNotification('Terjadi kesalahan saat mengubah status', 'error');
            }
        },
        
        // CRUD Functions
        openAddModal() {
            // Trigger existing modal
            if (typeof window.openAddModal === 'function') {
                window.openAddModal();
            }
        },
        
        openImportModal() {
            if (typeof window.openImportModal === 'function') {
                window.openImportModal();
            }
        },
        
        openBulkResetModal() {
            if (typeof window.openBulkResetModal === 'function') {
                window.openBulkResetModal();
            }
        },
        
        editStudent(student) {
            if (typeof window.editStudent === 'function') {
                window.editStudent(student);
            }
        },
        
        deleteStudent(student) {
            if (typeof window.deleteStudent === 'function') {
                window.deleteStudent(student);
            }
        },

        openAlumniModal(student) {
            this.alumniStudent = student;
            this.alumniForm = {
                alumni_can_submit: student.alumni_can_submit !== false,
                alumni_reviewer_id: student.alumni_reviewer_id || ''
            };
            this.alumniModalOpen = true;
        },

        closeAlumniModal() {
            if (this.alumniSaving) return;
            this.alumniModalOpen = false;
            this.alumniStudent = null;
        },

        async saveAlumniLifecycle(action) {
            if (!this.alumniStudent || this.alumniSaving) return;

            const promptText = action === 'reactivate'
                ? 'Aktifkan kembali sebagai siswa? Pamong lama tidak akan dipulihkan otomatis.'
                : (action === 'graduate'
                    ? 'Tetapkan sebagai Alumni dan akhiri seluruh penugasan Pamong aktif?'
                    : 'Simpan setelan Alumni ini?');
            if (!window.confirm(promptText)) return;

            this.alumniSaving = true;
            try {
                const response = await fetch(`/siswa/${this.alumniStudent.id}/alumni`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        action,
                        alumni_can_submit: this.alumniForm.alumni_can_submit,
                        alumni_reviewer_id: this.alumniForm.alumni_reviewer_id || null
                    })
                });
                const result = await response.json();
                if (!response.ok) {
                    const errors = result.errors ? Object.values(result.errors).flat().join(' ') : null;
                    throw new Error(errors || result.message || 'Setelan Alumni gagal disimpan.');
                }

                window.showNotification(result.message, 'success');
                this.alumniModalOpen = false;
                this.alumniStudent = null;
                await this.loadStudents(this.currentPage);
            } catch (error) {
                window.showNotification(error.message || 'Setelan Alumni gagal disimpan.', 'error');
            } finally {
                this.alumniSaving = false;
            }
        },
        
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show brief notification
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                toast.textContent = 'Password disalin!';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        },
        
        // Biodata Functions
        viewBiodata(student) {
            this.biodataStudent = student;
            this.biodataEditing = false;
            this.biodataForm = {
                nama: student.nama || '',
                nis: student.nis || '',
                jenis_kelamin: student.jenis_kelamin || '',
                tanggal_lahir: student.tanggal_lahir || '',
                kelompok: student.kelompok || '',
                kelas_id: student.kelas_id || '',
                nama_wali: student.nama_wali || '',
                phone_wali: student.phone_wali || '',
                email_wali: student.email_wali || ''
            };
            this.showBiodataModal = true;
        },
        
        enableBiodataEdit() {
            this.biodataEditing = true;
        },
        
        cancelBiodataEdit() {
            this.biodataEditing = false;
            // Reset form to original values
            this.biodataForm = {
                nama: this.biodataStudent.nama || '',
                nis: this.biodataStudent.nis || '',
                jenis_kelamin: this.biodataStudent.jenis_kelamin || '',
                tanggal_lahir: this.biodataStudent.tanggal_lahir || '',
                kelompok: this.biodataStudent.kelompok || '',
                kelas_id: this.biodataStudent.kelas_id || '',
                nama_wali: this.biodataStudent.nama_wali || '',
                phone_wali: this.biodataStudent.phone_wali || '',
                email_wali: this.biodataStudent.email_wali || ''
            };
        },
        
        async saveBiodata() {
            if (!this.biodataStudent) return;
            
            // Filter out empty values - only send fields that have values
            const dataToSend = {};
            Object.keys(this.biodataForm).forEach(key => {
                const value = this.biodataForm[key];
                // Include field if it has a value (not empty string, not null, not undefined)
                if (value !== '' && value !== null && value !== undefined) {
                    dataToSend[key] = value;
                }
            });
            
            try {
                const response = await fetch(`/siswa/${this.biodataStudent.id}`, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(dataToSend)
                });
                
                const data = await response.json();
                
                if (response.ok || data.success) {
                    // Update local student data with the values that were sent
                    Object.assign(this.biodataStudent, dataToSend);
                    this.biodataEditing = false;
                    
                    // Show success notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                    toast.textContent = 'Biodata berhasil diperbarui!';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2000);
                    
                    // Reload students list
                    await this.loadStudents(this.currentPage);
                } else {
                    // Handle validation errors
                    if (data.errors) {
                        const errorMessages = Object.values(data.errors).flat().join('\n');
                        window.showNotification('Validasi gagal: ' + errorMessages, 'error');
                    } else {
                        window.showNotification(data.message || 'Gagal menyimpan biodata', 'error');
                    }
                }
            } catch (error) {
                console.error('Error saving biodata:', error);
                window.showNotification('Terjadi kesalahan saat menyimpan biodata', 'error');
            }
        }
    };
}
</script>
@endpush
