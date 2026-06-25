<!-- General Settings Form -->
<form action="{{ route('settings.update.general') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="pkg-panel-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Pengaturan Umum Website
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Site Title -->
            <div>
                <label for="site_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Judul Website <span class="text-red-500">*</span>
                </label>
                <input type="text" name="site_title" id="site_title" 
                       value="{{ old('site_title', $themeSettings->app_name ?? $generalSettings['site_title'] ?? 'PKG Presensi') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Judul yang tampil di browser tab & saat share ke WA"
                       required>
                <p class="mt-1 text-xs text-gray-500">Tampil di tab browser, header website, dan saat di-share ke WhatsApp</p>
                @error('site_title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Site Name -->
            <div>
                <label for="site_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Deskripsi Website <span class="text-red-500">*</span>
                </label>
                <input type="text" name="site_name" id="site_name" 
                       value="{{ old('site_name', $themeSettings->app_description ?? $generalSettings['site_name'] ?? 'Pembinaan Karakter Generus') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Deskripsi singkat website"
                       required>
                <p class="mt-1 text-xs text-gray-500">Tampil di bawah judul & sebagai deskripsi saat di-share</p>
                @error('site_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Primary Color -->
            <div>
                <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Utama <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-3">
                    <input type="color" name="primary_color" id="primary_color" 
                           value="{{ old('primary_color', $themeSettings->primary_color ?? $generalSettings['primary_color'] ?? '#667EEA') }}"
                           data-preview="preview-primary"
                           class="h-10 w-20 rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                    <span class="hex-value text-sm text-gray-500">{{ old('primary_color', $themeSettings->primary_color ?? $generalSettings['primary_color'] ?? '#667EEA') }}</span>
                    <div id="preview-primary" class="w-10 h-10 rounded-lg shadow-md" style="background-color: {{ $themeSettings->primary_color ?? $generalSettings['primary_color'] ?? '#667EEA' }}"></div>
                </div>
                @error('primary_color')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Site Logo -->
            <div>
                <label for="site_logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Logo Website
                </label>
                <div class="flex items-center gap-4">
                    @if($themeSettings->logo_path)
                        <img src="{{ asset('storage/' . $themeSettings->logo_path) }}" alt="Current Logo" class="h-12 w-auto rounded bg-white p-1">
                    @elseif(!empty($generalSettings['site_logo']))
                        <img src="{{ Storage::url($generalSettings['site_logo']) }}" alt="Current Logo" class="h-12 w-auto rounded bg-white p-1">
                    @endif
                    <input type="file" name="site_logo" id="site_logo" 
                           accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                           class="w-full px-4 py-2 pkg-field">
                </div>
                <p class="mt-1 text-xs text-gray-500">Format: PNG, JPG, SVG. Maksimal 2MB. Logo ini akan muncul saat di-share ke WhatsApp</p>
                @error('site_logo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Site Favicon -->
            <div>
                <label for="site_favicon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Ikon Tab Browser
                </label>
                <div class="flex items-center gap-4">
                    @if($themeSettings->favicon_path)
                        <img src="{{ asset('storage/' . $themeSettings->favicon_path) }}" alt="Ikon tab saat ini" class="h-10 w-10 rounded bg-white p-1 object-contain">
                    @elseif($themeSettings->logo_path)
                        <img src="{{ asset('storage/' . $themeSettings->logo_path) }}" alt="Ikon tab dari logo" class="h-10 w-10 rounded bg-white p-1 object-contain">
                    @elseif(!empty($generalSettings['site_logo']))
                        <img src="{{ Storage::url($generalSettings['site_logo']) }}" alt="Ikon tab dari logo" class="h-10 w-10 rounded bg-white p-1 object-contain">
                    @endif
                    <input type="file" name="site_favicon" id="site_favicon"
                           accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/x-icon,image/webp"
                           class="w-full px-4 py-2 pkg-field">
                </div>
                <p class="mt-1 text-xs text-gray-500">Format: PNG, JPG, SVG, ICO, WEBP. Jika kosong, ikon tab memakai Logo Website.</p>
                @error('site_favicon')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                Menu Tugas PKG aktif sekarang selalu ditampilkan pada akun admin, pamong, dan ortu sesuai layout portal masing-masing. Tombol aktif/nonaktif menu lama sudah dihapus agar navigasi tetap konsisten.
            </div>
        </div>

        <!-- Preview Share -->
        <div class="mt-6 pkg-card-soft p-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Preview saat di-share ke WhatsApp:</p>
            <div class="pkg-card p-4 max-w-sm">
                <div class="flex items-start gap-3">
                    @if($themeSettings->logo_path)
                    <img src="{{ asset('storage/' . $themeSettings->logo_path) }}" alt="Logo" class="w-16 h-16 rounded object-cover">
                    @else
                    <div class="w-16 h-16 rounded bg-gray-200 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @endif
                    <div class="flex-1">
                        <p class="font-bold text-gray-900 dark:text-white text-sm">{{ $themeSettings->app_name ?? 'PKG Presensi' }}</p>
                        <p class="text-gray-500 text-xs mt-1">{{ $themeSettings->app_description ?? 'Pembinaan Karakter Generus' }}</p>
                        <p class="text-blue-500 text-xs mt-1">{{ request()->getHost() }}</p>
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

<!-- Footer Settings Form -->
<form action="{{ route('settings.update.footer') }}" method="POST" class="space-y-6 mt-8">
    @csrf
    @method('PUT')

    <div class="pkg-panel-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Pengaturan Footer Website
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Pengaturan ini akan ditampilkan di footer semua halaman publik (beranda, lapor PKG, dll)</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Footer Text -->
            <div>
                <label for="footer_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Teks Footer / Deskripsi
                </label>
                <input type="text" name="footer_text" id="footer_text" 
                       value="{{ old('footer_text', $themeSettings->footer_text ?? 'Pembinaan Karakter Generus') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Deskripsi singkat di footer">
                @error('footer_text')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Footer Organization -->
            <div>
                <label for="footer_organization" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nama Organisasi
                </label>
                <input type="text" name="footer_organization" id="footer_organization" 
                       value="{{ old('footer_organization', $themeSettings->footer_organization ?? 'SMA AFBS') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Nama organisasi/sekolah">
                @error('footer_organization')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Footer Address -->
            <div class="md:col-span-2">
                <label for="footer_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Alamat
                </label>
                <input type="text" name="footer_address" id="footer_address" 
                       value="{{ old('footer_address', $themeSettings->footer_address ?? '') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Alamat lengkap (opsional)">
                @error('footer_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Footer Phone -->
            <div>
                <label for="footer_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    No. Telepon
                </label>
                <input type="text" name="footer_phone" id="footer_phone" 
                       value="{{ old('footer_phone', $themeSettings->footer_phone ?? '') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Nomor telepon (opsional)">
                @error('footer_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Footer Email -->
            <div>
                <label for="footer_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Email
                </label>
                <input type="email" name="footer_email" id="footer_email" 
                       value="{{ old('footer_email', $themeSettings->footer_email ?? '') }}"
                       class="w-full px-4 py-2 pkg-field"
                       placeholder="Email kontak (opsional)">
                @error('footer_email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Preview -->
        <div class="mt-6 rounded-lg bg-gray-900 p-4">
            <p class="text-xs text-gray-400 mb-2">Preview Footer:</p>
            <div class="text-white">
                <p class="font-bold">{{ $themeSettings->app_name ?? 'PKG Presensi' }}</p>
                <p class="text-gray-400 text-sm">{{ $themeSettings->footer_text ?? 'Pembinaan Karakter Generus' }}</p>
                @if($themeSettings->footer_organization)
                <p class="text-gray-400 text-sm mt-2">{{ $themeSettings->footer_organization }}</p>
                @endif
                <p class="text-gray-500 text-xs mt-4">&copy; {{ date('Y') }} {{ $themeSettings->app_name ?? 'PKG Presensi' }}. Hak cipta dilindungi.</p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="pkg-page-actions justify-end">
        <button type="submit" class="btn-success">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Footer
        </button>
    </div>
</form>

