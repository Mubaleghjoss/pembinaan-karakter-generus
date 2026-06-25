{{-- Kelola Akun Tab Content --}}
<div class="space-y-6" x-init="loadPamongForAccount()">
    <!-- Filters -->
    <div class="pkg-filter-bar">
        <div class="pkg-filter-grid sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Anggota</label>
                    <input type="text" 
                           x-model="accountFilters.search"
                           @input.debounce.300ms="loadPamongForAccount()"
                           placeholder="Username atau email..."
                           class="w-full pkg-field text-sm">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select x-model="accountFilters.status" 
                        @change="loadPamongForAccount()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Bidang</label>
                <select x-model="accountFilters.team_id"
                        @change="loadPamongForAccount()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua Bidang</option>
                    <template x-for="team in availableTeams" :key="'account-team-'+team.id">
                        <option :value="team.id" x-text="team.name"></option>
                    </template>
                </select>
            </div>

            <div class="flex items-end">
                <button @click="loadPamongForAccount()" class="pkg-btn-primary w-full !justify-center !px-4 !py-2.5 text-sm">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="pkg-card-soft border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/30">
        <div class="flex">
            <svg class="h-5 w-5 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Informasi Password</h3>
                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                    Kolom password menampilkan password terakhir yang disimpan setelah akun dibuat, diimpor, direset, atau diubah admin. Jika tertulis <strong>Tidak tersimpan</strong>, reset atau ubah password akun tersebut agar tersinkron dengan login.
                </p>
            </div>
        </div>
    </div>

    <!-- Account Management -->
    <div class="pkg-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kelola Akun Tim PKG</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Reset password, ubah password, dan kelola status akun pamong atau pengurus PKG.</p>
            </div>
            <a href="{{ route('pamong.export-accounts') }}" 
               class="btn-success inline-flex items-center whitespace-nowrap !px-4 !py-2 text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Ekspor Excel
            </a>
        </div>

        <!-- Loading State -->
        <div x-show="accountLoading" class="p-6">
            <div class="animate-pulse space-y-4">
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!accountLoading && accountPamong.length === 0" class="p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada anggota</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tidak ada akun tim yang sesuai dengan filter.</p>
        </div>

        <!-- Table -->
        <div x-show="!accountLoading && accountPamong.length > 0" class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-[980px] divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Anggota</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bidang / Jabatan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Password</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="pamong in accountPamong" :key="pamong.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td data-label="Anggota" class="px-4 py-4 pkg-mobile-main">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-500 to-teal-600 flex items-center justify-center">
                                        <span class="text-white font-semibold text-sm" x-text="pamong.username?.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="pamong.username"></div>
                                            <span class="inline-flex px-2 py-0.5 text-[11px] font-medium rounded-full"
                                                  :class="pamong.role?.name === 'pkg_manager' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'"
                                                  x-text="pamong.role?.name === 'teacher' ? 'Pamong' : (pamong.role?.display_name || 'Pamong')"></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Email" class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400" x-text="pamong.email"></td>
                            <td data-label="Bidang / Jabatan" class="px-4 py-4">
                                <template x-if="pamong.organizational_team">
                                    <div>
                                        <div class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                                             :style="`background:${pamong.organizational_team.color_hex || '#2563EB'}`">
                                            <span x-text="pamong.organizational_team.short_name || pamong.organizational_team.name"></span>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="pamong.organizational_title || 'Anggota bidang'"></div>
                                    </div>
                                </template>
                                <template x-if="!pamong.organizational_team">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Belum ditetapkan</span>
                                </template>
                            </td>
                            <td data-label="Password" class="px-4 py-4">
                                <div class="flex items-center gap-2" x-data="{ showPw: false }">
                                    <code class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded font-mono"
                                          :class="showPw && !pamong.plain_password ? 'text-yellow-700 dark:text-yellow-200' : ''"
                                          x-text="showPw ? (pamong.plain_password || 'Tidak tersimpan') : '********'"></code>
                                    <button @click="showPw = !showPw" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Tampilkan/Sembunyikan">
                                        <svg x-show="!showPw" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPw" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td data-label="Status" class="px-4 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="pamong.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                                      x-text="pamong.status === 'active' ? 'Aktif' : 'Tidak Aktif'"></span>
                            </td>
                            <td data-label="Aksi" class="px-4 py-4 text-right pkg-mobile-actions">
                                <div class="flex items-center justify-end space-x-1">
                                    <button @click="openChangePasswordModal(pamong)" 
                                            class="inline-flex items-center px-2 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800 rounded-lg transition-colors"
                                            title="Ubah Password">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Ubah
                                    </button>
                                    <button @click="resetSinglePassword(pamong)" 
                                            class="inline-flex items-center px-2 py-1.5 text-xs font-medium text-yellow-700 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900 dark:text-yellow-200 dark:hover:bg-yellow-800 rounded-lg transition-colors"
                                            title="Reset Password ke Username">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Reset
                                    </button>
                                    <button @click="toggleAccountStatus(pamong)" 
                                            class="inline-flex items-center px-2 py-1.5 text-xs font-medium rounded-lg transition-colors"
                                            :class="pamong.status === 'active' ? 'text-red-700 bg-red-100 hover:bg-red-200 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800' : 'text-green-700 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:text-green-200 dark:hover:bg-green-800'"
                                            :title="pamong.status === 'active' ? 'Nonaktifkan' : 'Aktifkan'">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path x-show="pamong.status === 'active'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            <path x-show="pamong.status !== 'active'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
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

<!-- Change Password Modal -->
<div x-show="showChangePasswordModal" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" @click="showChangePasswordModal = false"></div>

        <!-- Modal Content -->
        <div class="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ubah Password</h3>
                <button @click="showChangePasswordModal = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="mt-4 space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Mengubah password untuk: <strong x-text="changePasswordPamong?.username"></strong>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password Baru</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" 
                               x-model="newPassword"
                               placeholder="Minimal 6 karakter"
                               class="w-full pkg-field text-sm pr-10">
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Konfirmasi Password</label>
                    <input :type="showPassword ? 'text' : 'password'" 
                           x-model="confirmPassword"
                           placeholder="Ulangi password baru"
                           class="w-full pkg-field text-sm">
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Password akan disimpan dan dapat dilihat oleh admin di halaman ini.
                </p>
            </div>

            <!-- Footer -->
            <div class="mt-6 flex justify-end gap-3">
                <button @click="showChangePasswordModal = false" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                    Batal
                </button>
                <button @click="changePassword()" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    Simpan Password
                </button>
            </div>
        </div>
    </div>
</div>
