const CACHE_NAME = 'comunidad-v1';
const STATIC_URLS = ['/', '/feed', '/css/app.css', '/js/app.js', '/img/icon-192.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (response && response.status === 200 && response.type === 'basic') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => caches.match(event.request))
    );
});

self.addEventListener('push', (event) => {
    let data = { title: 'Comunidad', body: '', url: '/feed', icon: '/img/icon-192.png' };

    try {
        if (event.data) {
            data = { ...data, ...event.data.json() };
        }
    } catch (e) {
        /* usar valores por defecto */
    }

    const options = {
        body: data.body || '',
        icon: data.icon || '/img/icon-192.png',
        badge: '/img/icon-192.png',
        data: { url: data.url || '/feed' },
    };

    event.waitUntil(self.registration.showNotification(data.title || 'Comunidad', options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const rawUrl = event.notification.data?.url || '/feed';
    const targetUrl = new URL(rawUrl, self.location.origin).href;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
