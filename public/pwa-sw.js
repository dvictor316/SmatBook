const SMARTPROBOOK_SW_VERSION = 'smartprobook-desktop-install-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Intentionally no fetch cache: SmartProBook remains live and server-driven, avoiding stale tenant data.
