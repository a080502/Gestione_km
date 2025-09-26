const cacheName = 'v1';
const cacheAssets = [
  'index.html',
  'global.css',
  'script.js',
  'icona-192x192.png',
  'icona-512x512.png'
];

// Chiama l'evento install
self.addEventListener('install', e => {
  console.log('Service Worker: Installed');
  e.waitUntil(
    caches
      .open(cacheName)
      .then(cache => {
        console.log('Service Worker: Caching Files');
        cache.addAll(cacheAssets);
      })
      .then(() => self.skipWaiting())
  );
});

// Chiama l'evento activate
self.addEventListener('activate', e => {
  console.log('Service Worker: Activated');
  // Rimuovi cache indesiderate
  e.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== cacheName) {
            console.log('Service Worker: Clearing Old Cache');
            return caches.delete(cache);
          }
        })
      );
    })
  );
});

// Chiama l'evento fetch
self.addEventListener('fetch', e => {
  console.log('Service Worker: Fetching');
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});