/*
 * Service worker SI-PASAR.
 * Cuma menyimpan cache untuk file statis (CSS/JS/ikon) supaya buka app lebih cepat
 * dan tetap tampil walau sinyal jelek. TIDAK menyimpan data laporan/notifikasi -
 * itu wajib selalu diambil dari server supaya statusnya selalu akurat.
 */
const CACHE = 'sipasar-shell-v1';
const SHELL = [
    '/css/sipasar.min.css',
    '/js/app.min.js',
    '/js/zoom-viewer.min.js',
    '/icon.svg',
    '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => Promise.all(
            SHELL.map((url) => fetch(url).then((res) => res.ok && cache.put(url, res)).catch(() => {}))
        ))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin || !SHELL.includes(url.pathname)) return; // hanya shell statis

    event.respondWith(
        caches.match(request).then((cached) => cached || fetch(request).then((res) => {
            if (res.ok) caches.open(CACHE).then((c) => c.put(request, res.clone()));
            return res;
        }))
    );
});
