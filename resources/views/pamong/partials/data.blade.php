{{-- Data Pamong Tab Content --}}
<div class="space-y-6">
    <!-- Filters -->
    <div class="pkg-filter-bar">
        <div class="pkg-filter-grid sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Anggota</label>
                    <input type="text" 
                           x-model="filters.search"
                           @input.debounce.300ms="loadPamong()"
                           placeholder="Username atau email..."
                           class="w-full pkg-field text-sm">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select x-model="filters.status" 
                        @change="loadPamong()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Bidang</label>
                <select x-model="filters.team_id"
                        @change="loadPamong()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua Bidang</option>
                    <template x-for="team in availableTeams" :key="'data-team-'+team.id">
                        <option :value="team.id" x-text="team.name"></option>
                    </template>
                </select>
            </div>

            <div class="flex items-end">
                <button @click="loadPamong()" class="pkg-btn-primary w-full !justify-center !px-4 !py-2.5 text-sm">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Pamong Table -->
    <div class="pkg-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Daftar Tim PKG</h3>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-6">
            <div class="animate-pulse space-y-4">
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && pamongList.length === 0" class="p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada anggota</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada data pamong atau pengurus PKG yang tersedia.</p>
        </div>

        <!-- Table -->
        <div x-show="!loading && pamongList.length > 0" class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-[920px] divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pamong</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bidang</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Login Terakhir</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Jumlah Siswa</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="pamong in pamongList" :key="pamong.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td data-label="Pamong" class="px-4 py-4 pkg-mobile-main">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-500 to-teal-600 flex items-center justify-center">
                                            <span class="text-white font-semibold text-sm" x-text="pamong.username?.charAt(0).toUpperCase()"></span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="pamong.username"></div>
                                            <span class="inline-flex px-2 py-0.5 text-[11px] font-medium rounded-full"
                                                  :class="pamong.role?.name === 'pkg_manager' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'"
                                                  x-text="pamong.role?.name === 'teacher' ? 'Pamong' : (pamong.role?.display_name || 'Pamong')"></span>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="pamong.email"></div>
                                        <div x-show="pamong.organizational_title" class="text-xs text-gray-500 dark:text-gray-400" x-text="pamong.organizational_title"></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Status" class="px-4 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full w-fit"
                                          :class="pamong.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                                          x-text="pamong.status === 'active' ? 'Aktif' : 'Tidak Aktif'"></span>
                                    <span x-show="pamong.pamong_permission?.is_excluded" 
                                          class="inline-flex px-2 py-1 text-xs font-semibold rounded-full w-fit bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        Full Access
                                    </span>
                                </div>
                            </td>
                            <td data-label="Bidang" class="px-4 py-4">
                                <template x-if="pamong.organizational_team">
                                    <div>
                                        <div class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                                             :style="`background:${pamong.organizational_team.color_hex || '#2563EB'}`">
                                            <span x-text="pamong.organizational_team.short_name || pamong.organizational_team.name"></span>
                                        </div>
                                        <div x-show="pamong.organizational_title" class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="pamong.organizational_title"></div>
                                    </div>
                                </template>
                                <template x-if="!pamong.organizational_team">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Belum ditetapkan</span>
                                </template>
                            </td>
                            <td data-label="Login Terakhir" class="px-4 py-4">
                                <template x-if="pamong.last_login_at">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full" :class="(new Date() - new Date(pamong.last_login_at)) < 1800000 ? 'bg-green-500' : 'bg-gray-400'"></span>
                                            <span class="text-xs font-medium" :class="(new Date() - new Date(pamong.last_login_at)) < 1800000 ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'" x-text="(new Date() - new Date(pamong.last_login_at)) < 1800000 ? 'Online' : 'Offline'"></span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="new Date(pamong.last_login_at).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'})"></div>
                                    </div>
                                </template>
                                <template x-if="!pamong.last_login_at">
                                    <span class="text-xs text-red-500 font-medium">Belum Login</span>
                                </template>
                            </td>
                            <td data-label="Jumlah Siswa" class="px-4 py-4">
                                <span class="px-3 py-1 text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full"
                                      x-text="pamong.role?.name === 'teacher' ? ((pamong.assigned_students_count || 0) + ' siswa') : 'Tidak pakai siswa binaan'"></span>
                            </td>
                            <td data-label="Aksi" class="px-4 py-4 text-right pkg-mobile-actions">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="showDetail(pamong)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400" title="Detail">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <a :href="`/pamong/${pamong.id}/activity-log`" class="text-purple-600 hover:text-purple-900 dark:text-purple-400" title="Log Aktivitas">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>
                                    <button x-show="pamong.role?.name === 'teacher'" @click="openAssignModal(pamong)" class="text-green-600 hover:text-green-900 dark:text-green-400" title="Assign Siswa">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && pagination.last_page > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Menampilkan <span x-text="pagination.from"></span> - <span x-text="pagination.to"></span> dari <span x-text="pagination.total"></span>
                </p>
                <nav class="inline-flex rounded-md shadow-sm -space-x-px">
                    <template x-for="page in Array.from({length: pagination.last_page}, (_, i) => i + 1).slice(Math.max(0, currentPage - 3), currentPage + 2)" :key="page">
                        <button @click="changePage(page)"
                                :class="page === currentPage ? 'bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-900 dark:text-blue-200' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300'"
                                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                x-text="page"></button>
                    </template>
                </nav>
            </div>
        </div>
    </div>
</div>

