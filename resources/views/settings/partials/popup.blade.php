<div class="space-y-6">
    <div class="pkg-panel p-6">
        <div class="flex flex-col gap-2 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Popup Otomatis Setelah Login</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Atur popup yang muncul otomatis setelah pengguna login. Status "Wajib" membuat popup tidak bisa ditutup
                sampai pengguna menuju langkah yang diminta.
            </p>
        </div>

        <form action="{{ route('settings.update.popup') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            @foreach($popupSettings as $popup)
                <div class="pkg-card p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div>
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ $popup['title'] }}</h4>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $popup['description'] }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @foreach($popup['targets'] as $target)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ $target }}
                                    </span>
                                @endforeach
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    Aksi: {{ $popup['action_label'] }}
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 sm:gap-5 lg:min-w-[280px]">
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <input
                                    type="checkbox"
                                    name="popups[{{ $popup['key'] }}][enabled]"
                                    value="1"
                                    class="mt-1 pkg-check"
                                    {{ $popup['enabled'] ? 'checked' : '' }}
                                >
                                <span>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Aktif</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Popup tetap diproses saat kondisi terpenuhi.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <input
                                    type="checkbox"
                                    name="popups[{{ $popup['key'] }}][required]"
                                    value="1"
                                    class="mt-1 pkg-check"
                                    {{ $popup['required'] ? 'checked' : '' }}
                                >
                                <span>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Wajib</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Popup tidak menyediakan tombol tutup.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                Untuk popup wajib, halaman tujuan tetap dibuka tanpa blokir penuh agar pengguna bisa menyelesaikan aksi,
                misalnya mengisi biodata atau menautkan biometrik.
            </div>

            <div class="pkg-page-actions justify-end">
                <button
                    type="submit"
                    class="btn-primary text-sm"
                >
                    Simpan Pengaturan Popup
                </button>
            </div>
        </form>
    </div>
</div>

