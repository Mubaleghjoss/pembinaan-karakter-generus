const forms = document.querySelectorAll('[data-quran-scan-form]');

if (forms.length) {
    import('html5-qrcode').then(({ Html5Qrcode }) => {
        forms.forEach((form, index) => {
            if (form.dataset.quranScanReady === 'true') return;
            form.dataset.quranScanReady = 'true';

            const fileInput = form.querySelector('[data-quran-scan-file]');
            const payloadInput = form.querySelector('[data-quran-sheet-payload]');
            const status = form.querySelector('[data-quran-scan-status]');
            const submit = form.querySelector('[data-quran-scan-submit]');
            const readerId = `quran-qr-reader-${index}`;
            const hiddenReader = form.querySelector('#quran-qr-reader-hidden');
            hiddenReader.id = readerId;
            const reader = new Html5Qrcode(readerId, { verbose: false });

            fileInput.addEventListener('change', async () => {
                payloadInput.value = '';
                submit.disabled = true;
                const file = fileInput.files?.[0];
                if (!file) {
                    status.textContent = 'Pilih foto lembar untuk membaca QR.';
                    return;
                }

                status.textContent = 'Membaca QR dan memeriksa foto...';
                status.className = 'mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200';

                try {
                    const decoded = await reader.scanFile(file, true);
                    if (!/^PKGQURAN:[0-9a-f-]{36}:[A-Za-z0-9]+$/i.test(decoded)) {
                        throw new Error('QR bukan lembar Tracer Bacaan Al-Quran PKG.');
                    }

                    payloadInput.value = decoded;
                    submit.disabled = false;
                    status.textContent = 'QR valid terbaca. Foto siap diunggah untuk layar konfirmasi.';
                    status.className = 'mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200';
                } catch (error) {
                    status.textContent = error?.message || 'QR belum terbaca. Foto ulang dengan QR yang lebih jelas.';
                    status.className = 'mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200';
                }
            });
        });
    }).catch(() => {
        forms.forEach((form) => {
            const status = form.querySelector('[data-quran-scan-status]');
            status.textContent = 'Pemindai QR tidak dapat dimuat. Silakan muat ulang halaman.';
        });
    });
}
