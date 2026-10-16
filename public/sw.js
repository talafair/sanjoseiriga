const CACHE_NAME = 'talafair-shell-v6';
const APP_SHELL = [
  new URL('./manifest.json', self.location).href,
  new URL('./images/talafair-logo.png', self.location).href,
  new URL('./images/default-avatar.svg', self.location).href,
  new URL('./css/theme.css', self.location).href,
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('push', (event) => {
  const rawPayload = event.data ? event.data.text() : '{}';
  let payload = {};

  try {
    payload = JSON.parse(rawPayload);
  } catch {
    payload = { body: rawPayload };
  }

  event.waitUntil(self.registration.showNotification(payload.title || 'TalaFair', {
    body: payload.body || 'You have a new TalaFair update.',
    icon: payload.icon || new URL('./images/icon-192.png', self.location).href,
    badge: new URL('./images/icon-192.png', self.location).href,
    tag: payload.tag || 'talafair-update',
    data: payload.data || {},
  }));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const requestedUrl = event.notification.data?.url || self.registration.scope;
  let destination = new URL(self.registration.scope);
  try {
    const candidate = new URL(requestedUrl, self.registration.scope);
    if (candidate.origin === self.location.origin) destination = candidate;
  } catch {
    destination = new URL(self.registration.scope);
  }

  event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async (windows) => {
    for (const client of windows) {
      if (new URL(client.url).origin !== self.location.origin) continue;
      await client.navigate(destination.href);
      return client.focus();
    }

    return self.clients.openWindow(destination.href);
  }));
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const requestUrl = new URL(event.request.url);
  const isSameOrigin = requestUrl.origin === self.location.origin;
  const isQrLibrary = requestUrl.href.startsWith('https://unpkg.com/html5-qrcode@');
  if (!isSameOrigin && !isQrLibrary) return;

  const networkFirst = event.request.mode === 'navigate';
  const cacheResponse = (response) => {
    if (/\bno-store\b/i.test(response.headers.get('Cache-Control') || '')) {
      return response;
    }

    const copy = response.clone();
    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
    return response;
  };

  event.respondWith(
    (networkFirst ? fetch(event.request).then(cacheResponse).catch(() => caches.match(event.request)) : caches.match(event.request).then((cached) => cached || fetch(event.request).then(cacheResponse)).catch(() => caches.match(event.request)))
  );
});
