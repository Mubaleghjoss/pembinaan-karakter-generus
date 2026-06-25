<!-- ID Card Settings Form -->
<form action="{{ route('settings.update.id-card') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="pkg-panel-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
            </svg>
            Pengaturan ID Card
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card Title -->
            <div>
                <label for="card_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Judul Kartu <span class="text-red-500">*</span>
                </label>
                <input type="text" name="card_title" id="card_title" 
                       value="{{ old('card_title', $idCardSettings['card_title'] ?? 'KARTU IDENTITAS') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Contoh: KARTU IDENTITAS"
                       required>
                @error('card_title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Card Subtitle -->
            <div>
                <label for="card_subtitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Subtitle Kartu <span class="text-red-500">*</span>
                </label>
                <input type="text" name="card_subtitle" id="card_subtitle" 
                       value="{{ old('card_subtitle', $idCardSettings['card_subtitle'] ?? 'Pembinaan Karakter Generus') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Contoh: Pembinaan Karakter Generus"
                       required>
                @error('card_subtitle')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Card Color -->
            <div>
                <label for="card_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Kartu <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-3">
                    <input type="color" name="card_color" id="card_color" 
                           value="{{ old('card_color', $idCardSettings['card_color'] ?? '#667EEA') }}"
                           data-preview="preview-card"
                           class="h-10 w-20 rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                    <span class="hex-value text-sm text-gray-500">{{ old('card_color', $idCardSettings['card_color'] ?? '#667EEA') }}</span>
                    <div id="preview-card" class="w-10 h-10 rounded-lg shadow-md" style="background-color: {{ $idCardSettings['card_color'] ?? '#667EEA' }}"></div>
                </div>
                @error('card_color')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="card_footer_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Teks Footer Kartu
                </label>
                <input type="text" name="card_footer_text" id="card_footer_text"
                       value="{{ old('card_footer_text', $idCardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Contoh: Kartu ini adalah identitas resmi peserta PKG Panunggangan">
                <p class="mt-1 text-xs text-gray-500">Teks ini akan tampil di bagian bawah kartu, menggantikan URL/domain.</p>
                @error('card_footer_text')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Card Logo -->
            <div>
                <label for="card_logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Logo Kartu
                </label>
                <div class="flex items-center gap-4">
                    @if(!empty($idCardSettings['card_logo']))
                        <img src="{{ Storage::url($idCardSettings['card_logo']) }}" alt="Current Logo" class="h-12 w-auto rounded">
                    @endif
                    <input type="file" name="card_logo" id="card_logo" 
                           accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                           class="w-full px-4 py-2 pkg-field">
                </div>
                <p class="mt-1 text-xs text-gray-500">Format: PNG, JPG, SVG. Maksimal 1MB</p>
                @error('card_logo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Preview Card -->
        <div class="mt-8">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Preview ID Card:</h3>
            <div class="flex justify-center">
                <div id="id-card-live-preview" class="w-[85.6mm] h-[54mm] overflow-hidden rounded-lg shadow-xl" style="background: linear-gradient(135deg, {{ $idCardSettings['card_color'] ?? '#667EEA' }} 0%, {{ $idCardSettings['card_color'] ?? '#667EEA' }}99 100%);">
                    <div class="h-full p-4 flex flex-col text-white">
                        <div class="text-center">
                            <h4 id="id-card-preview-title" class="text-lg font-bold">{{ $idCardSettings['card_title'] ?? 'KARTU IDENTITAS' }}</h4>
                            <p id="id-card-preview-subtitle" class="text-xs opacity-80">{{ $idCardSettings['card_subtitle'] ?? 'Pembinaan Karakter Generus' }}</p>
                        </div>
                        <div class="flex-1 flex items-center justify-center">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-white/20 rounded-full mx-auto mb-2"></div>
                            <p class="text-sm font-semibold">Nama Siswa</p>
                            <p class="text-xs opacity-80">NIS: 123456</p>
                        </div>
                    </div>
                    <div id="id-card-preview-footer" class="mt-auto text-center text-[10px] opacity-80">
                        {{ $idCardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan' }}
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="pkg-page-actions justify-end">
        <a href="{{ route('dashboard') }}" class="btn-secondary">
            Batal
        </a>
        <button type="submit" class="btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Pengaturan
        </button>
    </div>
</form>

