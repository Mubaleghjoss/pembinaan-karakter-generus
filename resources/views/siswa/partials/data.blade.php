{{-- Data Siswa Tab Content - This partial contains the main student data table --}}
{{-- The content is rendered inline in the parent view for now --}}
{{-- This file serves as a placeholder for future extraction --}}

<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <!-- Biodata Lengkap -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-green-100 dark:border-green-900 overflow-hidden cursor-pointer" @click="filters.biodata_status = 'complete'; loadStudents()">
            <div class="p-5 flex items-center justify-between hover:bg-green-50/50 dark:hover:bg-green-900/20 transition-colors">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Biodata Lengkap</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $biodataStats['total_lengkap'] ?? 0 }} <span class="text-sm font-normal text-gray-500">Siswa Aktif</span></h3>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/40 rounded-lg text-green-600 dark:text-green-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Biodata Belum Lengkap -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-100 dark:border-red-900 overflow-hidden cursor-pointer" @click="filters.biodata_status = 'incomplete'; loadStudents()">
            <div class="p-5 flex items-center justify-between hover:bg-red-50/50 dark:hover:bg-red-900/20 transition-colors">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Biodata Belum Lengkap</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $biodataStats['total_belum_lengkap'] ?? 0 }} <span class="text-sm font-normal text-gray-500">Siswa Aktif</span></h3>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900/40 rounded-lg text-red-600 dark:text-red-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <!-- Biometrik Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-blue-100 dark:border-blue-900 overflow-hidden">
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Biometrik Terdaftar</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalBiometrik ?? 0 }} <span class="text-sm font-normal text-gray-500">/ {{ $totalSiswaAktif ?? 0 }} Siswa</span></h3>
                </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-lg text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                        </svg>
                    </div>
                </div>
                @if(($totalSiswaAktif ?? 0) > 0)
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: {{ round(($totalBiometrik / $totalSiswaAktif) * 100) }}%"></div>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ round(($totalBiometrik / $totalSiswaAktif) * 100) }}% sudah daftar biometrik</p>
                @endif
                @if(($totalBiometrikLegacy ?? 0) > 0)
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">{{ $totalBiometrikLegacy }} siswa masih memakai credential lama dan perlu daftar ulang</p>
                @endif
            </div>
        </div>

        <!-- Level Distribution Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-purple-100 dark:border-purple-900 overflow-hidden">
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Distribusi Level Siswa</p>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/40 rounded-lg text-purple-600 dark:text-purple-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-2">
                    @forelse($levelDistribution as $lvl)
                    <div class="flex items-center gap-2">
                        <span class="text-sm w-8 text-center" title="{{ $lvl->nama }}">{{ $lvl->badge_icon_url }}</span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between text-xs mb-0.5">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $lvl->nama }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $lvl->siswa_count }} siswa</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full transition-all duration-300" style="width: {{ ($totalSiswaAktif > 0) ? round(($lvl->siswa_count / $totalSiswaAktif) * 100) : 0 }}%; background-color: {{ $lvl->warna ?? '#8B5CF6' }}"></div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-gray-400 italic">Belum ada data level.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="pkg-card">
        <div class="p-4 sm:p-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cari Siswa</label>
                    <input type="text" 
                           x-model="filters.search"
                           @input.debounce.300ms="loadStudents()"
                           placeholder="Nama atau NIS..."
                           class="w-full pkg-field text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelas</label>
                    <select x-model="filters.kelas_id" 
                            @change="loadStudents()"
                            class="w-full pkg-field text-sm">
                        <option value="">Semua Kelas</option>
                        <template x-for="kelas in classes" :key="kelas.id">
                            <option :value="kelas.id" x-text="kelas.nama"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Siswa</label>
                    <select x-model="filters.status" 
                            @change="loadStudents()"
                            class="w-full pkg-field text-sm">
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                        <option value="">Semua Status</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Biodata</label>
                    <select x-model="filters.biodata_status" 
                            @change="loadStudents()"
                            class="w-full pkg-field text-sm">
                        <option value="">Semua Biodata</option>
                        <option value="complete">Lengkap</option>
                        <option value="incomplete">Belum Lengkap</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="pkg-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Daftar Siswa</h3>
            <div class="flex gap-2">
                @if(auth()->user()->hasPamongCrudPermission('siswa', 'import'))
                <button @click="openImportModal()" 
                        class="inline-flex items-center px-3 py-1.5 border border-green-600 rounded-lg text-sm font-medium text-green-600 bg-white hover:bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import
                </button>
                @endif
                @if(auth()->user()->hasPamongCrudPermission('siswa', 'create'))
                <button @click="openAddModal()" 
                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Tambah
                </button>
                @endif
            </div>
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
        <div x-show="!loading && students.length === 0" class="p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada siswa</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai dengan menambahkan siswa baru.</p>
        </div>

        <!-- Table -->
        <div x-show="!loading && students.length > 0" class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Siswa</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kelas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Biodata</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Biometrik</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Login Terakhir</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="student in students" :key="student.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
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
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="student.nis"></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Kelas" class="px-4 py-4 text-sm text-gray-900 dark:text-white" x-text="student.kelas?.nama || '-'"></td>
                            <td data-label="Biodata" class="px-4 py-4 text-sm">
                                <template x-if="student.is_biodata_complete === true">
                                    <span class="inline-flex items-center text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 px-2 py-1 rounded-md">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Lengkap
                                    </span>
                                </template>
                                <template x-if="student.is_biodata_complete === false">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-md w-fit">
                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            Belum Lengkap
                                        </span>
                                        <span class="text-xs text-amber-700 dark:text-amber-500 leading-tight block mt-1">
                                            Kurang: <span x-text="(student.missing_biodata_fields || []).join(', ')"></span>
                                        </span>
                                    </div>
                                </template>
                            </td>
                            <td data-label="Biometrik" class="px-4 py-4 text-sm">
                                <template x-if="student.biometric_status === 'active'">
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        Aktif
                                    </span>
                                </template>
                                <template x-if="student.biometric_status === 'legacy'">
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                        Legacy
                                    </span>
                                </template>
                                <template x-if="student.biometric_status === 'inactive'">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        Belum aktif
                                    </span>
                                </template>
                            </td>
                            <td data-label="Login Terakhir" class="px-4 py-4 text-sm">
                                <template x-if="student.last_login_at">
                                    <div>
                                        <span class="text-gray-700 dark:text-gray-300 text-xs" x-text="new Date(student.last_login_at).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})"></span>
                                        <span class="block text-gray-400 dark:text-gray-500 text-xs" x-text="new Date(student.last_login_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})"></span>
                                    </div>
                                </template>
                                <template x-if="!student.last_login_at">
                                    <span class="text-gray-400 dark:text-gray-500 text-xs italic">Belum pernah</span>
                                </template>
                            </td>
                            <td data-label="Status" class="px-4 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="student.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                                      x-text="student.is_active ? 'Aktif' : 'Tidak Aktif'"></span>
                            </td>
                            <td data-label="Aksi" class="px-4 py-4 text-right pkg-mobile-actions">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="viewBiodata(student)" class="text-purple-600 hover:text-purple-900 dark:text-purple-400" title="Info Biodata">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    @if(auth()->user()->hasPamongCrudPermission('siswa', 'edit'))
                                    <button @click="editStudent(student)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    @endif
                                    @if(auth()->user()->hasPamongCrudPermission('siswa', 'delete'))
                                    <button @click="deleteStudent(student)" class="text-red-600 hover:text-red-900 dark:text-red-400" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    @endif
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

