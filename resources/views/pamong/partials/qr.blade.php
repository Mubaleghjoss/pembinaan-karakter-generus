{{-- Generate QR Tab Content --}}
<div class="space-y-6" x-init="loadPamongForQR()">
    <!-- Filters -->
    <div class="pkg-card">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Filter Pamong</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cari Pamong</label>
                    <input type="text" 
                           x-model="qrFilters.search"
                           @input.debounce.300ms="loadPamongForQR()"
                           placeholder="Username atau email..."
                           class="w-full pkg-field text-sm">
                </div>

                <div class="flex items-end">
                    <button @click="loadPamongForQR()" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 text-sm">
                        <svg class="w-4 h-4 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Generation -->
    <div class="pkg-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kartu ID Pamong</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Unduh kartu ID pamong aktif dengan QR presensi yang siap dipakai.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button @click="downloadAllPamongCards()"
                        class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh Semua Kartu
                </button>
                <button @click="downloadSelectedQR()" 
                        :disabled="selectedQrPamong.length === 0"
                        :class="selectedQrPamong.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                        class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh Terpilih (<span x-text="selectedQrPamong.length"></span>)
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="qrLoading" class="p-6">
            <div class="animate-pulse space-y-4">
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!qrLoading && qrPamong.length === 0" class="p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada pamong aktif</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tidak ada pamong aktif yang tersedia untuk kartu ID.</p>
        </div>

        <!-- Table -->
        <div x-show="!qrLoading && qrPamong.length > 0" class="overflow-x-auto">
            <table class="min-w-[760px] divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" 
                                   x-model="selectAllQr"
                                   @change="toggleSelectAllQr()"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pamong</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="pamong in qrPamong" :key="pamong.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-4">
                                <input type="checkbox" 
                                       :value="pamong.id"
                                       x-model="selectedQrPamong"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-500 to-teal-600 flex items-center justify-center">
                                        <span class="text-white font-semibold text-sm" x-text="pamong.username?.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="pamong.name || pamong.username"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="pamong.username"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400" x-text="pamong.email"></td>
                            <td class="px-4 py-4 text-right">
                                <button @click="downloadQR(pamong)" class="text-purple-600 hover:text-purple-900 dark:text-purple-400" title="Lihat Kartu QR">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

