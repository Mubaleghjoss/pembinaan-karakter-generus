// PKG Presensi Service Worker
const CACHE_NAME = 'pkg-presensi-v13';
const urlsToCache = [
    '/',
    '/manifest.json',
    '/images/icons/pkg-logo-192.png',
    '/images/icons/pkg-logo-512.png'
];

// Install event - cache essential files
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache).catch(err => {
                    console.log('Cache addAll error:', err);
                });
            })
    );
    self.skipWaiting();
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - network first, fallback to cache
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    const requestUrl = new URL(event.request.url);

    // Token sesi tidak boleh disimpan di Cache API karena berubah setelah logout/login.
    if (requestUrl.pathname === '/csrf-token') {
        event.respondWith(fetch(event.request, { cache: 'no-store' }));
        return;
    }

    // Always use the network for full page navigations so old Blade HTML is not served from cache.
    if (event.request.mode === 'navigate') {
        event.respondWith(fetch(event.request));
        return;
    }

    // Bukti foto dan voice note harus selalu dari jaringan/storage terbaru.
    if (
        requestUrl.pathname.startsWith('/storage/') ||
        requestUrl.pathname.startsWith('/media/sync-proxy')
    ) {
        event.respondWith(fetch(event.request, { cache: 'no-store' }));
        return;
    }

    if (requestUrl.pathname.startsWith('/build/')) {
        event.respondWith(fetch(event.request));
        return;
    }

    // Skip API requests and external URLs
    if (event.request.url.includes('/api/') || 
        !event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Clone the response
                const responseClone = response.clone();
                
                // Cache successful responses
                if (response.status === 200) {
                    caches.open(CACHE_NAME)
                        .then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                }
                
                return response;
            })
            .catch(() => {
                // Fallback to cache
                return caches.match(event.request)
                    .then((response) => {
                        if (response) {
                            return response;
                        }
                        return undefined;
                    });
            })
    );
});

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = { body: event.data?.text() || 'Ada pembaruan Tugas PKG.' };
    }

    const title = payload.title || 'PKG Panunggangan';
    const data = payload.data && typeof payload.data === 'object' ? payload.data : {};
    const badgeCount = Math.max(0, Number(data.badge_count) || 0);
    const notificationOptions = {
        body: payload.body || 'Ada pembaruan yang perlu diperiksa.',
        icon: payload.icon || '/images/icons/pkg-logo-192.png',
        badge: payload.badge || '/images/icons/pkg-logo-192.png',
        tag: payload.tag || 'pkg-update',
        renotify: Boolean(payload.renotify),
        requireInteraction: Boolean(payload.requireInteraction),
        vibrate: Array.isArray(payload.vibrate) ? payload.vibrate : [180, 80, 180],
        data: {
            url: data.url || '/',
            badge_count: badgeCount,
        },
    };

    event.waitUntil((async () => {
        await self.registration.showNotification(title, notificationOptions);

        if ('setAppBadge' in self.navigator) {
            if (badgeCount > 0) {
                await self.navigator.setAppBadge(badgeCount);
            } else if ('clearAppBadge' in self.navigator) {
                await self.navigator.clearAppBadge();
            }
        }
    })());
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    let targetUrl = new URL(event.notification.data?.url || '/', self.location.origin);
    if (targetUrl.origin !== self.location.origin) {
        targetUrl = new URL('/', self.location.origin);
    }

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({
            type: 'window',
            includeUncontrolled: true,
        });

        for (const client of windows) {
            if ('navigate' in client) {
                await client.navigate(targetUrl.href);
            }

            if ('focus' in client) {
                return client.focus();
            }
        }

        return self.clients.openWindow(targetUrl.href);
    })());
});
