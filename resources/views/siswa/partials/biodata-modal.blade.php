{{-- Biodata Modal - Must be inside x-data scope --}}
<div x-show="showBiodataModal" 
     x-cloak
     @click.self="showBiodataModal = false"
     class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="pkg-modal max-w-4xl w-full max-h-[90vh] overflow-hidden"
         @click.stop
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-600 to-blue-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Biodata Siswa</h3>
                        <p class="text-sm text-purple-100" x-text="biodataStudent?.nama"></p>
                    </div>
                </div>
                <button @click="showBiodataModal = false" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Content -->
        <div class="px-6 py-4 max-h-[calc(90vh-180px)] overflow-y-auto">
            <!-- Info Box -->
            <div class="mb-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <p class="ml-3 text-sm text-blue-700 dark:text-blue-300">
                        Semua field bersifat <strong>opsional</strong>. Anda hanya perlu mengisi/memperbaiki field yang diperlukan.
                    </p>
                </div>
            </div>
            
            <!-- Photo Section -->
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <template x-if="biodataStudent?.foto_url">
                        <img :src="biodataStudent.foto_url" :alt="biodataStudent.nama" class="w-32 h-32 rounded-full object-cover border-4 border-purple-200 dark:border-purple-800">
                    </template>
                    <template x-if="!biodataStudent?.foto_url">
                        <div class="w-32 h-32 rounded-full bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center border-4 border-purple-200 dark:border-purple-800">
                            <span class="text-white font-bold text-4xl" x-text="biodataStudent?.nama?.charAt(0).toUpperCase()"></span>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Form -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data Pribadi -->
                <div class="col-span-2">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Data Pribadi
                    </h4>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Lengkap</label>
                    <input type="text" 
                           x-model="biodataForm.nama"
                           :disabled="!biodataEditing"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">NIS</label>
                    <input type="text" 
                           x-model="biodataForm.nis"
                           :disabled="!biodataEditing"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jenis Kelamin</label>
                    <select x-model="biodataForm.jenis_kelamin"
                            :disabled="!biodataEditing"
                            class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                        <option value="">Pilih Jenis Kelamin</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Lahir</label>
                    <input type="date" 
                           x-model="biodataForm.tanggal_lahir"
                           :disabled="!biodataEditing"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelas</label>
                    <select x-model="biodataForm.kelas_id"
                            :disabled="!biodataEditing"
                            class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                        <option value="">Pilih Kelas</option>
                        <template x-for="kelas in classes" :key="kelas.id">
                            <option :value="kelas.id" x-text="kelas.nama"></option>
                        </template>
                    </select>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelompok</label>
                    <select x-model="biodataForm.kelompok"
                            :disabled="!biodataEditing"
                            class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                        <option value="">Pilih Kelompok</option>
                        @foreach(($kelompokOptions ?? []) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Data Wali -->
                <div class="col-span-2 mt-4">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Data Wali/Orang Tua
                    </h4>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Wali</label>
                    <input type="text" 
                           x-model="biodataForm.nama_wali"
                           :disabled="!biodataEditing"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">No. Telepon Wali</label>
                    <input type="text" 
                           x-model="biodataForm.phone_wali"
                           :disabled="!biodataEditing"
                           placeholder="08xxxxxxxxxx"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Wali</label>
                    <input type="email" 
                           x-model="biodataForm.email_wali"
                           :disabled="!biodataEditing"
                           placeholder="email@example.com"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <template x-if="biodataStudent?.last_login_at">
                        <span>Terakhir login: <span x-text="new Date(biodataStudent.last_login_at).toLocaleString('id-ID')"></span></span>
                    </template>
                    <template x-if="!biodataStudent?.last_login_at">
                        <span class="italic">Belum pernah login</span>
                    </template>
                </div>
                <div class="flex gap-2">
                    <template x-if="!biodataEditing">
                        @if(auth()->user()->hasPamongCrudPermission('siswa', 'edit'))
                        <button @click="enableBiodataEdit()" 
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Biodata
                        </button>
                        @endif
                        <button @click="showBiodataModal = false" 
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                            Tutup
                        </button>
                    </template>
                    <template x-if="biodataEditing">
                        <button @click="saveBiodata()" 
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan
                        </button>
                        <button @click="cancelBiodataEdit()" 
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                            Batal
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>


