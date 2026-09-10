const CACHE_NAME = 'okgv-static-v1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET' || !new URL(request.url).pathname.startsWith('/build/')) {
        return;
    }

    event.respondWith(caches.open(CACHE_NAME).then(async (cache) => {
        const cached = await cache.match(request);
        const network = fetch(request).then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }

            return response;
        });

        return cached ?? network;
    }));
});
