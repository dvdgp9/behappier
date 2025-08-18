// Minimal service worker for behappier (cache static assets)
const CACHE_NAME = 'behappier-v1.2';
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

self.addEventListener('fetch', (event) => {
  const req = event.request;
  // Network-first for PHP pages, cache-first for assets
  if (req.destination === 'document' || req.headers.get('Accept')?.includes('text/html')) {
    event.respondWith(
      fetch(req).then((res) => {
        const resClone = res.clone();
        caches.open(CACHE_NAME).then((c) => c.put(req, resClone));
        return res;
      }).catch(() => caches.match(req))
    );
  } else {
    event.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        const resClone = res.clone();
        caches.open(CACHE_NAME).then((c) => c.put(req, resClone));
        return res;
      }))
    );
  }
});
