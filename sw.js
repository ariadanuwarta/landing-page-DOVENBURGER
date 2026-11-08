/* =====================================================
   DOVEN BURGER - sw.js (service worker)
   - Aset statis (css/js/gambar): tampil cepat dari cache, diperbarui di belakang layar
   - Halaman: ambil dari jaringan dulu; bila offline, pakai salinan terakhir atau offline.html
   - Permintaan ke PHP (pesan, lacak, ulasan, admin) TIDAK pernah di-cache
   Naikkan VERSION setiap kali Anda mengubah berkas ini agar cache lama dibersihkan.
   ===================================================== */
'use strict';

const VERSION = 'doven-v5';
const STATIC = VERSION + '-static';
const PAGES = VERSION + '-pages';
const OFFLINE_URL = 'offline.html';
const PRECACHE = [OFFLINE_URL, 'manifest.webmanifest', 'assets/icons/icon-192.png'];
const LEWATI = /\/(proses|lacak|ulasan|admin|koneksi)\.php$/;
const ASET = /\.(?:css|js|png|jpe?g|webp|svg|woff2?)$/i;

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(STATIC).then(function (c) { return c.addAll(PRECACHE); }).then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then(function (keys) {
                return Promise.all(keys.filter(function (k) { return k.indexOf(VERSION) !== 0; }).map(function (k) { return caches.delete(k); }));
            })
            .then(function () { return self.clients.claim(); })
    );
});

function simpan(nama, req, res) {
    if (res && res.ok && res.type === 'basic') {
        var salinan = res.clone();
        caches.open(nama).then(function (c) { c.put(req, salinan); });
    }
    return res;
}

self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') return;
    var url = new URL(req.url);
    if (url.origin !== self.location.origin || LEWATI.test(url.pathname)) return;

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req)
                .then(function (res) { return simpan(PAGES, req, res); })
                .catch(function () {
                    return caches.match(req).then(function (hit) { return hit || caches.match(OFFLINE_URL); });
                })
        );
        return;
    }

    if (ASET.test(url.pathname) || url.pathname.endsWith('.webmanifest')) {
        event.respondWith(
            caches.match(req).then(function (hit) {
                var jaringan = fetch(req).then(function (res) { return simpan(STATIC, req, res); }).catch(function () { return hit; });
                return hit || jaringan;
            })
        );
    }
});
