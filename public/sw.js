// Service worker minimal du CRM (PWA).
// Stratégie : réseau d'abord (les données CRM doivent être fraîches), avec
// repli sur le cache pour la coquille de l'app si hors-ligne.
const CACHE = 'crm-shell-v1';
const SHELL = ['/manifest.webmanifest', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    // On ne touche qu'aux GET de même origine ; le reste passe au réseau.
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }
    event.respondWith(
        fetch(request)
            .then((resp) => {
                // Met en cache les assets statiques (build, icônes).
                if (resp.ok && /\/(build|icons)\//.test(request.url)) {
                    const copy = resp.clone();
                    caches.open(CACHE).then((c) => c.put(request, copy));
                }
                return resp;
            })
            .catch(() => caches.match(request))
    );
});
