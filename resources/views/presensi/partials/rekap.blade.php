{{-- Rekap Presensi Tab Content --}}
<div class="space-y-3 sm:space-y-4">
    <div x-show="allDates" class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-100">
        <strong>Mode antrean lintas tanggal.</strong>
        Daftar ini menampilkan seluruh presensi belum terverifikasi dari semua tanggal. Tombol
        "Verifikasi Semua Antrean" akan memverifikasi semua data yang sesuai filter.
        <button type="button" @click="allDates = false; loadPresensi()" class="ml-1 font-bold underline">Kembali ke rekap harian</button>
    </div>

    @if(false)
    <x-collapsible-section title="Ringkasan Kehadiran" description="Jumlah kehadiran berdasarkan filter aktif." compact>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-5">
        <div class="pkg-card-soft rounded-2xl p-3 sm:p-4">
            <div class="flex items-center">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gray-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div class="ml-2 sm:ml-3">
                        <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
                        <p class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white" x-text="stats.total || 0"></p>
                    </div>
            </div>
        </div>

        <div class="pkg-card-soft rounded-2xl p-3 sm:p-4">
            <div class="flex items-center">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-green-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-2 sm:ml-3">
                        <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">Hadir</p>
                        <p class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white" x-text="stats.hadir || 0"></p>
                    </div>
            </div>
        </div>

        <div class="pkg-card-soft rounded-2xl p-3 sm:p-4">
            <div class="flex items-center">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-2 sm:ml-3">
                        <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">Terlambat</p>
                        <p class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white" x-text="stats.terlambat || 0"></p>
                    </div>
            </div>
        </div>

        <div class="pkg-card-soft rounded-2xl p-3 sm:p-4">
            <div class="flex items-center">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-red-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-2 sm:ml-3">
                        <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">Tidak Hadir</p>
                        <p class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white" x-text="stats.tidak_hadir || 0"></p>
                    </div>
            </div>
        </div>

        <div class="pkg-card-soft col-span-2 rounded-2xl p-3 sm:col-span-1 sm:p-4">
            <div class="flex items-center">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.25-4.5l-.02.01M6.75 6a.75.75 0 01.75-.75h10.5a.75.75 0 01.75.75v10.5a.75.75 0 01-.75.75H7.5a.75.75 0 01-.75-.75V6z" />
                        </svg>
                    </div>
                    <div class="ml-2 sm:ml-3">
                        <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">Terverifikasi</p>
                        <p class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white" x-text="stats.verified || 0"></p>
                    </div>
            </div>
        </div>
    </div>
    </x-collapsible-section>

    @endif

    <x-collapsible-section title="Filter Rekap" description="Atur tanggal, Pamong, kelas sekolah, status, dan verifikasi." compact>
    <div class="pkg-filter-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal</label>
                <input type="date" x-model="filters.tanggal" @change="loadPresensi()"
                       :disabled="allDates"
                       :title="allDates ? 'Tanggal diabaikan dalam mode antrean lintas tanggal' : ''"
                       class="w-full pkg-field text-sm disabled:cursor-not-allowed disabled:opacity-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelas Sekolah</label>
                <select x-model="filters.school_grade" @change="loadPresensi()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua Kelas Sekolah</option>
                    <template x-for="(label, value) in schoolGrades" :key="value">
                        <option :value="value" x-text="label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kelompok</label>
                <select x-model="filters.kelompok" @change="loadPresensi()" class="pkg-field w-full text-sm">
                    <option value="">Semua Kelompok</option>
                    <template x-for="(label, value) in kelompokOptions" :key="value">
                        <option :value="value" x-text="label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select x-model="filters.status" @change="loadPresensi()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua Status</option>
                    <option value="hadir">Hadir</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="alpha">Alpa (Tanpa Keterangan)</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Verifikasi</label>
                <select x-model="filters.verified" @change="loadPresensi()"
                        class="w-full pkg-field text-sm">
                    <option value="">Semua</option>
                    <option value="1">Terverifikasi</option>
                    <option value="0">Belum Verifikasi</option>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="loadPresensi()"
                        class="pkg-btn-primary w-full py-2 px-4 font-medium">
                    <svg class="w-4 h-4 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
            </div>
    </div>
    </x-collapsible-section>

    <div x-show="!allDates">
    <x-collapsible-section title="Ringkasan Kelompok" description="Pusat pemantauan Hadir, Sakit, Izin, Alpa, dan Belum Presensi." :open="true" compact>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ringkasan Kelompok</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Status seluruh Generus pada tanggal terpilih, termasuk yang belum memiliki data kelompok.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pamong</label>
                <select x-model="filters.pamong_id" @change="loadPresensi()" class="w-full pkg-field text-sm">
                    <option value="">Semua Pamong</option>
                    <template x-for="pamong in pamongOptions" :key="pamong.id"><option :value="pamong.id" x-text="pamong.name"></option></template>
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="copyAttendanceSummary()" class="btn-secondary min-h-11 px-3 py-2 text-sm">Salin Semua</button>
                <button type="button" @click="shareAttendanceWhatsApp()" class="btn-success min-h-11 px-3 py-2 text-sm">Bagikan Semua ke WhatsApp</button>
            </div>
        </div>

        <div x-show="groupSummary.length === 0" class="pkg-empty-state">
            <svg class="pkg-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 018 0v2m-4-9a4 4 0 100-8 4 4 0 000 8zM5 21a2 2 0 01-2-2v-1a7 7 0 0114 0v1a2 2 0 01-2 2H5z" />
            </svg>
            <h3 class="pkg-empty-title">Belum ada pembagian kelompok</h3>
            <p class="pkg-empty-copy">Tetapkan kelompok siswa agar rekap harian per wilayah bisa ditampilkan di sini.</p>
        </div>

        <div x-show="groupSummary.length > 0" class="grid gap-4 xl:grid-cols-3">
            <template x-for="group in groupSummary" :key="group.key">
                <div class="pkg-card-soft rounded-3xl border border-gray-200/80 p-5 dark:border-gray-700/80">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white" x-text="group.label"></h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Total <span class="font-semibold" x-text="group.total_siswa"></span> siswa
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-right text-xs text-gray-500 dark:text-gray-400">
                            <div>Hadir <span class="font-semibold text-green-600 dark:text-green-400" x-text="group.hadir_count"></span></div>
                            <div>Sakit <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="group.sakit_count"></span></div>
                            <div>Izin <span class="font-semibold text-blue-600 dark:text-blue-400" x-text="group.izin_count"></span></div>
                            <div>Alpa <span class="font-semibold text-red-600 dark:text-red-400" x-text="group.alpha_count"></span></div>
                            <div class="col-span-2">Belum Presensi <span class="font-semibold" x-text="group.belum_hadir_count"></span></div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div class="rounded-2xl border border-green-200/80 bg-green-50/80 p-4 dark:border-green-900/50 dark:bg-green-950/20">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-green-800 dark:text-green-300">Sudah hadir</p>
                                <span class="rounded-full bg-white/80 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300" x-text="group.hadir_count"></span>
                            </div>
                            <div x-show="group.hadir.length === 0" class="mt-3 text-sm text-green-700/80 dark:text-green-300/80">
                                Belum ada siswa yang hadir dari kelompok ini.
                            </div>
                            <div x-show="group.hadir.length > 0" class="mt-3 space-y-2">
                                <template x-for="student in group.hadir" :key="'hadir-' + group.key + '-' + student.id">
                                    <div class="rounded-2xl bg-white/85 px-3 py-2.5 dark:bg-slate-900/60">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="student.nama"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    <span x-text="student.nis"></span>
                                                    <span x-show="student.kelas"> - <span x-text="student.kelas"></span></span>
                                                </p>
                                            </div>
                                            <div class="flex flex-col items-end gap-2 text-right">
                                                <p class="text-xs font-semibold text-green-700 dark:text-green-300" x-text="student.status_label"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="student.jam_masuk || '-'"></p>
                                                @if($canCreateManualAttendance ?? false)
                                                <button type="button" x-show="!student.has_scan_proof" @click="selectSiswaFromRekap(student)" class="btn-secondary min-h-9 px-2.5 py-1 text-xs">Ubah</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <template x-for="category in attendanceCategories.filter(item => ['sakit', 'izin', 'alpha'].includes(item.key))" :key="category.key">
                            <div x-show="group[category.key].length > 0" class="rounded-2xl border p-4" :class="category.panelClass">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold" :class="category.textClass" x-text="category.label"></p>
                                    <span class="rounded-full bg-white/80 px-2.5 py-1 text-xs font-semibold dark:bg-slate-900/50" :class="category.textClass" x-text="group[category.count]"></span>
                                </div>
                                <div class="mt-3 space-y-2">
                                    <template x-for="student in group[category.key]" :key="category.key + '-' + group.key + '-' + student.id">
                                        <div class="flex min-w-0 items-start justify-between gap-3 rounded-2xl bg-white/85 px-3 py-2.5 dark:bg-slate-900/60">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="student.nama"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400"><span x-text="student.nis"></span><span x-show="student.kelas"> - <span x-text="student.kelas"></span></span></p>
                                            </div>
                                            @if($canCreateManualAttendance ?? false)
                                            <button type="button" @click="selectSiswaFromRekap(student)" class="btn-secondary min-h-9 shrink-0 px-2.5 py-1 text-xs">Ubah</button>
                                            @endif
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="rounded-2xl border border-amber-200/80 bg-amber-50/80 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-amber-900 dark:text-amber-300">Belum hadir</p>
                                <span class="rounded-full bg-white/80 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300" x-text="group.belum_hadir_count"></span>
                            </div>
                            <div x-show="group.belum_hadir.length === 0" class="mt-3 text-sm text-amber-800/80 dark:text-amber-300/80">
                                Semua siswa pada kelompok ini sudah hadir.
                            </div>
                            <div x-show="group.belum_hadir.length > 0" class="mt-3 space-y-2">
                                <template x-for="student in group.belum_hadir" :key="'belum-' + group.key + '-' + student.id">
                                    <div class="rounded-2xl bg-white/85 px-3 py-2.5 dark:bg-slate-900/60">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="student.nama"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    <span x-text="student.nis"></span>
                                                    <span x-show="student.kelas"> - <span x-text="student.kelas"></span></span>
                                                </p>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/50 dark:text-amber-300" x-text="student.status_label"></span>
                                                @if($canCreateManualAttendance ?? false)
                                                <button type="button"
                                                        @click="selectSiswaFromRekap(student)"
                                                        class="btn-secondary min-h-9 px-2.5 py-1 text-xs">
                                                    Input/Ubah
                                                </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 border-t border-gray-200/80 pt-4 dark:border-gray-700/80">
                        <button type="button" @click="copyAttendanceSummary(group.key)" class="btn-secondary min-h-11 px-3 py-2 text-sm">Salin Teks</button>
                        <button type="button" @click="shareAttendanceWhatsApp(group.key)" class="btn-success min-h-11 px-3 py-2 text-sm">Bagikan WA</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
    </x-collapsible-section>

    </div>

    <x-collapsible-section title="Data Presensi Harian" description="Daftar rinci, bukti, verifikasi, dan koreksi status." compact>
        <div class="flex flex-col gap-3 border-b border-gray-200 pb-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Data Presensi</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Aksi mengikuti filter tanggal, kelas, status, dan verifikasi yang aktif.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        @click="bulkVerifyAttendance()"
                        :disabled="bulkVerifying || presensi.length === 0"
                        class="btn-success px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-show="!bulkVerifying" x-text="allDates ? 'Verifikasi Semua Antrean' : 'Verifikasi Semua'"></span>
                    <span x-show="bulkVerifying">Memproses...</span>
                </button>
                <a :href="exportUrl()" class="btn-secondary px-3 py-2 text-sm">
                    Unduh Excel
                </a>
            </div>
        </div>

        <!-- Loading -->
        <div x-show="loading" class="p-6">
            <div class="animate-pulse space-y-4">
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && presensi.length === 0" class="pkg-empty-state">
            <svg class="pkg-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="pkg-empty-title">Tidak ada data presensi</h3>
            <p class="pkg-empty-copy">Coba ubah tanggal, kelas, status, atau filter verifikasi untuk melihat hasil lain.</p>
        </div>

        <!-- Table -->
        <div x-show="!loading && presensi.length > 0" class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Siswa</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Jam Masuk</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bukti</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Verifikasi</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="item in presensi" :key="item.id">
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td data-label="Siswa" class="px-4 py-4 pkg-mobile-main">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <template x-if="item.siswa?.foto_url">
                                            <img class="h-10 w-10 rounded-full object-cover" :src="item.siswa.foto_url" :alt="item.siswa?.nama" x-on:error="item.siswa.foto_url = null">
                                        </template>
                                        <template x-if="!item.siswa?.foto_url">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                                <span class="text-white font-semibold text-sm" x-text="item.siswa?.nama?.charAt(0).toUpperCase()"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="item.siswa?.nama"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="item.siswa?.nis + ' - ' + (item.siswa?.school_grade_label || 'Kelas belum dikonfirmasi')"></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Tanggal" class="px-4 py-4 text-sm text-gray-900 dark:text-white" x-text="item.tanggal"></td>
                            <td data-label="Jam Masuk" class="px-4 py-4 text-sm text-gray-900 dark:text-white" x-text="item.jam_masuk || '-'"></td>
                            <td data-label="Status" class="px-4 py-4">
                                <span class="pkg-status-badge"
                                      :class="statusBadgeClass(item.status)"
                                      x-text="statusLabel(item.status)"></span>
                            </td>
                            <td data-label="Bukti" class="px-4 py-4">
                                <template x-if="item.face_proof">
                                    <div class="flex min-w-[190px] items-center gap-3">
                                        <a :href="item.face_proof.proof_url" target="_blank" rel="noopener" class="block h-12 w-12 overflow-hidden rounded-xl border border-emerald-200 dark:border-emerald-900">
                                            <img :src="item.face_proof.proof_url" alt="Bukti scan wajah" class="h-full w-full object-cover">
                                        </a>
                                        <div class="space-y-1">
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-200">
                                                Scan Wajah
                                            </span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <span x-text="item.face_proof.similarity_percent ?? '-'"></span>% cocok
                                                <span> - </span>
                                                <span x-text="Math.round(item.face_proof.distance_meters || 0)"></span> m
                                            </p>
                                        </div>
                                    </div>
                                </template>
                                <span x-show="!item.face_proof" class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            </td>
                            <td data-label="Verifikasi" class="px-4 py-4">
                                <span class="pkg-status-badge"
                                      :class="item.is_verified ? 'pkg-status-success' : 'pkg-status-neutral'"
                                      x-text="item.is_verified ? 'Terverifikasi' : 'Belum'"></span>
                            </td>
                            <td data-label="Aksi" class="px-4 py-4 text-right pkg-mobile-actions">
                                <div class="flex items-center justify-end space-x-2">
                                    <button x-show="!item.is_verified" @click="verifyAttendance(item)" 
                                            class="rounded-lg p-1.5 text-blue-600 transition hover:bg-blue-50 hover:text-blue-900 dark:text-blue-400 dark:hover:bg-blue-900/20" title="Verifikasi">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    @if($canEditPresensi ?? false)
                                    <button type="button" @click="editPresensi(item)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700" title="Koreksi presensi" aria-label="Koreksi presensi">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
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
    </x-collapsible-section>

    <div id="laporan-periode" @click.capture="loadReportPanel('period')">
        <x-collapsible-section title="Laporan Periode Siswa/Pamong" description="Laporan rentang tanggal dimuat hanya saat diperlukan." :open="request('panel') === 'laporan-periode'" compact>
            <div data-report-panel="period">
                <div x-show="!reportPanels.period.loaded" class="pkg-card-soft flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Buka rekap periode untuk siswa, Pamong, dan pengurus tanpa meninggalkan halaman Presensi.</p>
                    <button type="button" @click="loadReportPanel('period')" :disabled="reportPanels.period.loading" class="btn-secondary min-h-11 shrink-0 px-4 py-2">
                        <span x-show="!reportPanels.period.loading">Muat Laporan Periode</span><span x-show="reportPanels.period.loading">Memuat...</span>
                    </button>
                </div>
                <p x-show="reportPanels.period.error" class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950/30 dark:text-red-300" x-text="reportPanels.period.error"></p>
                <div x-show="reportPanels.period.loaded" x-html="reportPanels.period.html"></div>
            </div>
        </x-collapsible-section>
    </div>

    <div id="rekap-generus" @click.capture="loadReportPanel('generus')">
        <x-collapsible-section title="Rekap Generus Tugas/RPP" description="Ringkasan Tugas PKG, kehadiran, dan target RPP dimuat saat dibuka." :open="request('panel') === 'rekap-generus'" compact>
            <div data-report-panel="generus">
                <div x-show="!reportPanels.generus.loaded" class="pkg-card-soft flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Lihat performa Generus per kelompok dalam panel yang ringkas.</p>
                    <button type="button" @click="loadReportPanel('generus')" :disabled="reportPanels.generus.loading" class="btn-secondary min-h-11 shrink-0 px-4 py-2">
                        <span x-show="!reportPanels.generus.loading">Muat Rekap Generus</span><span x-show="reportPanels.generus.loading">Memuat...</span>
                    </button>
                </div>
                <p x-show="reportPanels.generus.error" class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950/30 dark:text-red-300" x-text="reportPanels.generus.error"></p>
                <div x-show="reportPanels.generus.loaded" x-html="reportPanels.generus.html"></div>
            </div>
        </x-collapsible-section>
    </div>
</div>
