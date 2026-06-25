<!-- Theme Settings Form -->
<form action="{{ route('settings.update.theme') }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    <!-- Informasi Aplikasi -->
    <div class="pkg-panel-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Informasi Aplikasi
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nama Aplikasi
                </label>
                <input type="text" name="app_name" id="app_name" 
                       value="{{ old('app_name', $themeSettings->app_name ?? 'PKG Presensi') }}"
                       class="w-full px-4 py-2 pkg-field"
                       required>
                @error('app_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label for="app_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Deskripsi Aplikasi
                </label>
                <textarea name="app_description" id="app_description" rows="3"
                          class="w-full px-4 py-2 pkg-field">{{ old('app_description', $themeSettings->app_description ?? '') }}</textarea>
                @error('app_description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Tema Warna -->
    <div class="pkg-panel-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
            </svg>
            Tema Warna
        </h2>

        <div class="mb-6">
            <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Preset Cepat</p>
            <div class="flex flex-wrap gap-3">
                <button type="button" class="btn-secondary text-sm !px-4 !py-2" data-theme-preset="emerald-ocean">Emerald Ocean</button>
                <button type="button" class="btn-secondary text-sm !px-4 !py-2" data-theme-preset="sunrise-warm">Sunrise Warm</button>
                <button type="button" class="btn-secondary text-sm !px-4 !py-2" data-theme-preset="midnight-gold">Midnight Gold</button>
                <button type="button" class="btn-secondary text-sm !px-4 !py-2" data-theme-preset="clean-slate">Clean Slate</button>
            </div>
            <p class="mt-2 text-xs text-gray-500">Preset ini akan mengisi semua warna tema, termasuk sidebar dan topbar.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Primary Color -->
            <div>
                <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Utama
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="primary_color" id="primary_color" 
                           value="{{ old('primary_color', $themeSettings->primary_color ?? '#667EEA') }}"
                           data-preview="preview-primary"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('primary_color', $themeSettings->primary_color ?? '#667EEA') }}</p>
            </div>

            <!-- Secondary Color -->
            <div>
                <label for="secondary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Sekunder
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="secondary_color" id="secondary_color" 
                           value="{{ old('secondary_color', $themeSettings->secondary_color ?? '#764BA2') }}"
                           data-preview="preview-secondary"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('secondary_color', $themeSettings->secondary_color ?? '#764BA2') }}</p>
            </div>

            <!-- Accent Color -->
            <div>
                <label for="accent_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Aksen
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="accent_color" id="accent_color" 
                           value="{{ old('accent_color', $themeSettings->accent_color ?? '#F59E0B') }}"
                           data-preview="preview-accent"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('accent_color', $themeSettings->accent_color ?? '#F59E0B') }}</p>
            </div>

            <div>
                <label for="success_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Sukses
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="success_color" id="success_color"
                           value="{{ old('success_color', $themeSettings->success_color ?? '#10B981') }}"
                           data-preview="preview-success"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('success_color', $themeSettings->success_color ?? '#10B981') }}</p>
            </div>

            <div>
                <label for="warning_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Peringatan
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="warning_color" id="warning_color"
                           value="{{ old('warning_color', $themeSettings->warning_color ?? '#F59E0B') }}"
                           data-preview="preview-warning"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('warning_color', $themeSettings->warning_color ?? '#F59E0B') }}</p>
            </div>

            <div>
                <label for="danger_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Bahaya
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="danger_color" id="danger_color"
                           value="{{ old('danger_color', $themeSettings->danger_color ?? '#EF4444') }}"
                           data-preview="preview-danger"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('danger_color', $themeSettings->danger_color ?? '#EF4444') }}</p>
            </div>

            <div>
                <label for="light_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Dasar Light
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="light_color" id="light_color"
                           value="{{ old('light_color', $themeSettings->light_color ?? '#F8FAFC') }}"
                           data-preview="preview-light"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('light_color', $themeSettings->light_color ?? '#F8FAFC') }}</p>
            </div>

            <div>
                <label for="dark_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Dasar Dark
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="dark_color" id="dark_color"
                           value="{{ old('dark_color', $themeSettings->dark_color ?? '#020617') }}"
                           data-preview="preview-dark"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('dark_color', $themeSettings->dark_color ?? '#020617') }}</p>
            </div>

            <div>
                <label for="sidebar_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Sidebar
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="sidebar_color" id="sidebar_color"
                           value="{{ old('sidebar_color', $themeSettings->sidebar_color ?? '#FFFFFF') }}"
                           data-preview="preview-sidebar"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('sidebar_color', $themeSettings->sidebar_color ?? '#FFFFFF') }}</p>
            </div>

            <div>
                <label for="topbar_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Warna Topbar
                </label>
                <div class="flex items-center gap-2">
                    <input type="color" name="topbar_color" id="topbar_color"
                           value="{{ old('topbar_color', $themeSettings->topbar_color ?? '#FFFFFF') }}"
                           data-preview="preview-topbar"
                           class="h-10 w-full rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-gray-500 hex-value">{{ old('topbar_color', $themeSettings->topbar_color ?? '#FFFFFF') }}</p>
            </div>
        </div>

        <!-- Preview -->
        <div id="theme-live-preview"
             class="theme-preview-scope mt-6 pkg-card-soft border-2 border-dashed border-gray-300 dark:border-gray-600 p-6"
             style="
                --color-primary: {{ $themeSettings->primary_color ?? '#667EEA' }};
                --color-secondary: {{ $themeSettings->secondary_color ?? '#764BA2' }};
                --color-accent: {{ $themeSettings->accent_color ?? '#F59E0B' }};
                --color-success: {{ $themeSettings->success_color ?? '#10B981' }};
                --color-warning: {{ $themeSettings->warning_color ?? '#F59E0B' }};
                --color-danger: {{ $themeSettings->danger_color ?? '#EF4444' }};
                --color-dark: {{ $themeSettings->dark_color ?? '#020617' }};
                --color-light: {{ $themeSettings->light_color ?? '#F8FAFC' }};
                --color-sidebar: {{ $themeSettings->sidebar_color ?? '#FFFFFF' }};
                --color-topbar: {{ $themeSettings->topbar_color ?? '#FFFFFF' }};
             ">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Preview Warna:</h3>
                <div class="flex items-center gap-2 rounded-xl border border-[var(--pkg-border)] bg-[var(--pkg-surface)] p-1">
                    <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-theme-preview-mode="light">Light</button>
                    <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-theme-preview-mode="dark">Dark</button>
                </div>
            </div>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div id="preview-primary" class="w-12 h-12 rounded-lg shadow-md" style="background-color: {{ $themeSettings->primary_color ?? '#667EEA' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Utama</span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="preview-secondary" class="w-12 h-12 rounded-lg shadow-md" style="background-color: {{ $themeSettings->secondary_color ?? '#764BA2' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Sekunder</span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="preview-accent" class="w-12 h-12 rounded-lg shadow-md" style="background-color: {{ $themeSettings->accent_color ?? '#F59E0B' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Aksen</span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="preview-success" class="w-12 h-12 rounded-lg shadow-md" style="background-color: {{ $themeSettings->success_color ?? '#10B981' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Sukses</span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="preview-warning" class="w-12 h-12 rounded-lg shadow-md" style="background-color: {{ $themeSettings->warning_color ?? '#F59E0B' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Peringatan</span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="preview-danger" class="w-12 h-12 rounded-lg shadow-md" style="background-color: {{ $themeSettings->danger_color ?? '#EF4444' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Bahaya</span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="preview-light" class="w-12 h-12 rounded-lg shadow-md border border-gray-200" style="background-color: {{ $themeSettings->light_color ?? '#F8FAFC' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Light</span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="preview-dark" class="w-12 h-12 rounded-lg shadow-md" style="background-color: {{ $themeSettings->dark_color ?? '#020617' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Dark</span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="preview-sidebar" class="w-12 h-12 rounded-lg shadow-md border border-gray-200" style="background-color: {{ $themeSettings->sidebar_color ?? '#FFFFFF' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Sidebar</span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="preview-topbar" class="w-12 h-12 rounded-lg shadow-md border border-gray-200" style="background-color: {{ $themeSettings->topbar_color ?? '#FFFFFF' }}"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Topbar</span>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div class="pkg-sidebar rounded-2xl p-4">
                    <p class="mb-3 text-sm font-medium text-[var(--pkg-heading)]">Preview Sidebar</p>
                    <div class="space-y-2">
                        <div class="rounded-xl bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700">Menu Aktif</div>
                        <div class="rounded-xl px-3 py-2 text-sm text-[var(--pkg-text-muted)]">Menu Normal</div>
                    </div>
                </div>
                <div class="pkg-topbar rounded-2xl p-4">
                    <p class="text-sm font-medium text-[var(--pkg-heading)]">Preview Topbar</p>
                    <p class="mt-1 text-sm text-[var(--pkg-text-muted)]">Area header setelah login.</p>
                </div>
                <div class="pkg-panel p-4">
                    <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-200">Preview Tombol</p>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="btn-primary">Primer</button>
                        <button type="button" class="btn-secondary">Sekunder</button>
                        <button type="button" class="btn-success">Sukses</button>
                        <button type="button" class="btn-danger">Bahaya</button>
                    </div>
                </div>
                <div class="pkg-panel p-4">
                    <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-200">Preview Surface</p>
                    <div class="rounded-xl border border-[var(--pkg-border)] bg-[var(--pkg-surface)] p-4 shadow-sm">
                        <p class="font-semibold text-[var(--pkg-heading)]">Panel Aplikasi</p>
                        <p class="mt-1 text-sm text-[var(--pkg-text-muted)]">Perubahan warna ini akan terasa di area setelah login.</p>
                    </div>
                </div>
                <div class="pkg-panel p-4">
                    <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-200">Preview Light</p>
                    <div class="rounded-xl border p-4" style="background: color-mix(in srgb, var(--color-light) 92%, #ffffff); border-color: rgba(148,163,184,.25);">
                        <p class="font-semibold" style="color:#0f172a;">Konten Light Mode</p>
                        <p class="mt-1 text-sm" style="color:#475569;">Surface terang akan mengikuti warna dasar light.</p>
                    </div>
                </div>
                <div class="pkg-panel p-4">
                    <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-200">Preview Dark</p>
                    <div class="rounded-xl border p-4" style="background: color-mix(in srgb, var(--color-dark) 84%, #0f172a); border-color: rgba(100,116,139,.5);">
                        <p class="font-semibold text-white">Konten Dark Mode</p>
                        <p class="mt-1 text-sm text-slate-300">Surface gelap akan mengikuti warna dasar dark.</p>
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

