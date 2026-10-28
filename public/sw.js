const VERSION = 'sajel-pwa-v4';
const STATIC_CACHE = `${VERSION}-static`;
const PAGE_CACHE = `${VERSION}-quick-attendance`;
const CORE_ASSETS = [
    '/manifest.webmanifest',
    '/favicon.ico',
    '/pwa-icon.svg',
    '/pwa-icon-192.png',
    '/pwa-icon-512.png',
];
const QUICK_ATTENDANCE_PATTERN = /^\/(?:ar|en|fr)\/auth\/quick-attendance\/?$/;

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(STATIC_CACHE);
        const buildAssets = await buildAssetUrls();
        const assets = [...new Set([...CORE_ASSETS, ...buildAssets])];

        // A single missing optional asset must not abort the installation.
        await Promise.allSettled(assets.map((asset) => cache.add(asset)));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('sajel-pwa-') && !key.startsWith(VERSION))
                    .map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'CLEAR_QUICK_ATTENDANCE_DATA') {
        event.waitUntil(caches.delete(PAGE_CACHE));

        return;
    }

    if (event.data?.type !== 'CACHE_QUICK_ATTENDANCE') {
        return;
    }

    event.waitUntil(cacheVisitedAttendancePage(event.data));
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));

        return;
    }

    if (QUICK_ATTENDANCE_PATTERN.test(url.pathname)) {
        event.respondWith(networkFirst(request, PAGE_CACHE));

        return;
    }

    if (CORE_ASSETS.includes(url.pathname)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));

        return;
    }

    if (['style', 'script', 'font', 'image'].includes(request.destination)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
    }
});

/**
 * Read Vite's build manifest so the first offline refresh has the hashed
 * JavaScript and CSS assets, not just the HTML page.
 */
async function buildAssetUrls() {
    try {
        const response = await fetch('/build/manifest.json', { cache: 'no-store' });

        if (! response.ok) {
            return [];
        }

        const manifest = await response.json();
        const visited = new Set();
        const urls = new Set();

        const visit = (key) => {
            if (visited.has(key) || ! manifest[key]) {
                return;
            }

            visited.add(key);

            const entry = manifest[key];

            if (entry.file) {
                urls.add(`/build/${entry.file}`);
            }

            [...(entry.css ?? []), ...(entry.assets ?? [])].forEach((file) => {
                urls.add(`/build/${file}`);
            });

            [...(entry.imports ?? []), ...(entry.dynamicImports ?? [])].forEach(visit);
        };

        Object.keys(manifest).forEach(visit);

        return [...urls];
    } catch (error) {
        console.warn('Sajel could not pre-cache the Vite build manifest.', error);

        return [];
    }
}

async function cacheVisitedAttendancePage({ pageUrl, assetUrls = [] }) {
    try {
        const page = new URL(pageUrl, self.location.origin);

        if (page.origin !== self.location.origin || ! QUICK_ATTENDANCE_PATTERN.test(page.pathname)) {
            return;
        }

        const response = await fetch(page.href, {
            cache: 'no-store',
            credentials: 'same-origin',
        });
        const contentType = response.headers.get('content-type') ?? '';

        if (! response.ok || response.redirected || ! contentType.includes('text/html')) {
            return;
        }

        const pageCache = await caches.open(PAGE_CACHE);
        await pageCache.put(page.href, response.clone());

        const staticCache = await caches.open(STATIC_CACHE);
        const assets = [...new Set(assetUrls)]
            .map((url) => {
                try {
                    return new URL(url, self.location.origin);
                } catch {
                    return null;
                }
            })
            .filter((url) => url?.origin === self.location.origin)
            .map((url) => url.href);

        await Promise.allSettled(assets.map((url) => staticCache.add(url)));
    } catch (error) {
        console.warn('Sajel could not cache the current attendance page for offline use.', error);
    }
}

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    const response = await fetch(request);

    if (response.ok) {
        await cache.put(request, response.clone());
    }

    return response;
}

async function networkFirst(request, cacheName) {
    const cache = await caches.open(cacheName);

    try {
        const response = await fetch(request);
        const isAuthenticatedPage = response.ok
            && ! response.redirected
            && response.headers.get('content-type')?.includes('text/html');

        if (isAuthenticatedPage) {
            await cache.put(request, response.clone());
        }

        if (response.status >= 500) {
            const cached = await cache.match(request);

            if (cached) {
                return cached;
            }
        }

        return response;
    } catch {
        const cached = await cache.match(request);

        if (cached) {
            return cached;
        }

        return new Response('This attendance page is not available offline yet.', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}
