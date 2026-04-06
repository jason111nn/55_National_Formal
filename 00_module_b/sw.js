const CACHE_NAME = 'healthy-diet-v1';

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            // 快取離線時需要的基礎頁面與圖
            return cache.addAll(['./offline.html']);
        })
    );
});

self.addEventListener('fetch', event => {
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match('./offline.html'))
        );
    }
});
