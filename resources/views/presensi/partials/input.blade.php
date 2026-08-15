{{-- Input Manual Presensi Tab Content --}}
<div class="space-y-3 sm:space-y-4">
    <x-collapsible-section
        title="Input Presensi Manual"
        description="Input izin, sakit, kehadiran, atau koreksi data siswa."
        section-id="manual-attendance"
        compact
        data-manual-input-panel
    >
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ ($canAccessAllManualAttendanceStudents ?? false) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200' }}">
                {{ ($canAccessAllManualAttendanceStudents ?? false) ? 'Cakupan: semua siswa' : 'Cakupan: siswa binaan' }}
            </p>
            <button type="button" @click="openQrScanner()"
                    class="pkg-btn-primary inline-flex items-center px-4 py-2 text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    Scan QR
            </button>
        </div>

        <form @submit.prevent="submitManualPresensi()">
            <div class="pkg-filter-grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Pilih Siswa -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Siswa</label>
                    <div class="relative">
                        <input type="text" 
                               x-model="manualInput.searchSiswa"
                               @input.debounce.300ms="searchSiswaForManual()"
                               placeholder="Cari nama atau NIS..."
                               class="w-full pkg-field">
                        
                        <!-- Search Results Dropdown -->
                        <div x-show="manualInput.searchResults.length > 0" 
                             class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="siswa in manualInput.searchResults" :key="siswa.id">
                                <button type="button"
                                        @click="selectSiswaForManual(siswa)"
                                        class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center">
                                    <template x-if="siswa.foto_url">
                                        <img class="h-8 w-8 rounded-full object-cover mr-3" :src="siswa.foto_url" :alt="siswa.nama" x-on:error="siswa.foto_url = null">
                                    </template>
                                    <template x-if="!siswa.foto_url">
                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center mr-3">
                                            <span class="text-white font-semibold text-xs" x-text="siswa.nama?.charAt(0).toUpperCase()"></span>
                                        </div>
                                    </template>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="siswa.nama"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="siswa.nis + ' - ' + (siswa.school_grade_label || 'Kelas belum dikonfirmasi')"></div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Selected Siswa -->
                    <div x-show="manualInput.selectedSiswa" class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <template x-if="manualInput.selectedSiswa?.foto_url">
                                    <img class="h-10 w-10 rounded-full object-cover mr-3" :src="manualInput.selectedSiswa.foto_url" :alt="manualInput.selectedSiswa?.nama" x-on:error="manualInput.selectedSiswa.foto_url = null">
                                </template>
                                <template x-if="!manualInput.selectedSiswa?.foto_url">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center mr-3">
                                        <span class="text-white font-semibold" x-text="manualInput.selectedSiswa?.nama?.charAt(0).toUpperCase()"></span>
                                    </div>
                                </template>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white" x-text="manualInput.selectedSiswa?.nama"></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="manualInput.selectedSiswa?.nis + ' - ' + (manualInput.selectedSiswa?.school_grade_label || 'Kelas belum dikonfirmasi')"></div>
                                </div>
                            </div>
                            <button type="button" @click="manualInput.selectedSiswa = null; manualInput.searchSiswa = ''" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tanggal -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal</label>
                    <input type="date" x-model="manualInput.tanggal"
                           class="w-full pkg-field">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Kehadiran</label>
                    <select x-model="manualInput.status"
                            data-manual-status
                            class="w-full pkg-field">
                        <option value="">Pilih Status</option>
                        <option value="hadir">Hadir</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="alpha">Tidak Hadir (Alpha)</option>
                    </select>
                </div>

                <!-- Jam Masuk (optional) -->
                <div x-show="manualInput.status === 'hadir' || manualInput.status === 'terlambat'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jam Masuk</label>
                    <input type="time" x-model="manualInput.jam_masuk"
                           class="w-full pkg-field">
                </div>

                <!-- Keterangan -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Keterangan (Opsional)</label>
                    <textarea x-model="manualInput.keterangan" rows="3"
                              placeholder="Tambahkan keterangan jika diperlukan..."
                              class="w-full pkg-field"></textarea>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap justify-end gap-2">
                <button type="button" @click="resetManualInput()" 
                        class="pkg-btn-secondary px-4 py-2">
                    Reset
                </button>
                <button type="submit" 
                        :disabled="!manualInput.selectedSiswa || !manualInput.status"
                        class="btn-success px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    Simpan Presensi
                </button>
            </div>
        </form>
    </x-collapsible-section>

    @if($canAccessAllManualAttendanceStudents ?? false)
    <x-collapsible-section title="Input Presensi Massal" description="Pilih Pamong, lalu persempit dengan kelas sekolah bila diperlukan. Siswa multi-Pamong otomatis dideduplikasi." compact>
        <div class="pkg-filter-grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pamong</label>
                <select x-model="bulkInput.pamong_id" class="w-full pkg-field">
                    <option value="">Semua Pamong</option>
                    <template x-for="pamong in pamongOptions" :key="pamong.id"><option :value="pamong.id" x-text="pamong.name"></option></template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelas Sekolah</label>
                <select x-model="bulkInput.school_grade" class="w-full pkg-field">
                    <option value="">Semua Kelas Sekolah</option>
                    <template x-for="(label, value) in schoolGrades" :key="value">
                        <option :value="value" x-text="label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal</label>
                <input type="date" x-model="bulkInput.tanggal"
                       class="w-full pkg-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Default</label>
                <select x-model="bulkInput.status"
                        class="w-full pkg-field">
                    <option value="hadir">Hadir</option>
                    <option value="alpha">Tidak Hadir</option>
                </select>
            </div>
        </div>
        
        <div class="mt-4">
            <button @click="submitBulkPresensi()" 
                    :disabled="!bulkInput.school_grade && !bulkInput.pamong_id"
                    class="pkg-btn-primary px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed">
                Input Presensi Generus
            </button>
        </div>
    </x-collapsible-section>

    <x-collapsible-section title="Impor dari Excel" description="Impor data presensi siswa dan pamong secara massal." compact>
        @if(session('warning'))
            <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ session('warning') }}</p>
                        @if(session('import_recap_url'))
                            <a href="{{ session('import_recap_url') }}" class="mt-2 inline-flex text-sm font-semibold text-yellow-800 underline dark:text-yellow-200">
                                Lihat rekap sesuai rentang impor
                            </a>
                        @endif
                        @if(session('import_errors'))
                            <ul class="mt-2 text-xs text-yellow-600 dark:text-yellow-400 list-disc list-inside">
                                @foreach(array_slice(session('import_errors'), 0, 5) as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                                @if(count(session('import_errors')) > 5)
                                    <li>... dan {{ count(session('import_errors')) - 5 }} error lainnya</li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Download Template -->
            <div class="pkg-card-soft rounded-2xl p-4">
                <h5 class="font-medium text-gray-900 dark:text-white mb-2">1. Unduh Template</h5>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Download template Excel berisi sheet siswa dan pamong. Ubah tanggal dan status sesuai data historis.</p>
                <a href="{{ route('presensi.import.template') }}" 
                   class="btn-success inline-flex items-center px-4 py-2 text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh Template
                </a>
            </div>

            <!-- Upload File -->
            <div class="pkg-card-soft rounded-2xl p-4">
                <h5 class="font-medium text-gray-900 dark:text-white mb-2">2. Upload File</h5>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Upload file Excel yang sudah diisi. Sheet siswa akan masuk ke presensi siswa, sheet pamong akan masuk ke presensi pamong.</p>
                <form action="{{ route('presensi.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                           class="block w-full text-sm text-gray-500 dark:text-gray-400
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-lg file:border-0
                                  file:text-sm file:font-medium
                                  file:bg-blue-50 file:text-blue-700
                                  dark:file:bg-blue-900/30 dark:file:text-blue-300
                                  hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50">
                    @php($pointPeriods = $pointPeriods ?? collect())
                    @if($pointPeriods->isNotEmpty())
                    <select name="period_id" class="w-full pkg-field text-sm">
                        <option value="">Tanpa periode poin khusus</option>
                        @foreach($pointPeriods as $period)
                            <option value="{{ $period->id }}">{{ $period->name }} ({{ $period->start_date?->format('d M Y') }} - {{ $period->end_date?->format('d M Y') ?? 'Berjalan' }})</option>
                        @endforeach
                    </select>
                    @endif
                    <input type="text" name="source_label" maxlength="120" placeholder="Sumber data, misalnya Presensi Ramadan 2025" class="w-full pkg-field text-sm">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="award_points" value="1" class="rounded border-gray-300 text-indigo-600">
                        Tambahkan poin siswa dari data impor ini
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="mark_verified" value="1" checked class="rounded border-gray-300 text-indigo-600">
                        Tandai langsung sebagai terverifikasi
                    </label>
                    <button type="submit" 
                            class="pkg-btn-primary inline-flex items-center px-4 py-2 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Impor Data
                    </button>
                </form>
            </div>
        </div>

        <!-- Format Info -->
        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <h5 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Format Kolom Excel:</h5>
            <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
                <li><strong>tipe</strong> - siswa atau pamong</li>
                <li><strong>nis</strong> - NIS siswa untuk sheet siswa</li>
                <li><strong>username/email</strong> - identitas pamong untuk sheet pamong</li>
                <li><strong>tanggal</strong> - Format: YYYY-MM-DD atau DD/MM/YYYY (wajib)</li>
                <li><strong>status</strong> - hadir, terlambat, izin, sakit, alpha</li>
                <li><strong>jam_masuk</strong> - Format: HH:MM (opsional)</li>
                <li><strong>jam_keluar</strong> - Format: HH:MM (opsional)</li>
                <li><strong>keterangan</strong> - Catatan tambahan (opsional)</li>
            </ul>
            <p class="mt-3 text-xs text-blue-700 dark:text-blue-300">Jika Anda mengimpor presensi lama, pilih periode poin agar monitoring bulanan tetap rapi. Jika hanya ingin memindahkan data tanpa mengubah poin, biarkan opsi tambah poin tidak dicentang.</p>
        </div>
    </x-collapsible-section>
    @endif
</div>
