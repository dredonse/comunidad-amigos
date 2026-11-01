/* Service Worker de Comunidad de Amigos
 *
 * Caché SOLO de recursos estáticos y de una página "sin conexión" genérica.
 * Las páginas HTML (feed, mensajes, administración…) nunca se guardan: contienen datos
 * privados y seguirían en el navegador después de cerrar sesión.
 */
const CACHE_NAME = 'comunidad-static-v2';
const STATIC_URLS = ['/offline.html', '/css/app.css', '/js/app.js', '/img/icon-192.png', '/img/default.svg'];
const STATIC_PREFIXES = ['/css/', '/js/', '/img/'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    // Borra cachés de versiones anteriores (la v1 guardaba páginas privadas)
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Navegación: siempre la red; sin conexión, la página genérica
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    // Estáticos: red primero, copia en caché como reserva
    if (STATIC_PREFIXES.some((prefix) => url.pathname.startsWith(prefix))) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() => caches.match(request))
        );
    }
    // Todo lo demás (uploads, peticiones AJAX…) va a la red sin pasar por la caché
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

    // Solo se abren URLs del propio sitio
    const target = new URL(event.notification.data?.url || '/feed', self.location.origin);
    const targetUrl = target.origin === self.location.origin ? target.href : self.location.origin + '/feed';

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
