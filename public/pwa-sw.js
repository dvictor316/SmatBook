const SMARTPROBOOK_SW_VERSION = 'smartprobook-static-v4';
const STATIC_CACHE = `${SMARTPROBOOK_SW_VERSION}-assets`;
const POS_PAGE_CACHE = 'smartprobook-pos-page-v2';
const PREVIOUS_POS_PAGE_CACHE = 'smartprobook-pos-page-v1';
const POS_CACHE_KEY = new Request(new URL('/pos', self.location.origin));
const STATIC_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/assets/img/log-favicon.png',
    '/assets/img/logos.png',
    '/js/pos-offline-auth.js',
];

function clearOfflineCredentials() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('smartprobook-pos', 3);
        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains('pending-sales')) {
                db.createObjectStore('pending-sales', { keyPath: 'client_sale_id' });
            }
            if (!db.objectStoreNames.contains('inventory-snapshots')) {
                db.createObjectStore('inventory-snapshots', { keyPath: 'key' });
            }
            if (!db.objectStoreNames.contains('offline-credentials')) {
                db.createObjectStore('offline-credentials', { keyPath: 'key' });
            }
        };
        request.onerror = () => reject(request.error || new Error('Offline POS credentials could not be opened.'));
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction('offline-credentials', 'readwrite');
            transaction.objectStore('offline-credentials').clear();
            transaction.oncomplete = () => {
                db.close();
                resolve();
            };
            transaction.onerror = () => {
                db.close();
                reject(transaction.error || new Error('Offline POS credentials could not be cleared.'));
            };
            transaction.onabort = () => {
                db.close();
                reject(transaction.error || new Error('Offline POS credentials could not be cleared.'));
            };
        };
    });
}

async function notifyOfflineAuthCleared() {
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    clients.forEach((client) => client.postMessage({ type: 'offline-auth-cleared' }));
}

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => (key.startsWith('smartprobook-static-') && key !== STATIC_CACHE)
                    || key === PREVIOUS_POS_PAGE_CACHE)
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'clear-private-pos-cache') {
        event.waitUntil(Promise.all([
            caches.delete(POS_PAGE_CACHE),
            clearOfflineCredentials(),
            notifyOfflineAuthCleared(),
        ]).catch((error) => {
            console.error('Could not clear private offline POS data.', error);
            throw error;
        }));
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    const isLogout = ['GET', 'POST'].includes(request.method)
        && /(?:^|\/)(?:emergency\/)?logout(?:-emergency)?\/?$/.test(url.pathname);
    const normalizedPath = url.pathname.replace(/\/+$/, '');
    const isLogin = request.method === 'POST'
        && ['/login', '/saas-login', '/login-account'].includes(normalizedPath);
    const isCredentialChange = ['POST', 'PUT', 'PATCH'].includes(request.method)
        && /(?:^|\/)(?:reset-password|profile\/password|settings\/password)\/?$/.test(url.pathname);
    if (url.origin === self.location.origin && (isLogout || isLogin || isCredentialChange)) {
        event.respondWith(fetch(request).then((response) => {
            if (response.ok || response.redirected) {
                if (isLogout || isCredentialChange) {
                    event.waitUntil(Promise.all([
                        caches.delete(POS_PAGE_CACHE),
                        clearOfflineCredentials(),
                        notifyOfflineAuthCleared(),
                    ]).catch((error) => {
                        console.error('Could not clear private offline POS data.', error);
                        throw error;
                    }));
                } else {
                    event.waitUntil(caches.delete(POS_PAGE_CACHE));
                }
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
