/* =====================================================
   DOVEN BURGER - pwa.js
   Pendaftaran service worker, tombol "Pasang aplikasi", dan info online/offline.
   Service worker hanya aktif lewat HTTPS (atau localhost), sesuai aturan browser.
   ===================================================== */
(function () {
    'use strict';

    var toast = function (msg, isError) { if (window.Doven && window.Doven.toast) window.Doven.toast(msg, isError); };
    var aman = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';

    if ('serviceWorker' in navigator && aman) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('sw.js').catch(function () { /* tidak kritis */ });
        });
    }

    /* ---------- Tombol pasang aplikasi ---------- */
    var btn = document.getElementById('installBtn');
    var tunda = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        tunda = e;
        if (btn) btn.hidden = false;
    });
    if (btn) {
        btn.addEventListener('click', function () {
            if (!tunda) return;
            tunda.prompt();
            tunda.userChoice.then(function (pilihan) {
                if (pilihan.outcome === 'accepted') toast('Terima kasih! DOVEN BURGER dipasang di perangkatmu.');
                tunda = null;
                btn.hidden = true;
            });
        });
    }
    window.addEventListener('appinstalled', function () { tunda = null; if (btn) btn.hidden = true; });

    /* ---------- Online / offline ---------- */
    function setOnline(on, tampilkanToast) {
        document.body.classList.toggle('is-offline', !on);
        if (!tampilkanToast) return;
        if (on) toast('Kembali online.');
        else toast('Kamu sedang offline. Pesanan bisa dikirim lagi setelah tersambung.', true);
    }
    window.addEventListener('online', function () { setOnline(true, true); });
    window.addEventListener('offline', function () { setOnline(false, true); });
    if (!navigator.onLine) setOnline(false, false);
})();
