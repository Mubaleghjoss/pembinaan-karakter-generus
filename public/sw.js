// PKG Presensi Service Worker
const CACHE_NAME = 'pkg-presensi-v8';
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
