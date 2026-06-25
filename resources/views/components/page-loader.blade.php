@php
    $loaderLogoPath = $siteSettings['site_logo'] ?? ($currentTheme->logo_path ?? null);
    $pageLoaderLogo = $loaderLogoPath ? asset('storage/' . $loaderLogoPath) : asset('img/logo_pkg.svg');
@endphp

<div id="pkg-page-loader" class="fixed inset-0 z-[140] hidden items-center justify-center bg-slate-950/60 px-4 backdrop-blur-sm">
    <div class="pkg-modal w-full max-w-md overflow-hidden p-0 shadow-2xl">
        <div class="flex flex-col items-center px-6 pb-6 pt-7 text-center">
            <div class="relative flex h-24 w-24 items-center justify-center">
                <span id="pkg-page-loader-ring" class="absolute inset-0 rounded-full border-4 border-emerald-400/25"></span>
                <span class="absolute inset-3 rounded-full border border-slate-200 dark:border-slate-700"></span>
                <div class="relative flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-200 dark:bg-slate-950 dark:ring-slate-700">
                    <img src="{{ $pageLoaderLogo }}" alt="Logo PKG" class="h-12 w-12 object-contain" decoding="async">
                </div>
            </div>

            <div id="pkg-page-loader-status" class="mt-5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
                Memproses
            </div>
            <h2 id="pkg-page-loader-title" class="mt-4 text-xl font-bold text-slate-900 dark:text-white">Membuka halaman...</h2>
            <p id="pkg-page-loader-message" class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Mohon tunggu sebentar.</p>
            <p id="pkg-page-loader-detail" class="mt-4 hidden w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-xs leading-5 text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300"></p>

            <div id="pkg-page-loader-actions" class="mt-6 hidden w-full flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                <button type="button" id="pkg-page-loader-close" class="btn-secondary justify-center px-4 py-2 text-sm">Tutup</button>
                <button type="button" id="pkg-page-loader-reload" class="btn-primary justify-center px-4 py-2 text-sm">Coba Lagi</button>
            </div>
        </div>
    </div>
</div>

<script>
if (!window.pkgPageLoaderInitialized) {
    window.pkgPageLoaderInitialized = true;

    window.pkgPageLoader = {
        timer: null,
        hintTimer: null,
        offlineVisible: false,
        visibleType: 'loading',
        element() {
            return document.getElementById('pkg-page-loader');
        },
        titleElement() {
            return document.getElementById('pkg-page-loader-title');
        },
        messageElement() {
            return document.getElementById('pkg-page-loader-message');
        },
        detailElement() {
            return document.getElementById('pkg-page-loader-detail');
        },
        statusElement() {
            return document.getElementById('pkg-page-loader-status');
        },
        ringElement() {
            return document.getElementById('pkg-page-loader-ring');
        },
        actionsElement() {
            return document.getElementById('pkg-page-loader-actions');
        },
        setTone(type) {
            const status = this.statusElement();
            const ring = this.ringElement();
            status.className = 'mt-5 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]';
            ring.className = 'absolute inset-0 rounded-full border-4';

            if (type === 'error' || type === 'offline') {
                status.classList.add('border-red-200', 'bg-red-50', 'text-red-700', 'dark:border-red-900/60', 'dark:bg-red-950/40', 'dark:text-red-200');
                ring.classList.add('border-red-400/30');
                return;
            }

            status.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700', 'dark:border-emerald-900/60', 'dark:bg-emerald-950/40', 'dark:text-emerald-200');
            ring.classList.add('border-emerald-400/25', 'animate-ping');
        },
        describeError(error) {
            if (!navigator.onLine) {
                return 'Browser melaporkan koneksi offline. Periksa Wi-Fi/data, DNS, atau koneksi ke server lokal.';
            }

            if (!error) {
                return 'Detail error tidak tersedia dari browser.';
            }

            if (typeof error === 'string') {
                return error;
            }

            if (error.message) {
                return error.message;
            }

            try {
                return JSON.stringify(error);
            } catch (_error) {
                return 'Error tidak dapat dibaca.';
            }
        },
        show(options = {}) {
            const overlay = this.element();
            if (!overlay) return;

            const type = options.type || (options.offline ? 'offline' : 'loading');
            if (type === 'loading' && !options.force) {
                return;
            }

            this.visibleType = type;
            this.setTone(type);
            this.statusElement().textContent = options.status || (type === 'loading' ? 'Memproses' : 'Perhatian');
            this.titleElement().textContent = options.title || 'Membuka halaman...';
            this.messageElement().textContent = options.message || 'Mohon tunggu sebentar.';

            const detail = options.detail || '';
            const detailElement = this.detailElement();
            detailElement.textContent = detail;
            detailElement.classList.toggle('hidden', !detail);

            this.actionsElement().classList.toggle('hidden', type === 'loading' && !options.showActions);

            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            this.offlineVisible = type === 'offline';

            clearTimeout(this.hintTimer);
            if (type === 'loading') {
                this.hintTimer = setTimeout(() => {
                    if (!navigator.onLine) {
                        this.setOfflineState();
                        return;
                    }
                    this.show({
                        type: 'error',
                        status: 'Terlalu Lama',
                        title: 'Proses belum selesai',
                        message: 'Permintaan berjalan lebih lama dari biasanya.',
                        detail: 'Kemungkinan koneksi lambat, server belum merespons, atau halaman sedang memproses data besar.',
                        showActions: true,
                    });
                }, 9000);
            }
        },
        hide() {
            const overlay = this.element();
            if (!overlay) return;
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            this.offlineVisible = false;
            this.visibleType = 'loading';
            clearTimeout(this.hintTimer);
        },
        setOfflineState() {
            this.show({
                type: 'offline',
                status: 'Offline',
                title: 'Koneksi terputus',
                message: 'Tidak ada internet atau server sedang tidak bisa dijangkau.',
                detail: 'Browser sedang offline. Periksa Wi-Fi/data, proxy, atau koneksi ke server lokal lalu coba lagi.',
                showActions: true,
            });
        },
        setErrorState(error, context = 'Terjadi kesalahan') {
            this.show({
                type: 'error',
                status: 'Error',
                title: context,
                message: 'Permintaan tidak berhasil diproses.',
                detail: this.describeError(error),
                showActions: true,
            });
        },
        success(message = 'Berhasil diproses.') {
            this.cancelScheduled();
            this.hide();
            if (window.showNotification) {
                window.showNotification(message, 'success');
            }
        },
        schedule(options = {}, delay = 120) {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.show(options), delay);
        },
        cancelScheduled() {
            clearTimeout(this.timer);
            this.timer = null;
        }
    };

    document.getElementById('pkg-page-loader-close')?.addEventListener('click', () => {
        window.pkgPageLoader.cancelScheduled();
        window.pkgPageLoader.hide();
    });

    document.getElementById('pkg-page-loader-reload')?.addEventListener('click', () => {
        window.location.reload();
    });

    window.addEventListener('pageshow', () => {
        if (window.pkgPageLoader.visibleType === 'loading') {
            window.pkgPageLoader.cancelScheduled();
            window.pkgPageLoader.hide();
        }
    });

    window.addEventListener('load', () => {
        if (window.pkgPageLoader.visibleType === 'loading') {
            window.pkgPageLoader.cancelScheduled();
            window.pkgPageLoader.hide();
        }
    });

    window.addEventListener('offline', () => {
        window.pkgPageLoader.setOfflineState();
    });

    window.addEventListener('online', () => {
        if (window.pkgPageLoader.offlineVisible) {
            window.pkgPageLoader.hide();
            if (window.showNotification) {
                window.showNotification('Koneksi kembali normal.', 'success');
            }
        }
    });

    window.addEventListener('error', (event) => {
        window.pkgPageLoader.setErrorState(event.error || event.message, 'Kesalahan script');
    });

    window.addEventListener('unhandledrejection', (event) => {
        window.pkgPageLoader.setErrorState(event.reason, 'Permintaan gagal');
    });

    if (!window.pkgFetchErrorWatcherInstalled && window.fetch) {
        window.pkgFetchErrorWatcherInstalled = true;
        const nativeFetch = window.fetch.bind(window);
        window.fetch = async (...args) => {
            try {
                return await nativeFetch(...args);
            } catch (error) {
                if (!navigator.onLine || error instanceof TypeError) {
                    window.pkgPageLoader.setErrorState(error, 'Koneksi atau permintaan gagal');
                }
                throw error;
            }
        };
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.hasAttribute('data-no-loader')) return;
        if (!form.hasAttribute('data-show-loader')) return;

        window.pkgPageLoader.schedule({
            title: form.dataset.loaderTitle || 'Memproses permintaan...',
            message: form.dataset.loaderMessage || 'Data sedang dikirim.'
        }, 80);
    });
}
</script>
