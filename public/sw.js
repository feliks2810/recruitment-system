const CACHE_NAME = 'recruitment-system-cache-v4';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/images/icon-192x192.png?v=4',
    '/images/icon-512x512.png?v=4',
    '/images/screenshot-desktop.png?v=4',
    '/images/screenshot-mobile.png?v=4',
    '/images/logo.png',
    '/images/Logo Patria.png'
];

// Install Event - cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(err => console.warn('PWA Cache install partially failed', err));
        })
    );
    self.skipWaiting();
});

// Activate Event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch Event - network first strategy for everything else
self.addEventListener('fetch', event => {
    // Only cache GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Ignore cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // If response is valid, clone and cache it
                if (response && response.status === 200 && response.type === 'basic') {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return response;
            })
            .catch(() => {
                // Network failed, fallback to cache
                return caches.match(event.request);
            })
    );
});
