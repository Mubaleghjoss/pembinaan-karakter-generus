{{-- Chat Pribadi Tab Content --}}
<div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-4 lg:gap-6">
    <!-- Contact List -->
    <div class="pkg-card min-w-0 overflow-hidden lg:col-span-1" :class="selectedSiswa ? 'hidden lg:block' : 'block'">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white">Daftar Siswa</h2>
        </div>

        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="pkg-filter-grid">
                <div>
                    <label for="pribadi-search" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Cari</label>
                    <input
                        id="pribadi-search"
                        type="text"
                        x-model.debounce.250ms="pribadiSearch"
                        class="pkg-field text-sm"
                        placeholder="Nama atau NIS"
                    >
                </div>
                <div>
                    <label for="pribadi-kelas" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Kelas</label>
                    <select id="pribadi-kelas" x-model="pribadiKelas" class="pkg-field text-sm">
                        <option value="">Semua kelas</option>
                        <template x-for="kelas in kelasOptions()" :key="kelas.id">
                            <option :value="String(kelas.id)" x-text="kelas.nama"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="max-h-96 overflow-y-auto">
            <template x-if="siswaList.length > 0">
                <template x-for="siswa in filteredSiswaList()" :key="siswa.id">
                    <button @click="selectSiswa(siswa)"
                            :class="selectedSiswa?.id === siswa.id ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="w-full p-4 flex items-center gap-3 text-left transition-colors">
                        <div class="w-10 h-10 rounded-full bg-purple-500 flex items-center justify-center text-white font-semibold relative">
                            <span x-text="siswa.nama?.charAt(0).toUpperCase()"></span>
                            <template x-if="getUnreadCount(siswa.id) > 0">
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
                                      x-text="getUnreadCount(siswa.id) > 99 ? '99+' : getUnreadCount(siswa.id)"></span>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate" x-text="siswa.nama"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="(siswa.kelas?.nama || '-') + ' | ' + siswa.nis"></p>
                        </div>
                    </button>
                </template>
            </template>
            <template x-if="siswaList.length === 0">
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <p>Belum ada siswa aktif</p>
                </div>
            </template>
            <template x-if="siswaList.length > 0 && filteredSiswaList().length === 0">
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <p>Tidak ada siswa yang cocok dengan filter</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="pkg-card pkg-portal-mobile-chat min-w-0 flex-col lg:col-span-3 lg:h-[500px]" :class="selectedSiswa ? 'flex' : 'hidden lg:flex'">
        <!-- Chat Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <button type="button" @click="closePribadiConversation()" class="btn-secondary !h-10 !w-10 !p-0 lg:hidden" aria-label="Kembali ke daftar siswa">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <template x-if="selectedSiswa">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-purple-500 flex items-center justify-center text-white font-semibold">
                        <span x-text="selectedSiswa.nama?.charAt(0).toUpperCase()"></span>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white" x-text="selectedSiswa.nama"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="(selectedSiswa.kelas?.nama || '-') + ' | ' + selectedSiswa.nis"></p>
                    </div>
                </div>
            </template>
            <template x-if="!selectedSiswa">
                <p class="text-gray-500 dark:text-gray-400">Pilih siswa untuk memulai chat</p>
            </template>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" id="pribadi-messages-container">
            <template x-if="!selectedSiswa">
                <div class="h-full flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p>Pilih siswa untuk memulai percakapan</p>
                    </div>
                </div>
            </template>
            <template x-if="pribadiLoading">
                <div class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            </template>
            <template x-for="(msg, index) in pribadiMessages" :key="msg.id">
                <div class="space-y-2">
                    <template x-if="shouldShowDateSeparator(pribadiMessages, index)">
                        <div class="flex justify-center">
                            <span class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" x-text="messageDateLabel(msg)"></span>
                        </div>
                    </template>
                    <div :class="msg.is_mine ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.is_mine ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'"
                             class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg">
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
            <form @submit.prevent="sendPribadiMessage()" class="flex gap-2 items-end" data-no-csrf-handler>
                <label class="cursor-pointer px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                       :class="!selectedSiswa ? 'opacity-50 cursor-not-allowed' : ''">
                    <input type="file" accept="image/*" @change="handlePribadiImage" class="hidden" :disabled="!selectedSiswa" x-ref="pribadiImageInput">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </label>
                <textarea x-model="pribadiNewMessage" :disabled="!selectedSiswa" rows="1"
                          @keydown="if($event.key === 'Enter' && !$event.shiftKey) { $event.preventDefault(); sendPribadiMessage(); }"
                          class="flex-1 pkg-field resize-none"
                          placeholder="Ketik pesan..."
                          style="min-height: 38px; max-height: 120px;"></textarea>
                <button type="submit" :disabled="!selectedSiswa || (!pribadiNewMessage.trim() && !pribadiSelectedImage)"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
            <div x-show="pribadiSelectedImage" class="mt-2 relative inline-block">
                <img :src="pribadiImagePreview" class="h-20 rounded-lg">
                <button @click="clearPribadiImage()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">&times;</button>
            </div>
        </div>
    </div>
</div>


