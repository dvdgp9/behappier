// behappier Service Worker (Online-First Strategy)
const CACHE_NAME = 'behappier-v1.3';
const ASSETS = [
  '/',
  '/index.php',
  '/home.php',
  '/task.php',
  '/history.php',
  '/account.php',
  '/assets/styles.css',
  '/assets/app.js',
  '/assets/brand/Logo-behappier-180.png',
  '/assets/brand/Logo-behappier-192.png',
  '/assets/brand/favicon.png',
  '/assets/sfx/timer-end.mp3'
];

// Install event - cache all assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(ASSETS))
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => 
      Promise.all(keys.map((key) => {
        if (key !== CACHE_NAME) {
          return caches.delete(key);
        }
      }))
    ).then(() => self.clients.claim())
  );
});

// Fetch event - network first strategy with cache fallback
self.addEventListener('fetch', (event) => {
  const req = event.request;
  
  // Network-first for PHP pages and documents, cache-first for assets
  if (req.destination === 'document' || req.headers.get('Accept')?.includes('text/html')) {
    event.respondWith(
      fetch(req)
        .then((response) => {
          // Clone the response to put in cache
          const responseClone = response.clone();
          
          // Cache the response for future use
          caches.open(CACHE_NAME)
            .then((cache) => cache.put(req, responseClone));
            
          return response;
        })
        .catch(() => {
          // Fallback to cache if network fails
          return caches.match(req);
        })
    );
  } else {
    // Cache-first for assets
    event.respondWith(
      caches.match(req)
        .then((response) => {
          // Return cached version if found
          if (response) return response;
          
          // Otherwise fetch from network
          return fetch(req);
        })
    );
  }
});
