const SMARTPROBOOK_SW_VERSION = 'smartprobook-static-v3';
const STATIC_CACHE = `${SMARTPROBOOK_SW_VERSION}-assets`;
const POS_PAGE_CACHE = 'smartprobook-pos-page-v1';
const POS_CACHE_KEY = new Request(new URL('/pos', self.location.origin));
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
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('smartprobook-static-') && key !== STATIC_CACHE)
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'clear-private-pos-cache') {
        event.waitUntil(caches.delete(POS_PAGE_CACHE));
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    const isLogout = ['GET', 'POST'].includes(request.method)
        && /(?:^|\/)(?:emergency\/)?logout\/?$/.test(url.pathname);
    const isLogin = request.method === 'POST' && url.pathname.replace(/\/+$/, '') === '/login';
    if (url.origin === self.location.origin && (isLogout || isLogin)) {
        event.respondWith(fetch(request).then((response) => {
            if (response.ok || response.redirected) {
                event.waitUntil(caches.delete(POS_PAGE_CACHE));
            }
            return response;
        }));
        return;
    }

    if (request.method !== 'GET') return;
    if (request.mode === 'navigate') {
        if (url.origin === self.location.origin && url.pathname.replace(/\/+$/, '') === '/pos') {
            event.respondWith(fetch(request).then((response) => {
                if (response.ok && !response.redirected) {
                    const copy = response.clone();
                    event.waitUntil(caches.open(POS_PAGE_CACHE).then((cache) => cache.put(POS_CACHE_KEY, copy)));
                }
                return response;
            }).catch(async () => {
                const cachedPos = await caches.open(POS_PAGE_CACHE).then((cache) => cache.match(POS_CACHE_KEY));
                return cachedPos || caches.match('/offline.html');
            }));
            return;
        }

        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    if (url.origin !== self.location.origin) return;
    if (!['style', 'script', 'image', 'font', 'manifest'].includes(request.destination)) return;

    event.respondWith(
        caches.match(request).then((cached) => cached || fetch(request).then((response) => {
            if (response.ok && response.type === 'basic') {
                const copy = response.clone();
                event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy)));
            }
            return response;
        }))
    );
});
