const CACHE_NAME = 'vitae-pro-v1';
const ASSETS_TO_CACHE = [
    '/offline.html',
    '/public/images/Curriculo Vitae Pro - logomarca.avif',
    '/public/images/icon-192.png'
];

// Install Event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', (event) => {
    // Apenas requisições GET
    if (event.request.method !== 'GET') return;

    // Estratégia de Cache para Imagens e Fontes: Cache First, Network Fallback
    // Estratégia para HTML/PHP: Network First, Fallback to Offline Page

    const url = new URL(event.request.url);

    // Imagens e arquivos estáticos da pasta public
    if (url.pathname.startsWith('/public/')) {
        event.respondWith(
            caches.match(event.request).then((response) => {
                return response || fetch(event.request).then((fetchResponse) => {
                    // Opcional: Cache dinâmico de novos assets. 
                    // Por segurança, não vamos cachear dinamicamente para não encher o disco.
                    return fetchResponse;
                });
            })
        );
        return;
    }

    // Navegação
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.match('/offline.html');
            })
        );
        return;
    }
});
