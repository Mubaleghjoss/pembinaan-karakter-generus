@extends('layouts.app')

@section('title', 'Chat - PKG Presensi')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8" x-data="chatManager()">
    
    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Chat</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Berkomunikasi dengan {{ $recipientScopeLabel ?? 'siswa yang tersedia' }} dan grup chat yang tersedia
            </p>
        </div>
    </div>

    <!-- Tabs Component -->
    <x-tabs 
        :tabs="[
            ['id' => 'pribadi', 'label' => 'Chat Pribadi', 'icon' => ''],
            ['id' => 'grup', 'label' => 'Grup Chat', 'icon' => ''],
            ['id' => 'broadcast', 'label' => 'Siaran', 'icon' => '']
        ]"
        :default-tab="request('tab', 'pribadi')"
        persist-key="chat-tab"
    >
        <!-- Tab: Chat Pribadi -->
        <x-tab-panel id="pribadi">
            @include('pamong.chat.partials.pribadi')
        </x-tab-panel>

        <!-- Tab: Grup Chat -->
        <x-tab-panel id="grup" :lazy="true">
            @include('pamong.chat.partials.grup')
        </x-tab-panel>

        <!-- Tab: Siaran -->
        <x-tab-panel id="broadcast" :lazy="true">
            @include('pamong.chat.partials.broadcast')
        </x-tab-panel>
    </x-tabs>
</div>
@endsection

@push('scripts')
<script>
function chatManager() {
    return {
        // Common state
        siswaList: @json($siswaList ?? []),
        recipientScopeLabel: @json($recipientScopeLabel ?? 'siswa yang tersedia'),
        groups: @json($groups ?? []),
        unreadCounts: {},
        unreadRefreshInterval: null,
        activeChatMode: null,
        pribadiSearch: '',
        pribadiKelas: '',
        
        // Pribadi chat state
        selectedSiswa: null,
        pribadiMessages: [],
        pribadiNewMessage: '',
        pribadiLoading: false,
        pribadiSelectedImage: null,
        pribadiImagePreview: null,
        pribadiRefreshInterval: null,
        
        // Grup chat state
        selectedGroup: null,
        grupMessages: [],
        grupNewMessage: '',
        grupLoading: false,
        grupSelectedImage: null,
        grupImagePreview: null,
        grupRefreshInterval: null,
        canCreateGroupChat: @json($canCreateGroupChat ?? false),
        groupUserCandidates: @json($groupUserCandidates ?? []),
        groupSiswaCandidates: @json($groupSiswaCandidates ?? []),
        showCreateGroupModal: false,
        creatingGroup: false,
        editingGroupId: null,
        loadingGroupEdit: false,
        createGroupSearchUsers: '',
        createGroupSearchSiswa: '',
        createGroupForm: {
            name: '',
            description: '',
            user_ids: [],
            siswa_ids: [],
            admin_user_ids: []
        },
        
        // Broadcast state
        broadcastMessage: '',
        broadcastSelectedImage: null,
        broadcastImagePreview: null,
        broadcastLoading: false,
        broadcastSuccess: false,
        broadcastSuccessMessage: '',
        broadcastStageMessage: '',
        broadcastProgress: 0,
        broadcastProgressTimer: null,
        
        async init() {
            await this.loadUnreadCounts();
            this.restoreSelectedSiswaFromQuery();
            this.startUnreadPolling();
            document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
            window.addEventListener('pagehide', () => this.stopAllPolling());
        },
        
        async loadUnreadCounts() {
            if (document.hidden) return;
            try {
                const res = await fetch('/pamong-chat/unread-counts', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.unreadCounts = data.unread_counts || {};
            } catch (e) {
                console.error('Failed to load unread counts:', e);
            }
        },
        
        getUnreadCount(id) {
            return this.unreadCounts[`siswa_${id}`] || 0;
        },

        messageDateKey(message) {
            return message?.full_date || message?.date || '';
        },

        messageDateLabel(message) {
            return message?.date_label || message?.date || message?.full_date || '';
        },

        shouldShowDateSeparator(messages, index) {
            if (!messages?.[index]) {
                return false;
            }

            if (index === 0) {
                return true;
            }

            return this.messageDateKey(messages[index]) !== this.messageDateKey(messages[index - 1]);
        },

        kelasOptions() {
            return this.siswaList
                .map((siswa) => ({
                    id: siswa.kelas?.id ?? '',
                    nama: siswa.kelas?.nama ?? 'Tanpa Kelas',
                }))
                .filter((kelas) => kelas.id !== '')
                .filter((kelas, index, list) => list.findIndex((item) => item.id === kelas.id) === index)
                .sort((a, b) => a.nama.localeCompare(b.nama, 'id'));
        },

        filteredSiswaList() {
            const keyword = this.pribadiSearch.trim().toLowerCase();

            return this.siswaList.filter((siswa) => {
                const kelasId = siswa.kelas?.id ? String(siswa.kelas.id) : '';
                const matchesKelas = !this.pribadiKelas || kelasId === this.pribadiKelas;
                const matchesKeyword = !keyword
                    || (siswa.nama || '').toLowerCase().includes(keyword)
                    || String(siswa.nis || '').toLowerCase().includes(keyword);

                return matchesKelas && matchesKeyword;
            });
        },

        restoreSelectedSiswaFromQuery() {
            const params = new URLSearchParams(window.location.search);
            const siswaId = params.get('siswa_id');
            if (!siswaId) {
                return;
            }

            const siswa = this.siswaList.find((item) => String(item.id) === String(siswaId));
            if (siswa) {
                this.selectSiswa(siswa);
            }
        },
        
        // ========== PRIBADI CHAT FUNCTIONS ==========
        selectSiswa(siswa) {
            this.activeChatMode = 'pribadi';
            this.selectedSiswa = siswa;
            this.loadPribadiMessages();
            setTimeout(() => this.loadUnreadCounts(), 500);
            this.stopGrupPolling();
            this.startPribadiPolling();
        },

        closePribadiConversation() {
            this.selectedSiswa = null;
            this.pribadiMessages = [];
            this.stopPribadiPolling();
        },

        startPribadiPolling() {
            this.stopPribadiPolling();
            if (this.selectedSiswa && !document.hidden) {
                this.pribadiRefreshInterval = setInterval(() => this.loadPribadiMessages(false), 5000);
            }
        },

        stopPribadiPolling() {
            if (this.pribadiRefreshInterval) clearInterval(this.pribadiRefreshInterval);
            this.pribadiRefreshInterval = null;
        },
        
        async loadPribadiMessages(showLoading = true) {
            if (!this.selectedSiswa || document.hidden) return;
            const currentContainer = document.getElementById('pribadi-messages-container');
            const shouldStickToBottom = showLoading || !currentContainer || (currentContainer.scrollHeight - currentContainer.scrollTop - currentContainer.clientHeight < 100);
            this.pribadiLoading = showLoading && this.pribadiMessages.length === 0;
            
            try {
                const res = await fetch(`/pamong-chat/messages?siswa_id=${this.selectedSiswa.id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.pribadiMessages = data.messages;
                    this.$nextTick(() => {
                        const container = document.getElementById('pribadi-messages-container');
                        if (container && shouldStickToBottom) container.scrollTop = container.scrollHeight;
                    });
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.pribadiLoading = false;
            }
        },
        
        handlePribadiImage(e) {
            const file = e.target.files[0];
            if (file) {
                this.pribadiSelectedImage = file;
                this.pribadiImagePreview = URL.createObjectURL(file);
            }
        },
        
        clearPribadiImage() {
            this.pribadiSelectedImage = null;
            this.pribadiImagePreview = null;
            if (this.$refs.pribadiImageInput) this.$refs.pribadiImageInput.value = '';
        },
        
        async sendPribadiMessage() {
            if ((!this.pribadiNewMessage.trim() && !this.pribadiSelectedImage) || !this.selectedSiswa) return;
            
            try {
                const formData = new FormData();
                formData.append('siswa_id', this.selectedSiswa.id);
                formData.append('message', this.pribadiNewMessage);
                if (this.pribadiSelectedImage) {
                    formData.append('attachment', this.pribadiSelectedImage);
                }

                const res = await fetch('/pamong-chat/send', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    this.pribadiMessages.push(data.message);
                    this.pribadiNewMessage = '';
                    this.clearPribadiImage();
                    this.$nextTick(() => {
                        const container = document.getElementById('pribadi-messages-container');
                        if (container) container.scrollTop = container.scrollHeight;
                    });
                }
            } catch (e) {
                console.error(e);
                window.showNotification('Gagal mengirim pesan', 'error');
            }
        },
        
        // ========== GRUP CHAT FUNCTIONS ==========
        openCreateGroupModal() {
            if (!this.canCreateGroupChat) return;
            this.resetCreateGroupForm();
            this.editingGroupId = null;
            this.showCreateGroupModal = true;
        },

        closeCreateGroupModal() {
            this.showCreateGroupModal = false;
        },

        resetCreateGroupForm() {
            this.createGroupForm = {
                name: '',
                description: '',
                user_ids: [],
                siswa_ids: [],
                admin_user_ids: []
            };
            this.createGroupSearchUsers = '';
            this.createGroupSearchSiswa = '';
            this.creatingGroup = false;
            this.loadingGroupEdit = false;
        },

        filteredGroupUsers() {
            const keyword = this.createGroupSearchUsers.trim().toLowerCase();

            return this.groupUserCandidates.filter((user) => {
                return !keyword
                    || (user.name || '').toLowerCase().includes(keyword)
                    || (user.username || '').toLowerCase().includes(keyword)
                    || (user.role_label || '').toLowerCase().includes(keyword);
            });
        },

        filteredGroupSiswa() {
            const keyword = this.createGroupSearchSiswa.trim().toLowerCase();

            return this.groupSiswaCandidates.filter((siswa) => {
                return !keyword
                    || (siswa.nama || '').toLowerCase().includes(keyword)
                    || String(siswa.nis || '').toLowerCase().includes(keyword)
                    || (siswa.kelas?.nama || '').toLowerCase().includes(keyword);
            });
        },

        isGroupUserSelected(id) {
            return this.createGroupForm.user_ids.includes(Number(id));
        },

        isGroupAdminUser(id) {
            return this.createGroupForm.admin_user_ids.includes(Number(id));
        },

        isGroupSiswaSelected(id) {
            return this.createGroupForm.siswa_ids.includes(Number(id));
        },

        toggleGroupUser(id) {
            id = Number(id);
            const index = this.createGroupForm.user_ids.indexOf(id);

            if (index >= 0) {
                this.createGroupForm.user_ids.splice(index, 1);
                this.createGroupForm.admin_user_ids = this.createGroupForm.admin_user_ids.filter((userId) => userId !== id);
                return;
            }

            this.createGroupForm.user_ids.push(id);
        },

        toggleGroupAdminUser(id) {
            id = Number(id);

            if (!this.isGroupUserSelected(id)) {
                this.createGroupForm.user_ids.push(id);
            }

            const index = this.createGroupForm.admin_user_ids.indexOf(id);
            if (index >= 0) {
                this.createGroupForm.admin_user_ids.splice(index, 1);
                return;
            }

            this.createGroupForm.admin_user_ids.push(id);
        },

        toggleGroupSiswa(id) {
            id = Number(id);
            const index = this.createGroupForm.siswa_ids.indexOf(id);

            if (index >= 0) {
                this.createGroupForm.siswa_ids.splice(index, 1);
                return;
            }

            this.createGroupForm.siswa_ids.push(id);
        },

        selectedGroupMemberCount() {
            return this.createGroupForm.user_ids.length + this.createGroupForm.siswa_ids.length;
        },

        async openEditGroupModal(group = null) {
            const target = group || this.selectedGroup;
            if (!target?.id || !target.can_manage) return;

            this.resetCreateGroupForm();
            this.editingGroupId = target.id;
            this.showCreateGroupModal = true;
            this.loadingGroupEdit = true;

            try {
                const res = await fetch(`/pamong-chat/groups/${target.id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.editingGroupId = data.group.id;
                    this.createGroupForm = {
                        name: data.group.name || '',
                        description: data.group.description || '',
                        user_ids: (data.group.user_ids || [])
                            .map((id) => Number(id))
                            .filter((id) => this.groupUserCandidates.some((user) => Number(user.id) === id)),
                        siswa_ids: (data.group.siswa_ids || [])
                            .map((id) => Number(id))
                            .filter((id) => this.groupSiswaCandidates.some((siswa) => Number(siswa.id) === id)),
                        admin_user_ids: (data.group.admin_user_ids || [])
                            .map((id) => Number(id))
                            .filter((id) => this.groupUserCandidates.some((user) => Number(user.id) === id))
                    };
                    return;
                }

                window.showNotification(data.message || 'Gagal memuat detail grup', 'error');
                this.closeCreateGroupModal();
            } catch (e) {
                console.error(e);
                window.showNotification('Terjadi kesalahan saat memuat detail grup', 'error');
                this.closeCreateGroupModal();
            } finally {
                this.loadingGroupEdit = false;
            }
        },

        async submitCreateGroup() {
            if (this.creatingGroup) return;

            if (!this.createGroupForm.name.trim()) {
                window.showNotification('Nama grup wajib diisi', 'warning');
                return;
            }

            if (this.selectedGroupMemberCount() === 0) {
                window.showNotification('Pilih minimal satu anggota grup', 'warning');
                return;
            }

            this.creatingGroup = true;

            try {
                const url = this.editingGroupId
                    ? `/pamong-chat/groups/${this.editingGroupId}`
                    : @json(route('pamong.chat.groups.store'));
                const res = await fetch(url, {
                    method: this.editingGroupId ? 'PUT' : 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.createGroupForm)
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.groups = [data.group, ...this.groups.filter((group) => Number(group.id) !== Number(data.group.id))];
                    this.closeCreateGroupModal();
                    this.selectGroup(data.group);
                    window.location.hash = 'grup';
                    window.showNotification(data.message || (this.editingGroupId ? 'Grup chat berhasil diperbarui' : 'Grup chat berhasil dibuat'), 'success');
                    return;
                }

                window.showNotification(data.message || 'Gagal menyimpan grup chat', 'error');
            } catch (e) {
                console.error(e);
                window.showNotification('Terjadi kesalahan saat menyimpan grup chat', 'error');
            } finally {
                this.creatingGroup = false;
            }
        },

        selectGroup(group) {
            this.activeChatMode = 'grup';
            this.selectedGroup = group;
            this.loadGrupMessages();
            this.stopPribadiPolling();
            this.startGrupPolling();
        },

        closeGrupConversation() {
            this.selectedGroup = null;
            this.grupMessages = [];
            this.stopGrupPolling();
        },

        startGrupPolling() {
            this.stopGrupPolling();
            if (this.selectedGroup && !document.hidden) {
                this.grupRefreshInterval = setInterval(() => this.loadGrupMessages(false), 5000);
            }
        },

        stopGrupPolling() {
            if (this.grupRefreshInterval) clearInterval(this.grupRefreshInterval);
            this.grupRefreshInterval = null;
        },
        
        async loadGrupMessages(showLoading = true) {
            if (!this.selectedGroup || document.hidden) return;
            const currentContainer = document.getElementById('grup-messages-container');
            const shouldStickToBottom = showLoading || !currentContainer || (currentContainer.scrollHeight - currentContainer.scrollTop - currentContainer.clientHeight < 100);
            this.grupLoading = showLoading && this.grupMessages.length === 0;
            
            try {
                const res = await fetch(`/group-chat/${this.selectedGroup.id}/messages`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.grupMessages = data.messages;
                    this.$nextTick(() => {
                        const container = document.getElementById('grup-messages-container');
                        if (container && shouldStickToBottom) container.scrollTop = container.scrollHeight;
                    });
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.grupLoading = false;
            }
        },
        
        handleGrupImage(e) {
            const file = e.target.files[0];
            if (file) {
                this.grupSelectedImage = file;
                this.grupImagePreview = URL.createObjectURL(file);
            }
        },
        
        clearGrupImage() {
            this.grupSelectedImage = null;
            this.grupImagePreview = null;
            if (this.$refs.grupImageInput) this.$refs.grupImageInput.value = '';
        },
        
        async sendGrupMessage() {
            if ((!this.grupNewMessage.trim() && !this.grupSelectedImage) || !this.selectedGroup) return;
            
            try {
                const formData = new FormData();
                formData.append('message', this.grupNewMessage);
                if (this.grupSelectedImage) formData.append('attachment', this.grupSelectedImage);

                const res = await fetch(`/group-chat/${this.selectedGroup.id}/send`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    this.grupMessages.push(data.message);
                    this.grupNewMessage = '';
                    this.clearGrupImage();
                    this.$nextTick(() => {
                        const container = document.getElementById('grup-messages-container');
                        if (container) container.scrollTop = container.scrollHeight;
                    });
                }
            } catch (e) {
                console.error(e);
                window.showNotification('Gagal mengirim pesan', 'error');
            }
        },

        startUnreadPolling() {
            if (this.unreadRefreshInterval) clearInterval(this.unreadRefreshInterval);
            if (!document.hidden) {
                this.unreadRefreshInterval = setInterval(() => this.loadUnreadCounts(), 10000);
            }
        },

        stopAllPolling() {
            this.stopPribadiPolling();
            this.stopGrupPolling();
            if (this.unreadRefreshInterval) clearInterval(this.unreadRefreshInterval);
            this.unreadRefreshInterval = null;
        },

        handleVisibilityChange() {
            if (document.hidden) {
                this.stopAllPolling();
                return;
            }

            this.loadUnreadCounts();
            this.startUnreadPolling();
            if (this.activeChatMode === 'pribadi' && this.selectedSiswa) {
                this.loadPribadiMessages(false);
                this.startPribadiPolling();
            } else if (this.activeChatMode === 'grup' && this.selectedGroup) {
                this.loadGrupMessages(false);
                this.startGrupPolling();
            }
        },
        
        // ========== BROADCAST FUNCTIONS ==========
        handleBroadcastImage(e) {
            const file = e.target.files[0];
            if (file) {
                this.broadcastSelectedImage = file;
                this.broadcastImagePreview = URL.createObjectURL(file);
            }
        },
        
        clearBroadcastImage() {
            this.broadcastSelectedImage = null;
            this.broadcastImagePreview = null;
            if (this.$refs.broadcastImageInput) this.$refs.broadcastImageInput.value = '';
        },

        startBroadcastProgress() {
            this.broadcastProgress = 8;
            this.broadcastStageMessage = `Menyiapkan siaran untuk ${this.siswaList.length} ${this.recipientScopeLabel}...`;

            if (this.broadcastProgressTimer) {
                clearInterval(this.broadcastProgressTimer);
            }

            this.broadcastProgressTimer = setInterval(() => {
                if (this.broadcastProgress < 28) {
                    this.broadcastProgress += 4;
                    this.broadcastStageMessage = 'Memeriksa lampiran dan isi pesan...';
                    return;
                }

                if (this.broadcastProgress < 62) {
                    this.broadcastProgress += 3;
                    this.broadcastStageMessage = 'Mengirim siaran ke daftar penerima...';
                    return;
                }

                if (this.broadcastProgress < 88) {
                    this.broadcastProgress += 1;
                    this.broadcastStageMessage = 'Menyelesaikan penyimpanan pesan siaran...';
                }
            }, 220);
        },

        finishBroadcastProgress(successMessage) {
            if (this.broadcastProgressTimer) {
                clearInterval(this.broadcastProgressTimer);
                this.broadcastProgressTimer = null;
            }

            this.broadcastProgress = 100;
            this.broadcastStageMessage = successMessage;
        },

        failBroadcastProgress(errorMessage) {
            if (this.broadcastProgressTimer) {
                clearInterval(this.broadcastProgressTimer);
                this.broadcastProgressTimer = null;
            }

            this.broadcastStageMessage = errorMessage;
        },
        
        async sendBroadcast() {
            if ((!this.broadcastMessage.trim() && !this.broadcastSelectedImage) || this.broadcastLoading) return;
            
            this.broadcastLoading = true;
            this.broadcastSuccess = false;
            this.startBroadcastProgress();
            
            try {
                const formData = new FormData();
                formData.append('message', this.broadcastMessage);
                if (this.broadcastSelectedImage) {
                    formData.append('attachment', this.broadcastSelectedImage);
                }

                const res = await fetch('{{ route('pamong.chat.broadcast.send') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.success) {
                    this.broadcastSuccessMessage = data.message || 'Siaran berhasil dikirim!';
                    this.broadcastSuccess = true;
                    this.finishBroadcastProgress(this.broadcastSuccessMessage);
                    this.broadcastMessage = '';
                    this.clearBroadcastImage();
                    
                    setTimeout(() => {
                        this.broadcastSuccess = false;
                        this.broadcastStageMessage = '';
                        this.broadcastProgress = 0;
                    }, 5000);
                } else {
                    this.failBroadcastProgress(data.message || 'Gagal mengirim siaran.');
                    window.showNotification(data.message || 'Gagal mengirim siaran', 'error');
                }
            } catch (e) {
                console.error(e);
                this.failBroadcastProgress('Terjadi kesalahan saat mengirim siaran.');
                window.showNotification('Terjadi kesalahan saat mengirim siaran', 'error');
            } finally {
                this.broadcastLoading = false;
            }
        }
    };
}
</script>
@endpush
