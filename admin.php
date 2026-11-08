<?php
/**
 * admin.php - Dashboard pesanan DOVEN BURGER.
 *
 * Pertama kali: buka admin.php, buat password, salin hash yang tampil ke
 * ADMIN_PASSWORD_HASH di koneksi.php. Setelah itu login dengan password tersebut.
 *
 * Catatan keamanan: untuk produksi, pasang HTTPS dan (idealnya) batasi akses
 * file ini lewat IP / basic-auth di level server.
 */

declare(strict_types=1);

session_name('doven_admin');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'httponly' => true,
    'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS']),
]);
session_start();

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/koneksi.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

$hash = defined('ADMIN_PASSWORD_HASH') ? (string)ADMIN_PASSWORD_HASH : '$2y$10$ZvfvdMPnY7..Dlbc5vw./.LZm8K7Ixkvg0hnZdN.PRYoFemw6Yd9u';
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf'];

function csrfValid(): bool
{
    $kirim = (string)($_SERVER['HTTP_X_CSRF'] ?? $_POST['csrf'] ?? '');
    return hash_equals((string)($_SESSION['csrf'] ?? ''), $kirim);
}

$css = <<<'CSS'
:root{--bg:#f1f5f9;--card:#fff;--ink:#0f172a;--muted:#64748b;--line:rgba(15,23,42,.09);--orange:#f97316;--field:#fff;--tint:#fff7ed;--ok:#16a34a;--bad:#dc2626}
@media (prefers-color-scheme:dark){:root{--bg:#0b1120;--card:#131c31;--ink:#f1f5f9;--muted:#94a3b8;--line:rgba(148,163,184,.18);--field:#0f172a;--tint:rgba(249,115,22,.12)}}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter','Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--ink);line-height:1.5;-webkit-font-smoothing:antialiased}
button{font:inherit;cursor:pointer;border:0;background:none;color:inherit}
a{color:inherit}
:focus-visible{outline:3px solid rgba(249,115,22,.55);outline-offset:2px;border-radius:8px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.7rem 1.3rem;border-radius:999px;font-weight:700;font-size:.9rem;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;transition:transform .3s,box-shadow .3s}
.btn:hover{transform:translateY(-2px);box-shadow:0 10px 24px -8px rgba(249,115,22,.6)}
.btn.ghost{background:var(--card);color:var(--ink);box-shadow:0 0 0 1px var(--line)}
.logo{display:inline-flex;align-items:center;gap:.6rem;font-weight:900;letter-spacing:-.04em;font-size:1.15rem}
.logo i{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;background:linear-gradient(135deg,#f97316,#ea580c);font-style:normal;font-size:1.1rem}
.logo b{color:var(--orange)}
.gate{min-height:100vh;display:grid;place-items:center;padding:1.2rem}
.gate-card{width:min(420px,100%);background:var(--card);border-radius:24px;padding:2.2rem;box-shadow:0 28px 60px -20px rgba(15,23,42,.25),0 0 0 1px var(--line)}
.gate-card h1{font-size:1.5rem;font-weight:900;letter-spacing:-.04em;margin:1.2rem 0 .3rem}
.gate-card p{color:var(--muted);font-size:.92rem;margin-bottom:1.2rem}
.gate-card label{display:block;font-size:.82rem;font-weight:700;margin-bottom:.4rem}
.gate-card input{width:100%;padding:.85rem 1rem;border-radius:14px;border:1.5px solid var(--line);background:var(--field);color:var(--ink);font:inherit;margin-bottom:1rem}
.gate-card input:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 4px rgba(249,115,22,.15)}
.gate-card .btn{width:100%}
.galat{background:rgba(220,38,38,.1);color:var(--bad);padding:.7rem 1rem;border-radius:12px;font-size:.85rem;font-weight:600;margin-bottom:1rem}
.hash{display:block;word-break:break-all;background:var(--tint);border:1px dashed var(--orange);border-radius:12px;padding:.8rem 1rem;font:600 .82rem ui-monospace,Menlo,monospace;margin-bottom:1rem;user-select:all}
.top{background:#0f172a;color:#fff;position:sticky;top:0;z-index:20}
.top-in{width:min(1240px,94%);margin-inline:auto;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.8rem 0}
.top .btn.ghost{background:rgba(255,255,255,.08);color:#fff;box-shadow:none;padding:.55rem 1rem}
.top-r{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}
.live{display:inline-flex;align-items:center;gap:.45rem;font-size:.78rem;font-weight:700;color:#86efac}
.live::before{content:'';width:8px;height:8px;border-radius:50%;background:#22c55e;animation:p 1.8s infinite}
@keyframes p{0%{box-shadow:0 0 0 0 rgba(34,197,94,.6)}70%,100%{box-shadow:0 0 0 9px transparent}}
.wrap{width:min(1240px,94%);margin:1.6rem auto 4rem}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.6rem}
.stat{background:var(--card);border-radius:20px;padding:1.2rem 1.4rem;box-shadow:0 0 0 1px var(--line)}
.stat small{display:block;color:var(--muted);font-weight:600;font-size:.8rem}
.stat strong{display:block;font-size:1.9rem;font-weight:900;letter-spacing:-.04em;line-height:1.2}
.stat.hot strong{color:var(--orange)}
.bar{display:flex;flex-wrap:wrap;gap:.8rem;justify-content:space-between;align-items:center;margin-bottom:1.2rem}
.tabs{display:flex;gap:.4rem;flex-wrap:wrap}
.tab{padding:.5rem 1rem;border-radius:999px;font-weight:700;font-size:.85rem;background:var(--card);box-shadow:0 0 0 1px var(--line);color:var(--muted)}
.tab small{margin-left:.35rem;font-weight:800;opacity:.7}
.tab[aria-pressed=true]{background:#0f172a;color:#fff}
.bar input{padding:.65rem 1.1rem;border-radius:999px;border:1.5px solid var(--line);background:var(--field);color:var(--ink);font:inherit;min-width:min(260px,100%)}
.bar input:focus{outline:none;border-color:var(--orange)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,360px),1fr));gap:1rem}
.o{background:var(--card);border-radius:22px;padding:1.3rem;box-shadow:0 0 0 1px var(--line);display:flex;flex-direction:column;gap:.8rem}
.o.fresh{animation:fresh 2.4s ease-out}
@keyframes fresh{0%,40%{box-shadow:0 0 0 3px var(--orange),0 12px 30px -10px rgba(249,115,22,.5)}100%{box-shadow:0 0 0 1px var(--line)}}
.o-h{display:flex;justify-content:space-between;align-items:flex-start;gap:.8rem}
.o-code{font-weight:900;letter-spacing:.06em;color:var(--orange)}
.o-h small{display:block;color:var(--muted);font-size:.76rem}
.o-total{font-weight:900;font-size:1.15rem;letter-spacing:-.03em}
.o-who{font-weight:700}
.o-who a{color:var(--muted);font-weight:600;font-size:.85rem;text-decoration:none;margin-left:.4rem}
.o-who a:hover{color:var(--orange)}
.chips{display:flex;gap:.4rem;flex-wrap:wrap}
.chip{font-size:.72rem;font-weight:700;padding:.2rem .65rem;border-radius:999px;background:var(--tint);color:#c2410c}
.o-addr{font-size:.85rem;color:var(--muted)}
.o ul{list-style:none;display:grid;gap:.35rem;padding:.8rem 0;border-block:1px dashed var(--line);font-size:.9rem}
.o li em{font-style:normal;color:var(--muted)}
.o li small{display:block;color:var(--muted);font-size:.76rem}
.o li small.nt{font-style:italic}
.o-note{font-size:.84rem;background:var(--tint);border-radius:12px;padding:.55rem .8rem}
.o-sum{font-size:.78rem;color:var(--muted)}
.sts{display:flex;flex-wrap:wrap;gap:.35rem}
.st{padding:.4rem .85rem;border-radius:999px;font-size:.78rem;font-weight:700;box-shadow:0 0 0 1px var(--line);transition:background .2s,color .2s}
.st:hover:not(:disabled){background:var(--orange);color:#fff;box-shadow:none}
.st:disabled{cursor:default;color:#fff;box-shadow:none}
.st.s-baru{background:#2563eb}.st.s-diproses{background:#d97706}.st.s-siap{background:#7c3aed}.st.s-selesai{background:var(--ok)}.st.s-batal{background:var(--bad)}
.lap{display:grid;grid-template-columns:2fr 1fr;gap:1rem;margin-bottom:1.6rem}
.lap-card{background:var(--card);border-radius:20px;padding:1.2rem 1.4rem;box-shadow:0 0 0 1px var(--line)}
.lap-card h2{font-size:.9rem;font-weight:800;margin-bottom:1rem}
.bars{display:grid;grid-template-columns:repeat(7,1fr);gap:.6rem;height:180px}
.bcol{display:grid;grid-template-rows:1fr auto auto;gap:.25rem;text-align:center;min-width:0}
.bcol .bx{display:flex;align-items:flex-end}
.bfill{width:100%;background:linear-gradient(180deg,#fb923c,#ea580c);border-radius:8px 8px 4px 4px;min-height:3px}
.bcol.now .bfill{background:linear-gradient(180deg,#34d399,#16a34a)}
.bcol small{color:var(--muted);font-size:.7rem;font-weight:700}
.bcol b{font-size:.68rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.top-list{list-style:none;display:grid;gap:.6rem;counter-reset:t}
.top-list li{display:flex;align-items:center;gap:.7rem;font-size:.88rem;counter-increment:t}
.top-list li::before{content:counter(t);width:24px;height:24px;border-radius:50%;display:grid;place-items:center;background:var(--tint);color:#c2410c;font-weight:800;font-size:.75rem;flex:none}
.top-list span{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600}
.top-list em{font-style:normal;color:var(--muted);font-weight:700;font-size:.8rem}
.views{display:flex;gap:.5rem;margin-bottom:1.2rem;flex-wrap:wrap}
.bar-r{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap}
.btn.sm{padding:.55rem 1rem;font-size:.82rem}
.chip.jd{background:rgba(124,58,237,.12);color:#6d28d9}
.rv{background:var(--card);border-radius:22px;padding:1.2rem 1.3rem;box-shadow:0 0 0 1px var(--line);display:flex;flex-direction:column;gap:.6rem}
.rv-h{display:flex;justify-content:space-between;gap:.8rem;align-items:flex-start}
.rv-h strong{display:block}
.rv-h small{color:var(--muted);font-size:.76rem}
.rv-stars{color:#f59e0b;letter-spacing:.1em;white-space:nowrap}
.rv-stars i{font-style:normal;color:var(--line)}
.rv p{font-size:.9rem}
.rv p.kmt{color:var(--muted);font-style:italic}
.rv-act{display:flex;gap:.4rem;flex-wrap:wrap;align-items:center}
.rv-act .st.hapus:hover:not(:disabled){background:var(--bad)}
.badge{font-size:.72rem;font-weight:800;padding:.2rem .65rem;border-radius:999px}
.badge.on{background:rgba(22,163,74,.13);color:#15803d}
.badge.off{background:rgba(245,158,11,.15);color:#b45309}
@media(max-width:820px){.lap{grid-template-columns:1fr}}
.kosong{text-align:center;color:var(--muted);padding:4rem 1rem;grid-column:1/-1}
.err{background:rgba(220,38,38,.1);color:var(--bad);padding:.7rem 1rem;border-radius:12px;font-size:.85rem;font-weight:600;margin-bottom:1rem}
@media(max-width:820px){.stats{grid-template-columns:repeat(2,1fr)}}
@media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
CSS;

$logo = '<span class="logo"><i>🍔</i><span>DOVEN<b> ADMIN</b></span></span>';

function halaman(string $judul, string $css, string $isi, string $skrip = ''): void
{
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex,nofollow"><title>' . e($judul) . '</title>'
        . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">'
        . '<style>' . $css . '</style></head><body>' . $isi . $skrip . '</body></html>';
}

/* ---------- Mode setup: belum ada password ---------- */
if ($hash === '') {
    $hasil = '';
    $galatSetup = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid()) {
        $pw = (string)($_POST['password'] ?? '');
        if (mb_strlen($pw) < 10) {
            $galatSetup = 'Password minimal 10 karakter.';
        } else {
            $hasil = password_hash($pw, PASSWORD_DEFAULT);
        }
    }
    $isi = '<main class="gate"><div class="gate-card">' . $logo . '<h1>Atur password admin</h1>'
        . '<p>Dashboard masih nonaktif. Buat password, lalu salin hash di bawah ke <code>ADMIN_PASSWORD_HASH</code> pada <code>koneksi.php</code>.</p>';
    if ($galatSetup !== '') {
        $isi .= '<div class="galat">' . e($galatSetup) . '</div>';
    }
    if ($hasil !== '') {
        $isi .= '<label>Hash password Anda</label><code class="hash">' . e($hasil) . '</code>'
            . '<p>Simpan di koneksi.php seperti ini:<br><code>const ADMIN_PASSWORD_HASH = \'...hash...\';</code><br>Lalu muat ulang halaman ini.</p>';
    } else {
        $isi .= '<form method="post"><input type="hidden" name="csrf" value="' . e($csrf) . '">'
            . '<label for="pw">Password baru</label><input id="pw" type="password" name="password" minlength="10" autocomplete="new-password" required>'
            . '<button class="btn" type="submit">Buat hash</button></form>';
    }
    halaman('Setup Admin | DOVEN BURGER', $css, $isi . '</div></main>');
    exit;
}

/* ---------- Keluar ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'keluar' && csrfValid()) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

/* ---------- Masuk ---------- */
$galat = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'masuk') {
    $sekarang = time();
    if ((int)($_SESSION['gagal_t'] ?? 0) < $sekarang - 600) {
        $_SESSION['gagal'] = 0;
    }
    if ((int)($_SESSION['gagal'] ?? 0) >= 5) {
        $galat = 'Terlalu banyak percobaan. Coba lagi dalam 10 menit.';
    } elseif (csrfValid() && password_verify((string)($_POST['password'] ?? ''), $hash)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['gagal'] = 0;
        header('Location: admin.php');
        exit;
    } else {
        $_SESSION['gagal'] = (int)($_SESSION['gagal'] ?? 0) + 1;
        $_SESSION['gagal_t'] = $sekarang;
        $galat = 'Password salah.';
        usleep(600000); // perlambat tebakan beruntun
    }
}

$aksi = (string)($_GET['aksi'] ?? ($_POST['aksi'] ?? ''));

if (empty($_SESSION['admin'])) {
    if (in_array($aksi, ['data', 'status', 'laporan', 'ulasan', 'ulasan_set'], true)) {
        header('Content-Type: application/json; charset=utf-8');
        jsonBalas(false, 'Sesi berakhir. Muat ulang halaman dan masuk lagi.', [], 401);
    }
    $isi = '<main class="gate"><div class="gate-card">' . $logo . '<h1>Masuk dashboard</h1><p>Khusus pengelola DOVEN BURGER.</p>'
        . ($galat !== '' ? '<div class="galat" role="alert">' . e($galat) . '</div>' : '')
        . '<form method="post"><input type="hidden" name="aksi" value="masuk"><input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<label for="pw">Password</label><input id="pw" type="password" name="password" autocomplete="current-password" required autofocus>'
        . '<button class="btn" type="submit">Masuk</button></form></div></main>';
    halaman('Masuk | DOVEN BURGER Admin', $css, $isi);
    exit;
}

/**
 * Susun klausa WHERE dari filter status + kata kunci (dipakai tampilan dan ekspor CSV).
 * @return array{0:string,1:string,2:array<int,string>} [sql WHERE (boleh kosong), tipe bind, parameter]
 */
function filterPesanan(string $filter, string $q): array
{
    $where = [];
    $tipeBind = '';
    $param = [];
    if (isset(STATUS_PESANAN[$filter])) {
        $where[] = 'status = ?';
        $tipeBind .= 's';
        $param[] = $filter;
    }
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(kode_pesanan LIKE ? OR nama_lengkap LIKE ? OR nomor_wa LIKE ?)';
        $tipeBind .= 'sss';
        array_push($param, $like, $like, $like);
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $tipeBind, $param];
}

/* ---------- Ekspor CSV (mengikuti filter yang sedang aktif) ---------- */
if ($aksi === 'ekspor') {
    [$klausa, $tipeBind, $param] = filterPesanan((string)($_GET['status'] ?? ''), trim((string)($_GET['q'] ?? '')));
    try {
        $stmt = $koneksi->prepare('SELECT id, kode_pesanan, nama_lengkap, nomor_wa, tipe, alamat, pembayaran, kode_promo, subtotal, diskon, ongkir, total, catatan, jadwal, status, created_at
                FROM pesanan' . $klausa . ' ORDER BY id DESC LIMIT 5000');
        if ($param) {
            $stmt->bind_param($tipeBind, ...$param);
        }
        $stmt->execute();
        $baris = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $itemTeks = [];
        if ($baris) {
            $ids = array_map(static fn(array $r): int => (int)$r['id'], $baris);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $si = $koneksi->prepare('SELECT pesanan_id, nama_menu, varian, qty, addons FROM pesanan_item WHERE pesanan_id IN (' . $in . ') ORDER BY id');
            $si->bind_param(str_repeat('i', count($ids)), ...$ids);
            $si->execute();
            foreach ($si->get_result() as $it) {
                $itemTeks[(int)$it['pesanan_id']][] = (int)$it['qty'] . 'x ' . $it['nama_menu'] . ' (' . $it['varian'] . ')' . ($it['addons'] ? ' + ' . $it['addons'] : '');
            }
            $si->close();
        }
    } catch (mysqli_sql_exception $e) {
        error_log('[DOVEN BURGER] Admin ekspor: ' . $e->getMessage());
        http_response_code(500);
        exit('Ekspor gagal.');
    }

    // Cegah CSV injection: sel yang diawali = + - @ dianggap rumus oleh Excel/Sheets
    $aman = static function (mixed $v): string {
        $t = (string)$v;
        return $t !== '' && strpos("=+-@\t\r", $t[0]) !== false ? "'" . $t : $t;
    };

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pesanan-doven-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
    fputcsv($out, ['Kode', 'Waktu pesan', 'Nama', 'WhatsApp', 'Tipe', 'Jadwal', 'Alamat', 'Pembayaran', 'Promo', 'Item', 'Subtotal', 'Diskon', 'Ongkir', 'Total', 'Status', 'Catatan'], ',', '"', '');
    foreach ($baris as $r) {
        fputcsv($out, [
            $r['kode_pesanan'], $r['created_at'], $aman($r['nama_lengkap']),
            // nomor sudah dinormalisasi (hanya angka); format ="..." menjaga angka 0 di depan agar tidak hilang di Excel
            preg_match('/^[0-9]+$/', (string)$r['nomor_wa']) === 1 ? '="' . $r['nomor_wa'] . '"' : $aman($r['nomor_wa']),
            TIPE_PESANAN[$r['tipe']] ?? $r['tipe'], $r['jadwal'] ?? '', $aman($r['alamat'] ?? ''),
            METODE_BAYAR[$r['pembayaran']] ?? $r['pembayaran'], $r['kode_promo'] ?? '',
            $aman(implode('; ', $itemTeks[(int)$r['id']] ?? [])),
            $r['subtotal'], $r['diskon'], $r['ongkir'], $r['total'],
            STATUS_PESANAN[$r['status']] ?? $r['status'], $aman($r['catatan'] ?? ''),
        ], ',', '"', '');
    }
    fclose($out);
    exit;
}

/* ---------- API: laporan (omzet 7 hari + menu terlaris 30 hari) ---------- */
if ($aksi === 'laporan') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $perHari = [];
        $res = $koneksi->query("SELECT DATE(created_at) AS d, COUNT(*) AS n, COALESCE(SUM(total), 0) AS omzet
                                FROM pesanan WHERE status <> 'batal' AND created_at >= (CURDATE() - INTERVAL 6 DAY)
                                GROUP BY DATE(created_at)");
        foreach ($res as $r) {
            $perHari[(string)$r['d']] = ['n' => (int)$r['n'], 'omzet' => (int)$r['omzet']];
        }
        $namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $hari = [];
        for ($i = 6; $i >= 0; $i--) {
            $t = strtotime('-' . $i . ' day');
            $k = date('Y-m-d', $t);
            $hari[] = ['label' => $namaHari[(int)date('w', $t)] . ' ' . date('j', $t), 'omzet' => $perHari[$k]['omzet'] ?? 0, 'n' => $perHari[$k]['n'] ?? 0, 'hariIni' => $i === 0];
        }
        $terlaris = [];
        $res = $koneksi->query("SELECT i.nama_menu, SUM(i.qty) AS q
                                FROM pesanan_item i JOIN pesanan p ON p.id = i.pesanan_id
                                WHERE p.status <> 'batal' AND p.created_at >= (CURDATE() - INTERVAL 29 DAY)
                                GROUP BY i.nama_menu ORDER BY q DESC, i.nama_menu LIMIT 5");
        foreach ($res as $r) {
            $terlaris[] = ['nama' => $r['nama_menu'], 'qty' => (int)$r['q']];
        }
    } catch (mysqli_sql_exception $e) {
        error_log('[DOVEN BURGER] Admin laporan: ' . $e->getMessage());
        jsonBalas(false, 'Laporan belum bisa dimuat.', [], 500);
    }
    echo json_encode(['sukses' => true, 'hari' => $hari, 'terlaris' => $terlaris], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- API: ulasan pelanggan ---------- */
if ($aksi === 'ulasan') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $daftar = [];
        $res = $koneksi->query('SELECT u.id, u.nama, u.rating, u.komentar, u.tampil, u.created_at, p.kode_pesanan
                                FROM ulasan u JOIN pesanan p ON p.id = u.pesanan_id ORDER BY u.id DESC LIMIT 80');
        foreach ($res as $r) {
            $daftar[] = [
                'id' => (int)$r['id'], 'nama' => $r['nama'], 'rating' => (int)$r['rating'], 'komentar' => $r['komentar'],
                'tampil' => (int)$r['tampil'] === 1, 'kode' => $r['kode_pesanan'], 'waktu' => date('c', strtotime((string)$r['created_at'])),
            ];
        }
    } catch (mysqli_sql_exception $e) {
        error_log('[DOVEN BURGER] Admin ulasan: ' . $e->getMessage());
        jsonBalas(false, 'Ulasan belum bisa dimuat. Pastikan tabel ulasan sudah dibuat (lihat migrasi_v3.sql).', [], 500);
    }
    echo json_encode(['sukses' => true, 'ulasan' => $daftar], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($aksi === 'ulasan_set' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!csrfValid()) {
        jsonBalas(false, 'Token keamanan tidak valid. Muat ulang halaman.', [], 403);
    }
    $id = (int)($_POST['id'] ?? 0);
    $tindakan = (string)($_POST['tindakan'] ?? '');
    if ($id < 1 || !in_array($tindakan, ['tampil', 'sembunyi', 'hapus'], true)) {
        jsonBalas(false, 'Data tidak valid.', [], 422);
    }
    try {
        if ($tindakan === 'hapus') {
            $stmt = $koneksi->prepare('DELETE FROM ulasan WHERE id = ?');
            $stmt->bind_param('i', $id);
        } else {
            $nilai = $tindakan === 'tampil' ? 1 : 0;
            $stmt = $koneksi->prepare('UPDATE ulasan SET tampil = ? WHERE id = ?');
            $stmt->bind_param('ii', $nilai, $id);
        }
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('[DOVEN BURGER] Admin ulasan_set: ' . $e->getMessage());
        jsonBalas(false, 'Perubahan gagal disimpan.', [], 500);
    }
    jsonBalas(true, 'Ulasan diperbarui.');
}

/* ---------- API: data pesanan (JSON) ---------- */
if ($aksi === 'data') {
    header('Content-Type: application/json; charset=utf-8');
    $filter = (string)($_GET['status'] ?? '');
    $q      = trim((string)($_GET['q'] ?? ''));

    [$klausa, $tipeBind, $param] = filterPesanan($filter, $q);

    try {
        $sql = 'SELECT id, kode_pesanan, nama_lengkap, nomor_wa, tipe, alamat, pembayaran, kode_promo, subtotal, diskon, ongkir, total, catatan, jadwal, status, created_at
                FROM pesanan' . $klausa . ' ORDER BY id DESC LIMIT 100';

        $stmt = $koneksi->prepare($sql);
        if ($param) {
            $stmt->bind_param($tipeBind, ...$param);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $pesanan = [];
        while ($r = $res->fetch_assoc()) {
            $id = (int)$r['id'];
            $pesanan[$id] = [
                'id' => $id, 'kode' => $r['kode_pesanan'], 'nama' => $r['nama_lengkap'], 'wa' => $r['nomor_wa'],
                'tipe' => $r['tipe'], 'alamat' => $r['alamat'] ?? '', 'bayar' => $r['pembayaran'], 'promo' => $r['kode_promo'] ?? '',
                'subtotal' => (int)$r['subtotal'], 'diskon' => (int)$r['diskon'], 'ongkir' => (int)$r['ongkir'], 'total' => (int)$r['total'],
                'catatan' => $r['catatan'] ?? '', 'status' => $r['status'],
                'jadwal' => $r['jadwal'] !== null ? labelJadwal((string)$r['jadwal']) : '',
                'waktu' => date('c', strtotime((string)$r['created_at'])), 'items' => [],
            ];
        }
        $stmt->close();

        if ($pesanan) {
            $ids = array_keys($pesanan);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $si = $koneksi->prepare('SELECT pesanan_id, nama_menu, varian, qty, addons, catatan FROM pesanan_item WHERE pesanan_id IN (' . $in . ') ORDER BY id');
            $si->bind_param(str_repeat('i', count($ids)), ...$ids);
            $si->execute();
            $ri = $si->get_result();
            while ($it = $ri->fetch_assoc()) {
                $pesanan[(int)$it['pesanan_id']]['items'][] = [
                    'nama' => $it['nama_menu'], 'varian' => $it['varian'], 'qty' => (int)$it['qty'],
                    'addons' => $it['addons'] ?? '', 'catatan' => $it['catatan'] ?? '',
                ];
            }
            $si->close();
        }

        $s = $koneksi->query(
            "SELECT COUNT(*) AS semua,
                    COALESCE(SUM(DATE(created_at) = CURDATE()), 0) AS hari_ini,
                    COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() AND status <> 'batal' THEN total ELSE 0 END), 0) AS omzet,
                    COALESCE(SUM(status IN ('baru','diproses','siap')), 0) AS aktif
             FROM pesanan"
        )->fetch_assoc();
        $perStatus = [];
        foreach ($koneksi->query('SELECT status, COUNT(*) AS n FROM pesanan GROUP BY status') as $r) {
            $perStatus[$r['status']] = (int)$r['n'];
        }
    } catch (mysqli_sql_exception $e) {
        error_log('[DOVEN BURGER] Admin data: ' . $e->getMessage());
        jsonBalas(false, 'Data belum bisa dimuat.', [], 500);
    }

    echo json_encode([
        'sukses' => true,
        'stats' => [
            'semua' => (int)$s['semua'], 'hari_ini' => (int)$s['hari_ini'],
            'omzet' => (int)$s['omzet'], 'aktif' => (int)$s['aktif'], 'per_status' => $perStatus,
        ],
        'pesanan' => array_values($pesanan),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- API: ubah status (POST) ---------- */
if ($aksi === 'status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!csrfValid()) {
        jsonBalas(false, 'Token keamanan tidak valid. Muat ulang halaman.', [], 403);
    }
    $id = (int)($_POST['id'] ?? 0);
    $statusBaru = (string)($_POST['status'] ?? '');
    if ($id < 1 || !isset(STATUS_PESANAN[$statusBaru])) {
        jsonBalas(false, 'Data tidak valid.', [], 422);
    }
    try {
        $stmt = $koneksi->prepare('UPDATE pesanan SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $statusBaru, $id);
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('[DOVEN BURGER] Admin status: ' . $e->getMessage());
        jsonBalas(false, 'Status gagal diubah.', [], 500);
    }
    jsonBalas(true, 'Status diperbarui.', ['status' => $statusBaru]);
}

/* ---------- Dashboard ---------- */
$konfig = json_encode([
    'csrf' => $csrf, 'status' => STATUS_PESANAN, 'tipe' => TIPE_PESANAN, 'bayar' => METODE_BAYAR,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

$isi = '<header class="top"><div class="top-in">' . $logo . '<div class="top-r">'
    . '<span class="live" id="live">Live</span>'
    . '<button type="button" class="btn ghost" id="suara" aria-pressed="false">🔕 Suara</button>'
    . '<form method="post"><input type="hidden" name="aksi" value="keluar"><input type="hidden" name="csrf" value="' . e($csrf) . '">'
    . '<button class="btn ghost" type="submit">Keluar</button></form></div></div></header>'
    . '<main class="wrap"><div id="err" class="err" role="alert" hidden></div>'
    . '<section class="stats" aria-label="Ringkasan">'
    . '<div class="stat hot"><small>Pesanan aktif</small><strong id="sAktif">-</strong></div>'
    . '<div class="stat"><small>Pesanan hari ini</small><strong id="sHari">-</strong></div>'
    . '<div class="stat"><small>Omzet hari ini</small><strong id="sOmzet">-</strong></div>'
    . '<div class="stat"><small>Total pesanan</small><strong id="sSemua">-</strong></div></section>'
    . '<section class="lap" aria-label="Laporan penjualan">'
    . '<div class="lap-card"><h2>Omzet 7 hari terakhir</h2><div class="bars" id="bars"></div></div>'
    . '<div class="lap-card"><h2>Menu terlaris (30 hari)</h2><ol class="top-list" id="topMenu"></ol></div></section>'
    . '<div class="views" role="group" aria-label="Tampilan">'
    . '<button type="button" class="tab" id="vPesanan" aria-pressed="true">Pesanan</button>'
    . '<button type="button" class="tab" id="vUlasan" aria-pressed="false">Ulasan<small id="uBaru"></small></button></div>'
    . '<div id="paneP"><div class="bar"><div class="tabs" id="tabs" role="group" aria-label="Filter status"></div>'
    . '<div class="bar-r"><input type="search" id="cari" placeholder="Cari kode, nama, atau nomor WA" aria-label="Cari pesanan">'
    . '<a class="btn ghost sm" id="ekspor" href="admin.php?aksi=ekspor">⬇ Ekspor CSV</a></div></div>'
    . '<div class="grid" id="daftar" aria-live="polite"></div></div>'
    . '<div id="paneU" hidden><div class="grid" id="daftarU" aria-live="polite"></div></div></main>';

$js = <<<'JS'
(function () {
    'use strict';
    var A = window.ADMIN;
    var $ = function (s) { return document.querySelector(s); };
    var esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };
    var rp = function (n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); };
    var ORDER = ['baru', 'diproses', 'siap', 'selesai', 'batal'];
    var filter = '', q = '', maxId = 0, pertama = true, suara = false, ctx = null, judul = document.title;

    function ago(iso) {
        var m = Math.round((Date.now() - new Date(iso).getTime()) / 60000);
        if (m < 1) return 'baru saja';
        if (m < 60) return m + ' mnt lalu';
        var h = Math.floor(m / 60);
        return h < 24 ? h + ' jam lalu' : Math.floor(h / 24) + ' hari lalu';
    }

    function beep() {
        if (!suara) return;
        try {
            ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
            var o = ctx.createOscillator(), g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination); o.frequency.value = 880;
            g.gain.setValueAtTime(0.001, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.15, ctx.currentTime + 0.02);
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            o.start(); o.stop(ctx.currentTime + 0.42);
        } catch (e) { /* audio tidak tersedia */ }
    }

    function kartu(o, baruIds) {
        var items = o.items.map(function (i) {
            return '<li><span><b>' + i.qty + '×</b> ' + esc(i.nama) + ' <em>' + esc(i.varian) + '</em>' +
                (i.addons ? '<small>+ ' + esc(i.addons) + '</small>' : '') +
                (i.catatan ? '<small class="nt">“' + esc(i.catatan) + '”</small>' : '') + '</span></li>';
        }).join('');
        var btns = ORDER.map(function (s) {
            var on = o.status === s;
            return '<button type="button" class="st' + (on ? ' s-' + s : '') + '" data-id="' + o.id + '" data-s="' + s + '"' + (on ? ' disabled' : '') + '>' + esc(A.status[s]) + '</button>';
        }).join('');
        var sum = 'Subtotal ' + rp(o.subtotal) + (o.diskon ? ' · Diskon ' + esc(o.promo) + ' −' + rp(o.diskon) : '') + (o.tipe === 'delivery' ? ' · Ongkir ' + (o.ongkir ? rp(o.ongkir) : 'gratis') : '');
        return '<article class="o' + (baruIds.indexOf(o.id) > -1 ? ' fresh' : '') + '">' +
            '<div class="o-h"><div><span class="o-code">' + esc(o.kode) + '</span><small>' + ago(o.waktu) + '</small></div><span class="o-total">' + rp(o.total) + '</span></div>' +
            '<div class="o-who">' + esc(o.nama) + '<a href="https://wa.me/62' + esc(o.wa.replace(/^0/, '')) + '" target="_blank" rel="noopener">' + esc(o.wa) + '</a></div>' +
            '<div class="chips"><span class="chip">' + esc(A.tipe[o.tipe] || o.tipe) + '</span><span class="chip">' + esc(A.bayar[o.bayar] || o.bayar) + '</span>' +
                (o.jadwal ? '<span class="chip jd">⏰ ' + esc(o.jadwal) + '</span>' : '') + '</div>' +
            (o.alamat ? '<p class="o-addr">📍 ' + esc(o.alamat) + '</p>' : '') +
            '<ul>' + items + '</ul>' +
            (o.catatan ? '<p class="o-note">📝 ' + esc(o.catatan) + '</p>' : '') +
            '<p class="o-sum">' + sum + '</p><div class="sts">' + btns + '</div></article>';
    }

    function tabs(per, semua) {
        var html = '<button type="button" class="tab" data-f="" aria-pressed="' + (filter === '') + '">Semua<small>' + semua + '</small></button>';
        ORDER.forEach(function (s) {
            html += '<button type="button" class="tab" data-f="' + s + '" aria-pressed="' + (filter === s) + '">' + esc(A.status[s]) + '<small>' + (per[s] || 0) + '</small></button>';
        });
        $('#tabs').innerHTML = html;
    }

    function muat() {
        var url = 'admin.php?aksi=data&status=' + encodeURIComponent(filter) + '&q=' + encodeURIComponent(q);
        $('#ekspor').href = 'admin.php?aksi=ekspor&status=' + encodeURIComponent(filter) + '&q=' + encodeURIComponent(q);
        return fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.sukses) throw new Error(d.pesan || 'Gagal memuat');
                $('#err').hidden = true;
                $('#sAktif').textContent = d.stats.aktif;
                $('#sHari').textContent = d.stats.hari_ini;
                $('#sOmzet').textContent = rp(d.stats.omzet);
                $('#sSemua').textContent = d.stats.semua;
                tabs(d.stats.per_status, d.stats.semua);

                var baru = [];
                if (!pertama) {
                    baru = d.pesanan.filter(function (o) { return o.id > maxId; }).map(function (o) { return o.id; });
                }
                d.pesanan.forEach(function (o) { if (o.id > maxId) maxId = o.id; });
                if (baru.length) {
                    beep();
                    document.title = '(' + baru.length + ' baru) ' + judul;
                    setTimeout(function () { document.title = judul; }, 6000);
                }
                pertama = false;
                $('#daftar').innerHTML = d.pesanan.length ? d.pesanan.map(function (o) { return kartu(o, baru); }).join('')
                    : '<p class="kosong">Belum ada pesanan untuk filter ini.</p>';
            })
            .catch(function (e) {
                var el = $('#err');
                el.textContent = (e && e.message ? e.message : 'Koneksi bermasalah') + '. Mencoba lagi otomatis...';
                el.hidden = false;
            });
    }

    function ubah(id, status) {
        var fd = new FormData();
        fd.append('aksi', 'status'); fd.append('id', id); fd.append('status', status);
        return fetch('admin.php', { method: 'POST', body: fd, headers: { 'X-CSRF': A.csrf, Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.sukses) throw new Error(d.pesan);
                return muat();
            })
            .catch(function (e) {
                var el = $('#err'); el.textContent = e.message || 'Status gagal diubah.'; el.hidden = false;
            });
    }

    $('#daftar').addEventListener('click', function (e) {
        var b = e.target.closest('.st');
        if (b && !b.disabled) { b.disabled = true; ubah(b.dataset.id, b.dataset.s); }
    });
    $('#tabs').addEventListener('click', function (e) {
        var b = e.target.closest('.tab');
        if (b) { filter = b.dataset.f; muat(); }
    });
    var tunda;
    $('#cari').addEventListener('input', function (e) {
        clearTimeout(tunda);
        tunda = setTimeout(function () { q = e.target.value.trim(); muat(); }, 300);
    });
    $('#suara').addEventListener('click', function () {
        suara = !suara;
        this.setAttribute('aria-pressed', String(suara));
        this.textContent = suara ? '🔔 Suara aktif' : '🔕 Suara';
        if (suara) beep();
    });

    /* ---------- Laporan ---------- */
    function muatLaporan() {
        return fetch('admin.php?aksi=laporan', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.sukses) throw new Error(d.pesan);
                var maks = Math.max.apply(null, d.hari.map(function (h) { return h.omzet; }).concat([1]));
                $('#bars').innerHTML = d.hari.map(function (h) {
                    var pct = h.omzet > 0 ? Math.max(3, Math.round(h.omzet / maks * 100)) : 0;
                    var ringkas = h.omzet >= 1000000 ? (h.omzet / 1000000).toFixed(1).replace('.', ',') + ' jt' : (h.omzet >= 1000 ? Math.round(h.omzet / 1000) + ' rb' : String(h.omzet));
                    return '<div class="bcol' + (h.hariIni ? ' now' : '') + '" title="' + esc(h.label) + ': ' + esc(rp(h.omzet)) + ' (' + h.n + ' pesanan)">' +
                        '<div class="bx"><div class="bfill" style="height:' + pct + '%"></div></div><b>' + esc(ringkas) + '</b><small>' + esc(h.label) + '</small></div>';
                }).join('');
                $('#topMenu').innerHTML = d.terlaris.length ? d.terlaris.map(function (t) {
                    return '<li><span>' + esc(t.nama) + '</span><em>' + t.qty + ' terjual</em></li>';
                }).join('') : '<li><span>Belum ada data penjualan.</span></li>';
            })
            .catch(function () { /* laporan opsional: diam saja bila gagal */ });
    }

    /* ---------- Ulasan ---------- */
    var tampilan = 'pesanan';
    function bintang(n) { return '★★★★★'.slice(0, n) + '<i>' + '★★★★★'.slice(0, 5 - n) + '</i>'; }
    function kartuUlasan(u) {
        return '<article class="rv"><div class="rv-h"><div><strong>' + esc(u.nama) + '</strong><small>' + esc(u.kode) + ' · ' + ago(u.waktu) + '</small></div>' +
            '<span class="rv-stars" aria-label="' + u.rating + ' dari 5">' + bintang(u.rating) + '</span></div>' +
            (u.komentar ? '<p>' + esc(u.komentar) + '</p>' : '<p class="kmt">Hanya memberi bintang, tanpa komentar.</p>') +
            '<div class="rv-act"><span class="badge ' + (u.tampil ? 'on' : 'off') + '">' + (u.tampil ? 'Tampil di website' : 'Menunggu persetujuan') + '</span>' +
            '<button type="button" class="st" data-u="' + u.id + '" data-t="' + (u.tampil ? 'sembunyi' : 'tampil') + '">' + (u.tampil ? 'Sembunyikan' : 'Tampilkan') + '</button>' +
            '<button type="button" class="st hapus" data-u="' + u.id + '" data-t="hapus">Hapus</button></div></article>';
    }
    function muatUlasan() {
        return fetch('admin.php?aksi=ulasan', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.sukses) throw new Error(d.pesan || 'Gagal memuat ulasan');
                var menunggu = d.ulasan.filter(function (u) { return !u.tampil; }).length;
                $('#uBaru').textContent = menunggu ? menunggu : '';
                $('#daftarU').innerHTML = d.ulasan.length ? d.ulasan.map(kartuUlasan).join('') : '<p class="kosong">Belum ada ulasan dari pelanggan.</p>';
            })
            .catch(function (e) { $('#daftarU').innerHTML = '<p class="kosong">' + esc(e.message || 'Ulasan belum bisa dimuat.') + '</p>'; });
    }
    function ubahUlasan(id, tindakan) {
        var fd = new FormData();
        fd.append('aksi', 'ulasan_set'); fd.append('id', id); fd.append('tindakan', tindakan);
        return fetch('admin.php', { method: 'POST', body: fd, headers: { 'X-CSRF': A.csrf, Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (!d.sukses) throw new Error(d.pesan); return muatUlasan(); })
            .catch(function (e) { var el = $('#err'); el.textContent = e.message || 'Perubahan gagal disimpan.'; el.hidden = false; return muatUlasan(); });
    }
    function pilihTampilan(nama) {
        tampilan = nama;
        $('#paneP').hidden = nama !== 'pesanan';
        $('#paneU').hidden = nama !== 'ulasan';
        $('#vPesanan').setAttribute('aria-pressed', String(nama === 'pesanan'));
        $('#vUlasan').setAttribute('aria-pressed', String(nama === 'ulasan'));
        if (nama === 'ulasan') muatUlasan();
    }
    $('#vPesanan').addEventListener('click', function () { pilihTampilan('pesanan'); });
    $('#vUlasan').addEventListener('click', function () { pilihTampilan('ulasan'); });
    $('#daftarU').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-u]');
        if (!b) return;
        if (b.dataset.t === 'hapus' && !window.confirm('Hapus ulasan ini secara permanen?')) return;
        b.disabled = true;
        ubahUlasan(b.dataset.u, b.dataset.t);
    });

    muat();
    muatLaporan();
    muatUlasan();   // memperbarui lencana jumlah ulasan yang menunggu persetujuan
    setInterval(muat, 15000);
    setInterval(muatLaporan, 60000);
    setInterval(function () { if (tampilan === 'ulasan' || !document.hidden) muatUlasan(); }, 60000);
})();

JS;

halaman('Dashboard Pesanan | DOVEN BURGER', $css, $isi, '<script>window.ADMIN=' . $konfig . ';</script><script>' . $js . '</script>');
