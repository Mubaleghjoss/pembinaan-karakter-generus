{{-- Akun Siswa Tab Content --}}
<div class="space-y-6">
    <!-- Header -->
    <div class="pkg-card p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kelola Akun Siswa</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Reset password dan kelola akses akun siswa</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <a href="{{ route('siswa.export-accounts') }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Ekspor Excel
                </a>
                <button @click="openBulkResetModal()" 
                        class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    Reset Password Massal
                </button>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="pkg-card p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelas</label>
                <select x-model="accountFilters.kelas_id" 
                        @change="loadStudentsForAccount()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua Kelas</option>
                    <template x-for="kelas in classes" :key="kelas.id">
                        <option :value="kelas.id" x-text="kelas.nama"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cari Siswa</label>
                <input type="text" 
                       x-model="accountFilters.search"
                       @input.debounce.300ms="loadStudentsForAccount()"
                       placeholder="Nama atau NIS..."
                       class="w-full pkg-field text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select x-model="accountFilters.status" 
                        @change="loadStudentsForAccount()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Tidak Aktif</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Account Table -->
    <div class="pkg-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Daftar Akun</h3>
                <div class="flex items-center gap-2">
                    <input type="checkbox" 
                           x-model="selectAllAccounts" 
                           @change="toggleSelectAllAccounts()"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Pilih Semua</span>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div x-show="accountLoading" class="p-6">
            <div class="animate-pulse space-y-4">
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!accountLoading && accountStudents.length === 0" class="p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada siswa</h3>
        </div>

        <!-- Table -->
        <div x-show="!accountLoading && accountStudents.length > 0" class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-12"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Siswa</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Username</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Password</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kelas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="student in accountStudents" :key="student.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td data-label="Pilih" class="px-4 py-4">
                                <input type="checkbox" 
                                       :value="student.id" 
                                       x-model="selectedAccounts"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td data-label="Siswa" class="px-4 py-4 pkg-mobile-main">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <template x-if="student.foto_url">
                                            <img class="h-10 w-10 rounded-full object-cover" :src="student.foto_url" :alt="student.nama">
                                        </template>
                                        <template x-if="!student.foto_url">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                                <span class="text-white font-semibold text-sm" x-text="student.nama?.charAt(0).toUpperCase()"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="student.nama"></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Username" class="px-4 py-4">
                                <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono text-gray-800 dark:text-gray-200" x-text="student.nis"></code>
                            </td>
                            <td data-label="Password" class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <code class="px-2 py-1 bg-amber-50 dark:bg-amber-900/30 rounded text-sm font-mono text-amber-800 dark:text-amber-200" 
                                          x-text="student.password_plain || student.nis"></code>
                                    <button @click="copyToClipboard(student.password_plain || student.nis)" 
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            title="Salin password">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td data-label="Kelas" class="px-4 py-4 text-sm text-gray-900 dark:text-white" x-text="student.kelas?.nama || '-'"></td>
                            <td data-label="Status" class="px-4 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="student.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                                      x-text="student.is_active ? 'Aktif' : 'Tidak Aktif'"></span>
                            </td>
                            <td data-label="Aksi" class="px-4 py-4 text-right pkg-mobile-actions">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="resetSinglePassword(student)" 
                                            class="inline-flex items-center px-2 py-1 border border-amber-600 text-xs rounded text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                                            title="Reset Password">
                                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                        Reset
                                    </button>
                                    <button @click="toggleAccountStatus(student)" 
                                            class="inline-flex items-center px-2 py-1 border text-xs rounded"
                                            :class="student.is_active ? 'border-red-600 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20' : 'border-green-600 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20'"
                                            :title="student.is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" x-show="student.is_active" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" x-show="!student.is_active" />
                                        </svg>
                                        <span x-text="student.is_active ? 'Nonaktifkan' : 'Aktifkan'"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Bulk Actions -->
        <div x-show="selectedAccounts.length > 0" 
             class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-blue-50 dark:bg-blue-900/20">
            <div class="flex items-center justify-between">
                <span class="text-sm text-blue-700 dark:text-blue-300">
                    <span x-text="selectedAccounts.length"></span> siswa dipilih
                </span>
                <div class="flex gap-2">
                    <button @click="resetSelectedPasswords()" 
                            class="inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded-lg">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Reset Password
                    </button>
                    <button @click="selectedAccounts = []" 
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

