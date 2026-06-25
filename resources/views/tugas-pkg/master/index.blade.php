@extends('layouts.app')

@section('title', 'Buat Tugas PKG')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="bulkManager()">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Master Tugas PKG</h1>
            <p class="pkg-page-subheading">Buat, ubah, dan atur tugas PKG beserta aturan bukti, poin, dan periode aktifnya.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('tugas-pkg.index') }}" class="btn-secondary px-4 py-2 text-sm">Lihat Tugas Aktif</a>
            <a href="{{ route('tugas-pkg.verification') }}" class="btn-primary px-4 py-2 text-sm">Verifikasi Tugas</a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(($expiredDeactivatedCount ?? 0) > 0)
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
        {{ $expiredDeactivatedCount }} tugas yang periode selesainya sudah lewat otomatis dibuat nonaktif.
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
        <div class="flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <h3 class="font-semibold text-sm">Gagal Menyimpan Perubahan</h3>
                <ul class="mt-1 list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="pkg-card p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Tugas</div>
            <div class="mt-1 text-3xl font-black text-gray-900 dark:text-white">{{ $taskSummary['total'] ?? 0 }}</div>
        </div>
        <div class="pkg-card p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Aktif</div>
            <div class="mt-1 text-3xl font-black text-green-600">{{ $taskSummary['active'] ?? 0 }}</div>
        </div>
        <div class="pkg-card p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Nonaktif</div>
            <div class="mt-1 text-3xl font-black text-red-600">{{ $taskSummary['inactive'] ?? 0 }}</div>
        </div>
        <div class="pkg-card p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Periode Lewat</div>
            <div class="mt-1 text-3xl font-black text-amber-600">{{ $taskSummary['expired'] ?? 0 }}</div>
        </div>
        <div class="pkg-card p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Dengan Bukti</div>
            <div class="mt-1 text-3xl font-black text-blue-600">{{ $taskSummary['with_proof'] ?? 0 }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Form -->
        <div class="lg:col-span-1">
            <div class="pkg-card p-6 lg:sticky lg:top-6">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Tambah Tugas PKG</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Isi periode selesai jika tugas hanya berlaku sampai tanggal tertentu.</p>
                </div>
                <form action="{{ route('tugas-pkg.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="nama" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nama Tugas <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required
                            class="w-full px-3 py-2 pkg-field"
                            placeholder="Contoh: Jujur, Disiplin">
                    </div>
                    <div>
                        <label for="deskripsi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi" rows="2"
                            class="w-full px-3 py-2 pkg-field"
                            placeholder="Deskripsi tugas...">{{ old('deskripsi') }}</textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="kategori" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Kategori <span class="text-red-500">*</span>
                            </label>
                            <select name="kategori" id="kategori" required class="w-full px-3 py-2 pkg-field">
                                <option value="harian" {{ old('kategori') == 'harian' ? 'selected' : '' }}>Harian</option>
                                <option value="mingguan" {{ old('kategori') == 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                                <option value="bulanan" {{ old('kategori') == 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                            </select>
                        </div>
                    </div>
                    <div x-data="{ jenis: '{{ old('jenis_penyelesaian', 'checklist') }}', allowsPhotoProof: {{ old('allows_photo_proof') ? 'true' : 'false' }}, allowsVoiceProof: {{ old('allows_voice_note_proof') ? 'true' : 'false' }} }">
                        <label for="jenis_penyelesaian" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Jenis Penyelesaian <span class="text-red-500">*</span>
                        </label>
                        <select name="jenis_penyelesaian" id="jenis_penyelesaian" x-model="jenis" required class="w-full px-3 py-2 pkg-field mb-3">
                            <option value="checklist">Checklist Standar</option>
                            <option value="teks">Input Teks (Siswa Mengetik)</option>
                            <option value="klik">Hitungan / Klik (Zikir)</option>
                        </select>
                        
                        <!-- Target Teks (Optional) -->
                        <div x-show="jenis === 'teks'" class="mb-3">
                            <label for="target_teks" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan / Soal Teks</label>
                            <textarea name="target_teks" id="target_teks" rows="2" class="w-full px-3 py-2 pkg-field" placeholder="Contoh: Ketikkan lafal niat wudhu...">{{ old('target_teks') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Siswa akan diminta membalas dengan mengetik sesuatu.</p>
                        </div>
                        
                        <!-- Target Klik (Required if jenis == klik) -->
                        <div x-show="jenis === 'klik'" class="mb-3">
                            <label for="target_klik" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Target Hitungan / Klik <span class="text-red-500" x-show="jenis === 'klik'">*</span>
                            </label>
                            <input type="number" name="target_klik" id="target_klik" value="{{ old('target_klik') }}" min="1" max="10000" class="w-full px-3 py-2 pkg-field" placeholder="Contoh: 1000">
                            <p class="text-xs text-gray-500 mt-1">Siswa akan diharuskan menekan layar/tombol sebanyak target hitungan ini untuk menyelesaikannya.</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200/80 p-4 dark:border-slate-700/80 space-y-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Opsi Bukti Tugas</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Aktifkan salah satu atau keduanya. Setiap opsi bisa punya instruksi dan bonus poin sendiri.</p>
                            </div>

                            <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="allows_photo_proof" value="1" x-model="allowsPhotoProof" class="rounded border-gray-300 text-blue-600">
                                    Izinkan upload bukti foto
                                </label>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Di halaman siswa yang tampil hanya judul <strong>Foto</strong> dan petunjuk yang Anda isi di bawah ini.
                                </p>
                                <div x-show="allowsPhotoProof" class="mt-3 space-y-3">
                                    <div>
                                        <label for="photo_proof_bonus_points" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Bonus poin bukti foto
                                        </label>
                                        <input type="number" name="photo_proof_bonus_points" id="photo_proof_bonus_points" value="{{ old('photo_proof_bonus_points', 0) }}" min="0" max="1000" class="w-full px-3 py-2 pkg-field" placeholder="Contoh: 5">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bonus diberikan saat tugas diverifikasi dan foto memang diunggah.</p>
                                    </div>
                                    <div>
                                        <label for="photo_proof_instruction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Petunjuk foto untuk siswa
                                        </label>
                                        <textarea name="photo_proof_instruction" id="photo_proof_instruction" rows="3" class="w-full px-3 py-2 pkg-field" placeholder="Contoh: Foto harus jelas menampilkan kegiatan yang sedang dilakukan dan wajah siswa.">{{ old('photo_proof_instruction') }}</textarea>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tulis singkat, langsung, dan mudah dipahami. Hindari label teknis seperti aktif, opsional, atau ukuran file.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-800 dark:bg-violet-900/20">
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="allows_voice_note_proof" value="1" x-model="allowsVoiceProof" class="rounded border-gray-300 text-violet-600">
                                    Izinkan upload voice note
                                </label>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Di halaman siswa yang tampil hanya judul <strong>Voice note</strong> dan petunjuk yang Anda isi di bawah ini.
                                </p>
                                <div x-show="allowsVoiceProof" class="mt-3 space-y-3">
                                    <div>
                                        <label for="voice_note_bonus_points" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Bonus poin voice note
                                        </label>
                                        <input type="number" name="voice_note_bonus_points" id="voice_note_bonus_points" value="{{ old('voice_note_bonus_points', 0) }}" min="0" max="1000" class="w-full px-3 py-2 pkg-field" placeholder="Contoh: 3">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bonus diberikan saat tugas diverifikasi dan voice note memang diunggah.</p>
                                    </div>
                                    <div>
                                        <label for="voice_note_instruction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Petunjuk voice note untuk siswa
                                        </label>
                                        <textarea name="voice_note_instruction" id="voice_note_instruction" rows="3" class="w-full px-3 py-2 pkg-field" placeholder="Contoh: Ucapkan hafalan dengan jelas, sebutkan nama di awal rekaman, lalu baca sampai selesai.">{{ old('voice_note_instruction') }}</textarea>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Fokus pada isi rekaman yang harus dikirim siswa. Batas durasi tetap diatur pada field terpisah di bawah.</p>
                                    </div>
                                    <div>
                                        <label for="voice_note_max_seconds" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Batas durasi voice note
                                        </label>
                                        <input type="number" name="voice_note_max_seconds" id="voice_note_max_seconds" value="{{ old('voice_note_max_seconds') }}" min="1" max="1800" class="w-full px-3 py-2 pkg-field" placeholder="Contoh: 60 atau 120">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika durasi tidak dibatasi. Satuan detik.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                                <label for="proof_requirement" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Aturan bukti
                                </label>
                                <select name="proof_requirement" id="proof_requirement" class="w-full px-3 py-2 pkg-field">
                                    <option value="optional" {{ old('proof_requirement', 'optional') === 'optional' ? 'selected' : '' }}>Bukti opsional</option>
                                    <option value="required_any" {{ old('proof_requirement') === 'required_any' ? 'selected' : '' }}>Minimal salah satu bukti wajib</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Jika dipilih wajib, siswa harus mengunggah minimal satu bukti dari opsi yang diaktifkan.</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="poin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Poin <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="poin" id="poin" value="{{ old('poin', 10) }}" min="1" max="1000"
                                class="w-full px-3 py-2 pkg-field"
                                required>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Mulai
                            </label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai') }}"
                                class="w-full px-3 py-2 pkg-field">
                        </div>
                        <div>
                            <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Selesai
                            </label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ old('tanggal_selesai') }}"
                                class="w-full px-3 py-2 pkg-field">
                        </div>
                    </div>
                    <!-- Pin Penghargaan Info -->
                    @if(isset($characterPins) && $characterPins->count() > 0)
                    <div class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                        <h4 class="text-xs font-bold text-purple-700 dark:text-purple-300 mb-2 flex items-center gap-1">
                            PIN Penghargaan Terkait Tugas PKG
                        </h4>
                        @foreach($characterPins as $pin)
                        <div class="flex items-center gap-2 mb-1.5 last:mb-0">
                            <span class="text-lg">{{ $pin->icon_url }}</span>
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $pin->nama }}</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pin->criteria_description }}</p>
                            </div>
                        </div>
                        @endforeach
                        <p class="text-xs text-purple-600 dark:text-purple-400 mt-2 pt-2 border-t border-purple-200 dark:border-purple-700">
                            Catatan: siswa otomatis mendapat pin jika sudah menyelesaikan dan diverifikasi sejumlah tugas PKG di atas.
                        </p>
                    </div>
                    @else
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Pin penghargaan untuk Tugas PKG belum tersedia. <a href="{{ route('admin.gamification.badges') }}" class="text-indigo-600 hover:underline">Buat pin</a>
                        </p>
                    </div>
                    @endif

                    <button type="submit" class="btn-primary w-full px-4 py-2">
                        Tambah Tugas
                    </button>
                </form>
            </div>
        </div>

        <!-- List -->
        <div class="lg:col-span-2">
            <div class="pkg-card">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Daftar Tugas</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola status, periode, poin, dan bukti tugas PKG.</p>
                    </div>
                    <span class="pkg-status-badge pkg-status-neutral">{{ $karakter->total() }} hasil</span>
                </div>
                <!-- Filters -->
                <div class="pkg-filter-bar m-4">
                    <form method="GET" class="pkg-filter-grid grid-cols-1 md:grid-cols-5">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari tugas..."
                            class="px-3 py-2 pkg-field text-sm md:col-span-2">
                        <select name="status" class="px-3 py-2 pkg-field text-sm">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                        <select name="kategori" class="px-3 py-2 pkg-field text-sm">
                            <option value="">Semua Kategori</option>
                            <option value="harian" {{ request('kategori') == 'harian' ? 'selected' : '' }}>Harian</option>
                            <option value="mingguan" {{ request('kategori') == 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                            <option value="bulanan" {{ request('kategori') == 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                        </select>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary flex-1 px-4 py-2 text-sm">Filter</button>
                            <a href="{{ route('tugas-pkg.master') }}" class="btn-secondary px-4 py-2 text-sm">Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Bulk Actions Toolbar -->
                <div x-show="selectedIds.length > 0" x-cloak 
                     class="p-3 bg-blue-50 dark:bg-blue-900/30 border-b border-blue-200 dark:border-blue-800 flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        <span x-text="selectedIds.length"></span> item dipilih
                    </span>
                    <div class="flex flex-wrap items-center gap-2">
                        <button @click="doBulk('aktivasi')" class="btn-success px-3 py-1.5 text-xs">
                            Aktifkan
                        </button>
                        <button @click="doBulk('nonaktifkan')" class="btn-secondary px-3 py-1.5 text-xs">
                            Nonaktifkan
                        </button>
                        <button @click="doBulk('hapus')" class="btn-danger px-3 py-1.5 text-xs">
                            Hapus
                        </button>
                        <div class="flex items-center gap-1">
                            <select x-model="bulkKategori" class="px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="harian">Harian</option>
                                <option value="mingguan">Mingguan</option>
                                <option value="bulanan">Bulanan</option>
                            </select>
                            <button @click="doBulk('ubah_kategori')" class="btn-secondary px-3 py-1.5 text-xs">
                                Ubah Kategori
                            </button>
                        </div>
                        <div class="flex items-center gap-1">
                            <input type="number" x-model="bulkPoin" min="1" max="1000" class="w-16 px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" placeholder="Poin">
                            <button @click="doBulk('ubah_poin')" class="btn-secondary px-3 py-1.5 text-xs">
                                Ubah Poin
                            </button>
                        </div>
                    </div>
                    <button @click="selectedIds = []; selectAll = false" class="ml-auto px-2 py-1 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400">
                        Batalkan
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto pkg-mobile-table">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-center w-10">
                                    <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Deskripsi</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Poin</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Periode</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($karakter as $item)
                            <tr x-data="{ editing: false }">
                                <td data-label="Pilih" class="px-4 py-4 text-center">
                                    <input type="checkbox" value="{{ $item->id }}" x-model="selectedIds" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td data-label="Nama" class="px-4 py-4 pkg-mobile-main">
                                    <template x-if="!editing">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->nama }}</span>
                                    </template>
                                    <template x-if="editing">
                                        <form action="{{ route('tugas-pkg.update', $item) }}" method="POST" class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="nama" value="{{ $item->nama }}" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-700 dark:text-white focus:ring-1 focus:ring-blue-500" required>
                                            <textarea name="deskripsi" rows="2" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-700 dark:text-white focus:ring-1 focus:ring-blue-500">{{ $item->deskripsi }}</textarea>
                                            <div class="grid grid-cols-2 gap-2">
                                                <select name="kategori" class="px-2 py-1.5 text-sm border rounded dark:bg-gray-700 dark:text-white focus:ring-1 focus:ring-blue-500">
                                                    <option value="harian" {{ $item->kategori == 'harian' ? 'selected' : '' }}>Harian</option>
                                                    <option value="mingguan" {{ $item->kategori == 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                                                    <option value="bulanan" {{ $item->kategori == 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                                                </select>
                                                <input type="number" name="poin" value="{{ $item->poin }}" min="1" max="1000" class="px-2 py-1.5 text-sm border rounded dark:bg-gray-700 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Poin">
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="date" name="tanggal_mulai" value="{{ $item->tanggal_mulai?->format('Y-m-d') }}" class="px-2 py-1.5 text-sm border rounded dark:bg-gray-700 dark:text-white focus:ring-1 focus:ring-blue-500">
                                                <input type="date" name="tanggal_selesai" value="{{ $item->tanggal_selesai?->format('Y-m-d') }}" class="px-2 py-1.5 text-sm border rounded dark:bg-gray-700 dark:text-white focus:ring-1 focus:ring-blue-500">
                                            </div>

                                            <!-- Jenis Penyelesaian for Edit -->
                                            <div x-data="{ jenisEdit: '{{ $item->jenis_penyelesaian ?? 'checklist' }}', allowsPhotoProofEdit: {{ $item->allows_photo_proof ? 'true' : 'false' }}, allowsVoiceProofEdit: {{ $item->allows_voice_note_proof ? 'true' : 'false' }} }" class="p-2 bg-gray-50 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Penyelesaian</label>
                                                <select name="jenis_penyelesaian" x-model="jenisEdit" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-600 dark:text-white focus:ring-1 focus:ring-blue-500 mb-2">
                                                    <option value="checklist">Checklist Standar</option>
                                                    <option value="teks">Input Teks</option>
                                                    <option value="klik">Hitungan / Zikir</option>
                                                </select>

                                                <div x-show="jenisEdit === 'teks'">
                                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan / Soal Teks</label>
                                                    <textarea name="target_teks" rows="2" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-600 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Ketikkan lafal...">{{ $item->target_teks }}</textarea>
                                                </div>

                                                <div x-show="jenisEdit === 'klik'">
                                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Target Jumlah Klik (Zikir)</label>
                                                    <input type="number" name="target_klik" value="{{ $item->target_klik }}" min="1" max="10000" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-600 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Target hitungan...">
                                                </div>

                                                <div class="mt-3 border-t border-gray-200 dark:border-gray-600 pt-3">
                                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                        <input type="checkbox" name="allows_photo_proof" value="1" x-model="allowsPhotoProofEdit" class="rounded border-gray-300 text-blue-600">
                                                        Izinkan upload bukti foto
                                                    </label>
                                                    <div x-show="allowsPhotoProofEdit" class="mt-2 space-y-2">
                                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Bonus poin bukti foto</label>
                                                        <input type="number" name="photo_proof_bonus_points" value="{{ $item->photo_proof_bonus_points }}" min="0" max="1000" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-600 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Bonus bukti foto">
                                                        <textarea name="photo_proof_instruction" rows="3" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-600 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Petunjuk foto untuk siswa">{{ $item->photo_proof_instruction }}</textarea>
                                                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Yang tampil ke siswa hanya judul Foto dan petunjuk ini.</p>
                                                    </div>
                                                </div>

                                                <div class="mt-3 border-t border-gray-200 dark:border-gray-600 pt-3">
                                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                        <input type="checkbox" name="allows_voice_note_proof" value="1" x-model="allowsVoiceProofEdit" class="rounded border-gray-300 text-violet-600">
                                                        Izinkan upload voice note
                                                    </label>
                                                    <div x-show="allowsVoiceProofEdit" class="mt-2 space-y-2">
                                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Bonus poin voice note</label>
                                                        <input type="number" name="voice_note_bonus_points" value="{{ $item->voice_note_bonus_points }}" min="0" max="1000" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-600 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Bonus voice note">
                                                        <textarea name="voice_note_instruction" rows="3" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-600 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Petunjuk voice note untuk siswa">{{ $item->voice_note_instruction }}</textarea>
                                                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Yang tampil ke siswa hanya judul Voice note dan petunjuk ini.</p>
                                                        <input type="number" name="voice_note_max_seconds" value="{{ $item->voice_note_max_seconds }}" min="1" max="1800" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-600 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Batas durasi voice note (detik)">
                                                    </div>
                                                </div>

                                                <div class="mt-3 border-t border-gray-200 dark:border-gray-600 pt-3">
                                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Aturan bukti</label>
                                                    <select name="proof_requirement" class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-600 dark:text-white focus:ring-1 focus:ring-blue-500">
                                                        <option value="optional" {{ ($item->proof_requirement ?? 'optional') === 'optional' ? 'selected' : '' }}>Bukti opsional</option>
                                                        <option value="required_any" {{ ($item->proof_requirement ?? 'optional') === 'required_any' ? 'selected' : '' }}>Minimal salah satu bukti wajib</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="flex gap-2 pt-1">
                                                <button type="submit" class="px-3 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded font-medium disabled:opacity-50">Simpan</button>
                                                <button type="button" @click="editing = false" class="px-3 py-1.5 text-xs bg-gray-400 hover:bg-gray-500 text-white rounded font-medium">Batal</button>
                                            </div>
                                        </form>
                                    </template>
                                </td>
                                <td data-label="Deskripsi" class="px-4 py-4">
                                    <template x-if="!editing">
                                        <div>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($item->deskripsi, 40) ?: '-' }}</span>
                                            @if($item->allows_photo_proof || $item->allows_voice_note_proof)
                                            <div class="mt-2 space-y-2">
                                                @if($item->allows_photo_proof)
                                                <div class="text-xs">
                                                    <div class="font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Foto</div>
                                                    <div class="mt-0.5 text-gray-500 dark:text-gray-400">{{ Str::limit($item->photo_proof_instruction ?: 'Ambil foto bukti sesuai instruksi tugas.', 70) }}</div>
                                                </div>
                                                @endif
                                                @if($item->allows_voice_note_proof)
                                                <div class="text-xs">
                                                    <div class="font-semibold uppercase tracking-wide text-violet-600 dark:text-violet-400">Voice note</div>
                                                    <div class="mt-0.5 text-gray-500 dark:text-gray-400">{{ Str::limit($item->voice_note_instruction ?: 'Gunakan rekaman suara sesuai petunjuk tugas.', 70) }}</div>
                                                </div>
                                                @endif
                                            </div>
                                            @endif
                                        </div>
                                    </template>
                                </td>
                                <td data-label="Kategori" class="px-4 py-4 text-center">
                                    <span class="pkg-status-badge {{ $item->kategori === 'harian' ? 'pkg-status-info' : ($item->kategori === 'mingguan' ? 'pkg-status-neutral' : 'pkg-status-warning') }}">
                                        {{ $item->kategori_label }}
                                    </span>
                                </td>
                                <td data-label="Poin" class="px-4 py-4 text-center">
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">+{{ $item->poin }}</span>
                                </td>
                                <td data-label="Periode" class="px-4 py-4 text-center">
                                    @if($item->formatted_period)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item->formatted_period }}</span>
                                        @if($item->isExpired())
                                            <span class="mt-1 inline-flex pkg-status-badge pkg-status-danger">Berakhir</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">Tanpa batas</span>
                                    @endif
                                </td>
                                <td data-label="Status" class="px-4 py-4 text-center">
                                    <span class="pkg-status-badge {{ $item->is_active ? 'pkg-status-success' : 'pkg-status-danger' }}">
                                        {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td data-label="Aksi" class="px-4 py-4 text-right pkg-mobile-actions">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="editing = !editing" class="pkg-btn-secondary px-3 py-1.5 text-xs">Edit</button>
                                        <form action="{{ route('tugas-pkg.toggle-status', $item) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="pkg-btn-secondary px-3 py-1.5 text-xs">
                                                {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-0 pkg-mobile-empty">
                                    <div class="pkg-empty-state">
                                        <h3 class="pkg-empty-title">Belum ada tugas PKG</h3>
                                        <p class="pkg-empty-copy">Tambahkan tugas pertama untuk mulai mengatur jadwal, bukti, dan poin tugas PKG.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $karakter->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden bulk form -->
    <form id="bulkForm" action="{{ route('tugas-pkg.bulk-action') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="action" id="bulkFormAction">
        <input type="hidden" name="kategori" id="bulkFormKategori">
        <input type="hidden" name="poin" id="bulkFormPoin">
    </form>
</div>

<script>
function bulkManager() {
    return {
        selectedIds: [],
        selectAll: false,
        bulkKategori: 'harian',
        bulkPoin: 10,

        toggleSelectAll() {
            if (this.selectAll) {
                const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
                this.selectedIds = Array.from(checkboxes).map(cb => cb.value);
            } else {
                this.selectedIds = [];
            }
        },

        async doBulk(action) {
            if (this.selectedIds.length === 0) {
                window.showNotification('Pilih minimal 1 item', 'warning');
                return;
            }

            let confirmMsg = '';
            switch(action) {
                case 'aktivasi': confirmMsg = `Aktifkan ${this.selectedIds.length} tugas?`; break;
                case 'nonaktifkan': confirmMsg = `Nonaktifkan ${this.selectedIds.length} tugas?`; break;
                case 'hapus': confirmMsg = `HAPUS ${this.selectedIds.length} tugas? Aksi ini tidak bisa dikembalikan!`; break;
                case 'ubah_kategori': confirmMsg = `Ubah kategori ${this.selectedIds.length} tugas ke "${this.bulkKategori}"?`; break;
                case 'ubah_poin': confirmMsg = `Ubah poin ${this.selectedIds.length} tugas ke "${this.bulkPoin}"?`; break;
            }

            const confirmed = await window.showConfirmation(confirmMsg, {
                title: 'Aksi massal tugas',
                confirmText: 'Lanjutkan',
                tone: action === 'hapus' ? 'danger' : 'warning'
            });
            if (!confirmed) return;

            const form = document.getElementById('bulkForm');
            document.getElementById('bulkFormAction').value = action;
            document.getElementById('bulkFormKategori').value = this.bulkKategori;
            document.getElementById('bulkFormPoin').value = this.bulkPoin;

            // Add selected IDs as hidden inputs
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            this.selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });

            form.submit();
        }
    };
}
</script>
@endsection

