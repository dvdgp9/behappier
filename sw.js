// Service worker for behappier — online-first with cache fallback
const CACHE_NAME = 'behappier-v1.3';
const ASSETS = [
  '/',
  '/index.php',
  '/home.php',
  '/assets/styles.css',
  '/assets/app.js',
  '/assets/brand/Logo-behappier-180.png',
  '/assets/brand/Logo-behappier-192.png',
  '/assets/brand/favicon.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.map((k) => k !== CACHE_NAME && caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

// Online-first: try network, update cache, fallback to cache
self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  event.respondWith(
    fetch(request)
      .then((networkResp) => {
        // Update cache in background (same-origin only)
        const copy = networkResp.clone();
        if (new URL(request.url).origin === self.location.origin) {
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy)).catch(() => {});
        }
        return networkResp;
      })
      .catch(() => caches.match(request).then((cached) => cached || caches.match('/')))
  );
});

// (Removed secondary fetch handler to avoid conflicts)
