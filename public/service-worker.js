/**
 * Play Snooker service worker.
 *
 * Caches static build assets (CSS/JS bundles, icons, fonts) and the
 * practice-mode page shell so the digital pool/snooker game engine keeps
 * working offline once a player has loaded it at least once. Live,
 * account-specific pages (dashboard, wallet, betting, etc.) always hit the
 * network first since they depend on fresh, personalized server data.
 */

const CACHE_VERSION = 'play-snooker-v2';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PAGE_CACHE = `${CACHE_VERSION}-pages`;

const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    '/play/practice',
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    OFFLINE_URL,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)).catch(() => {}),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => key.startsWith('play-snooker-') && key !== STATIC_CACHE && key !== PAGE_CACHE)
                .map((key) => caches.delete(key)),
        )),
    );
    self.clients.claim();
});

function isStaticAsset(url) {
    return url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || /\.(css|js|woff2?|png|jpg|jpeg|svg|webp)$/.test(url.pathname);
}

self.addEventListener('push', (event) => {
    if (!event.data) return;

    const payload = event.data.json();

    event.waitUntil(
        self.registration.showNotification(payload.title || 'Play Snooker', {
            body: payload.body,
            icon: payload.icon || '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            data: payload.data || {},
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/dashboard';

    event.waitUntil(
        self.clients.matchAll({ type: 'window' }).then((clients) => {
            const existing = clients.find((client) => client.url.includes(url));
            if (existing) return existing.focus();
            return self.clients.openWindow(url);
        }),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Static build assets & icons: cache-first for instant, offline-capable loads.
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.open(STATIC_CACHE).then(async (cache) => {
                const cached = await cache.match(request);
                if (cached) return cached;

                try {
                    const response = await fetch(request);
                    cache.put(request, response.clone()).catch(() => {});
                    return response;
                } catch (error) {
                    return cached || Response.error();
                }
            }),
        );
        return;
    }

    // Page navigations: network-first, falling back to cache, then an
    // offline page. The practice page is cached explicitly at install time
    // so solo practice always works without a connection.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Clone synchronously, before returning the original response
                    // to the browser — if this were done inside the async
                    // `caches.open().then(...)` callback below, the browser could
                    // already be reading the original response's body by the time
                    // that callback runs, and `clone()` throws once a response
                    // body has started being consumed.
                    const responseToCache = response.clone();

                    caches.open(PAGE_CACHE)
                        .then((cache) => cache.put(request, responseToCache))
                        .catch(() => {});

                    return response;
                })
                .catch(async () => {
                    const cache = await caches.open(PAGE_CACHE);
                    const staticCache = await caches.open(STATIC_CACHE);
                    return (await cache.match(request))
                        || (await staticCache.match(request))
                        || (await staticCache.match(OFFLINE_URL));
                }),
        );
    }
});
