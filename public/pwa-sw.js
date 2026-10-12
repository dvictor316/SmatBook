const SMARTPROBOOK_SW_VERSION = 'smartprobook-static-v2';
const STATIC_CACHE = `${SMARTPROBOOK_SW_VERSION}-assets`;
const STATIC_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/assets/img/log-favicon.png',
    '/assets/img/logos.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== STATIC_CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    if (url.origin !== self.location.origin) return;
    if (!['style', 'script', 'image', 'font', 'manifest'].includes(request.destination)) return;

    event.respondWith(
        caches.match(request).then((cached) => cached || fetch(request).then((response) => {
            if (response.ok && response.type === 'basic') {
                const copy = response.clone();
                caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
            }
            return response;
        }))
    );
});
