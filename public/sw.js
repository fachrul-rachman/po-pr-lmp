/* Service worker for static asset caching only.
 * Must not cache Livewire responses, PO/PR details, uploaded photos, Accurate, workflow actions, or admin logs.
 */

const CACHE_VERSION = 'v1';
const STATIC_CACHE = `po-pr-static-${CACHE_VERSION}`;

const OFFLINE_URL = '/offline.html';
const PRECACHE_URLS = [
  OFFLINE_URL,
  '/manifest.webmanifest',
  '/pwa/icon-192.png',
  '/pwa/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    (async () => {
      const cache = await caches.open(STATIC_CACHE);
      await cache.addAll(PRECACHE_URLS);
      self.skipWaiting();
    })()
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    (async () => {
      const keys = await caches.keys();
      await Promise.all(
        keys.map((k) => {
          if (k.startsWith('po-pr-static-') && k !== STATIC_CACHE) return caches.delete(k);
          return Promise.resolve();
        })
      );
      self.clients.claim();
    })()
  );
});

function isSameOrigin(url) {
  try {
    return new URL(url).origin === self.location.origin;
  } catch {
    return false;
  }
}

function shouldBypassCache(requestUrl) {
  // Never cache uploaded photos or any sensitive pages/data.
  // Also never cache Livewire endpoints.
  const u = new URL(requestUrl, self.location.origin);
  const p = u.pathname;

  if (!isSameOrigin(u.href)) return true;

  if (p.startsWith('/livewire')) return true;
  if (p.startsWith('/item-photos/')) return true;

  // Never cache any app HTML pages (documents/logs/etc).
  // Navigation is always network-first with offline fallback.
  if (
    p.startsWith('/warehouse') ||
    p.startsWith('/spv') ||
    p.startsWith('/finance') ||
    p.startsWith('/admin') ||
    p.startsWith('/purchasing') ||
    p.startsWith('/dashboard') ||
    p.startsWith('/login') ||
    p.startsWith('/logout')
  ) {
    return true;
  }

  return false;
}

function isStaticAsset(request) {
  if (request.method !== 'GET') return false;
  const url = new URL(request.url);
  const p = url.pathname;

  if (!isSameOrigin(request.url)) return false;
  if (shouldBypassCache(request.url)) return false;

  // Vite build assets, css/js, icons.
  return (
    p.startsWith('/build/') ||
    p.startsWith('/pwa/') ||
    p.endsWith('.css') ||
    p.endsWith('.js') ||
    p.endsWith('.png') ||
    p.endsWith('.jpg') ||
    p.endsWith('.jpeg') ||
    p.endsWith('.svg') ||
    p.endsWith('.webp') ||
    p.endsWith('.ico')
  );
}

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Never intercept non-GET mutations: must be online.
  if (req.method !== 'GET') {
    event.respondWith(fetch(req));
    return;
  }

  // Navigation: network-first, then offline fallback.
  if (req.mode === 'navigate' || (req.headers.get('accept') || '').includes('text/html')) {
    event.respondWith(
      (async () => {
        try {
          return await fetch(req);
        } catch {
          const cache = await caches.open(STATIC_CACHE);
          return (await cache.match(OFFLINE_URL)) || new Response('Offline', { status: 503 });
        }
      })()
    );
    return;
  }

  // Static assets: cache-first.
  if (isStaticAsset(req)) {
    event.respondWith(
      (async () => {
        const cache = await caches.open(STATIC_CACHE);
        const cached = await cache.match(req);
        if (cached) return cached;

        const res = await fetch(req);
        // Cache successful same-origin responses only.
        if (res && res.ok && isSameOrigin(req.url)) {
          cache.put(req, res.clone());
        }
        return res;
      })()
    );
    return;
  }

  // Everything else: network-only (no caching of sensitive data).
  event.respondWith(fetch(req));
});

