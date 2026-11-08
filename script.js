/* =====================================================
   DOVEN BURGER - script.js (JavaScript murni)
   ===================================================== */
(function () {
    'use strict';

    var $ = function (s, c) { return (c || document).querySelector(s); };
    var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

    /* ---------- Navbar: efek scroll, menu mobile, link aktif ---------- */
    var navbar = $('#navbar');
    var hamburger = $('#hamburger');
    var navLinks = $('#navLinks');

    function onScrollNav() {
        navbar.classList.toggle('scrolled', window.scrollY > 24);
    }
    window.addEventListener('scroll', onScrollNav, { passive: true });
    onScrollNav();

    function toggleMenu(force) {
        var open = typeof force === 'boolean' ? force : !navLinks.classList.contains('open');
        navLinks.classList.toggle('open', open);
        hamburger.classList.toggle('open', open);
        hamburger.setAttribute('aria-expanded', String(open));
    }
    hamburger.addEventListener('click', function () { toggleMenu(); });
    $$('a', navLinks).forEach(function (a) { a.addEventListener('click', function () { toggleMenu(false); }); });

    var sections = $$('main section[id]');
    var links = $$('.nav-link');
    var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (en.isIntersecting) {
                links.forEach(function (l) {
                    l.classList.toggle('active', l.getAttribute('href') === '#' + en.target.id);
                });
            }
        });
    }, { rootMargin: '-45% 0px -50% 0px' });
    sections.forEach(function (s) { spy.observe(s); });

    /* ---------- Reveal saat scroll ---------- */
    var revealObs = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (en) {
            if (en.isIntersecting) {
                en.target.classList.add('visible');
                obs.unobserve(en.target);
            }
        });
    }, { threshold: 0.12 });
    $$('.reveal').forEach(function (el) { revealObs.observe(el); });

    /* ---------- Hitung angka statistik ---------- */
    function animateCount(el) {
        var target = parseFloat(el.dataset.count);
        var decimals = parseInt(el.dataset.decimals || '0', 10);
        var suffix = el.dataset.suffix || '';
        var dur = 1800;
        var start = null;
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 4);
            var val = target * eased;
            el.textContent = (decimals ? val.toFixed(decimals) : Math.round(val).toLocaleString('id-ID')) + suffix;
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
    var countObs = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (en) {
            if (en.isIntersecting) { animateCount(en.target); obs.unobserve(en.target); }
        });
    }, { threshold: 0.6 });
    $$('[data-count]').forEach(function (el) { countObs.observe(el); });

    /* ---------- Scrollytelling hero ---------- */
    var story = $('#beranda');
    var stage = $('#stage');
    var heroSvg = $('.hero-burger');
    var bLabels = $$('#blabels li');
    var hudDots = $$('.hud i');

    if (story && stage && heroSvg) {
        var cur = 0, target = 0, running = false;
        var smooth = function (x) { x = Math.min(Math.max(x, 0), 1); return x * x * (3 - 2 * x); };
        var setVar = function (n, v) { stage.style.setProperty(n, v); };

        var calcTarget = function () {
            var max = story.offsetHeight - window.innerHeight;
            target = Math.min(Math.max(-story.getBoundingClientRect().top / max, 0), 1);
        };

        var render = function (p) {
            var p1 = 1 - smooth((p - 0.2) / 0.1);
            var p2 = smooth((p - 0.26) / 0.12) * (1 - smooth((p - 0.6) / 0.1));
            var p3 = smooth((p - 0.68) / 0.12);
            var ex = smooth((p - 0.28) / 0.16) * (1 - smooth((p - 0.6) / 0.1));

            setVar('--p1', p1.toFixed(3));
            setVar('--p2', p2.toFixed(3));
            setVar('--p3', p3.toFixed(3));
            setVar('--bs', (1 + 0.05 * p2 - 0.2 * p3).toFixed(3));
            setVar('--by', (p3 * 50).toFixed(1) + 'px');
            setVar('--br', (p2 * -4).toFixed(2) + 'deg');
            heroSvg.style.setProperty('--ex', ex.toFixed(3));

            bLabels.forEach(function (li) {
                var yc = parseFloat(li.dataset.yc);
                var k = parseFloat(li.dataset.k);
                li.style.top = (((yc + k * ex + 40) / 700) * 100).toFixed(2) + '%';
                li.style.opacity = Math.max(0, (ex - 0.5) * 2).toFixed(2);
            });

            var active = p3 > 0.5 ? 2 : (p2 > 0.5 ? 1 : 0);
            hudDots.forEach(function (d, i) { d.classList.toggle('on', i === active); });
        };

        var tick = function () {
            calcTarget();
            cur += (target - cur) * 0.12;
            if (Math.abs(target - cur) < 0.0004) cur = target;
            render(cur);
            if (cur !== target) { requestAnimationFrame(tick); } else { running = false; }
        };
        var kick = function () { if (!running) { running = true; requestAnimationFrame(tick); } };

        window.addEventListener('scroll', kick, { passive: true });
        window.addEventListener('resize', kick);
        kick();
    }

    /* ---------- Teks raksasa: parallax horizontal ---------- */
    var gtRows = $$('.gt-row');
    function moveGiantType() {
        gtRows.forEach(function (row) {
            var r = row.getBoundingClientRect();
            var prog = (r.top - window.innerHeight) / (window.innerHeight + r.height);
            var dir = parseFloat(row.dataset.dir || '1');
            row.firstElementChild.style.transform = 'translateX(' + (prog * 700 * dir - (dir > 0 ? 0 : 700)) + 'px)';
        });
    }
    if (gtRows.length) {
        window.addEventListener('scroll', function () { requestAnimationFrame(moveGiantType); }, { passive: true });
        window.addEventListener('resize', moveGiantType);
        moveGiantType();
    }

    /* ---------- Toast ---------- */
    var toastEl = $('#toast');
    var toastTimer;
    function showToast(msg, isError) {
        toastEl.textContent = msg;
        toastEl.classList.toggle('error', !!isError);
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 3800);
    }

    // Dibagikan ke shop.js
    window.Doven = window.Doven || {};
    window.Doven.toast = showToast;

    /* ---------- Tema terang / gelap ---------- */
    var themeBtn = $('#themeToggle');
    var themeMeta = $('meta[name="theme-color"]');
    function applyTheme(t) {
        if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        else document.documentElement.removeAttribute('data-theme');
        if (themeMeta) themeMeta.setAttribute('content', t === 'dark' ? '#0b1120' : '#f8fafc');
        try { localStorage.setItem('doven_theme', t); } catch (e) { /* mode privat */ }
    }
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        });
    }

    /* ---------- Progress scroll & tombol ke atas ---------- */
    var progress = $('#progress');
    var toTop = $('#toTop');
    var uiTick = false;
    function updateScrollUI() {
        uiTick = false;
        var de = document.documentElement;
        var max = de.scrollHeight - de.clientHeight;
        if (progress) progress.style.transform = 'scaleX(' + (max > 0 ? Math.min(window.scrollY / max, 1) : 0).toFixed(4) + ')';
        if (toTop) toTop.classList.toggle('show', window.scrollY > 900);
    }
    window.addEventListener('scroll', function () {
        if (!uiTick) { uiTick = true; requestAnimationFrame(updateScrollUI); }
    }, { passive: true });
    window.addEventListener('resize', updateScrollUI);
    updateScrollUI();
    if (toTop) toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
})();
