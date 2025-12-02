/**
 * Service Worker para PWA
 * Maneja cache y funcionalidad offline
 */

const CACHE_NAME = 'reysystem-v1.0.0';
const ASSETS_TO_CACHE = [
    '/ReySystemDemo/',
    '/ReySystemDemo/index.php',
    '/ReySystemDemo/features/dashboard/analytics.php',
    '/ReySystemDemo/inventario.php',
    '/ReySystemDemo/nueva_venta.php',
    '/ReySystemDemo/features/command_palette/palette.js',
    'https://cdn.tailwindcss.com',
    'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Installing...');

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching assets');
                return cache.addAll(ASSETS_TO_CACHE);
            })
            .then(() => self.skipWaiting())
    );
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating...');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Estrategia de fetch: Network First, fallback to Cache
self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Si la respuesta es válida, guardarla en cache
                if (response && response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Si falla la red, intentar obtener del cache
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }

                    // Si no está en cache, mostrar página offline
                    if (event.request.mode === 'navigate') {
                        return caches.match('/ReySystemDemo/offline.html');
                    }
                });
            })
    );
});

// Sincronización en background
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);

    if (event.tag === 'sync-data') {
        event.waitUntil(syncData());
    }
});

// Notificaciones Push
self.addEventListener('push', (event) => {
    console.log('[SW] Push received');

    const data = event.data ? event.data.json() : {};
    const title = data.title || 'ReySystem';
    const options = {
        body: data.body || 'Nueva notificación',
        icon: '/ReySystemDemo/assets/icon-192.png',
        badge: '/ReySystemDemo/assets/badge.png',
        vibrate: [200, 100, 200],
        data: data.url || '/ReySystemDemo/',
        actions: [
            { action: 'open', title: 'Abrir' },
            { action: 'close', title: 'Cerrar' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Click en notificación
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'open' || !event.action) {
        const url = event.notification.data || '/ReySystemDemo/';
        event.waitUntil(
            clients.openWindow(url)
        );
    }
});

// Función auxiliar para sincronizar datos
async function syncData() {
    try {
        // Aquí se pueden sincronizar datos pendientes
        console.log('[SW] Syncing data...');
        return Promise.resolve();
    } catch (error) {
        console.error('[SW] Sync error:', error);
        return Promise.reject(error);
    }
}
