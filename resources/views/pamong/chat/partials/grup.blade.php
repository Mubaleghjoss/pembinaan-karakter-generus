{{-- Grup Chat Tab Content --}}
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Group List -->
    <div class="lg:col-span-1 pkg-card overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-gray-900 dark:text-white">{{ auth()->user()->isAdmin() ? 'Semua Grup' : 'Grup Anda' }}</h2>
                <button type="button"
                        x-show="canCreateGroupChat"
                        @click="openCreateGroupModal()"
                        class="btn-primary !px-3 !py-1.5 text-xs">
                    Buat
                </button>
            </div>
        </div>
        
        <div class="max-h-96 overflow-y-auto">
            <template x-if="groups.length > 0">
                <template x-for="group in groups" :key="group.id">
                    <button @click="selectGroup(group)"
                            :class="selectedGroup?.id === group.id ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="w-full p-4 flex items-center gap-3 text-left transition-colors">
                        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate" x-text="group.name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="(group.members_count || group.members?.length || 0) + ' anggota'"></p>
                        </div>
                    </button>
                </template>
            </template>
            <template x-if="groups.length === 0">
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <p>Belum ada grup</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="lg:col-span-3 pkg-card flex flex-col" style="height: 500px;">
        <!-- Chat Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <template x-if="selectedGroup">
                <div class="flex flex-1 items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900 dark:text-white" x-text="selectedGroup.name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="(selectedGroup.members_count || selectedGroup.members?.length || 0) + ' anggota'"></p>
                        </div>
                    </div>
                    <button type="button"
                            x-show="selectedGroup?.can_manage"
                            @click="openEditGroupModal(selectedGroup)"
                            class="btn-secondary !px-3 !py-1.5 text-xs">
                        Edit
                    </button>
                </div>
            </template>
            <template x-if="!selectedGroup">
                <p class="text-gray-500 dark:text-gray-400">Pilih grup untuk memulai chat</p>
            </template>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" id="grup-messages-container">
            <template x-if="!selectedGroup">
                <div class="h-full flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p>Pilih grup untuk memulai percakapan</p>
                    </div>
                </div>
            </template>
            <template x-if="grupLoading">
                <div class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            </template>
            <template x-for="(msg, index) in grupMessages" :key="msg.id">
                <div class="space-y-2">
                    <template x-if="shouldShowDateSeparator(grupMessages, index)">
                        <div class="flex justify-center">
                            <span class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" x-text="messageDateLabel(msg)"></span>
                        </div>
                    </template>
                    <div :class="msg.is_mine ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.is_mine ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'"
                             class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg">
                            <template x-if="!msg.is_mine">
                                <p class="text-xs font-semibold mb-1" :class="msg.sender_type === 'pamong' ? 'text-green-600' : 'text-blue-600'" x-text="msg.sender_name"></p>
                            </template>
                            <template x-if="msg.attachment_url">
                                <img :src="msg.attachment_url" class="rounded-lg max-w-full mb-2 cursor-pointer" @click="window.open(msg.attachment_url, '_blank')">
                            </template>
                            <p class="text-sm whitespace-pre-wrap" x-text="msg.message"></p>
                            <p class="text-xs mt-1 opacity-70" x-text="msg.created_at"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Input -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <form @submit.prevent="sendGrupMessage()" class="flex gap-2 items-end" data-no-csrf-handler>
                <label class="cursor-pointer px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                       :class="!selectedGroup ? 'opacity-50 cursor-not-allowed' : ''">
                    <input type="file" accept="image/*" @change="handleGrupImage" class="hidden" :disabled="!selectedGroup" x-ref="grupImageInput">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </label>
                <textarea x-model="grupNewMessage" :disabled="!selectedGroup" rows="1"
                          @keydown="if($event.key === 'Enter' && !$event.shiftKey) { $event.preventDefault(); sendGrupMessage(); }"
                          class="flex-1 pkg-field resize-none"
                          placeholder="Ketik pesan..."
                          style="min-height: 38px; max-height: 120px;"></textarea>
                <button type="submit" :disabled="!selectedGroup || (!grupNewMessage.trim() && !grupSelectedImage)"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
            <div x-show="grupSelectedImage" class="mt-2 relative inline-block">
                <img :src="grupImagePreview" class="h-20 rounded-lg">
                <button @click="clearGrupImage()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">&times;</button>
            </div>
        </div>
    </div>
</div>

<div x-show="showCreateGroupModal"
     x-cloak
     x-transition
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-modal="true"
     role="dialog">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-500/75 transition-opacity dark:bg-gray-900/75"
             @click="closeCreateGroupModal()"></div>

        <div class="relative pkg-modal flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="editingGroupId ? 'Edit Grup Chat' : 'Buat Grup Chat'"></h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atur nama grup, anggota, dan admin grup yang boleh mengelola peserta.</p>
                </div>
                <button type="button"
                        @click="closeCreateGroupModal()"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitCreateGroup()" class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
                <div x-show="loadingGroupEdit" class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Memuat detail grup...
                </div>
                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Grup</label>
                            <input type="text"
                                   x-model="createGroupForm.name"
                                   maxlength="120"
                                   class="w-full pkg-field"
                                   placeholder="Misal: Koordinasi Kelas 7">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi</label>
                            <textarea x-model="createGroupForm.description"
                                      rows="4"
                                      maxlength="500"
                                      class="w-full pkg-field"
                                      placeholder="Opsional"></textarea>
                        </div>
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-100">
                            <p><strong x-text="selectedGroupMemberCount()"></strong> anggota dipilih. Pembuat grup tetap menjadi admin grup.</p>
                            <p class="mt-1 text-xs">Centang Admin Grup pada akun tim yang boleh mengubah anggota.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <section class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="mb-3">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Admin dan Pamong</h4>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilih akun tim yang ikut di grup.</p>
                            </div>
                            <input type="text"
                                   x-model="createGroupSearchUsers"
                                   class="mb-3 w-full pkg-field text-sm"
                                   placeholder="Cari nama atau username...">
                            <div class="max-h-72 space-y-1 overflow-y-auto pr-1">
                                <template x-if="filteredGroupUsers().length === 0">
                                    <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada akun.</p>
                                </template>
                                <template x-for="user in filteredGroupUsers()" :key="'user-' + user.id">
                                    <div class="rounded-lg px-2 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <label class="flex cursor-pointer items-start gap-3">
                                            <input type="checkbox"
                                                   class="pkg-check mt-1"
                                                   :checked="isGroupUserSelected(user.id)"
                                                   @change="toggleGroupUser(user.id)">
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-medium text-gray-900 dark:text-white" x-text="user.name"></span>
                                                <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="user.username + ' - ' + user.role_label"></span>
                                            </span>
                                        </label>
                                        <label x-show="isGroupUserSelected(user.id)"
                                               class="ml-7 mt-2 flex cursor-pointer items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                            <input type="checkbox"
                                                   class="pkg-check"
                                                   :checked="isGroupAdminUser(user.id)"
                                                   @change="toggleGroupAdminUser(user.id)">
                                            Admin Grup
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </section>

                        <section class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="mb-3">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Siswa</h4>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pamong hanya melihat siswa sesuai scope aksesnya.</p>
                            </div>
                            <input type="text"
                                   x-model="createGroupSearchSiswa"
                                   class="mb-3 w-full pkg-field text-sm"
                                   placeholder="Cari nama, NIS, atau kelas...">
                            <div class="max-h-72 space-y-1 overflow-y-auto pr-1">
                                <template x-if="filteredGroupSiswa().length === 0">
                                    <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada siswa.</p>
                                </template>
                                <template x-for="siswa in filteredGroupSiswa()" :key="'siswa-' + siswa.id">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <input type="checkbox"
                                               class="pkg-check"
                                               :checked="isGroupSiswaSelected(siswa.id)"
                                               @change="toggleGroupSiswa(siswa.id)">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-medium text-gray-900 dark:text-white" x-text="siswa.nama"></span>
                                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="siswa.nis + ' - ' + (siswa.kelas?.nama || '-')"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </section>
                    </div>
                </div>
            </form>

            <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                <button type="button"
                        @click="closeCreateGroupModal()"
                        class="btn-secondary justify-center">
                    Batal
                </button>
                <button type="button"
                        @click="submitCreateGroup()"
                        :disabled="creatingGroup || loadingGroupEdit || !createGroupForm.name.trim() || selectedGroupMemberCount() === 0"
                        class="btn-primary justify-center disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-text="creatingGroup ? 'Menyimpan...' : (editingGroupId ? 'Simpan Grup' : 'Buat Grup')"></span>
                </button>
            </div>
        </div>
    </div>
</div>
