{{-- Default Tim PKG Permissions Settings --}}
<div class="pkg-card">
    <div class="p-6">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Hak Akses Default Tim PKG</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Atur menu dan akses default yang akan diberikan kepada pamong atau pengurus PKG baru. Pengaturan ini akan diterapkan secara otomatis saat akun operasional baru dibuat.
            </p>
        </div>

        <!-- Info Box -->
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/30">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Catatan Penting</h3>
                    <div class="mt-1 space-y-1 text-sm text-blue-700 dark:text-blue-300">
                        <p>Pengaturan ini hanya berlaku untuk pamong atau pengurus PKG baru yang dibuat setelah perubahan disimpan.</p>
                        <p>Untuk mengubah hak akses akun yang sudah ada, gunakan menu Tim PKG ke Hak Akses.</p>
                        <p>Admin tetap memiliki akses penuh ke semua fitur.</p>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('settings.update.permissions') }}" method="POST" x-data="permissionsForm()">
            @csrf
            @method('PUT')

            <div class="mb-8">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Paket Izin Bidang</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pilih paket siap pakai untuk mempercepat pengaturan default Pengurus PKG, lalu sesuaikan bila perlu.</p>
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($permissionPresets as $presetKey => $preset)
                    <button type="button"
                            @click="applyPreset('{{ $presetKey }}')"
                            class="rounded-xl border border-gray-200 bg-white p-4 text-left transition hover:border-blue-300 hover:bg-blue-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700 dark:hover:bg-blue-900/20">
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $preset['label'] }}</h4>
                            <span class="rounded-full bg-blue-100 px-2 py-1 text-[11px] font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">Terapkan</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $preset['description'] }}</p>
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Menu Permissions -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Akses Menu</h3>
                    <button type="button" @click="toggleAllMenus()" 
                            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                        <span x-text="selectedMenus.length === Object.keys(availableMenus).length ? 'Hapus Semua' : 'Pilih Semua'"></span>
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <template x-for="(label, key) in availableMenus" :key="key">
                        <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors border border-gray-200 dark:border-gray-600"
                               :class="selectedMenus.includes(key) ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/30' : ''">
                            <input type="checkbox" 
                                   name="menu_permissions[]"
                                   :value="key"
                                   x-model="selectedMenus"
                                   class="pkg-check">
                            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300" x-text="label"></span>
                        </label>
                    </template>
                </div>
            </div>

            <!-- CRUD Permissions -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Akses CRUD per Modul (Opsional)</h3>
                    <button type="button" @click="toggleAllCrudModules()" 
                            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                        <span x-text="isAllCrudSelected() ? 'Hapus Semua' : 'Pilih Semua'"></span>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <!-- Siswa CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Siswa</h5>
                            <button type="button" @click="toggleModuleCrud('siswa')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('siswa') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <template x-for="op in ['view', 'create', 'edit', 'delete', 'import', 'export']" :key="'siswa-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[siswa][]'"
                                           :value="op"
                                           :checked="isCrudSelected('siswa', op)"
                                           @change="toggleCrud('siswa', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Presensi CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Presensi</h5>
                            <button type="button" @click="toggleModuleCrud('presensi')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('presensi') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <template x-for="op in ['view', 'create', 'edit', 'delete', 'verify', 'export']" :key="'presensi-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[presensi][]'"
                                           :value="op"
                                           :checked="isCrudSelected('presensi', op)"
                                           @change="toggleCrud('presensi', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Input Manual CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Input Manual</h5>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Semua Siswa memberi akses cari dan isi presensi semua murid PKG.</p>
                            </div>
                            <button type="button" @click="toggleModuleCrud('manual_attendance')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('manual_attendance') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <template x-for="op in availableCrud.manual_attendance || ['view', 'create', 'all_students']" :key="'manual_attendance-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[manual_attendance][]'"
                                           :value="op"
                                           :checked="isCrudSelected('manual_attendance', op)"
                                           @change="toggleCrud('manual_attendance', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Jadwal Presensi</h5>
                            <button type="button" @click="toggleModuleCrud('jadwal')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('jadwal') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="op in ['view', 'create', 'edit', 'delete']" :key="'jadwal-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[jadwal][]'"
                                           :value="op"
                                           :checked="isCrudSelected('jadwal', op)"
                                           @change="toggleCrud('jadwal', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Materi CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Materi</h5>
                            <button type="button" @click="toggleModuleCrud('materi')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('materi') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="op in availableCrud.materi || ['view']" :key="'materi-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[materi][]'"
                                           :value="op"
                                           :checked="isCrudSelected('materi', op)"
                                           @change="toggleCrud('materi', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Tugas PKG CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Tugas PKG</h5>
                            <button type="button" @click="toggleModuleCrud('pr')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('pr') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                            <template x-for="op in ['view', 'create', 'edit', 'delete', 'verify']" :key="'pr-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[pr][]'"
                                           :value="op"
                                           :checked="isCrudSelected('pr', op)"
                                           @change="toggleCrud('pr', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Berita CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Berita</h5>
                            <button type="button" @click="toggleModuleCrud('berita')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('berita') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="op in ['view', 'create', 'edit', 'delete']" :key="'berita-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[berita][]'"
                                           :value="op"
                                           :checked="isCrudSelected('berita', op)"
                                           @change="toggleCrud('berita', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Gamifikasi CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Gamifikasi</h5>
                            <button type="button" @click="toggleModuleCrud('gamification')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('gamification') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            <template x-for="op in ['view', 'create', 'edit', 'delete', 'export', 'adjust', 'reset']" :key="'gamification-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[gamification][]'"
                                           :value="op"
                                           :checked="isCrudSelected('gamification', op)"
                                           @change="toggleCrud('gamification', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Game CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Game 29 Karakter</h5>
                            <button type="button" @click="toggleModuleCrud('game')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('game') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="op in ['view', 'create', 'edit', 'delete']" :key="'game-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[game][]'"
                                           :value="op"
                                           :checked="isCrudSelected('game', op)"
                                           @change="toggleCrud('game', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Chat CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Chat</h5>
                            <button type="button" @click="toggleModuleCrud('chat')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('chat') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="op in ['view', 'send', 'broadcast']" :key="'chat-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[chat][]'"
                                           :value="op"
                                           :checked="isCrudSelected('chat', op)"
                                           @change="toggleCrud('chat', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Grup Chat</h5>
                            <button type="button" @click="toggleModuleCrud('group_chat')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('group_chat') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="op in ['view', 'create', 'send']" :key="'group_chat-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[group_chat][]'"
                                           :value="op"
                                           :checked="isCrudSelected('group_chat', op)"
                                           @change="toggleCrud('group_chat', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Catatan Rapat</h5>
                            <button type="button" @click="toggleModuleCrud('catatan_rapat')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('catatan_rapat') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="op in ['view', 'create', 'edit', 'delete']" :key="'catatan_rapat-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[catatan_rapat][]'"
                                           :value="op"
                                           :checked="isCrudSelected('catatan_rapat', op)"
                                           @change="toggleCrud('catatan_rapat', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Tracer Karakter CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Tracer Karakter</h5>
                            <button type="button" @click="toggleModuleCrud('tracer_karakter')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('tracer_karakter') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                            <template x-for="op in ['view', 'create', 'edit', 'delete', 'export']" :key="'tracer_karakter-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[tracer_karakter][]'"
                                           :value="op"
                                           :checked="isCrudSelected('tracer_karakter', op)"
                                           @change="toggleCrud('tracer_karakter', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Cek Kehadiran CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Tracer Bacaan Al-Qur'an</h5>
                            <button type="button" @click="toggleModuleCrud('tracer_bacaan_quran')" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('tracer_bacaan_quran') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                            <template x-for="op in availableCrud.tracer_bacaan_quran || ['view', 'create', 'edit', 'verify', 'export']" :key="'tracer_bacaan_quran-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" :name="'crud_permissions[tracer_bacaan_quran][]'" :value="op" :checked="isCrudSelected('tracer_bacaan_quran', op)" @change="toggleCrud('tracer_bacaan_quran', op)" class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Cek Kehadiran CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Cek Kehadiran</h5>
                            <button type="button" @click="toggleModuleCrud('cek_kehadiran')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('cek_kehadiran') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <template x-for="op in ['view', 'delete']" :key="'cek_kehadiran-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[cek_kehadiran][]'"
                                           :value="op"
                                           :checked="isCrudSelected('cek_kehadiran', op)"
                                           @change="toggleCrud('cek_kehadiran', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Laporan Penyaksian CRUD -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Laporan Penyaksian</h5>
                            <button type="button" @click="toggleModuleCrud('laporan_penyaksian')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('laporan_penyaksian') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="op in ['view', 'tindak_lanjut', 'delete']" :key="'laporan_penyaksian-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[laporan_penyaksian][]'"
                                           :value="op"
                                           :checked="isCrudSelected('laporan_penyaksian', op)"
                                           @change="toggleCrud('laporan_penyaksian', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Ekspor Data</h5>
                            <button type="button" @click="toggleModuleCrud('export')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                <span x-text="isModuleFullySelected('export') ? 'Hapus' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="op in ['view', 'presensi', 'siswa', 'leaderboard']" :key="'export-'+op">
                                <label class="flex items-center p-2 bg-white dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" 
                                           :name="'crud_permissions[export][]'"
                                           :value="op"
                                           :checked="isCrudSelected('export', op)"
                                           @change="toggleCrud('export', op)"
                                           class="pkg-check">
                                    <span class="ml-1 text-xs text-gray-700 dark:text-gray-300" x-text="getCrudLabel(op)"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pkg-page-actions justify-end border-t border-gray-200 pt-6 dark:border-gray-700">
                <button type="submit" 
                        class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function permissionsForm() {
    return {
        availableMenus: @json($availableMenus),
        availableCrud: @json($availableCrud),
        presets: @json($permissionPresets),
        selectedMenus: @json($defaultPermissions['menu_permissions']),
        crudPermissions: @json($defaultPermissions['crud_permissions']),
        
        getCrudLabel(operation) {
            const labels = {
                'view': 'Lihat',
                'create': 'Tambah',
                'edit': 'Edit',
                'delete': 'Hapus',
                'import': 'Impor',
                'export': 'Ekspor',
                'verify': 'Verifikasi',
                'send': 'Kirim',
                'broadcast': 'Siaran',
                'tindak_lanjut': 'Tindak Lanjut',
                'adjust': 'Atur',
                'reset': 'Reset',
                'presensi': 'Presensi',
                'siswa': 'Data Siswa',
                'leaderboard': 'Peringkat',
                'all_students': 'Semua Siswa'
            };
            return labels[operation] || operation;
        },

        applyPreset(presetKey) {
            const preset = this.presets[presetKey];
            if (!preset) {
                return;
            }

            this.selectedMenus = [...(preset.menu_permissions || [])];
            this.crudPermissions = JSON.parse(JSON.stringify(preset.crud_permissions || {}));
        },
        
        toggleAllMenus() {
            if (this.selectedMenus.length === Object.keys(this.availableMenus).length) {
                this.selectedMenus = [];
            } else {
                this.selectedMenus = Object.keys(this.availableMenus);
            }
        },
        
        isCrudSelected(module, operation) {
            return this.crudPermissions[module]?.includes(operation) || false;
        },
        
        toggleCrud(module, operation) {
            if (!this.crudPermissions[module]) {
                this.crudPermissions[module] = [];
            }
            const index = this.crudPermissions[module].indexOf(operation);
            if (index > -1) {
                this.crudPermissions[module].splice(index, 1);
            } else {
                this.crudPermissions[module].push(operation);
            }
        },
        
        isModuleFullySelected(module) {
            const ops = this.availableCrud[module] || [];
            return ops.every(op => this.isCrudSelected(module, op));
        },
        
        toggleModuleCrud(module) {
            const ops = this.availableCrud[module] || [];
            
            if (this.isModuleFullySelected(module)) {
                this.crudPermissions[module] = [];
            } else {
                this.crudPermissions[module] = [...ops];
            }
        },
        
        isAllCrudSelected() {
            const modules = Object.keys(this.availableCrud);
            return modules.every(module => this.isModuleFullySelected(module));
        },
        
        toggleAllCrudModules() {
            if (this.isAllCrudSelected()) {
                this.crudPermissions = {};
            } else {
                this.crudPermissions = Object.fromEntries(
                    Object.entries(this.availableCrud).map(([module, ops]) => [module, [...ops]])
                );
            }
        }
    };
}
</script>
@endpush
