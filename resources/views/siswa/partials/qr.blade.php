{{-- QR Code Generation Tab Content --}}
<div class="space-y-6">
    <!-- Header -->
    <div class="pkg-card p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kartu ID Siswa</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Unduh kartu ID dengan QR presensi yang sudah tersimpan.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <button @click="downloadAllQR()" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh Semua Kartu
                </button>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="pkg-card p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelas Sekolah</label>
                <select x-model="qrFilters.school_grade"
                        @change="loadStudentsForQR()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua Kelas Sekolah</option>
                    <template x-for="(label, value) in schoolGrades" :key="value">
                        <option :value="value" x-text="label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cari Siswa</label>
                <input type="text" 
                       x-model="qrFilters.search"
                       @input.debounce.300ms="loadStudentsForQR()"
                       placeholder="Nama atau NIS..."
                       class="w-full pkg-field text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ukuran QR</label>
                <select x-model="qrSize"
                        class="w-full pkg-field text-sm">
                    <option value="200">Kecil (200px)</option>
                    <option value="300">Sedang (300px)</option>
                    <option value="400">Besar (400px)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- QR Grid -->
    <div class="pkg-card p-6">
        <!-- Loading -->
        <div x-show="qrLoading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Memuat data...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!qrLoading && qrStudents.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada siswa</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tidak ada siswa yang sesuai filter.</p>
        </div>

        <!-- Grid -->
        <div x-show="!qrLoading && qrStudents.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <template x-for="student in qrStudents" :key="student.id">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <!-- Avatar -->
                    <div class="mb-3">
                        <template x-if="student.foto_url">
                            <img class="h-12 w-12 rounded-full mx-auto object-cover" :src="student.foto_url" :alt="student.nama">
                        </template>
                        <template x-if="!student.foto_url">
                            <div class="h-12 w-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center mx-auto">
                                <span class="text-white font-bold" x-text="student.nama?.charAt(0).toUpperCase()"></span>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Info -->
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="student.nama"></h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="student.nis"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="student.school_grade_label || 'Kelas belum dikonfirmasi'"></p>
                    
                    <!-- Actions -->
                    <div class="mt-3 flex gap-2 justify-center">
                        <button @click="previewQR(student)" 
                                class="inline-flex items-center px-2 py-1 border border-gray-300 dark:border-gray-600 text-xs rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Preview
                        </button>
                        <button @click="downloadQR(student)" 
                                class="inline-flex items-center px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded">
                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Unduh
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- QR Preview Modal -->
<div x-show="showQRModal" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
     @click.self="showQRModal = false"
     style="display: none;">
    <div class="bg-white dark:bg-gray-800 rounded-lg max-w-sm w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">QR Code</h3>
            <button @click="showQRModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div x-show="selectedStudent" class="text-center">
            <div class="mb-4">
                <template x-if="selectedStudent?.foto_url">
                    <img class="h-16 w-16 rounded-full mx-auto object-cover mb-2" :src="selectedStudent.foto_url" :alt="selectedStudent.nama">
                </template>
                <template x-if="!selectedStudent?.foto_url">
                    <div class="h-16 w-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center mx-auto mb-2">
                        <span class="text-white font-bold text-xl" x-text="selectedStudent?.nama?.charAt(0).toUpperCase()"></span>
                    </div>
                </template>
                <h4 class="font-medium text-gray-900 dark:text-white" x-text="selectedStudent?.nama"></h4>
                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedStudent?.nis"></p>
            </div>
            
            <!-- QR Code Display -->
            <div id="qr-preview-container" class="flex justify-center mb-4 bg-white p-4 rounded-lg">
                <div x-show="qrPreviewLoading" class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <img x-show="!qrPreviewLoading && qrPreviewUrl" :src="qrPreviewUrl" alt="QR Code" class="max-w-full">
            </div>
            
            <div class="flex gap-3">
                <button @click="showQRModal = false" 
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    Tutup
                </button>
                <button @click="downloadQR(selectedStudent)" 
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    Unduh
                </button>
            </div>
        </div>
    </div>
</div>

