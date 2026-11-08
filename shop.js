/* =====================================================
   DOVEN BURGER - shop.js
   Keranjang multi-item, kustomisasi item, checkout, promo,
   lacak pesanan, filter/cari/urut menu, favorit, pesan ulang,
   saran tambahan (upsell), pesanan terjadwal, info pembayaran,
   ulasan pelanggan, dan cetak struk.
   Harga di sini hanya untuk tampilan; server selalu menghitung ulang.
   ===================================================== */
(function () {
    'use strict';

    var CFG = window.DOVEN || {};
    var MENU = CFG.menu || {};
    var ADDON = CFG.addons || {};
    var MAXQ = CFG.maksQty || 20;
    var MAXL = CFG.maksBaris || 15;
    var TIPE_LABEL = { 'dine-in': 'Makan di tempat', takeaway: 'Bawa pulang', delivery: 'Antar ke alamat' };

    var $ = function (s, c) { return (c || document).querySelector(s); };
    var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
    var has = function (o, k) { return Object.prototype.hasOwnProperty.call(o, k); };
    var fmt = function (n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); };
    var esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };
    var toast = function (msg, isError) { if (window.Doven && window.Doven.toast) window.Doven.toast(msg, isError); };
    var store = {
        get: function (k) { try { return JSON.parse(localStorage.getItem(k) || 'null'); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* mode privat */ } }
    };
    var timers = {};
    function debounce(name, fn, ms) { clearTimeout(timers[name]); timers[name] = setTimeout(fn, ms); }

    function post(url, fd) {
        return fetch(url, { method: 'POST', body: fd, headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (res) { return res.json().then(function (data) { return { status: res.status, data: data }; }); })
            .catch(function () { return { status: 0, data: { sukses: false, pesan: 'Gagal terhubung ke server. Periksa koneksi Anda.' } }; });
    }

    /* =====================================================
       Overlay (modal & drawer): fokus terkunci, Esc, kunci scroll
       ===================================================== */
    var stack = [];
    function openOv(el) {
        if (el.classList.contains('open')) return;
        el._last = document.activeElement;
        el.classList.add('open');
        el.setAttribute('aria-hidden', 'false');
        stack.push(el);
        document.body.classList.add('no-scroll');
        var f = $('[data-autofocus]', el);
        if (f) setTimeout(function () { f.focus(); }, 380);
    }
    function closeOv(el) {
        if (!el.classList.contains('open')) return;
        el.classList.remove('open');
        el.setAttribute('aria-hidden', 'true');
        var i = stack.indexOf(el);
        if (i > -1) stack.splice(i, 1);
        if (!stack.length) document.body.classList.remove('no-scroll');
        if (el._last && el._last.focus) el._last.focus();
        el.dispatchEvent(new CustomEvent('overlayclose'));
    }
    document.addEventListener('click', function (e) {
        var c = e.target.closest('[data-close]');
        if (!c) return;
        var ov = c.closest('.modal, .drawer');
        if (ov) closeOv(ov);
    });
    document.addEventListener('keydown', function (e) {
        var top = stack[stack.length - 1];
        if (!top) return;
        if (e.key === 'Escape') { closeOv(top); return; }
        if (e.key !== 'Tab') return;
        var f = $$('button, input:not(.hp), textarea, select, a[href]', top).filter(function (n) {
            return !n.disabled && n.getClientRects().length > 0;
        });
        if (!f.length) return;
        var first = f[0], last = f[f.length - 1];
        if (!top.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
        else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    /* =====================================================
       Status buka / tutup (WIB)
       ===================================================== */
    function jakartaHour() {
        try {
            return parseInt(new Intl.DateTimeFormat('en-GB', { hour: '2-digit', hour12: false, timeZone: 'Asia/Jakarta' }).format(new Date()), 10) % 24;
        } catch (e) { return new Date().getHours(); }
    }
    function isOpen() { var h = jakartaHour(); return h >= CFG.buka && h < CFG.tutup; }
    var pill = $('#openPill');
    function updatePill() {
        var open = isOpen();
        if (pill) { pill.classList.toggle('on', open); pill.textContent = open ? 'Buka sekarang' : 'Sedang tutup'; }
    }
    updatePill();
    setInterval(updatePill, 60000);

    /* =====================================================
       State keranjang
       ===================================================== */
    var KEY = 'doven_cart_v1';
    var cart = [];

    function lineKey(l) { return [l.id, l.varian, l.addons.slice().sort().join('+'), l.catatan || ''].join('|'); }
    function unit(l) { return MENU[l.id].harga[l.varian] + l.addons.reduce(function (s, a) { return s + ADDON[a].harga; }, 0); }
    function subtotal() { return cart.reduce(function (s, l) { return s + unit(l) * l.qty; }, 0); }
    function count() { return cart.reduce(function (s, l) { return s + l.qty; }, 0); }
    function findLine(key) { return cart.filter(function (l) { return l.key === key; })[0]; }

    function cleanLine(l) {
        if (!l || !has(MENU, l.id) || !has(MENU[l.id].harga, l.varian)) return null;
        if (MENU[l.id].tersedia === false) return null;
        var qty = parseInt(l.qty, 10);
        if (!(qty >= 1 && qty <= MAXQ)) return null;
        var addons = Array.isArray(l.addons) ? l.addons.filter(function (a, i, arr) { return has(ADDON, a) && arr.indexOf(a) === i; }) : [];
        if (addons.length && !MENU[l.id].addon) return null;
        var out = { id: l.id, varian: l.varian, qty: qty, addons: addons, catatan: String(l.catatan || '').slice(0, 120) };
        out.key = lineKey(out);
        return out;
    }
    (function load() {
        var raw = store.get(KEY);
        if (Array.isArray(raw)) cart = raw.map(cleanLine).filter(Boolean).slice(0, MAXL);
    })();
    function saveCart() { store.set(KEY, cart.map(function (l) { return { id: l.id, varian: l.varian, qty: l.qty, addons: l.addons, catatan: l.catatan }; })); }
    function payload() {
        return JSON.stringify(cart.map(function (l) { return { id: l.id, varian: l.varian, qty: l.qty, addons: l.addons, catatan: l.catatan || '' }; }));
    }

    function addLine(raw) {
        var l = cleanLine(raw);
        if (!l) { toast('Item tidak valid. Muat ulang halaman.', true); return false; }
        var ex = findLine(l.key);
        if (ex) ex.qty = Math.min(MAXQ, ex.qty + l.qty);
        else if (cart.length >= MAXL) { toast('Keranjang penuh (maks. ' + MAXL + ' jenis item).', true); return false; }
        else cart.push(l);
        commit();
        bump();
        return true;
    }
    function removeLine(key) { cart = cart.filter(function (l) { return l.key !== key; }); }
    function commit() {
        saveCart();
        renderCart();
        if (promo) {
            if (!cart.length) clearPromo();
            else debounce('promo', function () { applyPromo(promo.kode, true); }, 350);
        }
    }

    /* =====================================================
       Render keranjang
       ===================================================== */
    var cartBtn = $('#cartBtn'), cartCount = $('#cartCount'), cartBar = $('#cartBar');
    var drawer = $('#cartDrawer'), cartList = $('#cartList'), cartEmpty = $('#cartEmpty'), cartFoot = $('#cartFoot');
    var views = { cart: $('#viewCart'), checkout: $('#viewCheckout'), success: $('#viewSuccess') };
    var TITLES = { cart: 'Keranjang', checkout: 'Checkout', success: 'Terima kasih' };
    var current = 'cart';
    var cartBack = $('#cartBack'), cartTitle = $('#cartTitle');
    var refocus = null;

    function bump() {
        cartCount.classList.remove('bump');
        void cartCount.offsetWidth;
        cartCount.classList.add('bump');
    }

    function rowHtml(l) {
        var m = MENU[l.id];
        var ad = l.addons.length ? ' · + ' + l.addons.map(function (a) { return ADDON[a].nama; }).join(', ') : '';
        return '<li class="cl" data-key="' + esc(l.key) + '" style="--accent:' + esc(m.warna) + '">' +
            '<span class="cl-ico" aria-hidden="true">' + m.ikon + '</span>' +
            '<div class="cl-info"><strong>' + esc(m.nama) + '</strong><small>' + esc(l.varian + ad) + '</small>' +
            (l.catatan ? '<small class="cl-note">“' + esc(l.catatan) + '”</small>' : '') +
            '<div class="cl-row"><div class="qty qty-sm" role="group" aria-label="Jumlah ' + esc(m.nama) + '">' +
            '<button type="button" data-act="dec" aria-label="Kurangi jumlah">&minus;</button><output>' + l.qty + '</output>' +
            '<button type="button" data-act="inc" aria-label="Tambah jumlah"' + (l.qty >= MAXQ ? ' disabled' : '') + '>+</button></div>' +
            '<span class="cl-price">' + fmt(unit(l) * l.qty) + '</span></div></div>' +
            '<button type="button" class="cl-del" data-act="del" aria-label="Hapus ' + esc(m.nama) + ' dari keranjang">&times;</button></li>';
    }

    function renderCart() {
        var n = count(), sub = subtotal();
        cartCount.textContent = n;
        cartCount.hidden = n === 0;
        cartBar.hidden = n === 0;
        $('#cbCount').textContent = n;
        $('#cbTotal').textContent = fmt(sub);
        document.body.classList.toggle('has-cart', n > 0);

        cartList.innerHTML = cart.map(rowHtml).join('');
        renderUpsell();
        cartEmpty.hidden = n > 0;
        cartFoot.hidden = n === 0;
        $('#cartSub').textContent = fmt(sub);

        var rem = (CFG.gratisOngkir || 0) - sub;
        $('#meterText').textContent = rem > 0
            ? 'Tambah ' + fmt(rem) + ' lagi untuk gratis ongkir (khusus pesanan antar).'
            : 'Selamat, pesanan antar kamu gratis ongkir.';
        $('#meterFill').style.width = Math.min(100, sub / (CFG.gratisOngkir || 1) * 100).toFixed(1) + '%';

        if (refocus) {
            var li = $$('.cl', cartList).filter(function (x) { return x.dataset.key === refocus.key; })[0];
            var btn = li && $('[data-act="' + refocus.act + '"]:not([disabled])', li);
            if (btn) btn.focus();
            refocus = null;
        }
        if (!views.checkout.hidden) {
            if (!n) showView('cart'); else renderSummary();
        }
    }

    cartList.addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]');
        if (!b) return;
        var key = b.closest('.cl').dataset.key;
        var l = findLine(key);
        if (!l) return;
        var act = b.dataset.act;
        if (act === 'inc') l.qty = Math.min(MAXQ, l.qty + 1);
        else if (act === 'dec') { l.qty -= 1; if (l.qty < 1) { removeLine(key); act = null; } }
        else { removeLine(key); act = null; }
        refocus = act ? { key: key, act: act } : null;
        commit();
    });

    /* ---------- Drawer: 3 tampilan ---------- */
    function showView(name) {
        Object.keys(views).forEach(function (k) { views[k].hidden = k !== name; });
        cartBack.hidden = name !== 'checkout';
        cartTitle.textContent = TITLES[name];
        var body = $('.drawer-body', views[name]);
        if (body) body.scrollTop = 0;
        current = name;
    }
    function openCart() {
        if (current !== 'cart') showView('cart');
        renderCart();
        openOv(drawer);
    }
    cartBtn.addEventListener('click', openCart);
    cartBar.addEventListener('click', openCart);
    cartBack.addEventListener('click', function () { showView('cart'); });
    drawer.addEventListener('overlayclose', function () { if (current === 'success') showView('cart'); });

    /* =====================================================
       Checkout
       ===================================================== */
    var form = $('#checkoutForm');
    var alamatField = $('#alamatField');
    var promoInput = $('#promoInput'), promoBtn = $('#promoBtn'), promoMsg = $('#promoMsg');
    var coSum = $('#coSum'), coTotalBtn = $('#coTotalBtn'), coSubmit = $('#coSubmit');
    var promo = null;          // { kode, diskon, gratis_ongkir }
    var pendingPromo = '';     // kode yang disalin dari kartu promo

    function tipe() { var r = $('input[name="tipe"]:checked', form); return r ? r.value : 'takeaway'; }

    function totals() {
        var sub = subtotal(), t = tipe();
        var diskon = promo ? promo.diskon : 0;
        var ongkir = 0;
        if (t === 'delivery') ongkir = (sub >= CFG.gratisOngkir || (promo && promo.gratis_ongkir)) ? 0 : CFG.ongkir;
        return { subtotal: sub, diskon: diskon, ongkir: ongkir, total: Math.max(0, sub - diskon + ongkir), tipe: t };
    }
    function sumHtml(t, kode) {
        var h = '<div><dt>Subtotal</dt><dd>' + fmt(t.subtotal) + '</dd></div>';
        if (t.diskon > 0) h += '<div class="minus"><dt>Diskon' + (kode ? ' (' + esc(kode) + ')' : '') + '</dt><dd>&minus;' + fmt(t.diskon) + '</dd></div>';
        if (t.tipe === 'delivery') h += '<div><dt>Ongkir</dt><dd>' + (t.ongkir > 0 ? fmt(t.ongkir) : 'Gratis') + '</dd></div>';
        return h + '<div class="tot"><dt>Total</dt><dd>' + fmt(t.total) + '</dd></div>';
    }
    function renderSummary() {
        var t = totals();
        coSum.innerHTML = sumHtml(t, promo && promo.kode);
        coTotalBtn.textContent = fmt(t.total);
    }

    function setPromoMsg(text, kind) {
        promoMsg.textContent = text || '';
        promoMsg.className = 'promo-msg' + (kind ? ' ' + kind : '');
    }
    function setPromoUi() {
        promoBtn.textContent = promo ? 'Hapus' : 'Pakai';
        promoInput.readOnly = !!promo;
    }
    function clearPromo() {
        promo = null;
        promoInput.value = '';
        setPromoMsg('');
        setPromoUi();
        renderSummary();
    }
    function applyPromo(code, quiet) {
        code = String(code || '').trim().toUpperCase();
        if (!code) { setPromoMsg('Masukkan kode promo terlebih dahulu.', 'bad'); return Promise.resolve(); }
        if (!cart.length) return Promise.resolve();
        promoBtn.disabled = true;
        var fd = new FormData();
        fd.append('aksi', 'promo');
        fd.append('kode_promo', code);
        fd.append('tipe', tipe());
        fd.append('keranjang', payload());
        return post('proses.php', fd).then(function (r) {
            promoBtn.disabled = false;
            if (r.data.sukses) {
                promo = { kode: r.data.kode, diskon: r.data.diskon, gratis_ongkir: !!r.data.gratis_ongkir };
                promoInput.value = promo.kode;
                var note = promo.gratis_ongkir && tipe() !== 'delivery' ? ' Berlaku untuk pesanan antar.' : '';
                setPromoMsg(r.data.pesan + note, 'ok');
            } else if (r.status === 0 && quiet) {
                return; // gangguan jaringan saat cek ulang: biarkan promo apa adanya
            } else {
                var had = promo;
                promo = null;
                setPromoMsg(r.data.pesan || 'Kode promo tidak valid.', 'bad');
                if (quiet && had) toast('Kode ' + had.kode + ' dilepas: syarat tidak terpenuhi lagi.', true);
            }
            setPromoUi();
            renderSummary();
        });
    }
    promoBtn.addEventListener('click', function () {
        if (promo) { clearPromo(); return; }
        applyPromo(promoInput.value, false);
    });
    promoInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); promoBtn.click(); }
    });

    $$('input[name="tipe"]', form).forEach(function (r) {
        r.addEventListener('change', function () {
            alamatField.hidden = tipe() !== 'delivery';
            if (promo && promo.gratis_ongkir) {
                setPromoMsg('Kode ' + promo.kode + ' dipakai.' + (tipe() !== 'delivery' ? ' Berlaku untuk pesanan antar.' : ''), 'ok');
            }
            renderSummary();
        });
    });
    // Error hilang begitu pengguna mulai memperbaiki isian
    [form, $('#trackForm')].forEach(function (f) {
        f.addEventListener('input', function (e) {
            var fld = e.target.closest('.field');
            if (!fld) return;
            fld.classList.remove('invalid');
            var er = $('.err', fld);
            if (er) er.textContent = '';
        });
    });
    $('#co_wa').addEventListener('input', function (e) { e.target.value = e.target.value.replace(/[^\d+\s\-]/g, ''); });

    function openCheckout() {
        if (!cart.length) return;
        var profile = store.get('doven_profile');
        if (profile) {
            if (!form.nama_lengkap.value && profile.nama) form.nama_lengkap.value = profile.nama;
            if (!form.nomor_wa.value && profile.wa) form.nomor_wa.value = profile.wa;
        }
        $('#closedNotice').hidden = isOpen();
        showView('checkout');
        renderSummary();
        updateWaktuLabel();
        if (waktuMode() === 'jadwal') loadSlots();
        if (pendingPromo && !promo) {
            promoInput.value = pendingPromo;
            applyPromo(pendingPromo, false);
        }
        pendingPromo = '';
        setTimeout(function () { if (!form.nama_lengkap.value) form.nama_lengkap.focus(); }, 380);
    }
    $('#toCheckout').addEventListener('click', openCheckout);

    function clearErrors() {
        $$('.err', form).forEach(function (x) { x.textContent = ''; });
        $$('.field', form).forEach(function (f) { f.classList.remove('invalid'); });
    }
    function setError(name, msg) {
        if (name === 'kode_promo') { setPromoMsg(msg, 'bad'); return; }
        var el = $('[data-err="' + name + '"]', form);
        if (!el) return;
        el.textContent = msg;
        var f = el.closest('.field');
        if (f) f.classList.add('invalid');
    }
    function validate() {
        clearErrors();
        var ok = true;
        var nama = form.nama_lengkap.value.trim();
        if (nama.length < 3) { setError('nama_lengkap', 'Nama lengkap minimal 3 karakter.'); ok = false; }
        else if (!/^[\p{L}\p{M}\s.'\-]+$/u.test(nama)) { setError('nama_lengkap', 'Nama hanya boleh berisi huruf dan spasi.'); ok = false; }
        var wa = form.nomor_wa.value.replace(/[\s\-().]/g, '').replace(/^\+?62/, '0');
        if (!/^08\d{8,12}$/.test(wa)) { setError('nomor_wa', 'Nomor WhatsApp tidak valid (contoh: 081234567890).'); ok = false; }
        if (tipe() === 'delivery' && form.alamat.value.trim().length < 10) {
            setError('alamat', 'Alamat antar minimal 10 karakter (jalan, nomor, patokan).'); ok = false;
        }
        if (waktuMode() === 'jadwal' && (jadwalSel.disabled || !jadwalSel.value)) {
            setError('jadwal', 'Pilih waktu terlebih dahulu, atau ganti ke "Secepatnya".'); ok = false;
        }
        return ok;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!cart.length) return;
        if (!validate()) {
            var bad = $('.field.invalid input, .field.invalid textarea', form);
            if (bad) bad.focus();
            toast('Mohon lengkapi data dengan benar.', true);
            return;
        }
        coSubmit.classList.add('loading');
        coSubmit.disabled = true;

        var fd = new FormData(form);
        fd.append('aksi', 'pesan');
        fd.append('keranjang', payload());
        fd.append('kode_promo', promo ? promo.kode : '');
        var waRaw = form.nomor_wa.value, namaRaw = form.nama_lengkap.value.trim();
        var salinan = cart.map(function (l) { return { id: l.id, varian: l.varian, qty: l.qty, addons: l.addons.slice(), catatan: l.catatan }; });

        post('proses.php', fd).then(function (r) {
            coSubmit.classList.remove('loading');
            coSubmit.disabled = false;
            var d = r.data;
            if (!d.sukses) {
                if (d.errors) Object.keys(d.errors).forEach(function (k) { setError(k, d.errors[k]); });
                if (d.errors && d.errors.jadwal) loadSlots();
                toast(d.pesan || 'Terjadi kesalahan.', true);
                return;
            }
            store.set(LASTKEY, salinan);
            var waDigits = waRaw.replace(/\D/g, '');
            store.set('doven_last', { kode: d.kode_pesanan, wa: waDigits.slice(-4) });
            store.set('doven_profile', { nama: namaRaw, wa: waRaw });

            $('#successMsg').textContent = d.pesan;
            $('#orderCode').textContent = d.kode_pesanan;
            var rs = d.ringkasan || {};
            rs.tipe = d.tipe;
            $('#successSum').innerHTML = '<div><dt>Tipe</dt><dd>' + esc(TIPE_LABEL[d.tipe] || d.tipe) + '</dd></div>' +
                (d.jadwal_label ? '<div><dt>Waktu</dt><dd>' + esc(d.jadwal_label) + ' WIB</dd></div>' : '') + sumHtml(rs, promo && promo.kode);
            renderPay(d.info_bayar);
            $('#waBtn').href = d.wa_url || '#';

            cart = [];
            saveCart();
            clearPromo();
            form.reset();
            alamatField.hidden = true;
            jadwalField.hidden = true;
            jadwalSel.disabled = true;
            renderCart();
            renderReorder();
            showView('success');
            toast('Pesanan ' + d.kode_pesanan + ' berhasil dikirim.');
        });
    });

    /* =====================================================
       Kartu menu: varian, tambah, filter / cari / urut
       ===================================================== */
    var grid = $('#menuGrid');
    var cards = $$('.menu-card', grid);

    cards.forEach(function (card) {
        var out = $('[data-price-out]', card);
        var opts = $$('.vr', card);
        opts.forEach(function (b) {
            b.addEventListener('click', function () {
                opts.forEach(function (x) { x.classList.remove('on'); x.setAttribute('aria-checked', 'false'); });
                b.classList.add('on');
                b.setAttribute('aria-checked', 'true');
                out.textContent = fmt(b.dataset.price);
            });
        });
        var addBtn = $('.js-add', card);
        addBtn.addEventListener('click', function () {
            var on = $('.vr.on', card);
            var id = card.dataset.id;
            var v = on ? on.dataset.v : Object.keys(MENU[id].harga)[0];
            if (addBtn.dataset.quick !== '1') { openItem(id, v); return; }
            if (addLine({ id: id, varian: v, qty: 1, addons: [], catatan: '' })) {
                toast(MENU[id].nama + ' (' + v + ') masuk keranjang.');
                var html = addBtn.innerHTML;
                addBtn.classList.add('added');
                addBtn.textContent = 'Ditambahkan';
                setTimeout(function () { addBtn.classList.remove('added'); addBtn.innerHTML = html; }, 1200);
            }
        });
    });

    var state = { kat: 'semua', q: '', sort: 'rekomendasi' };
    var tabBtns = $$('#menuTabs .tab');
    var searchEl = $('#menuSearch'), sortEl = $('#menuSort'), emptyBox = $('#menuEmpty'), countEl = $('#menuCount');
    var cmp = {
        rekomendasi: function (a, b) { return a.dataset.idx - b.dataset.idx; },
        'harga-asc': function (a, b) { return a.dataset.min - b.dataset.min; },
        'harga-desc': function (a, b) { return b.dataset.min - a.dataset.min; },
        nama: function (a, b) { return $('.menu-name', a).textContent.localeCompare($('.menu-name', b).textContent, 'id'); }
    };
    function applyMenu() {
        var q = state.q.trim().toLowerCase(), shown = 0;
        cards.slice().sort(cmp[state.sort]).forEach(function (c) { grid.appendChild(c); });
        cards.forEach(function (c) {
            var katOk = state.kat === 'semua' || (state.kat === 'favorit' ? favs.indexOf(c.dataset.id) > -1 : c.dataset.kat === state.kat);
            var show = katOk && (!q || c.dataset.nama.indexOf(q) > -1);
            var was = c.hidden;
            c.hidden = !show;
            if (show) {
                shown++;
                c.classList.add('visible');
                if (was) { c.classList.remove('pop'); void c.offsetWidth; c.classList.add('pop'); }
            }
        });
        var kosongFav = shown === 0 && state.kat === 'favorit' && !q;
        $('strong', emptyBox).textContent = kosongFav ? 'Belum ada menu favorit' : 'Menu tidak ditemukan';
        $('p', emptyBox).textContent = kosongFav ? 'Ketuk ikon hati pada kartu menu untuk menyimpannya di sini.' : 'Coba kata kunci lain, atau tampilkan semua menu.';
        emptyBox.hidden = shown > 0;
        countEl.textContent = shown + ' menu ditampilkan';
    }
    tabBtns.forEach(function (t) {
        t.addEventListener('click', function () {
            state.kat = t.dataset.kat;
            tabBtns.forEach(function (x) { x.setAttribute('aria-pressed', String(x === t)); });
            applyMenu();
        });
    });
    searchEl.addEventListener('input', function () { state.q = searchEl.value; applyMenu(); });
    sortEl.addEventListener('change', function () { state.sort = sortEl.value; applyMenu(); });
    $('#menuReset').addEventListener('click', function () {
        state = { kat: 'semua', q: '', sort: 'rekomendasi' };
        searchEl.value = '';
        sortEl.value = 'rekomendasi';
        tabBtns.forEach(function (x) { x.setAttribute('aria-pressed', String(x.dataset.kat === 'semua')); });
        applyMenu();
    });

    /* =====================================================
       Modal kustomisasi item
       ===================================================== */
    var itemModal = $('#itemModal'), imDialog = $('#imDialog'), imVariants = $('#imVariants'), imAddons = $('#imAddons');
    var imQty = $('#imQty'), imTotal = $('#imTotal'), imNote = $('#imNote');
    var im = null;

    function imUnit() { return MENU[im.id].harga[im.varian] + im.addons.reduce(function (s, a) { return s + ADDON[a].harga; }, 0); }
    function updateIm() {
        imTotal.textContent = fmt(imUnit() * im.qty);
        imQty.textContent = im.qty;
        $('#imMinus').disabled = im.qty <= 1;
        $('#imPlus').disabled = im.qty >= MAXQ;
    }
    function openItem(id, varian) {
        var m = MENU[id];
        var keys = Object.keys(m.harga);
        im = { id: id, varian: has(m.harga, varian) ? varian : keys[0], qty: 1, addons: [] };
        imDialog.style.setProperty('--accent', m.warna);
        $('#imIco').textContent = m.ikon;
        $('#imTitle').textContent = m.nama;
        $('#imDesc').textContent = m.desc;
        imVariants.style.setProperty('--n', keys.length);
        imVariants.innerHTML = keys.map(function (v) {
            var on = v === im.varian;
            return '<button type="button" class="vr' + (on ? ' on' : '') + '" role="radio" aria-checked="' + on + '" data-v="' + esc(v) + '"><span>' + esc(v) + '</span><b>' + fmt(m.harga[v]) + '</b></button>';
        }).join('');
        $('#imAddonWrap').hidden = !m.addon;
        imAddons.innerHTML = m.addon ? Object.keys(ADDON).map(function (k) {
            return '<label class="ad"><input type="checkbox" value="' + esc(k) + '"><span>' + esc(ADDON[k].nama) + '</span><b>+' + fmt(ADDON[k].harga) + '</b></label>';
        }).join('') : '';
        imNote.value = '';
        updateIm();
        openOv(itemModal);
    }
    imVariants.addEventListener('click', function (e) {
        var b = e.target.closest('.vr');
        if (!b || !im) return;
        im.varian = b.dataset.v;
        $$('.vr', imVariants).forEach(function (x) { var on = x === b; x.classList.toggle('on', on); x.setAttribute('aria-checked', String(on)); });
        updateIm();
    });
    imAddons.addEventListener('change', function () {
        im.addons = $$('input:checked', imAddons).map(function (i) { return i.value; });
        updateIm();
    });
    $('#imMinus').addEventListener('click', function () { if (im.qty > 1) { im.qty--; updateIm(); } });
    $('#imPlus').addEventListener('click', function () { if (im.qty < MAXQ) { im.qty++; updateIm(); } });
    $('#imAdd').addEventListener('click', function () {
        var line = { id: im.id, varian: im.varian, qty: im.qty, addons: im.addons.slice(), catatan: imNote.value.trim() };
        var nama = MENU[im.id].nama;
        if (addLine(line)) {
            closeOv(itemModal);
            toast(line.qty + '× ' + nama + ' (' + line.varian + ') masuk keranjang.');
        }
    });

    /* =====================================================
       Lacak pesanan
       ===================================================== */
    var trackModal = $('#trackModal'), trForm = $('#trackForm'), trKode = $('#tr_kode'), trWa = $('#tr_wa'), trBtn = $('#trackBtn');
    var trResult = $('#trackResult');
    var STEPS = ['baru', 'diproses', 'siap', 'selesai'];
    var poll = null;
    var lastTrack = null;
    var reviewForm = $('#reviewForm'), reviewDone = $('#reviewDone'), rvBtn = $('#rvBtn');

    function stepLabels(t) {
        return ['Diterima', 'Dibuat', t === 'delivery' ? 'Diantar' : (t === 'dine-in' ? 'Disajikan' : 'Siap diambil'), 'Selesai'];
    }
    function stopPoll() { if (poll) { clearInterval(poll); poll = null; } }
    function trErr(name, msg) {
        var el = $('[data-err="' + name + '"]', trForm);
        if (!el) return;
        el.textContent = msg;
        el.closest('.field').classList.add('invalid');
    }
    function trClear() {
        $$('.err', trForm).forEach(function (x) { x.textContent = ''; });
        $$('.field', trForm).forEach(function (f) { f.classList.remove('invalid'); });
    }

    var lastStatus = { kode: '', status: '' };
    function renderTrack(d, silent) {
        trResult.hidden = false;
        if (silent && lastStatus.kode === d.kode && lastStatus.status !== d.status) {
            toast('Pesanan ' + d.kode + ' sekarang: ' + d.status_label + '.', d.status === 'batal');
            try { if (navigator.vibrate) navigator.vibrate(200); } catch (e) { /* tidak didukung */ }
        }
        lastStatus = { kode: d.kode, status: d.status };
        $('#trCode').textContent = d.kode;
        var st = $('#trStatus');
        st.textContent = d.status_label;
        st.className = 'chip-status st-' + d.status;

        var batal = d.status === 'batal', selesai = d.status === 'selesai';
        var idx = Math.max(0, STEPS.indexOf(d.status));
        var tl = $('#timeline');
        tl.className = 'timeline' + (batal ? ' is-batal' : '');
        tl.style.setProperty('--cur', batal ? 0 : idx);
        tl.innerHTML = stepLabels(d.tipe).map(function (label, i) {
            var cls = batal ? '' : (i < idx || selesai ? 'done' : (i === idx ? 'now' : ''));
            return '<li class="' + cls + '"' + (cls === 'now' ? ' aria-current="step"' : '') + '><i>' + (cls === 'done' ? '✓' : (i + 1)) + '</i>' + esc(label) + '</li>';
        }).join('');
        $('#trCancel').hidden = !batal;

        $('#trItems').innerHTML = d.items.map(function (i) {
            return '<li><span>' + i.qty + '× ' + esc(i.nama) + ' <em>' + esc(i.varian) + '</em>' + (i.addons ? '<small>+ ' + esc(i.addons) + '</small>' : '') + '</span><b>' + fmt(i.subtotal) + '</b></li>';
        }).join('');
        var r = d.ringkasan;
        $('#trSum').innerHTML = sumHtml({ subtotal: r.subtotal, diskon: r.diskon, ongkir: r.ongkir, total: r.total, tipe: d.tipe }, d.kode_promo);
        var final = batal || selesai;
        var canReview = d.status === 'selesai', done = canReview && reviewed(d.kode);
        reviewForm.hidden = !canReview || done;
        reviewDone.hidden = !done;
        $('#trMeta').textContent = d.tipe_label + (d.jadwal_label ? ' (' + d.jadwal_label + ' WIB)' : '') + ' · ' + d.pembayaran + ' · Dipesan ' + d.dibuat + ' · Diperbarui ' + d.diperbarui + (final ? '' : ' · Status diperbarui otomatis');
        if (final) stopPoll();
    }

    function doTrack(silent) {
        var kode = trKode.value.trim().toUpperCase();
        var wa = trWa.value.replace(/\D/g, '');
        if (!silent) {
            trClear();
            var ok = true;
            if (!/^DVN-[A-Z0-9]{6}$/.test(kode)) { trErr('kode', 'Contoh: DVN-A1B2C3'); ok = false; }
            if (wa.length < 4) { trErr('wa', 'Isi minimal 4 digit terakhir.'); ok = false; }
            if (!ok) return Promise.resolve();
            trBtn.classList.add('loading');
            trBtn.disabled = true;
        }
        var fd = new FormData();
        fd.append('kode', kode);
        fd.append('wa', wa);
        return post('lacak.php', fd).then(function (r) {
            trBtn.classList.remove('loading');
            trBtn.disabled = false;
            if (!r.data.sukses) {
                if (silent) return;
                trResult.hidden = true;
                stopPoll();
                if (r.data.errors) Object.keys(r.data.errors).forEach(function (k) { trErr(k, r.data.errors[k]); });
                toast(r.data.pesan || 'Pesanan tidak ditemukan.', true);
                return;
            }
            lastTrack = { kode: kode, wa: wa, data: r.data };
            renderTrack(r.data, silent);
            if (!silent) trResult.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            if (!poll && !(r.data.status === 'selesai' || r.data.status === 'batal')) {
                poll = setInterval(function () { if (!document.hidden) doTrack(true); }, 15000);
            }
        });
    }

    function openTrack(kode, wa, auto) {
        var last = store.get('doven_last');
        if (kode) trKode.value = kode;
        else if (!trKode.value && last) trKode.value = last.kode || '';
        if (wa) trWa.value = wa;
        else if (!trWa.value && last) trWa.value = last.wa || '';
        openOv(trackModal);
        if (auto) doTrack(false);
    }
    trForm.addEventListener('submit', function (e) { e.preventDefault(); stopPoll(); doTrack(false); });
    trKode.addEventListener('input', function () { trKode.value = trKode.value.toUpperCase().replace(/[^A-Z0-9\-]/g, ''); });
    trWa.addEventListener('input', function () { trWa.value = trWa.value.replace(/[^\d+\s\-]/g, ''); });
    trackModal.addEventListener('overlayclose', stopPoll);

    $$('.js-open-track').forEach(function (b) {
        b.addEventListener('click', function () {
            var nl = $('#navLinks');
            if (nl && nl.classList.contains('open')) $('#hamburger').click();
            openTrack();
        });
    });
    $('#trackNow').addEventListener('click', function () {
        var last = store.get('doven_last') || {};
        closeOv(drawer);
        openTrack(last.kode, last.wa, true);
    });

    /* =====================================================
       Kartu promo: salin kode
       ===================================================== */
    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(text).catch(function () {});
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) { /* diabaikan */ }
        document.body.removeChild(ta);
        return Promise.resolve();
    }
    $$('.promo-card').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var code = btn.dataset.code;
            pendingPromo = code;
            copyText(code).then(function () {
                toast('Kode ' + code + ' disalin dan otomatis dipakai saat checkout.');
            });
            var label = $('.promo-copy', btn);
            label.textContent = 'Tersalin';
            setTimeout(function () { label.textContent = 'Salin'; }, 1800);
        });
    });

    /* =====================================================
       Ulasan pelanggan (muncul di hasil lacak saat pesanan selesai)
       ===================================================== */
    function reviewed(code) { var l = store.get('doven_reviewed'); return Array.isArray(l) && l.indexOf(code) > -1; }
    function markReviewed(code) {
        var l = store.get('doven_reviewed');
        l = Array.isArray(l) ? l : [];
        if (l.indexOf(code) === -1) l.push(code);
        store.set('doven_reviewed', l.slice(-30));
    }
    reviewForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!lastTrack) return;
        var er = $('[data-err="rating"]', reviewForm);
        var rating = $('input[name="rating"]:checked', reviewForm);
        er.textContent = '';
        if (!rating) { er.textContent = 'Pilih jumlah bintang dulu ya.'; return; }
        rvBtn.classList.add('loading');
        rvBtn.disabled = true;
        var fd = new FormData();
        fd.append('kode', lastTrack.kode);
        fd.append('wa', lastTrack.wa);
        fd.append('rating', rating.value);
        fd.append('komentar', $('#rv_text').value.trim());
        post('ulasan.php', fd).then(function (r) {
            rvBtn.classList.remove('loading');
            rvBtn.disabled = false;
            var d = r.data;
            if (d.sukses || d.sudah) {
                markReviewed(lastTrack.kode);
                reviewForm.reset();
                reviewForm.hidden = true;
                reviewDone.hidden = false;
                toast(d.pesan);
                return;
            }
            if (d.errors && d.errors.rating) er.textContent = d.errors.rating;
            toast(d.pesan || 'Ulasan belum bisa dikirim.', true);
        });
    });
    $$('input[name="rating"]', reviewForm).forEach(function (i) {
        i.addEventListener('change', function () { $('[data-err="rating"]', reviewForm).textContent = ''; });
    });

    /* =====================================================
       Cetak struk (dari hasil lacak)
       ===================================================== */
    function printReceipt() {
        var d = lastTrack && lastTrack.data;
        if (!d) return;
        var r = d.ringkasan;
        var rows = d.items.map(function (i) {
            return '<tr><td>' + i.qty + '&times; ' + esc(i.nama) + ' (' + esc(i.varian) + ')' + (i.addons ? '<br><small>+ ' + esc(i.addons) + '</small>' : '') +
                '</td><td class="r">' + fmt(i.subtotal) + '</td></tr>';
        }).join('');
        var line = function (label, val, cls) { return '<tr' + (cls ? ' class="' + cls + '"' : '') + '><td>' + label + '</td><td class="r">' + val + '</td></tr>'; };
        var sum = line('Subtotal', fmt(r.subtotal)) +
            (r.diskon > 0 ? line('Diskon' + (d.kode_promo ? ' (' + esc(d.kode_promo) + ')' : ''), '-' + fmt(r.diskon)) : '') +
            (d.tipe === 'delivery' ? line('Ongkir', r.ongkir > 0 ? fmt(r.ongkir) : 'Gratis') : '') +
            line('TOTAL', fmt(r.total), 'tot');
        var css = '@page{size:80mm auto;margin:4mm}*{box-sizing:border-box}body{font:12px/1.45 "Courier New",monospace;color:#000;width:72mm;margin:0 auto}' +
            'h1{font-size:17px;text-align:center;margin:0}p{margin:0}.c{text-align:center}.hr{border-top:1px dashed #000;margin:7px 0}' +
            'table{width:100%;border-collapse:collapse}td{vertical-align:top;padding:1px 0}td.r{text-align:right;white-space:nowrap;padding-left:8px}' +
            'small{font-size:10px}.tot td{font-weight:bold;font-size:14px;padding-top:4px}';
        var html = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Struk ' + esc(d.kode) + '</title><style>' + css + '</style></head><body>' +
            '<h1>DOVEN BURGER</h1><p class="c">Struk pesanan</p><div class="hr"></div>' +
            '<p>Kode : ' + esc(d.kode) + '</p><p>Nama : ' + esc(d.nama) + '</p><p>Tipe : ' + esc(d.tipe_label) + '</p>' +
            (d.jadwal_label ? '<p>Waktu: ' + esc(d.jadwal_label) + ' WIB</p>' : '') +
            '<p>Bayar: ' + esc(d.pembayaran) + '</p><p>Status: ' + esc(d.status_label) + '</p><p>' + esc(d.dibuat) + '</p>' +
            '<div class="hr"></div><table>' + rows + '</table><div class="hr"></div><table>' + sum + '</table><div class="hr"></div>' +
            '<p class="c">Terima kasih sudah memesan!</p><p class="c">Premium beef burger, dibuat segar.</p></body></html>';

        var old = document.getElementById('receiptFrame');
        if (old) old.parentNode.removeChild(old);
        var f = document.createElement('iframe');
        f.id = 'receiptFrame';
        f.setAttribute('aria-hidden', 'true');
        f.setAttribute('tabindex', '-1');
        f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
        document.body.appendChild(f);
        var doc = f.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
        setTimeout(function () {
            try { f.contentWindow.focus(); f.contentWindow.print(); }
            catch (e) { toast('Browser ini belum mendukung cetak struk.', true); }
        }, 250);
    }
    $('#printBtn').addEventListener('click', printReceipt);

    /* =====================================================
       Favorit (disimpan di browser)
       ===================================================== */
    var FAVKEY = 'doven_fav';
    var favs = (store.get(FAVKEY) || []).filter(function (id) { return has(MENU, id); });
    function updateFavUi() {
        cards.forEach(function (c) {
            var b = $('.fav-btn', c);
            if (b) b.setAttribute('aria-pressed', String(favs.indexOf(c.dataset.id) > -1));
        });
        var n = $('#menuTabs [data-kat="favorit"] small');
        if (n) n.textContent = favs.length;
    }
    cards.forEach(function (c) {
        var b = $('.fav-btn', c);
        if (!b) return;
        b.addEventListener('click', function () {
            var id = c.dataset.id, i = favs.indexOf(id);
            if (i > -1) favs.splice(i, 1); else favs.push(id);
            store.set(FAVKEY, favs);
            updateFavUi();
            toast(i > -1 ? MENU[id].nama + ' dihapus dari favorit.' : MENU[id].nama + ' disimpan ke favorit.');
            if (state.kat === 'favorit') applyMenu();
        });
    });

    /* =====================================================
       Pesan lagi (dari pesanan terakhir di perangkat ini)
       ===================================================== */
    var LASTKEY = 'doven_lastcart';
    var reorderBar = $('#reorderBar'), reorderList = $('#reorderList');
    function lastLines() {
        var raw = store.get(LASTKEY);
        return Array.isArray(raw) ? raw.map(cleanLine).filter(Boolean).slice(0, MAXL) : [];
    }
    function renderReorder() {
        var ls = lastLines();
        reorderBar.hidden = !ls.length;
        if (!ls.length) return;
        var total = ls.reduce(function (sum, l) { return sum + unit(l) * l.qty; }, 0);
        reorderList.textContent = ls.map(function (l) { return l.qty + '× ' + MENU[l.id].nama + ' (' + l.varian + ')'; }).join(', ') + ' · ' + fmt(total);
    }
    $('#reorderBtn').addEventListener('click', function () {
        var ls = lastLines(), n = 0;
        ls.forEach(function (l) { if (addLine(l)) n++; });
        if (n) { toast('Pesanan terakhir dimasukkan ke keranjang.'); openCart(); }
    });
    $('#reorderX').addEventListener('click', function () { store.set(LASTKEY, null); renderReorder(); });

    /* =====================================================
       Saran tambahan di keranjang (upsell)
       ===================================================== */
    var upsell = $('#upsell'), upsellList = $('#upsellList');
    function renderUpsell() {
        var hasBurger = cart.some(function (l) { return MENU[l.id].kategori === 'burger'; });
        var inCart = cart.map(function (l) { return l.id; });
        var picks = !hasBurger ? [] : Object.keys(MENU).filter(function (id) {
            return MENU[id].kategori === 'assist' && MENU[id].tersedia !== false && inCart.indexOf(id) === -1;
        }).slice(0, 2);
        upsell.hidden = !picks.length;
        upsellList.innerHTML = picks.map(function (id) {
            var m = MENU[id], v = Object.keys(m.harga)[0];
            return '<div class="up-item"><span class="up-ico" aria-hidden="true">' + m.ikon + '</span><div class="up-info"><strong>' + esc(m.nama) + '</strong><small>' +
                esc(v) + ' · ' + fmt(m.harga[v]) + '</small></div><button type="button" class="up-add" data-id="' + esc(id) + '" data-v="' + esc(v) +
                '" aria-label="Tambah ' + esc(m.nama) + ' ke keranjang">+ Tambah</button></div>';
        }).join('');
    }
    upsellList.addEventListener('click', function (e) {
        var b = e.target.closest('.up-add');
        if (!b) return;
        var id = b.dataset.id;
        if (addLine({ id: id, varian: b.dataset.v, qty: 1, addons: [], catatan: '' })) toast(MENU[id].nama + ' ditambahkan.');
    });

    /* =====================================================
       Pesanan terjadwal
       ===================================================== */
    var jadwalField = $('#jadwalField'), jadwalSel = $('#co_jadwal'), jadwalHint = $('#jadwalHint');
    function waktuMode() { var r = $('input[name="waktu"]:checked', form); return r ? r.value : 'asap'; }
    function updateWaktuLabel() { $('#waktuLabel').textContent = tipe() === 'delivery' ? 'antar' : (tipe() === 'dine-in' ? 'makan' : 'ambil'); }
    function loadSlots() {
        jadwalSel.disabled = true;
        jadwalSel.innerHTML = '';
        jadwalHint.textContent = 'Memuat waktu yang tersedia...';
        var fd = new FormData();
        fd.append('aksi', 'slot');
        return post('proses.php', fd).then(function (r) {
            var slots = (r.data && r.data.slot) || [];
            if (!slots.length) {
                jadwalHint.textContent = r.status === 0 ? r.data.pesan : 'Belum ada waktu tersedia. Silakan pilih "Secepatnya".';
                return;
            }
            jadwalSel.innerHTML = slots.map(function (s) { return '<option value="' + esc(s.nilai) + '">' + esc(s.label) + ' WIB</option>'; }).join('');
            jadwalSel.disabled = false;
            jadwalHint.textContent = 'Paling cepat 30 menit dari sekarang, dalam jam buka.';
        });
    }
    $$('input[name="waktu"]', form).forEach(function (r) {
        r.addEventListener('change', function () {
            var on = waktuMode() === 'jadwal';
            jadwalField.hidden = !on;
            if (on) loadSlots();
            else {
                jadwalSel.disabled = true;
                jadwalField.classList.remove('invalid');
                $('[data-err="jadwal"]', form).textContent = '';
            }
        });
    });
    $$('input[name="tipe"]', form).forEach(function (r) { r.addEventListener('change', updateWaktuLabel); });

    /* =====================================================
       Info pembayaran di layar sukses
       ===================================================== */
    var payBox = $('#payBox');
    function renderPay(info) {
        if (!info) { payBox.hidden = true; payBox.innerHTML = ''; return; }
        if (info.jenis === 'transfer') {
            payBox.innerHTML = '<h4>Selesaikan pembayaran</h4><p>Transfer <span class="pay-total">' + fmt(info.total) + '</span> ke rekening berikut, lalu kirim bukti lewat WhatsApp.</p>' +
                '<div class="pay-rek"><div><small>' + esc(info.bank) + ' a.n. ' + esc(info.nama) + '</small><strong>' + esc(info.nomor) + '</strong></div>' +
                '<button type="button" class="pay-copy" data-copy="' + esc(info.nomor) + '">Salin</button></div>';
        } else {
            payBox.innerHTML = '<h4>Bayar dengan QRIS</h4><p>Scan kode di bawah dan bayar <span class="pay-total">' + fmt(info.total) + '</span>, lalu kirim bukti lewat WhatsApp.</p>' +
                (info.gambar ? '<img class="pay-qr" src="' + esc(info.gambar) + '" alt="Kode QRIS DOVEN BURGER">' : '<p>Kode QRIS akan dikirim tim kami lewat WhatsApp.</p>');
        }
        payBox.hidden = false;
    }
    payBox.addEventListener('click', function (e) {
        var b = e.target.closest('.pay-copy');
        if (!b) return;
        copyText(b.dataset.copy).then(function () {
            toast('Nomor rekening disalin.');
            b.textContent = 'Tersalin';
            setTimeout(function () { b.textContent = 'Salin'; }, 1800);
        });
    });

    /* ---------- Mulai ---------- */
    setPromoUi();
    updateFavUi();
    renderReorder();
    renderCart();
    // Pintasan PWA "Lacak pesanan" (?lacak=1): buka pelacakan, otomatis bila ada pesanan terakhir
    if (/[?&]lacak=1(?:&|$)/.test(location.search)) { var lp = store.get('doven_last'); openTrack(null, null, !!(lp && lp.kode)); }
})();
