{{-- Hak Akses Tab Content --}}
<div class="space-y-6" x-init="loadPamongForPermissions()">
    <!-- Filters -->
    <div class="pkg-filter-bar">
        <div class="pkg-filter-grid sm:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Pamong</label>
                    <input type="text" 
                           x-model="permissionsFilters.search"
                           @input.debounce.300ms="loadPamongForPermissions()"
                           placeholder="Username atau email..."
                           class="w-full pkg-field text-sm">
            </div>

            <div class="flex items-end">
                <button @click="loadPamongForPermissions()" class="pkg-btn-primary w-full !justify-center !px-4 !py-2.5 text-sm">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="pkg-card-soft border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/30">
        <div class="flex">
            <svg class="h-5 w-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Tentang Hak Akses</h3>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    Pilih satu akun pamong lalu klik "Atur Akses" atau tombol "Edit" pada barisnya. 
                    Detail CRUD akan muncul mengikuti menu yang dicentang.
                </p>
            </div>
        </div>
    </div>

    <!-- Permissions Management -->
    <div class="pkg-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kelola Hak Akses Pamong</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atur menu dan fitur yang dapat diakses oleh setiap pamong.</p>
                </div>
                
                <!-- Bulk Actions -->
                <div x-show="selectedPermissions.length > 0" 
                     x-transition
                     class="flex flex-wrap items-center gap-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        <span x-text="selectedPermissions.length"></span> dipilih
                    </span>
                    <button @click="openBulkPermissionsModal()" 
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Atur Akses
                    </button>
                    <button @click="bulkSetFullAccess()" 
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Full Access
                    </button>
                    <button @click="bulkRemoveFullAccess()" 
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Cabut Full Access
                    </button>
                    <button @click="bulkSetDefault()" 
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Kembalikan ke Default
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="permissionsLoading" class="p-6">
            <div class="animate-pulse space-y-4">
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!permissionsLoading && permissionsPamong.length === 0" class="p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada pamong</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tidak ada pamong yang sesuai dengan filter.</p>
        </div>

        <!-- Table -->
        <div x-show="!permissionsLoading && permissionsPamong.length > 0" class="pkg-mobile-table overflow-x-auto">
            <table class="min-w-[860px] divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" 
                                   x-model="selectAllPermissions"
                                   @change="toggleSelectAllPermissions()"
                                   class="pkg-check">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pamong</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status Akses</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Menu Diizinkan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="pamong in permissionsPamong" :key="pamong.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-4" data-label="Pilih">
                                <input type="checkbox" 
                                       :value="pamong.id"
                                       x-model.number="selectedPermissions"
                                       class="pkg-check">
                            </td>
                            <td class="pkg-mobile-main px-4 py-4" data-label="Pamong">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-500 to-teal-600 flex items-center justify-center">
                                        <span class="text-white font-semibold text-sm" x-text="pamong.username?.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="pamong.username"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="pamong.email"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4" data-label="Status akses">
                                <template x-if="pamong.pamong_permission?.is_excluded">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        Full Access
                                    </span>
                                </template>
                                <template x-if="!pamong.pamong_permission?.is_excluded">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        Terbatas
                                    </span>
                                </template>
                            </td>
                            <td class="px-4 py-4" data-label="Menu diizinkan">
                                <template x-if="pamong.pamong_permission?.is_excluded">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Semua menu</span>
                                </template>
                                <template x-if="!pamong.pamong_permission?.is_excluded">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-if="pamong.pamong_permission?.menu_permissions?.length > 0">
                                            <template x-for="menu in pamong.pamong_permission.menu_permissions.slice(0, 3)" :key="menu">
                                                <span class="inline-flex px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300" x-text="availableMenus[menu] || menu"></span>
                                            </template>
                                        </template>
                                        <template x-if="pamong.pamong_permission?.menu_permissions?.length > 3">
                                            <span class="inline-flex px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300" x-text="'+' + (pamong.pamong_permission.menu_permissions.length - 3) + ' lainnya'"></span>
                                        </template>
                                        <template x-if="!pamong.pamong_permission?.menu_permissions?.length">
                                            <span class="text-sm text-gray-400 dark:text-gray-500 italic">Belum diatur</span>
                                        </template>
                                    </div>
                                </template>
                            </td>
                            <td class="pkg-mobile-actions px-4 py-4 text-right" data-label="Aksi">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="toggleExcluded(pamong)" 
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                                            :class="pamong.pamong_permission?.is_excluded ? 'text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' : 'text-orange-700 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-200 dark:hover:bg-orange-800'"
                                            :title="pamong.pamong_permission?.is_excluded ? 'Cabut Full Access' : 'Berikan Full Access'">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        <span x-text="pamong.pamong_permission?.is_excluded ? 'Cabut Full' : 'Full Access'"></span>
                                    </button>
                                    <button @click="editPermissions(pamong)" 
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800 rounded-lg transition-colors"
                                            title="Edit Hak Akses">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

