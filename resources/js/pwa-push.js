function base64UrlToUint8Array(value) {
    const padding = '='.repeat((4 - (value.length % 4)) % 4);
    const normalized = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(normalized);

    return Uint8Array.from(Array.from(raw, (character) => character.charCodeAt(0)));
}

async function registerPkgServiceWorker() {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) {
        return null;
    }

    return navigator.serviceWorker.register('/sw.js');
}

async function updateAppBadge(count) {
    if (!('setAppBadge' in navigator)) {
        return;
    }

    const normalized = Math.max(0, Number(count) || 0);

    try {
        if (normalized > 0) {
            await navigator.setAppBadge(normalized);
        } else if ('clearAppBadge' in navigator) {
            await navigator.clearAppBadge();
        }
    } catch (error) {
        console.debug('Badge ikon PWA belum dapat diperbarui.', error);
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function sendSubscription(url, method, payload) {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || 'Pengaturan notifikasi gagal disimpan.');
    }

    return data;
}

function contentEncoding() {
    return window.PushManager?.supportedContentEncodings?.[0] || 'aes128gcm';
}

function setControlState(root, state) {
    const button = root.querySelector('[data-pwa-push-button]');
    const label = root.querySelector('[data-pwa-push-label]');

    if (!button || !label) {
        return;
    }

    root.dataset.state = state;
    button.disabled = state === 'working' || state === 'unsupported' || state === 'denied' || state === 'unconfigured';
    button.setAttribute('aria-pressed', String(state === 'active'));

    const labels = {
        active: 'Notifikasi aktif',
        inactive: 'Aktifkan notifikasi',
        working: 'Memproses...',
        denied: 'Notifikasi diblokir',
        unsupported: 'Notifikasi tidak didukung',
        unconfigured: 'Notifikasi belum siap',
    };

    label.textContent = labels[state] || labels.inactive;
    button.title = state === 'active'
        ? 'Tekan untuk menonaktifkan notifikasi pada perangkat ini'
        : label.textContent;
}

async function initializePushControl(root, registration) {
    if (!registration || !('PushManager' in window) || !('Notification' in window)) {
        setControlState(root, 'unsupported');
        return;
    }

    if (!root.dataset.vapidPublicKey) {
        setControlState(root, 'unconfigured');
        return;
    }

    const subscription = await registration.pushManager.getSubscription();
    const active = Boolean(subscription && Notification.permission === 'granted');

    setControlState(root, Notification.permission === 'denied' ? 'denied' : (active ? 'active' : 'inactive'));
    await updateAppBadge(root.dataset.badgeCount);

    root.querySelector('[data-pwa-push-button]')?.addEventListener('click', async () => {
        const current = await registration.pushManager.getSubscription();
        setControlState(root, 'working');

        try {
            if (current) {
                await sendSubscription(root.dataset.unsubscribeUrl, 'DELETE', { endpoint: current.endpoint });
                await current.unsubscribe();
                await updateAppBadge(0);
                setControlState(root, 'inactive');
                window.showNotification?.('Notifikasi dinonaktifkan pada perangkat ini.', 'success');
                return;
            }

            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                setControlState(root, permission === 'denied' ? 'denied' : 'inactive');
                return;
            }

            const created = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: base64UrlToUint8Array(root.dataset.vapidPublicKey),
            });
            const json = created.toJSON();
            const result = await sendSubscription(root.dataset.subscribeUrl, 'POST', {
                endpoint: created.endpoint,
                keys: json.keys,
                content_encoding: contentEncoding(),
            });

            root.dataset.badgeCount = String(result.badge_count || 0);
            await updateAppBadge(result.badge_count);
            setControlState(root, 'active');
            window.showNotification?.('Notifikasi tugas berhasil diaktifkan.', 'success');
        } catch (error) {
            console.error('Pengaturan Web Push gagal.', error);
            const existing = await registration.pushManager.getSubscription();
            setControlState(root, existing ? 'active' : 'inactive');
            window.showNotification?.(error.message || 'Notifikasi gagal diaktifkan.', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    let registration = null;

    try {
        registration = await registerPkgServiceWorker();
    } catch (error) {
        console.error('Service worker PWA gagal didaftarkan.', error);
    }

    document.querySelectorAll('[data-pwa-push-control]').forEach((root) => {
        initializePushControl(root, registration).catch((error) => {
            console.error('Kontrol notifikasi PWA gagal dimuat.', error);
            setControlState(root, 'unsupported');
        });
    });
});

window.pkgRegisterServiceWorker = registerPkgServiceWorker;
