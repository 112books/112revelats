var CACHE = '112revelats-v1';
var PRECACHE = [
  '/',
  '/css/fonts.css',
  '/fonts/beiruti-400-latin.woff2',
  '/fonts/literata-200-900-latin.woff2',
  '/img/112revelats-logo.png',
  '/img/favicon.png'
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE).then(function (cache) {
      return cache.addAll(PRECACHE);
    }).then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); }));
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function (event) {
  var req = event.request;
  if (req.method !== 'GET') { return; }
  var url = new URL(req.url);
  if (url.origin !== self.location.origin) { return; }

  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(function () { return caches.match('/'); }));
    return;
  }

  event.respondWith(
    fetch(req).then(function (resp) {
      if (resp && resp.ok) {
        var copy = resp.clone();
        caches.open(CACHE).then(function (cache) { cache.put(req, copy); });
      }
      return resp;
    }).catch(function () {
      return caches.match(req);
    })
  );
});
