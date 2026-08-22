{{-- Pamong Modals --}}

<!-- Detail Modal -->
<div x-show="showDetailModal" 
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
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" @click="showDetailModal = false"></div>

        <!-- Modal Content -->
        <div class="relative inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Pamong</h3>
                <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="mt-4" x-show="selectedPamong">
                <!-- Pamong Info -->
                <div class="flex items-center mb-6">
                    <div class="h-16 w-16 rounded-full bg-gradient-to-br from-green-500 to-teal-600 flex items-center justify-center">
                        <span class="text-white font-bold text-2xl" x-text="selectedPamong?.username?.charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-xl font-semibold text-gray-900 dark:text-white" x-text="selectedPamong?.username"></h4>
                        <p class="text-gray-500 dark:text-gray-400" x-text="selectedPamong?.email"></p>
                        <p x-show="selectedPamong?.organizational_team || selectedPamong?.organizational_title"
                           class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span x-text="selectedPamong?.organizational_team?.name || 'Belum ada bidang'"></span>
                            <span x-show="selectedPamong?.organizational_title">· <span x-text="selectedPamong?.organizational_title"></span></span>
                        </p>
                        <div class="flex gap-2 mt-2">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="selectedPamong?.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                                  x-text="selectedPamong?.status === 'active' ? 'Aktif' : 'Tidak Aktif'"></span>
                            <span x-show="selectedPamong?.pamong_permission?.is_excluded" 
                                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                Full Access
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Assigned Students -->
                <div>
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Siswa yang Ditugaskan (<span x-text="assignedStudents.length"></span>)
                    </h5>
                    
                    <div x-show="assignedStudents.length === 0" class="text-center py-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-gray-500 dark:text-gray-400">Belum ada siswa yang ditugaskan</p>
                    </div>

                    <div x-show="assignedStudents.length > 0" class="max-h-64 overflow-y-auto space-y-2">
                        <template x-for="student in assignedStudents" :key="student.id">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                        <span class="text-white text-xs font-medium" x-text="student.nama?.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="student.nama"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="student.nis + ' - ' + (student.kelas?.nama || '-')"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 flex justify-end gap-3">
                <button @click="showDetailModal = false" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                    Tutup
                </button>
                <button @click="showDetailModal = false; openAssignModal(selectedPamong)" 
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                    Kelola Penugasan
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Permissions Editor Modal -->
<div x-show="showBulkPermissionsModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="flex min-h-screen items-center justify-center px-4 py-6 text-center sm:p-6">
        <div class="fixed inset-0 bg-gray-500/75 transition-opacity dark:bg-gray-900/75" @click="showBulkPermissionsModal = false"></div>

        <div class="relative inline-block w-full max-w-5xl overflow-hidden rounded-xl bg-white text-left align-middle shadow-xl transition-all dark:bg-gray-800"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Atur Akses Pamong</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold" x-text="permissionEditorPamong?.name || permissionEditorPamong?.username || '-'"></span>
                            <span class="text-gray-400">/</span>
                            <span x-text="permissionEditorPamong?.username || '-'"></span>
                        </p>
                    </div>
                    <button @click="showBulkPermissionsModal = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="max-h-[72vh] overflow-y-auto px-5 py-5">
                <div class="mb-5 grid gap-3 md:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status Akses</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="permissionStatusLabel(permissionEditorPamong)"></p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700 md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Menu Aktif</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <template x-if="selectedMenuLabels().length === 0">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Belum ada menu dipilih.</span>
                            </template>
                            <template x-for="(label, index) in selectedMenuLabels().slice(0, 8)" :key="label + '-' + index">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200" x-text="label"></span>
                            </template>
                            <span x-show="selectedMenuLabels().length > 8"
                                  class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                  x-text="'+' + (selectedMenuLabels().length - 8) + ' menu'"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-100">
                    Untuk akun pamong yang boleh mengisi presensi semua murid PKG, centang menu <strong>Input Manual</strong>, lalu di detailnya pilih <strong>Semua Siswa</strong>.
                </div>

                <template x-if="Object.keys(permissionPresets).length > 0">
                    <div class="mb-5 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Paket Cepat</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Klik satu paket untuk mengisi menu &amp; detail izin otomatis. Masih bisa disesuaikan sebelum disimpan.</p>
                            </div>
                            <span x-show="activeBulkPresetKey()"
                                  class="whitespace-nowrap rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200"
                                  x-text="'Aktif: ' + (permissionPresets[activeBulkPresetKey()]?.label || '')"></span>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            <template x-for="(preset, key) in permissionPresets" :key="key">
                                <button type="button"
                                        @click="applyBulkPreset(key)"
                                        class="rounded-lg border px-3 py-2.5 text-left transition"
                                        :class="activeBulkPresetKey() === key ? 'border-emerald-300 bg-emerald-50 ring-2 ring-emerald-200 dark:border-emerald-700 dark:bg-emerald-900/20 dark:ring-emerald-900/40' : 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700 dark:hover:bg-blue-900/20'">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="preset.label"></span>
                                        <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-medium"
                                              :class="activeBulkPresetKey() === key ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200'"
                                              x-text="activeBulkPresetKey() === key ? 'Dipakai' : 'Terapkan'"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="preset.description"></p>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="grid gap-5 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Akses Menu</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Menu yang dicentang akan muncul untuk akun ini.</p>
                            </div>
                            <button type="button"
                                    @click="toggleAllMenus()"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                <span x-text="bulkMenuPermissions.length === Object.keys(availableMenus).length ? 'Hapus Semua' : 'Pilih Semua'"></span>
                            </button>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                            <template x-for="(label, key) in availableMenus" :key="key">
                                <label class="flex cursor-pointer items-center justify-between rounded-lg border px-3 py-2.5 transition"
                                       :class="isMenuSelected(key) ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20' : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700'">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <input type="checkbox"
                                               :checked="isMenuSelected(key)"
                                               @change="toggleMenuPermission(key, $event.target.checked)"
                                               class="pkg-check">
                                        <span class="truncate text-sm font-medium text-gray-800 dark:text-gray-100" x-text="label"></span>
                                    </span>
                                    <span x-show="getCrudModulesForMenu(key).length > 0" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-300">Detail</span>
                                </label>
                            </template>
                        </div>
                    </section>

                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Detail Izin</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Hanya modul dari menu yang dicentang yang ditampilkan.</p>
                            </div>
                            <button type="button"
                                    x-show="visibleCrudModules().length > 0"
                                    @click="toggleAllCrudModules()"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                <span x-text="isAllCrudSelected() ? 'Hapus Semua' : 'Pilih Semua'"></span>
                            </button>
                        </div>

                        <div x-show="visibleCrudModules().length === 0" class="pkg-empty-state py-10">
                            <p class="pkg-empty-title">Belum ada detail izin</p>
                            <p class="pkg-empty-copy">Centang menu di sebelah kiri untuk melihat pilihan CRUD yang terkait.</p>
                        </div>

                        <div class="space-y-3" x-show="visibleCrudModules().length > 0">
                            <template x-for="module in visibleCrudModules()" :key="module.key">
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div>
                                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white" x-text="module.label"></h5>
                                            <p x-show="module.key === 'manual_attendance'" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Opsi Semua Siswa memberi akses cari dan isi presensi semua murid PKG.
                                            </p>
                                        </div>
                                        <button type="button"
                                                @click="toggleModuleCrud(module.key)"
                                                class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                            <span x-text="isModuleFullySelected(module.key) ? 'Hapus' : 'Pilih Semua'"></span>
                                        </button>
                                    </div>

                                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                        <template x-for="op in module.operations" :key="module.key + '-' + op">
                                            <label class="flex cursor-pointer items-center rounded-lg border px-3 py-2 transition"
                                                   :class="isCrudSelected(module.key, op) ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/20' : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700'">
                                                <input type="checkbox"
                                                       :checked="isCrudSelected(module.key, op)"
                                                       @change="toggleCrud(module.key, op)"
                                                       class="pkg-check">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-200" x-text="getCrudLabel(op)"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                <button type="button"
                        @click="showBulkPermissionsModal = false"
                        class="btn-secondary text-sm !px-4 !py-2">
                    Batal
                </button>
                <button type="button"
                        @click="saveBulkPermissions()"
                        class="btn-primary text-sm !px-4 !py-2">
                    Simpan Akses
                </button>
            </div>
        </div>
    </div>
</div>
