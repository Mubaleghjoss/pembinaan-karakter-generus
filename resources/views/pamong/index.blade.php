@extends('layouts.app')

@section('title', 'Tim PKG - PKG Presensi')

@php
    $availableTeamsPayload = $teams->map(fn ($team) => [
        'id' => $team->id,
        'name' => $team->name,
        'short_name' => $team->short_name,
        'color_hex' => $team->color_hex,
        'is_active' => $team->is_active,
    ])->values();
@endphp

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8" 
     x-data="pamongManager()"
     x-init="init()">
    
    <!-- Breadcrumb -->
    <x-breadcrumb :items="[
            ['title' => 'Tim PKG', 'url' => route('settings.index', ['tab' => 'pamong'])]
    ]" />
    
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Tim PKG</h1>
            <p class="pkg-page-subheading">
                Kelola pamong dan pengurus PKG, bidang organisasi, generate QR code, akun, dan hak akses.
            </p>
        </div>
    </div>

    @include('settings.partials.admin-tabs')

    @php($pamongDefaultTab = ($tab ?? null) === 'pamong' ? request('pamong_tab', 'data') : request('tab', 'data'))

    <!-- Tabs Component -->
    <x-tabs 
        :tabs="[
            ['id' => 'data', 'label' => 'Data Tim', 'icon' => ''],
            ['id' => 'bidang', 'label' => 'Bidang', 'icon' => ''],
            ['id' => 'qr', 'label' => 'Generate QR', 'icon' => ''],
            ['id' => 'akun', 'label' => 'Kelola Akun', 'icon' => ''],
            ['id' => 'permissions', 'label' => 'Hak Akses', 'icon' => '']
        ]"
        :default-tab="$pamongDefaultTab"
        persist-key="pamong-tab"
    >
        <!-- Tab: Data Pamong -->
        <x-tab-panel id="data">
            @include('pamong.partials.data')
        </x-tab-panel>

        <x-tab-panel id="bidang">
            @include('pamong.partials.bidang')
        </x-tab-panel>

        <!-- Tab: Generate QR -->
        <x-tab-panel id="qr" :lazy="true">
            @include('pamong.partials.qr')
        </x-tab-panel>

        <!-- Tab: Kelola Akun -->
        <x-tab-panel id="akun" :lazy="true">
            @include('pamong.partials.akun')
        </x-tab-panel>

        <!-- Tab: Hak Akses -->
        <x-tab-panel id="permissions" :lazy="true">
            @include('pamong.partials.permissions')
        </x-tab-panel>
    </x-tabs>

    <!-- Modals (inside x-data scope) -->
    @include('pamong.partials.modals')
</div>

@endsection

@push('scripts')
<script>
function pamongManager() {
    return {
        // Common state
        loading: false,
        
        // Data tab state
        pamongList: [],
        availableTeams: @json($availableTeamsPayload),
        pagination: {},
        currentPage: 1,
        filters: {
            search: '',
            status: '',
            team_id: ''
        },
        
        // QR tab state
        qrPamong: [],
        qrLoading: false,
        qrFilters: {
            search: ''
        },
        selectedQrPamong: [],
        selectAllQr: false,
        
        // Account tab state
        accountPamong: [],
        accountLoading: false,
        accountFilters: {
            search: '',
            status: '',
            team_id: ''
        },
        selectedAccounts: [],
        selectAllAccounts: false,
        
        // Change password modal state
        showChangePasswordModal: false,
        changePasswordPamong: null,
        newPassword: '',
        confirmPassword: '',
        showPassword: false,
        
        // Permissions tab state
        permissionsPamong: [],
        permissionsLoading: false,
        permissionsFilters: {
            search: ''
        },
        selectedPermissions: [],
        selectAllPermissions: false,
        availableMenus: @json($availableMenus),
        availableCrud: @json($availableCrud),
        crudOperationLabels: @json($crudOperationLabels),
        defaultMenuPermissions: @json($defaultPermissions['menu_permissions'] ?? []),
        defaultCrudPermissions: @json($defaultPermissions['crud_permissions'] ?? []),
        
        // Bulk permissions modal state
        showBulkPermissionsModal: false,
        permissionEditorPamong: null,
        bulkMenuPermissions: [],
        bulkCrudPermissions: {},
        menuCrudModules: {
            siswa: ['siswa'],
            presensi: ['presensi'],
            manual_attendance: ['manual_attendance'],
            tracer_karakter: ['tracer_karakter'],
            cek_kehadiran: ['cek_kehadiran'],
            pr: ['pr'],
            tugas_pkg: ['pr'],
            chat: ['chat'],
            group_chat: ['group_chat'],
            catatan_rapat: ['catatan_rapat'],
            jadwal: ['jadwal'],
            pamong_presensi: ['pamong_presensi'],
            berita: ['berita'],
            gamification: ['gamification'],
            game: ['game'],
            laporan_penyaksian: ['laporan_penyaksian'],
            export: ['export'],
        },
        
        getCrudLabel(operation) {
            return this.crudOperationLabels[operation] || operation.replaceAll('_', ' ');
        },

        getModuleLabel(module) {
            const labels = {
                siswa: 'Data Siswa',
                presensi: 'Presensi Siswa',
                manual_attendance: 'Input Manual',
                tracer_karakter: 'Tracer Karakter',
                cek_kehadiran: 'Poin Kehadiran',
                pr: 'Tugas PKG',
                chat: 'Chat Siswa',
                group_chat: 'Grup Chat',
                catatan_rapat: 'Catatan Rapat',
                jadwal: 'Jadwal',
                pamong_presensi: 'Presensi Pamong',
                berita: 'Berita',
                gamification: 'Gamifikasi',
                game: 'Game 29 Karakter',
                laporan_penyaksian: 'Laporan Penyaksian',
                export: 'Ekspor Data',
            };

            return labels[module] || this.availableMenus[module] || module.replaceAll('_', ' ');
        },

        permissionStatusLabel(pamong) {
            if (pamong?.pamong_permission?.is_excluded) {
                return 'Full Access';
            }

            const menus = pamong?.pamong_permission?.menu_permissions;
            return Array.isArray(menus) && menus.length > 0 ? 'Terbatas' : 'Default';
        },

        permissionMenusFor(pamong) {
            if (pamong?.pamong_permission?.is_excluded) {
                return Object.keys(this.availableMenus);
            }

            const menus = pamong?.pamong_permission?.menu_permissions;
            return Array.isArray(menus) ? [...menus] : [...this.defaultMenuPermissions];
        },

        permissionCrudFor(pamong) {
            if (pamong?.pamong_permission?.is_excluded) {
                return this.cloneCrudPermissions(this.availableCrud);
            }

            const crud = pamong?.pamong_permission?.crud_permissions;
            return crud && typeof crud === 'object'
                ? this.cloneCrudPermissions(crud)
                : this.cloneCrudPermissions(this.defaultCrudPermissions);
        },

        cloneCrudPermissions(source) {
            const clone = {};

            Object.entries(source || {}).forEach(([module, operations]) => {
                clone[module] = Array.isArray(operations) ? [...operations] : [];
            });

            return clone;
        },

        getCrudModulesForMenu(menuKey) {
            return (this.menuCrudModules[menuKey] || [menuKey])
                .filter(module => Array.isArray(this.availableCrud[module]));
        },

        isMenuSelected(menuKey) {
            return this.bulkMenuPermissions.includes(menuKey);
        },

        toggleMenuPermission(menuKey, checked) {
            if (checked) {
                if (!this.bulkMenuPermissions.includes(menuKey)) {
                    this.bulkMenuPermissions.push(menuKey);
                }

                this.getCrudModulesForMenu(menuKey).forEach(module => {
                    if (!Array.isArray(this.bulkCrudPermissions[module]) || this.bulkCrudPermissions[module].length === 0) {
                        this.bulkCrudPermissions[module] = [...(this.defaultCrudPermissions[module] || ['view']).filter(op => (this.availableCrud[module] || []).includes(op))];

                        if (this.bulkCrudPermissions[module].length === 0 && (this.availableCrud[module] || []).includes('view')) {
                            this.bulkCrudPermissions[module] = ['view'];
                        }
                    }
                });

                return;
            }

            this.bulkMenuPermissions = this.bulkMenuPermissions.filter(menu => menu !== menuKey);
            this.getCrudModulesForMenu(menuKey).forEach(module => {
                const stillUsed = this.bulkMenuPermissions.some(menu => this.getCrudModulesForMenu(menu).includes(module));

                if (!stillUsed) {
                    delete this.bulkCrudPermissions[module];
                }
            });
        },

        selectedMenuLabels() {
            return this.bulkMenuPermissions.map(menu => this.availableMenus[menu] || menu);
        },

        visibleCrudModules() {
            const moduleKeys = [];

            this.bulkMenuPermissions.forEach(menu => {
                this.getCrudModulesForMenu(menu).forEach(module => {
                    if (!moduleKeys.includes(module)) {
                        moduleKeys.push(module);
                    }
                });
            });

            return moduleKeys.map(module => ({
                key: module,
                label: this.getModuleLabel(module),
                operations: this.availableCrud[module] || [],
            }));
        },

        isCrudSelected(module, operation) {
            return this.bulkCrudPermissions[module]?.includes(operation) || false;
        },

        toggleCrud(module, operation) {
            if (!this.bulkCrudPermissions[module]) {
                this.bulkCrudPermissions[module] = [];
            }

            const index = this.bulkCrudPermissions[module].indexOf(operation);

            if (index > -1) {
                this.bulkCrudPermissions[module].splice(index, 1);
            } else {
                this.bulkCrudPermissions[module].push(operation);
            }
        },

        isModuleFullySelected(module) {
            const ops = this.availableCrud[module] || [];
            return ops.length > 0 && ops.every(op => this.isCrudSelected(module, op));
        },

        toggleModuleCrud(module) {
            const ops = this.availableCrud[module] || [];

            if (this.isModuleFullySelected(module)) {
                this.bulkCrudPermissions[module] = [];
            } else {
                this.bulkCrudPermissions[module] = [...ops];
            }
        },

        isAllCrudSelected() {
            const modules = this.visibleCrudModules();
            return modules.length > 0 && modules.every(module => this.isModuleFullySelected(module.key));
        },

        toggleAllCrudModules() {
            if (this.isAllCrudSelected()) {
                this.visibleCrudModules().forEach(module => {
                    this.bulkCrudPermissions[module.key] = [];
                });
            } else {
                this.visibleCrudModules().forEach(module => {
                    this.bulkCrudPermissions[module.key] = [...module.operations];
                });
            }
        },
        
        // Modal state
        showDetailModal: false,
        showAssignModal: false,
        selectedPamong: null,
        assignedStudents: [],
        availableStudents: [],
        selectedKelas: '',
        classes: [],
        
        async init() {
            await this.loadClasses();
            await this.loadPamong();
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
        
        async loadPamong(page = 1) {
            this.loading = true;
            this.currentPage = page;
            
            try {
                const params = new URLSearchParams({
                    page: page,
                    search: this.filters.search,
                    status: this.filters.status,
                    team_id: this.filters.team_id,
                    with_permissions: '1',
                    with_assigned_count: '1',
                    with_last_login: '1'
                });
                
                const response = await fetch(`/pamong-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.pamongList = data.data || [];
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    from: data.from,
                    to: data.to,
                    total: data.total
                };
            } catch (error) {
                console.error('Error loading pamong:', error);
            } finally {
                this.loading = false;
            }
        },
        
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadPamong(page);
            }
        },
        
        // QR Functions
        async loadPamongForQR() {
            this.qrLoading = true;
            
            try {
                const params = new URLSearchParams({
                    per_page: 50,
                    search: this.qrFilters.search,
                    status: 'active'
                });
                
                const response = await fetch(`/pamong-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.qrPamong = data.data || [];
            } catch (error) {
                console.error('Error loading pamong for QR:', error);
            } finally {
                this.qrLoading = false;
            }
        },
        
        toggleSelectAllQr() {
            if (this.selectAllQr) {
                this.selectedQrPamong = this.qrPamong.map(p => p.id);
            } else {
                this.selectedQrPamong = [];
            }
        },
        
        downloadQR(pamong) {
            window.open(`/pamong-presensi/card/${pamong.id}`, '_blank');
        },

        downloadAllPamongCards() {
            const params = new URLSearchParams();

            if (this.qrFilters.search) {
                params.set('search', this.qrFilters.search);
            }

            const query = params.toString();
            window.open(`/pamong/cards/print${query ? `?${query}` : ''}`, '_blank');
        },
        
        downloadSelectedQR() {
            if (this.selectedQrPamong.length === 0) {
                window.showNotification('Pilih minimal satu pamong', 'warning');
                return;
            }

            const ids = this.selectedQrPamong.join(',');
            window.open(`/pamong/cards/print?ids=${ids}`, '_blank');
        },
        
        // Account Functions
        async loadPamongForAccount() {
            this.accountLoading = true;
            
            try {
                const params = new URLSearchParams({
                    per_page: 50,
                    search: this.accountFilters.search,
                    status: this.accountFilters.status,
                    team_id: this.accountFilters.team_id,
                    with_plain_password: '1'
                });
                
                const response = await fetch(`/pamong-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.accountPamong = data.data || [];
            } catch (error) {
                console.error('Error loading pamong for account:', error);
            } finally {
                this.accountLoading = false;
            }
        },
        
        toggleSelectAllAccounts() {
            if (this.selectAllAccounts) {
                this.selectedAccounts = this.accountPamong.map(p => p.id);
            } else {
                this.selectedAccounts = [];
            }
        },
        
        async resetSinglePassword(pamong) {
            const confirmed = await window.showConfirmation(`Reset password untuk ${pamong.username}? Password baru akan menjadi username.`, {
                title: 'Reset password pamong',
                confirmText: 'Reset',
                tone: 'warning'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/pamong/${pamong.id}/reset-password`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                if (data.success !== false) {
                    // Update password in local state
                    pamong.plain_password = pamong.username;
                    window.showNotification(`Password berhasil direset. Password baru: ${pamong.username}`, 'success');
                } else {
                    window.showNotification(data.message || 'Gagal reset password', 'error');
                }
            } catch (error) {
                console.error('Error resetting password:', error);
                window.showNotification('Terjadi kesalahan saat reset password', 'error');
            }
        },
        
        openChangePasswordModal(pamong) {
            this.changePasswordPamong = pamong;
            this.newPassword = '';
            this.confirmPassword = '';
            this.showPassword = false;
            this.showChangePasswordModal = true;
        },
        
        async changePassword() {
            if (!this.newPassword || this.newPassword.length < 6) {
                window.showNotification('Password minimal 6 karakter', 'warning');
                return;
            }
            if (this.newPassword !== this.confirmPassword) {
                window.showNotification('Konfirmasi password tidak cocok', 'warning');
                return;
            }
            
            try {
                const response = await fetch(`/pamong/${this.changePasswordPamong.id}/change-password`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        new_password: this.newPassword
                    })
                });
                const data = await response.json();
                if (data.success) {
                    // Update password in local state
                    this.changePasswordPamong.plain_password = this.newPassword;
                    window.showNotification('Password berhasil diubah', 'success');
                    this.showChangePasswordModal = false;
                } else {
                    window.showNotification(data.message || 'Gagal mengubah password', 'error');
                }
            } catch (error) {
                console.error('Error changing password:', error);
                window.showNotification('Terjadi kesalahan saat mengubah password', 'error');
            }
        },
        
        async toggleAccountStatus(pamong) {
            const action = pamong.status === 'active' ? 'nonaktifkan' : 'aktifkan';
            const confirmed = await window.showConfirmation(`${action.charAt(0).toUpperCase() + action.slice(1)} akun ${pamong.username}?`, {
                title: 'Ubah status akun pamong',
                confirmText: action.charAt(0).toUpperCase() + action.slice(1),
                tone: 'warning'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/pamong/${pamong.id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    pamong.status = pamong.status === 'active' ? 'inactive' : 'active';
                } else {
                    window.showNotification(data.message || 'Gagal mengubah status', 'error');
                }
            } catch (error) {
                console.error('Error toggling status:', error);
                window.showNotification('Terjadi kesalahan saat mengubah status', 'error');
            }
        },
        
        // Permissions Functions
        async loadPamongForPermissions() {
            this.permissionsLoading = true;
            
            try {
                const params = new URLSearchParams({
                    per_page: 50,
                    search: this.permissionsFilters.search,
                    with_permissions: '1'
                });
                
                const response = await fetch(`/pamong-list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.permissionsPamong = data.data || [];
            } catch (error) {
                console.error('Error loading pamong for permissions:', error);
            } finally {
                this.permissionsLoading = false;
            }
        },
        
        toggleSelectAllPermissions() {
            if (this.selectAllPermissions) {
                this.selectedPermissions = this.permissionsPamong.map(p => p.id);
            } else {
                this.selectedPermissions = [];
            }
        },
        
        openBulkPermissionsModal() {
            if (this.selectedPermissions.length === 0) {
                window.showNotification('Pilih minimal satu pamong', 'warning');
                return;
            }

            if (this.selectedPermissions.length > 1) {
                window.showNotification('Pilih satu pamong saja untuk mengatur akses detail', 'warning');
                return;
            }

            const selectedId = Number(this.selectedPermissions[0]);
            const pamong = this.permissionsPamong.find(item => Number(item.id) === selectedId);

            if (!pamong) {
                window.showNotification('Data pamong belum termuat. Muat ulang daftar terlebih dahulu.', 'warning');
                return;
            }

            this.openPermissionsModalFor(pamong);
        },

        openPermissionsModalFor(pamong) {
            this.permissionEditorPamong = pamong;
            this.selectedPermissions = [pamong.id];
            this.selectAllPermissions = false;
            this.bulkMenuPermissions = this.permissionMenusFor(pamong);
            this.bulkCrudPermissions = this.permissionCrudFor(pamong);
            this.showBulkPermissionsModal = true;
        },
        
        toggleAllMenus() {
            if (this.bulkMenuPermissions.length === Object.keys(this.availableMenus).length) {
                this.bulkMenuPermissions = [];
                this.bulkCrudPermissions = {};
            } else {
                this.bulkMenuPermissions = Object.keys(this.availableMenus);
                this.bulkMenuPermissions.forEach(menu => {
                    this.getCrudModulesForMenu(menu).forEach(module => {
                        if (!Array.isArray(this.bulkCrudPermissions[module]) || this.bulkCrudPermissions[module].length === 0) {
                            this.bulkCrudPermissions[module] = [...(this.defaultCrudPermissions[module] || ['view']).filter(op => (this.availableCrud[module] || []).includes(op))];

                            if (this.bulkCrudPermissions[module].length === 0 && (this.availableCrud[module] || []).includes('view')) {
                                this.bulkCrudPermissions[module] = ['view'];
                            }
                        }
                    });
                });
            }
        },

        crudPermissionsForSave() {
            const visibleModules = this.visibleCrudModules().map(module => module.key);
            const payload = {};

            visibleModules.forEach(module => {
                const allowed = this.availableCrud[module] || [];
                const selected = this.bulkCrudPermissions[module] || [];
                payload[module] = selected.filter(operation => allowed.includes(operation));
            });

            return payload;
        },
        
        async saveBulkPermissions() {
            if (!this.permissionEditorPamong) {
                window.showNotification('Pilih akun pamong terlebih dahulu', 'warning');
                return;
            }

            const confirmed = await window.showConfirmation(`Simpan akses untuk ${this.permissionEditorPamong.username}?`, {
                title: 'Simpan hak akses',
                confirmText: 'Simpan',
                tone: 'primary'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/pamong/bulk-permissions`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        pamong_ids: [this.permissionEditorPamong.id],
                        action: 'set_custom',
                        menu_permissions: this.bulkMenuPermissions,
                        crud_permissions: this.crudPermissionsForSave()
                    })
                });
                const data = await response.json();
                if (data.success) {
                    const savedCrud = this.crudPermissionsForSave();

                    this.permissionsPamong.forEach(pamong => {
                        if (Number(pamong.id) === Number(this.permissionEditorPamong.id)) {
                            if (!pamong.pamong_permission) {
                                pamong.pamong_permission = {};
                            }
                            pamong.pamong_permission.is_excluded = false;
                            pamong.pamong_permission.menu_permissions = [...this.bulkMenuPermissions];
                            pamong.pamong_permission.crud_permissions = {...savedCrud};
                            this.permissionEditorPamong = pamong;
                        }
                    });

                    this.selectedPermissions = [];
                    this.selectAllPermissions = false;
                    this.showBulkPermissionsModal = false;
                    window.showNotification('Hak akses berhasil diperbarui', 'success');
                } else {
                    window.showNotification(data.message || 'Gagal memperbarui akses', 'error');
                }
            } catch (error) {
                console.error('Error saving bulk permissions:', error);
                window.showNotification('Terjadi kesalahan saat memperbarui akses', 'error');
            }
        },
        
        editPermissions(pamong) {
            this.openPermissionsModalFor(pamong);
        },
        
        async toggleExcluded(pamong) {
            const newStatus = !pamong.pamong_permission?.is_excluded;
            const action = newStatus ? 'memberikan' : 'mencabut';
            const confirmed = await window.showConfirmation(`${action.charAt(0).toUpperCase() + action.slice(1)} Full Access untuk ${pamong.username}?`, {
                title: newStatus ? 'Berikan Full Access' : 'Cabut Full Access',
                confirmText: newStatus ? 'Berikan' : 'Cabut',
                tone: 'warning'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/pamong/bulk-permissions`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        pamong_ids: [pamong.id],
                        action: newStatus ? 'set_excluded' : 'remove_excluded'
                    })
                });
                const data = await response.json();
                if (data.success) {
                    if (!pamong.pamong_permission) {
                        pamong.pamong_permission = {};
                    }
                    pamong.pamong_permission.is_excluded = newStatus;
                } else {
                    window.showNotification(data.message || 'Gagal mengubah status', 'error');
                }
            } catch (error) {
                console.error('Error toggling excluded:', error);
                window.showNotification('Terjadi kesalahan saat mengubah Full Access', 'error');
            }
        },
        
        async bulkSetFullAccess() {
            if (this.selectedPermissions.length === 0) {
                window.showNotification('Pilih minimal satu pamong', 'warning');
                return;
            }
            
            const confirmed = await window.showConfirmation(`Berikan Full Access untuk ${this.selectedPermissions.length} pamong yang dipilih?`, {
                title: 'Berikan Full Access',
                confirmText: 'Berikan',
                tone: 'warning'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/pamong/bulk-permissions`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        pamong_ids: this.selectedPermissions,
                        action: 'set_excluded'
                    })
                });
                const data = await response.json();
                if (data.success) {
                    // Update local state
                    this.permissionsPamong.forEach(pamong => {
                        if (this.selectedPermissions.includes(pamong.id)) {
                            if (!pamong.pamong_permission) {
                                pamong.pamong_permission = {};
                            }
                            pamong.pamong_permission.is_excluded = true;
                        }
                    });
                    this.selectedPermissions = [];
                    this.selectAllPermissions = false;
                    window.showNotification('Full Access berhasil diberikan', 'success');
                } else {
                    window.showNotification(data.message || 'Gagal memberikan Full Access', 'error');
                }
            } catch (error) {
                console.error('Error setting full access:', error);
                window.showNotification('Terjadi kesalahan saat memberi Full Access', 'error');
            }
        },
        
        async bulkRemoveFullAccess() {
            if (this.selectedPermissions.length === 0) {
                window.showNotification('Pilih minimal satu pamong', 'warning');
                return;
            }
            
            const confirmed = await window.showConfirmation(`Cabut Full Access untuk ${this.selectedPermissions.length} pamong yang dipilih?`, {
                title: 'Cabut Full Access',
                confirmText: 'Cabut',
                tone: 'warning'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/pamong/bulk-permissions`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        pamong_ids: this.selectedPermissions,
                        action: 'remove_excluded'
                    })
                });
                const data = await response.json();
                if (data.success) {
                    // Update local state
                    this.permissionsPamong.forEach(pamong => {
                        if (this.selectedPermissions.includes(pamong.id)) {
                            if (!pamong.pamong_permission) {
                                pamong.pamong_permission = {};
                            }
                            pamong.pamong_permission.is_excluded = false;
                        }
                    });
                    this.selectedPermissions = [];
                    this.selectAllPermissions = false;
                    window.showNotification('Full Access berhasil dicabut', 'success');
                } else {
                    window.showNotification(data.message || 'Gagal mencabut Full Access', 'error');
                }
            } catch (error) {
                console.error('Error removing full access:', error);
                window.showNotification('Terjadi kesalahan saat mencabut Full Access', 'error');
            }
        },
        
        async bulkSetDefault() {
            if (this.selectedPermissions.length === 0) {
                window.showNotification('Pilih minimal satu pamong', 'warning');
                return;
            }
            
            const confirmed = await window.showConfirmation(`Kembalikan ke default untuk ${this.selectedPermissions.length} pamong yang dipilih? Menu akan mengikuti pengaturan default Tim PKG.`, {
                title: 'Kembalikan akses default',
                confirmText: 'Kembalikan',
                tone: 'warning'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/pamong/bulk-permissions`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        pamong_ids: this.selectedPermissions,
                        action: 'set_default'
                    })
                });
                const data = await response.json();
                if (data.success) {
                    // Update local state
                    this.permissionsPamong.forEach(pamong => {
                        if (this.selectedPermissions.includes(pamong.id)) {
                            if (!pamong.pamong_permission) {
                                pamong.pamong_permission = {};
                            }
                            pamong.pamong_permission.is_excluded = false;
                            pamong.pamong_permission.menu_permissions = [...this.defaultMenuPermissions];
                            pamong.pamong_permission.crud_permissions = {...this.defaultCrudPermissions};
                        }
                    });
                    this.selectedPermissions = [];
                    this.selectAllPermissions = false;
                    window.showNotification('Hak akses berhasil dikembalikan ke default', 'success');
                } else {
                    window.showNotification(data.message || 'Gagal mengembalikan ke default', 'error');
                }
            } catch (error) {
                console.error('Error setting default:', error);
                window.showNotification('Terjadi kesalahan saat mengembalikan akses default', 'error');
            }
        },
        
        // Detail & Assign Functions
        async showDetail(pamong) {
            this.selectedPamong = pamong;
            this.showDetailModal = true;
            
            try {
                const response = await fetch(`/pamong/${pamong.id}/students`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                this.assignedStudents = data.students || [];
            } catch (error) {
                console.error('Error loading assigned students:', error);
            }
        },
        
        openAssignModal(pamong) {
            window.location.href = `/pamong/${pamong.id}/assign`;
        }
    };
}
</script>
@endpush
