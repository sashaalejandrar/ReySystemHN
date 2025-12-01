// Service Worker for Rey System APP PWA
const CACHE_NAME = 'reysystem-v1.0.0';
const urlsToCache = [
    '/ReySystemDemo/',
    '/ReySystemDemo/index.php',
    '/ReySystemDemo/login.php',
    '/ReySystemDemo/ventas.php',
    '/ReySystemDemo/stock.php',
    '/ReySystemDemo/chat.php',
    '/ReySystemDemo/foro.php',
    '/ReySystemDemo/nova_rey.js',
    '/ReySystemDemo/manifest.json'
];

// Install event - cache resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
            .catch(err => console.log('Cache install error:', err))
    );
    self.skipWaiting();
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip chrome extensions and other non-http requests
    if (!event.request.url.startsWith('http')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Cache hit - return response
                if (response) {
                    return response;
                }

                // Clone the request
                const fetchRequest = event.request.clone();

                return fetch(fetchRequest).then(response => {
                    // Check if valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    // Clone the response
                    const responseToCache = response.clone();

                    // Cache the new response
                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                        });

                    return response;
                }).catch(error => {
                    console.log('Fetch error:', error);
                    // Return offline page if available
                    return caches.match('/ReySystemDemo/offline.html');
                });
            })
    );
});

// Background sync for offline actions
self.addEventListener('sync', event => {
    if (event.tag === 'sync-data') {
        event.waitUntil(syncData());
    }
});

async function syncData() {
    // Sync offline data when connection is restored
    console.log('Syncing offline data...');
}

// Push notifications
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : 'Nueva notificación',
        icon: '/ReySystemDemo/pwa-icons/icon-192x192.png',
        badge: '/ReySystemDemo/pwa-icons/icon-72x72.png',
        vibrate: [200, 100, 200],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        }
    };

    event.waitUntil(
        self.registration.showNotification('Rey System APP', options)
    );
});

// Notification click
self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('/ReySystemDemo/index.php')
    );
});
