<?php
declare(strict_types=1);

require_once __DIR__ . '/data.php';

$menu      = dataMenu();
$addon     = dataAddon();
$promoList = dataPromo();

$testiStatis = [
    ['nama' => 'Saddam Rizky Aziz', 'peran' => 'Pelanggan setia', 'teks' => 'Layup Burger-nya juara. Patty-nya juicy, rotinya lembut, dan saus rahasianya bikin balik lagi tiap minggu. Mantap pokoknya!', 'inisial' => 'RP'],
    ['nama' => 'Muhammad Wahyudin', 'peran' => 'Food blogger', 'teks' => 'Hook Shot adalah burger paling berani yang pernah saya coba di DOVEN BURGER. Truffle mayo-nya pas, tidak berlebihan.', 'inisial' => 'AP'],
    ['nama' => 'Aria Danuwarta', 'peran' => 'Kritikus', 'teks' => 'Free Throw cocok banget buat makan siang. Porsi pas, harga ramah di kantong, pesan lewat web juga gampang.', 'inisial' => 'BW'],
];

/* ---------- Ulasan asli pelanggan (yang sudah disetujui di admin.php) ---------- */
$ulasanAsli = [];
$ringkasUlasan = ['n' => 0, 'rata' => 0.0];
define('KONEKSI_LUNAK', true);   // bila database bermasalah, halaman tetap tampil memakai testimoni bawaan
require_once __DIR__ . '/koneksi.php';
if (isset($koneksi) && $koneksi instanceof mysqli) {
    try {
        $rs = $koneksi->query('SELECT COUNT(*) AS n, COALESCE(AVG(rating), 0) AS rata FROM ulasan WHERE tampil = 1')->fetch_assoc();
        $ringkasUlasan = ['n' => (int)($rs['n'] ?? 0), 'rata' => (float)($rs['rata'] ?? 0)];
        $res = $koneksi->query("SELECT nama, rating, komentar FROM ulasan WHERE tampil = 1 AND komentar <> '' ORDER BY id DESC LIMIT 6");
        while ($u = $res->fetch_assoc()) {
            $kata = preg_split('/\s+/u', trim((string)$u['nama'])) ?: [];
            $ini = '';
            foreach (array_slice($kata, 0, 2) as $k) {
                $ini .= mb_strtoupper(mb_substr($k, 0, 1));
            }
            $ulasanAsli[] = ['nama' => (string)$u['nama'], 'peran' => 'Pelanggan DOVEN', 'teks' => (string)$u['komentar'], 'inisial' => $ini !== '' ? $ini : 'D', 'rating' => (int)$u['rating']];
        }
        $koneksi->close();
    } catch (Throwable $e) {
        error_log('[DOVEN BURGER] Ulasan: ' . $e->getMessage());   // tabel belum dimigrasi / gangguan: pakai testimoni bawaan
    }
}
// Ulasan asli tampil lebih dulu; sisa slot (sampai 3 kartu) diisi testimoni bawaan
$testimoni = array_merge($ulasanAsli, array_slice($testiStatis, 0, max(0, 3 - count($ulasanAsli))));

$kategori = ['semua' => 'Semua', 'favorit' => 'Favorit', 'burger' => 'Burger', 'assist' => 'Assist'];
$hitungKat = array_count_values(array_column($menu, 'kategori'));
$hitungKat['semua'] = count($menu);
$hitungKat['favorit'] = 0;   // dihitung di browser dari daftar favorit pengunjung

// Data untuk shop.js (harga hanya untuk tampilan; server selalu menghitung ulang)
$cfgJs = [
    'menu' => array_map(static fn(array $m): array => [
        'nama' => $m['nama'], 'kategori' => $m['kategori'], 'ikon' => $m['ikon'], 'warna' => $m['warna'],
        'desc' => $m['desc'], 'harga' => $m['harga'], 'addon' => $m['addon'], 'tersedia' => ($m['tersedia'] ?? true) !== false,
    ], $menu),
    'addons'       => $addon,
    'ongkir'       => ONGKIR_FLAT,
    'gratisOngkir' => GRATIS_ONGKIR,
    'buka'         => JAM_BUKA,
    'tutup'        => JAM_TUTUP,
    'maksQty'      => MAKS_QTY,
    'maksBaris'    => MAKS_BARIS,
];

$semuaHarga = [];
foreach ($menu as $m) {
    foreach ($m['harga'] as $h) {
        $semuaHarga[] = $h;
    }
}
$jsonLd = [
    '@context' => 'https://schema.org', '@type' => 'Restaurant', 'name' => 'DOVEN BURGER',
    'servesCuisine' => 'Burger', 'priceRange' => rupiah(min($semuaHarga)) . ' - ' . rupiah(max($semuaHarga)),
    'telephone' => '+62 812-3456-7890', 'openingHours' => 'Mo-Su ' . sprintf('%02d:00-%02d:00', JAM_BUKA, JAM_TUTUP),
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Jl. Contoh Raya No. 88', 'addressLocality' => 'Jakarta Selatan', 'addressCountry' => 'ID'],
];
if ($ringkasUlasan['n'] > 0) {
    $jsonLd['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => round($ringkasUlasan['rata'], 1), 'reviewCount' => $ringkasUlasan['n']];
}

/** Ilustrasi SVG untuk item non-burger (dipakai bila foto di assets/ belum ada). */
function ikonItem(string $id): string
{
    switch ($id) {
        case 'fries':
            return <<<'SVG'
<svg class="menu-ico ico-fries" viewBox="0 0 160 220" role="img" aria-label="Kentang goreng"><g fill="#fcd34d"><rect x="28" y="14" width="15" height="120" rx="4" transform="rotate(-10 35 70)"/><rect x="48" y="4" width="15" height="130" rx="4" transform="rotate(-5 55 70)"/><rect x="68" y="0" width="15" height="134" rx="4"/><rect x="88" y="6" width="15" height="128" rx="4" transform="rotate(5 95 70)"/><rect x="108" y="12" width="15" height="122" rx="4" transform="rotate(11 115 70)"/></g><g fill="#f59e0b" opacity=".55"><rect x="68" y="0" width="5" height="134" rx="2"/><rect x="48" y="4" width="5" height="130" rx="2" transform="rotate(-5 55 70)"/></g><path d="M18 104 H142 L130 208 Q129 216 121 216 H39 Q31 216 30 208 Z" fill="#dc2626"/><path d="M24 150 H136 L134 176 H26 Z" fill="#fff" opacity=".92"/><text x="80" y="172" text-anchor="middle" font-family="Anton, Impact, sans-serif" font-size="26" fill="#dc2626">DOVEN</text></svg>
SVG;
        case 'shake':
            return <<<'SVG'
<svg class="menu-ico ico-shake" viewBox="0 0 120 250" role="img" aria-label="Milkshake"><rect x="70" y="0" width="9" height="90" rx="4" fill="#ef4444" transform="rotate(14 74 45)"/><path d="M18 92 H102 L92 232 Q91 240 83 240 H37 Q29 240 28 232 Z" fill="#fff" stroke="#e2e8f0" stroke-width="2"/><path d="M22 130 H98 L96 156 H24 Z" fill="#dc2626"/><path d="M25 180 H95 L93 206 H27 Z" fill="#dc2626"/><circle cx="40" cy="80" r="26" fill="#fff7ed"/><circle cx="66" cy="76" r="28" fill="#fff"/><circle cx="82" cy="86" r="18" fill="#fff7ed"/><path d="M32 84 q14 14 28 0 t28 0" fill="none" stroke="#7a4426" stroke-width="6" stroke-linecap="round"/><circle cx="58" cy="52" r="9" fill="#dc2626"/><path d="M58 44 q4 -10 10 -12" stroke="#16a34a" stroke-width="3" fill="none" stroke-linecap="round"/></svg>
SVG;
        case 'sauce':
            return <<<'SVG'
<svg class="menu-ico ico-sauce" viewBox="0 0 90 80" role="img" aria-label="Saus"><ellipse cx="45" cy="26" rx="34" ry="11" fill="#dc2626"/><path d="M12 26 H78 L70 70 Q69 76 62 76 H28 Q21 76 20 70 Z" fill="#fff" stroke="#e2e8f0" stroke-width="2"/><text x="45" y="58" text-anchor="middle" font-family="Anton, Impact, sans-serif" font-size="13" fill="#f97316">DVN</text></svg>
SVG;
    }
    return '';
}

/** Gambar satu lapisan burger (SVG) mulai dari koordinat y. */
function burgerLayer(string $type, float $y, string $uid): string
{
    $a = fn(float $d): string => (string)round($y + $d, 1);
    $s = '';
    switch ($type) {
        case 'bunTop':
            $s .= '<path d="M70 ' . $a(84) . ' C70 ' . $a(14) . ' 125 ' . $a(0) . ' 200 ' . $a(0) . ' C275 ' . $a(0) . ' 330 ' . $a(14) . ' 330 ' . $a(84) . ' Q200 ' . $a(98) . ' 70 ' . $a(84) . 'Z" fill="url(#bt' . $uid . ')"/>';
            $s .= '<ellipse cx="148" cy="' . $a(24) . '" rx="50" ry="11" fill="#fff" opacity=".22" transform="rotate(-12 148 ' . $a(24) . ')"/>';
            foreach ([[130, 24, -25], [170, 15, 10], [215, 13, -8], [255, 24, 28], [150, 44, 40], [200, 38, -30], [242, 48, 15], [108, 52, -10], [288, 50, -35], [180, 62, 20], [226, 66, -20]] as [$sx, $sy, $r]) {
                $s .= '<ellipse cx="' . $sx . '" cy="' . $a($sy) . '" rx="8" ry="3.8" fill="#fff4d6" transform="rotate(' . $r . ' ' . $sx . ' ' . $a($sy) . ')"/>';
            }
            return $s;
        case 'lettuce':
            $wave = 'q10 -16 20 0' . str_repeat(' t20 0', 13);
            $s .= '<path d="M60 ' . $a(22) . ' ' . $wave . ' L340 ' . $a(36) . ' Q200 ' . $a(42) . ' 60 ' . $a(36) . 'Z" fill="#16a34a"/>';
            $s .= '<path d="M60 ' . $a(14) . ' ' . $wave . ' L340 ' . $a(28) . ' Q200 ' . $a(34) . ' 60 ' . $a(28) . 'Z" fill="url(#lt' . $uid . ')"/>';
            return $s;
        case 'tomato':
            $s .= '<rect x="80" y="' . $a(0) . '" width="240" height="22" rx="11" fill="url(#tm' . $uid . ')"/>';
            foreach ([120, 160, 200, 240, 280] as $cx) {
                $s .= '<circle cx="' . $cx . '" cy="' . $a(11) . '" r="3" fill="#fecaca" opacity=".85"/>';
            }
            return $s;
        case 'cheese':
            return '<path d="M70 ' . $a(0) . ' H330 L338 ' . $a(10) . ' L304 ' . $a(10) . ' L300 ' . $a(28) . ' L286 ' . $a(11) . ' H132 L127 ' . $a(24) . ' L116 ' . $a(11) . ' H72 Z" fill="url(#ch' . $uid . ')"/>';
        case 'patty':
            $s .= '<rect x="74" y="' . $a(0) . '" width="252" height="44" rx="22" fill="url(#pt' . $uid . ')"/>';
            foreach ([[110, 14], [150, 28], [190, 12], [235, 30], [280, 16], [130, 34], [260, 36]] as [$px, $py]) {
                $s .= '<ellipse cx="' . $px . '" cy="' . $a($py) . '" rx="9" ry="3" fill="#2b1206" opacity=".5"/>';
            }
            foreach ([[170, 20], [215, 18], [96, 26], [300, 26]] as [$px, $py]) {
                $s .= '<ellipse cx="' . $px . '" cy="' . $a($py) . '" rx="8" ry="2.6" fill="#a8683f" opacity=".5"/>';
            }
            $s .= '<path d="M92 ' . $a(10) . ' Q200 ' . $a(2) . ' 308 ' . $a(10) . '" stroke="#fff" stroke-opacity=".18" stroke-width="3" fill="none" stroke-linecap="round"/>';
            return $s;
        case 'bacon':
            $d = 'M76 ' . $a(8) . ' q20 -10 40 0' . str_repeat(' t40 0', 5);
            return '<path d="' . $d . '" fill="none" stroke="#b91c1c" stroke-width="11" stroke-linecap="round"/><path d="' . $d . '" fill="none" stroke="#fda4af" stroke-width="3.5" stroke-linecap="round" opacity=".7" transform="translate(0 -1.5)"/>';
        case 'bunBottom':
            return '<path d="M70 ' . $a(0) . ' H330 L326 ' . $a(30) . ' Q318 ' . $a(52) . ' 280 ' . $a(52) . ' H120 Q82 ' . $a(52) . ' 74 ' . $a(30) . ' Z" fill="url(#bb' . $uid . ')"/>';
    }
    return $s;
}

/**
 * Bangun SVG burger berlapis.
 * Opsi: patties, bacon, bunL, bunD, vb (viewBox), spacing (jarak antar lapisan saat terpisah), cls.
 * @return array{svg:string, meta:array<int,array{label:string,yc:float,k:float}>}
 */
function burgerSvg(string $uid, array $o = []): array
{
    $patties = (int)($o['patties'] ?? 1);
    $bacon   = (bool)($o['bacon'] ?? false);
    $bunL    = (string)($o['bunL'] ?? '#f6b24f');
    $bunD    = (string)($o['bunD'] ?? '#c9741c');
    $vb      = (string)($o['vb'] ?? '40 60 320 520');
    $spacing = (float)($o['spacing'] ?? 30);
    $cls     = (string)($o['cls'] ?? '');

    $layers = [['bunTop', 84, 'Brioche bun panggang'], ['lettuce', 30, 'Selada segar'], ['tomato', 22, 'Irisan tomat']];
    for ($i = 0; $i < $patties; $i++) {
        if ($bacon && $i === $patties - 1) {
            $layers[] = ['bacon', 16, 'Bacon crispy'];
        }
        $layers[] = ['cheese', 26, 'Cheddar leleh'];
        $layers[] = ['patty', 44, 'Smash patty beef 90 gr'];
    }
    $layers[] = ['bunBottom', 52, 'Bun bawah'];

    $n = count($layers);
    $total = 0;
    foreach ($layers as $l) {
        $total += $l[1];
    }
    $total -= 6 * ($n - 1);
    $y = 320 - $total / 2;

    $meta = [];
    $body = '';
    foreach ($layers as $idx => [$type, $h, $label]) {
        $k = round(($idx - ($n - 1) / 2) * $spacing, 1);
        $meta[] = ['label' => $label, 'yc' => round($y + $h / 2, 1), 'k' => $k];
        $body .= '<g class="ly" style="--k:' . $k . '">' . burgerLayer($type, (float)$y, $uid) . '</g>';
        $y += $h - 6;
    }

    $defs = '<defs>'
        . '<radialGradient id="bt' . $uid . '" cx=".35" cy=".25" r=".9"><stop offset="0" stop-color="' . $bunL . '"/><stop offset="1" stop-color="' . $bunD . '"/></radialGradient>'
        . '<linearGradient id="bb' . $uid . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . $bunL . '"/><stop offset="1" stop-color="' . $bunD . '"/></linearGradient>'
        . '<linearGradient id="lt' . $uid . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#86efac"/><stop offset="1" stop-color="#22c55e"/></linearGradient>'
        . '<linearGradient id="tm' . $uid . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f87171"/><stop offset="1" stop-color="#dc2626"/></linearGradient>'
        . '<linearGradient id="ch' . $uid . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fde68a"/><stop offset="1" stop-color="#f59e0b"/></linearGradient>'
        . '<linearGradient id="pt' . $uid . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7a4426"/><stop offset="1" stop-color="#3f1d0b"/></linearGradient>'
        . '</defs>';

    $svg = '<svg class="burger-svg ' . $cls . '" viewBox="' . $vb . '" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustrasi burger berlapis">' . $defs . $body . '</svg>';

    return ['svg' => $svg, 'meta' => $meta];
}

/** Cari file gambar di assets/layers/ lalu assets/ (png, webp, jpg). Null jika tidak ada. */
function assetPath(string $name): ?string
{
    foreach (['assets/layers/', 'assets/'] as $dir) {
        foreach (['png', 'webp', 'jpg', 'jpeg'] as $ext) {
            $rel = $dir . $name . '.' . $ext;
            if (is_file(__DIR__ . '/' . $rel)) {
                return $rel . '?v=' . filemtime(__DIR__ . '/' . $rel);
            }
        }
    }
    return null;
}

/**
 * Kotak area terlihat (non-transparan) sebuah gambar, sebagai pecahan 0..1: [x0, y0, x1, y1].
 * PNG lapisan yang punya margin transparan lebar membuat tumpukan renggang; dengan kotak ini
 * susunan dihitung dari bagian yang benar-benar terlihat. Hasil di-cache agar tidak dipindai ulang.
 */
function visibleBox(string $abs): ?array
{
    $full = null;   // null = tidak bisa diukur (mis. ekstensi GD nonaktif); pemanggil memakai cadangan
    $cache = sys_get_temp_dir() . '/doven_bbox_' . md5($abs . '|' . (string)@filemtime($abs)) . '.json';
    if (is_file($cache)) {
        $c = json_decode((string)@file_get_contents($cache), true);
        if (is_array($c) && count($c) === 4) {
            return array_map('floatval', $c);
        }
    }
    if (!function_exists('imagecreatefromstring')) {
        return $full;
    }
    $raw = @file_get_contents($abs);
    $im = $raw === false ? false : @imagecreatefromstring($raw);
    if (!$im) {
        return $full;
    }
    $w = imagesx($im);
    $h = imagesy($im);
    $step = max(1, (int)floor(max($w, $h) / 500));
    $x0 = $w; $y0 = $h; $x1 = -1; $y1 = -1;
    for ($y = 0; $y < $h; $y += $step) {
        for ($x = 0; $x < $w; $x += $step) {
            if ((($rgba = imagecolorat($im, $x, $y)) >> 24 & 0x7F) < 90) {   // alpha GD: 0 = solid, 127 = transparan
                if ($x < $x0) { $x0 = $x; }
                if ($x > $x1) { $x1 = $x; }
                if ($y < $y0) { $y0 = $y; }
                if ($y > $y1) { $y1 = $y; }
            }
        }
    }
    imagedestroy($im);
    if ($x1 < $x0 || $y1 < $y0) {
        return $full;
    }
    $box = [$x0 / $w, $y0 / $h, min(1.0, ($x1 + $step) / $w), min(1.0, ($y1 + $step) / $h)];
    @file_put_contents($cache, json_encode($box));
    return $box;
}

/**
 * Susun burger dari foto lapisan (PNG transparan). Null jika ada lapisan yang belum tersedia.
 * Atur lebar tiap lapisan di $lebar dan tumpang-tindih di $overlap jika tumpukan terlihat renggang/sempit.
 */
function photoBurger(): ?array
{
    $lebar = [   // lebar lapisan dalam satuan kanvas 400 x 700
        'bun-top' => 270, 'lettuce' => 292, 'tomato' => 252, 'cheese' => 272,
        'patty' => 262, 'bacon' => 252, 'bun-bottom' => 270,
    ];
    $label = [
        'bun-top' => 'Brioche bun panggang', 'lettuce' => 'Selada segar', 'tomato' => 'Irisan tomat',
        'cheese' => 'Cheddar leleh', 'patty' => 'Smash patty beef 90 gr', 'bacon' => 'Bacon crispy',
        'bun-bottom' => 'Bun bawah',
    ];
    // Area terlihat tiap PNG (x0, y0, x1, y1 sebagai pecahan 0..1), dipakai hanya jika ekstensi GD PHP nonaktif.
    $kotakBawaan = [
        'bun-top' => [0.0654, 0.2279, 0.9265, 0.7721], 'lettuce' => [0.0556, 0.2917, 0.9592, 0.6936],
        'tomato' => [0.0425, 0.3162, 0.9575, 0.7083], 'cheese' => [0.0425, 0.3407, 0.9575, 0.6716],
        'patty' => [0.0359, 0.3186, 0.9493, 0.701], 'bacon' => [0.0343, 0.3186, 0.9673, 0.6887],
        'bun-bottom' => [0.049, 0.2941, 0.9461, 0.7255],
    ];
    $urutan  = ['bun-top', 'lettuce', 'tomato', 'cheese', 'patty', 'bacon', 'cheese', 'patty', 'bun-bottom'];
    $overlap = 0.50;   // tumpang-tindih antar lapisan (dari tinggi bagian yang terlihat); naikkan = lebih padat, turunkan = lebih renggang
    $spacing = 46;

    $info = [];
    foreach (array_keys($lebar) as $nama) {
        $path = assetPath($nama);
        if ($path === null) {
            return null;
        }
        $size = @getimagesize(__DIR__ . '/' . strtok($path, '?'));
        if (!$size || $size[0] < 1) {
            return null;
        }
        $box = visibleBox(__DIR__ . '/' . strtok($path, '?'));
        if ($box === null) {   // GD tidak tersedia: pakai ukuran bawaan bila rasio file 3:2 (berapa pun resolusinya), selain itu apa adanya
            $box = (abs($size[1] / $size[0] - 408 / 612) < 0.01 && isset($kotakBawaan[$nama])) ? $kotakBawaan[$nama] : [0.0, 0.0, 1.0, 1.0];
        }
        $info[$nama] = ['src' => $path, 'rasio' => $size[1] / $size[0], 'box' => $box];
    }

    $n = count($urutan);
    $tinggi = [];
    $total = 0.0;
    foreach ($urutan as $i => $nama) {
        $b = $info[$nama]['box'];
        $tinggi[$i] = $lebar[$nama] * $info[$nama]['rasio'] * ($b[3] - $b[1]);   // tinggi bagian yang terlihat (margin atas/bawah dibuang)
        $total += $tinggi[$i] * ($i < $n - 1 ? 1 - $overlap : 1);
    }
    $y = 320 - $total / 2;

    $html = '';
    $meta = [];
    foreach ($urutan as $i => $nama) {
        $w = $lebar[$nama];
        $h = $tinggi[$i];                                   // tinggi bagian terlihat
        $b = $info[$nama]['box'];
        $left = 200 - $w / 2;                               // lebar tetap seperti semula
        $top = $y - $b[1] * ($w * $info[$nama]['rasio']);   // geser naik sebesar margin transparan di atas
        $k = round(($i - ($n - 1) / 2) * $spacing, 1);
        $meta[] = ['label' => $label[$nama], 'yc' => round($y + $h / 2, 1), 'k' => $k];
        $html .= '<img class="ly ly-img" src="' . e($info[$nama]['src']) . '" alt="" draggable="false" style="--k:' . $k
            . ';left:' . round($left / 400 * 100, 2) . '%;top:' . round(($top + 40) / 700 * 100, 2)
            . '%;width:' . round($w / 400 * 100, 2) . '%;z-index:' . ($n - $i) . '">';
        $y += $h * (1 - $overlap);
    }

    return ['html' => '<div class="photo-layers hero-burger">' . $html . '</div>', 'meta' => $meta];
}

$hero = burgerSvg('h', ['patties' => 2, 'bacon' => true, 'vb' => '0 -40 400 700', 'spacing' => 46, 'cls' => 'hero-burger']);
$heroPhoto = photoBurger();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOVEN BURGER | 100% Premium Beef, New Recipe 2026</title>
    <meta name="description" content="DOVEN BURGER menyajikan burger premium dengan 100% daging sapi pilihan. Pesan Free Throw, Layup, Hook Shot, Alley-Oop, Buzzer Beater, atau Slam Dunk langsung dari website.">
    <meta name="theme-color" content="#f8fafc">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192.png">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="DOVEN">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta property="og:type" content="website">
    <meta property="og:title" content="DOVEN BURGER | 100% Premium Beef, New Recipe 2026">
    <meta property="og:description" content="Racik burgermu, pesan langsung dari website, dan lacak statusnya secara real-time.">
    <script>
        /* Terapkan tema sebelum halaman digambar agar tidak berkedip */
        try {
            var t = localStorage.getItem('doven_theme');
            if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
            if (t === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                var m = document.querySelector('meta[name="theme-color"]');
                if (m) m.setAttribute('content', '#0b1120');
            }
        } catch (e) {}
    </script>
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>

<a class="skip" href="#menu">Lewati ke menu</a>
<div class="progress" id="progress" aria-hidden="true"></div>

<div class="bg-orbs" aria-hidden="true">
    <span class="orb orb-1"></span>
    <span class="orb orb-2"></span>
    <span class="orb orb-3"></span>
</div>

<!-- ============ NAVBAR ============ -->
<header class="navbar" id="navbar">
    <div class="container nav-inner">
        <a href="#beranda" class="logo" aria-label="DOVEN BURGER">
            <span class="logo-icon">
                <svg viewBox="0 0 32 32" width="22" height="22" fill="none" aria-hidden="true">
                    <path d="M5 14c0-5.5 4.9-9 11-9s11 3.5 11 9H5Z" fill="#fff"/>
                    <rect x="4" y="16" width="24" height="3" rx="1.5" fill="#fff" opacity=".85"/>
                    <path d="M5 21h22c0 3.3-2.7 6-6 6H11c-3.3 0-6-2.7-6-6Z" fill="#fff"/>
                </svg>
            </span>
            <span class="logo-text">DOVEN<b>BURGER</b></span>
        </a>

        <nav class="nav-links" id="navLinks" aria-label="Navigasi utama">
            <a href="#menu" class="nav-link">The Line Up</a>
            <a href="#cara-pesan" class="nav-link">Cara Pesan</a>
            <a href="#tentang" class="nav-link">Tentang Kami</a>
            <a href="#testimoni" class="nav-link">Testimoni</a>
            <a href="#kontak" class="nav-link">Kontak</a>
            <button type="button" class="nav-link nav-track js-open-track">Lacak Pesanan</button>
            <a href="#menu" class="btn btn-primary btn-sm nav-cta-mobile">Pesan Sekarang</a>
        </nav>

        <div class="nav-tools">
            <button type="button" class="icon-btn" id="themeToggle" aria-label="Ganti tema terang atau gelap">
                <svg class="ic-moon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                <svg class="ic-sun" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            <button type="button" class="icon-btn cart-btn" id="cartBtn" aria-label="Buka keranjang">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                <span class="cart-count" id="cartCount" hidden>0</span>
            </button>
            <a href="#menu" class="btn btn-primary btn-sm nav-cta">Pesan Sekarang</a>
            <button class="hamburger" id="hamburger" aria-label="Buka menu" aria-expanded="false" aria-controls="navLinks">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>

<main>

<!-- ============ HERO SCROLLYTELLING ============ -->
<section class="story" id="beranda" aria-label="Perkenalan DOVEN BURGER">
    <div class="stage" id="stage">

        <svg class="court" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
            <g fill="none" stroke="#f97316" stroke-width="3">
                <circle cx="720" cy="560" r="180"/>
                <rect x="560" y="560" width="320" height="340"/>
                <path d="M180 900 V740 A540 540 0 0 1 1260 740 V900"/>
                <circle cx="720" cy="900" r="70"/>
            </g>
        </svg>
        <div class="ghost-word" aria-hidden="true">DOVEN</div>

        <!-- Scene 1: Smashed to order -->
        <div class="scene sc1">
            <div class="hero-copy">
                <span class="badge"><i class="dot"></i> New Recipe 2026 / 100% Premium Beef</span>
                <p>Daging sapi pilihan di-smash di atas hot plate, keju leleh sempurna, brioche dipanggang setiap pesanan.</p>
                <div class="hero-actions">
                    <a href="#menu" class="btn btn-primary btn-glow">Lihat Menu <span class="arrow">→</span></a>
                    <a href="#tentang" class="btn btn-outline">Kenali Kami</a>
                </div>
            </div>
            <h1 class="giant"><span>Smashed</span><span class="o">to order</span></h1>
            <div class="cue"><span></span>Scroll</div>
        </div>

        <!-- Scene 2: The stack -->
        <div class="scene sc2">
            <h2 class="giant"><span>The</span><span class="o">Stack</span></h2>
            <p class="cap">Sembilan lapisan, satu tujuan: gigitan pertama yang tidak terlupakan.</p>
        </div>

        <!-- Scene 3: Game day combo -->
        <div class="scene sc3">
            <div class="cta">
                <p>Tambah kentang goreng, milkshake, dan saus favorit.</p>
                <a href="#menu" class="btn btn-primary btn-glow">Pilih Pemainmu <span class="arrow">→</span></a>
            </div>
            <h2 class="giant"><span>Game Day</span><span class="o">Combo</span></h2>
        </div>

        <!-- Burger -->
        <div class="burger-wrap" id="burgerWrap">
            <div class="burger-in">
                <div class="plate"></div>
                <?= $heroPhoto ? $heroPhoto['html'] : $hero['svg'] ?>
                <ul class="blabels" id="blabels" aria-hidden="true">
                    <?php foreach (($heroPhoto['meta'] ?? $hero['meta']) as $m): ?>
                    <li data-yc="<?= $m['yc'] ?>" data-k="<?= $m['k'] ?>"><i></i><span><?= e($m['label']) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Assist scene 3 -->
        <?php if ($p = assetPath('fries')): ?><img class="side side-fries" src="<?= e($p) ?>" alt="" draggable="false"><?php else: ?>
        <svg class="side side-fries" viewBox="0 0 160 220" aria-hidden="true">
            <g fill="#fcd34d">
                <rect x="28" y="14" width="15" height="120" rx="4" transform="rotate(-10 35 70)"/>
                <rect x="48" y="4" width="15" height="130" rx="4" transform="rotate(-5 55 70)"/>
                <rect x="68" y="0" width="15" height="134" rx="4"/>
                <rect x="88" y="6" width="15" height="128" rx="4" transform="rotate(5 95 70)"/>
                <rect x="108" y="12" width="15" height="122" rx="4" transform="rotate(11 115 70)"/>
            </g>
            <g fill="#f59e0b" opacity=".55">
                <rect x="68" y="0" width="5" height="134" rx="2"/>
                <rect x="48" y="4" width="5" height="130" rx="2" transform="rotate(-5 55 70)"/>
            </g>
            <path d="M18 104 H142 L130 208 Q129 216 121 216 H39 Q31 216 30 208 Z" fill="#dc2626"/>
            <path d="M24 150 H136 L134 176 H26 Z" fill="#fff" opacity=".92"/>
            <text x="80" y="172" text-anchor="middle" font-family="Anton, Impact, sans-serif" font-size="26" fill="#dc2626">DOVEN</text>
        </svg>
        <?php endif; ?>
        <?php if ($p = assetPath('milkshake')): ?><img class="side side-shake" src="<?= e($p) ?>" alt="" draggable="false"><?php else: ?>
        <svg class="side side-shake" viewBox="0 0 120 250" aria-hidden="true">
            <rect x="70" y="0" width="9" height="90" rx="4" fill="#ef4444" transform="rotate(14 74 45)"/>
            <path d="M18 92 H102 L92 232 Q91 240 83 240 H37 Q29 240 28 232 Z" fill="#fff" stroke="#e2e8f0" stroke-width="2"/>
            <path d="M22 130 H98 L96 156 H24 Z" fill="#dc2626"/>
            <path d="M25 180 H95 L93 206 H27 Z" fill="#dc2626"/>
            <circle cx="40" cy="80" r="26" fill="#fff7ed"/>
            <circle cx="66" cy="76" r="28" fill="#fff"/>
            <circle cx="82" cy="86" r="18" fill="#fff7ed"/>
            <path d="M32 84 q14 14 28 0 t28 0" fill="none" stroke="#7a4426" stroke-width="6" stroke-linecap="round"/>
            <circle cx="58" cy="52" r="9" fill="#dc2626"/>
            <path d="M58 44 q4 -10 10 -12" stroke="#16a34a" stroke-width="3" fill="none" stroke-linecap="round"/>
        </svg>
        <?php endif; ?>
        <?php if ($p = assetPath('sauce')): ?><img class="side side-sauce" src="<?= e($p) ?>" alt="" draggable="false"><?php else: ?>
        <svg class="side side-sauce" viewBox="0 0 90 80" aria-hidden="true">
            <ellipse cx="45" cy="26" rx="34" ry="11" fill="#dc2626"/>
            <path d="M12 26 H78 L70 70 Q69 76 62 76 H28 Q21 76 20 70 Z" fill="#fff" stroke="#e2e8f0" stroke-width="2"/>
            <text x="45" y="58" text-anchor="middle" font-family="Anton, Impact, sans-serif" font-size="13" fill="#f97316">DVN</text>
        </svg>
        <?php endif; ?>

        <div class="hud" aria-hidden="true"><i class="on"></i><i></i><i></i></div>
    </div>
</section>

<!-- ============ MARQUEE ============ -->
<div class="marquee" aria-hidden="true">
    <div class="marquee-track">
        <?php for ($i = 0; $i < 2; $i++): ?>
        <span>100% PREMIUM BEEF</span><i>✦</i><span>SMASH PATTY</span><i>✦</i><span>BRIOCHE PANGGANG</span><i>✦</i><span>SAUS RAHASIA</span><i>✦</i><span>NEW RECIPE 2026</span><i>✦</i>
        <?php endfor; ?>
    </div>
</div>

<!-- ============ MENU / THE LINE UP ============ -->
<section class="section" id="menu">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">The Line Up</span>
            <h2>Pilih Pemain Andalanmu</h2>
            <p>Racik burger dengan tambahan favoritmu, masukkan ke keranjang, lalu checkout sekali jalan.</p>
        </div>

        <div class="menu-tools reveal">
            <div class="tabs" id="menuTabs" role="group" aria-label="Kategori menu">
                <?php foreach ($kategori as $k => $label): ?>
                <button type="button" class="tab" data-kat="<?= e($k) ?>" aria-pressed="<?= $k === 'semua' ? 'true' : 'false' ?>"><?= e($label) ?> <small><?= (int)($hitungKat[$k] ?? 0) ?></small></button>
                <?php endforeach; ?>
            </div>
            <div class="tools-right">
                <label class="search">
                    <span class="sr-only">Cari menu atau bahan</span>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" id="menuSearch" placeholder="Cari menu atau bahan" autocomplete="off">
                </label>
                <label class="sort">
                    <span class="sr-only">Urutkan menu</span>
                    <select id="menuSort">
                        <option value="rekomendasi">Rekomendasi</option>
                        <option value="harga-asc">Harga terendah</option>
                        <option value="harga-desc">Harga tertinggi</option>
                        <option value="nama">Nama A-Z</option>
                    </select>
                </label>
            </div>
        </div>
        <p class="sr-only" id="menuCount" role="status" aria-live="polite"></p>

        <div class="reorder" id="reorderBar" hidden>
            <span class="reorder-ico" aria-hidden="true">🔁</span>
            <div class="reorder-txt">
                <strong>Pesan lagi seperti terakhir kali?</strong>
                <small id="reorderList"></small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="reorderBtn">Masukkan ke keranjang</button>
            <button type="button" class="reorder-x" id="reorderX" aria-label="Sembunyikan saran pesan ulang">&times;</button>
        </div>

        <div class="menu-grid" id="menuGrid">
            <?php $n = 0; foreach ($menu as $id => $m):
                $varList   = $m['harga'];
                $first     = true;
                $hargaAwal = (int)reset($varList);
                $foto      = assetPath($m['foto']);
                $teksCari  = mb_strtolower($m['nama'] . ' ' . $m['desc'] . ' ' . implode(' ', $m['bahan']) . ' ' . $m['tag']);
                $habis     = ($m['tersedia'] ?? true) === false;
            ?>
            <article class="menu-card reveal<?= $habis ? ' habis' : '' ?>" style="--d:<?= ($n % 3) * 0.12 ?>s; --accent:<?= e($m['warna']) ?>"
                     data-id="<?= e($id) ?>" data-kat="<?= e($m['kategori']) ?>" data-idx="<?= $n ?>" data-min="<?= min($varList) ?>" data-nama="<?= e($teksCari) ?>">
                <span class="menu-tag"><?= e($m['tag']) ?></span>
                <button type="button" class="fav-btn" aria-pressed="false" aria-label="Tandai <?= e($m['nama']) ?> sebagai favorit">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.3 2.8 4.5 6.4 4.5c2 0 3.7 1.1 4.6 2.7h2c.9-1.6 2.6-2.7 4.6-2.7 3.6 0 5.5 3.8 4 7.3C19.5 16.4 12 21 12 21Z" stroke-width="2" stroke-linejoin="round"/></svg>
                </button>
                <?php if ($habis): ?><span class="habis-badge">Habis</span><?php endif; ?>
                <div class="menu-media">
                    <?php if ($foto): ?>
                    <img class="menu-photo" src="<?= e($foto) ?>" alt="<?= e($m['nama']) ?>" loading="lazy" draggable="false">
                    <?php elseif (is_array($m['svg'])): ?>
                    <?= burgerSvg('m' . $n, $m['svg'] + ['vb' => '40 60 320 520', 'spacing' => 30])['svg'] ?>
                    <?php else: ?>
                    <?= ikonItem($id) ?>
                    <?php endif; ?>
                    <span class="menu-glow"></span>
                </div>
                <div class="menu-body">
                    <h3 class="menu-name"><?= e($m['nama']) ?></h3>
                    <p><?= e($m['desc']) ?></p>
                    <ul class="chips">
                        <?php foreach ($m['bahan'] as $bh): ?>
                        <li><?= e($bh) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="variants" style="--n:<?= count($varList) ?>" role="radiogroup" aria-label="Pilih varian <?= e($m['nama']) ?>">
                        <?php foreach ($varList as $vn => $vh): ?>
                        <button type="button" class="vr<?= $first ? ' on' : '' ?>" role="radio" aria-checked="<?= $first ? 'true' : 'false' ?>" data-v="<?= e((string)$vn) ?>" data-price="<?= $vh ?>">
                            <span><?= e((string)$vn) ?></span><b><?= rupiah($vh) ?></b>
                        </button>
                        <?php $first = false; endforeach; ?>
                    </div>
                    <div class="buy-row">
                        <strong class="price" data-price-out><?= rupiah($hargaAwal) ?></strong>
                        <?php if ($habis): ?>
                        <button type="button" class="btn btn-dark js-add" data-quick="1" disabled>Stok habis</button>
                        <?php else: ?>
                        <button type="button" class="btn btn-dark js-add" data-quick="<?= $m['addon'] ? '0' : '1' ?>"><?= $m['addon'] ? 'Pilih &amp; Tambah' : 'Tambah' ?> <span class="plus" aria-hidden="true">+</span></button>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php $n++; endforeach; ?>
        </div>

        <div class="menu-empty" id="menuEmpty" hidden>
            <strong>Menu tidak ditemukan</strong>
            <p>Coba kata kunci lain, atau tampilkan semua menu.</p>
            <button type="button" class="btn btn-dark btn-sm" id="menuReset">Tampilkan semua menu</button>
        </div>
    </div>
</section>

<!-- ============ TEKS RAKSASA ============ -->
<section class="giant-type" aria-hidden="true">
    <div class="gt-row" data-dir="1"><span>Smash <em>+</em> Sauce <em>+</em> Side <em>+</em> Smash <em>+</em> Sauce <em>+</em> Side <em>+</em> Smash <em>+</em> Sauce <em>+</em> Side <em>+</em></span></div>
    <div class="gt-row outline" data-dir="-1"><span>Free Throw <em>•</em> Layup <em>•</em> Hook Shot <em>•</em> Free Throw <em>•</em> Layup <em>•</em> Hook Shot <em>•</em> Free Throw <em>•</em> Layup <em>•</em> Hook Shot <em>•</em></span></div>
</section>

<!-- ============ CARA PESAN ============ -->
<section class="section" id="cara-pesan">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">Cara Pesan</span>
            <h2>Tiga Langkah, Burger Datang</h2>
            <p>Tanpa daftar akun. Cukup nama dan nomor WhatsApp.</p>
        </div>
        <ol class="steps">
            <li class="step glass reveal">
                <span class="step-n" aria-hidden="true">1</span>
                <h3>Racik pesananmu</h3>
                <p>Pilih menu dan varian, tambahkan extra cheddar atau bacon, lalu masukkan ke keranjang. Pesan lebih dari satu menu sekaligus.</p>
            </li>
            <li class="step glass reveal" style="--d:.12s">
                <span class="step-n" aria-hidden="true">2</span>
                <h3>Checkout</h3>
                <p>Pilih makan di tempat, bawa pulang, atau antar. Ongkir antar <?= rupiah(ONGKIR_FLAT) ?>, gratis untuk belanja mulai <?= rupiah(GRATIS_ONGKIR) ?>.</p>
            </li>
            <li class="step glass reveal" style="--d:.24s">
                <span class="step-n" aria-hidden="true">3</span>
                <h3>Pantau statusnya</h3>
                <p>Setelah dikirim, kamu dapat kode pesanan. Pakai kode itu untuk melihat apakah burgermu sedang dibuat atau sudah siap.</p>
                <button type="button" class="btn btn-outline btn-sm js-open-track">Lacak pesanan</button>
            </li>
        </ol>

        <div class="promos reveal" aria-label="Kode promo">
            <?php foreach ($promoList as $kode => $pr): ?>
            <button type="button" class="promo-card" data-code="<?= e($kode) ?>" aria-label="Salin kode promo <?= e($kode) ?>">
                <span>
                    <span class="promo-code"><?= e($kode) ?></span>
                    <small><?= e($pr['label']) ?>, min. belanja <?= rupiah($pr['min']) ?></small>
                </span>
                <span class="promo-copy">Salin</span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ TENTANG KAMI ============ -->
<section class="section section-alt" id="tentang">
    <div class="container about-grid">
        <div class="about-visual reveal">
            <div class="about-card glass">
                <span class="about-big">2021</span>
                <p>Berdiri dari dapur kecil dengan satu obsesi: burger yang jujur, juicy, dan konsisten.</p>
            </div>
            <div class="about-card glass about-card-2">
                <span class="about-big">0%</span>
                <p>Filler & pengawet pada patty kami.</p>
            </div>
        </div>
        <div class="about-text reveal" style="--d:.1s">
            <span class="eyebrow">Tentang Kami</span>
            <h2>Dibuat Dengan Disiplin Seorang Atlet</h2>
            <p>DOVEN BURGER lahir dari keyakinan bahwa burger hebat butuh latihan: pemilihan daging, takaran, suhu, dan waktu. Setiap detail kami ulang sampai presisi.</p>
            <ul class="check-list">
                <li><b>Daging segar harian</b> dari pemasok sapi lokal terpilih.</li>
                <li><b>Smash technique</b> untuk kerak karamel yang renyah di luar, juicy di dalam.</li>
                <li><b>Roti brioche</b> dipanggang mentega tepat sebelum disajikan.</li>
                <li><b>Saus rumahan</b> diracik sendiri setiap pagi.</li>
            </ul>
            <a href="#menu" class="btn btn-primary">Coba Sekarang <span class="arrow">→</span></a>
        </div>
    </div>
    <div class="container">
        <div class="stats reveal">
            <div class="stat"><strong data-count="50000" data-suffix="+">50.000+</strong><span>Burger sudah disajikan</span></div>
            <div class="stat"><strong data-count="4.9" data-decimals="1">4,9</strong><span>Rating rata-rata pelanggan</span></div>
            <div class="stat"><strong data-count="100" data-suffix="%">100%</strong><span>Daging sapi premium</span></div>
            <div class="stat"><strong data-count="5">5</strong><span>Tahun di dapur</span></div>
        </div>
    </div>
</section>

<!-- ============ TESTIMONI ============ -->
<section class="section" id="testimoni">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">Testimoni</span>
            <h2>Kata Mereka Yang Sudah Mencoba</h2>
            <?php if ($ringkasUlasan['n'] > 0): ?>
            <p class="rating-sum"><span class="stars" aria-hidden="true">★</span> <strong><?= number_format($ringkasUlasan['rata'], 1, ',', '') ?></strong> dari 5 &middot; <?= (int)$ringkasUlasan['n'] ?> ulasan pelanggan</p>
            <?php endif; ?>
        </div>
        <div class="testi-grid">
            <?php foreach ($testimoni as $i => $t): ?>
            <figure class="testi-card glass reveal" style="--d:<?= $i * 0.12 ?>s">
                <?php $r = (int)($t['rating'] ?? 5); ?>
                <div class="stars" role="img" aria-label="Bintang <?= $r ?> dari 5"><?= str_repeat('★', $r) . str_repeat('<span class="off">★</span>', 5 - $r) ?></div>
                <blockquote>“<?= e($t['teks']) ?>”</blockquote>
                <figcaption>
                    <span class="avatar"><?= e($t['inisial']) ?></span>
                    <span><strong><?= e($t['nama']) ?></strong><small><?= e($t['peran']) ?></small></span>
                </figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
        <p class="testi-cta reveal">Sudah pernah memesan? <button type="button" class="link-btn js-open-track">Lacak pesananmu</button> lalu beri ulasan setelah selesai.</p>
    </div>
</section>

<!-- ============ FAQ ============ -->
<section class="section" id="faq">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">FAQ</span>
            <h2>Pertanyaan Yang Sering Muncul</h2>
        </div>
        <div class="faq reveal">
            <details>
                <summary>Berapa lama pesanan diproses?</summary>
                <p>Rata-rata 15 sampai 25 menit setelah pesanan dikonfirmasi, bisa lebih lama saat jam ramai. Pantau lewat menu Lacak Pesanan.</p>
            </details>
            <details>
                <summary>Area antar dan ongkirnya bagaimana?</summary>
                <p>Kami mengantar ke area sekitar outlet di Jakarta Selatan. Ongkir flat <?= rupiah(ONGKIR_FLAT) ?>, gratis untuk belanja mulai <?= rupiah(GRATIS_ONGKIR) ?> atau dengan kode GRATISONGKIR.</p>
            </details>
            <details>
                <summary>Metode pembayaran apa saja yang tersedia?</summary>
                <p>Tunai di tempat, QRIS, dan transfer bank. Untuk QRIS dan transfer, tim kami mengirim detail pembayaran lewat WhatsApp setelah pesanan masuk.</p>
            </details>
            <details>
                <summary>Bisakah saya mengubah atau membatalkan pesanan?</summary>
                <p>Bisa, selama statusnya belum Diproses. Hubungi kami di WhatsApp secepatnya dengan menyebutkan kode pesanan.</p>
            </details>
            <details>
                <summary>Ada alergi atau permintaan khusus?</summary>
                <p>Tulis di catatan item saat memilih menu, atau di catatan pesanan saat checkout. Untuk alergi serius, mohon konfirmasi lewat WhatsApp juga.</p>
            </details>
        </div>
    </div>
</section>

<!-- ============ KONTAK ============ -->
<section class="section section-alt" id="kontak">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">Kontak</span>
            <h2>Mampir atau Hubungi Kami</h2>
            <p>Kami siap melayani setiap hari. Pesan lewat website atau datang langsung ke outlet.</p>
        </div>
        <div class="contact-grid">
            <div class="contact-card glass reveal">
                <span class="c-icon">📍</span>
                <h3>Alamat</h3>
                <p>Jl. Contoh Raya No. 88,<br>Jakarta Selatan</p>
            </div>
            <div class="contact-card glass reveal" style="--d:.1s">
                <span class="c-icon">🕒</span>
                <h3>Jam Buka</h3>
                <p>Setiap hari<br><?= sprintf('%02d.00 - %02d.00', JAM_BUKA, JAM_TUTUP) ?> WIB</p>
                <span class="open-pill <?= tokoBuka() ? 'on' : '' ?>" id="openPill"><?= tokoBuka() ? 'Buka sekarang' : 'Sedang tutup' ?></span>
            </div>
            <div class="contact-card glass reveal" style="--d:.2s">
                <span class="c-icon">💬</span>
                <h3>WhatsApp</h3>
                <p>+62 812-3456-7890<br>hello@dovenburger.id</p>
            </div>
        </div>
    </div>
</section>

</main>

<!-- ============ FOOTER ============ -->
<footer class="footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <a href="#beranda" class="logo">
                <span class="logo-icon">
                    <svg viewBox="0 0 32 32" width="22" height="22" fill="none" aria-hidden="true">
                        <path d="M5 14c0-5.5 4.9-9 11-9s11 3.5 11 9H5Z" fill="#fff"/>
                        <rect x="4" y="16" width="24" height="3" rx="1.5" fill="#fff" opacity=".85"/>
                        <path d="M5 21h22c0 3.3-2.7 6-6 6H11c-3.3 0-6-2.7-6-6Z" fill="#fff"/>
                    </svg>
                </span>
                <span class="logo-text">DOVEN<b>BURGER</b></span>
            </a>
            <p>Premium beef burger dengan resep terbaru 2026. Dibuat segar, disajikan hangat.</p>
            <button type="button" class="btn btn-outline btn-sm install-btn" id="installBtn" hidden>📲 Pasang aplikasi DOVEN</button>
        </div>
        <div class="footer-col">
            <h4>Navigasi</h4>
            <a href="#menu">The Line Up</a>
            <a href="#tentang">Tentang Kami</a>
            <a href="#testimoni">Testimoni</a>
            <a href="#cara-pesan">Cara Pesan</a>
            <a href="#faq">FAQ</a>
            <a href="#kontak">Kontak</a>
        </div>
        <div class="footer-col">
            <h4>Ikuti Kami</h4>
            <div class="socials">
                <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>
                <a href="#" aria-label="TikTok"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.4 2.6 2 4.2 5 4.5"/></svg></a>
                <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8.5c0-.3.2-.5.5-.5Z"/></svg></a>
                <a href="#" aria-label="WhatsApp"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21l1.6-4.6A8.5 8.5 0 1 1 8 19.6L3 21Z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-.8.8c-.9-.4-1.6-1.1-2-2l.8-.8-1-2L9 9.500Z"/></svg></a>
            </div>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>&copy; <?= date('Y') ?> DOVEN BURGER. Seluruh hak cipta dilindungi.</span>
        <span>Dibuat dengan 🔥 di Jakarta</span>
    </div>
</footer>

<!-- ============ MODAL KUSTOMISASI ITEM ============ -->
<div class="modal" id="itemModal" role="dialog" aria-modal="true" aria-labelledby="imTitle" aria-hidden="true">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-dialog glass-strong" id="imDialog">
        <button type="button" class="modal-close" data-close aria-label="Tutup">&times;</button>
        <div class="im-head">
            <span class="im-ico" id="imIco" aria-hidden="true"></span>
            <div>
                <h3 id="imTitle">Menu</h3>
                <p class="modal-sub" id="imDesc"></p>
            </div>
        </div>
        <div class="im-group">
            <h4>Varian</h4>
            <div class="variants" id="imVariants" role="radiogroup" aria-label="Pilih varian"></div>
        </div>
        <div class="im-group" id="imAddonWrap">
            <h4>Tambahan <em>(opsional)</em></h4>
            <div class="addons" id="imAddons"></div>
        </div>
        <div class="im-group">
            <label class="im-label" for="imNote">Catatan untuk dapur <em>(opsional)</em></label>
            <textarea id="imNote" rows="2" maxlength="120" placeholder="Contoh: tanpa bawang, saus dipisah"></textarea>
        </div>
        <div class="im-foot">
            <div class="qty qty-lg" role="group" aria-label="Jumlah">
                <button type="button" id="imMinus" aria-label="Kurangi jumlah">&minus;</button>
                <output id="imQty" aria-live="polite">1</output>
                <button type="button" id="imPlus" aria-label="Tambah jumlah">+</button>
            </div>
            <button type="button" class="btn btn-primary btn-glow" id="imAdd">Tambah <b id="imTotal">Rp 0</b></button>
        </div>
    </div>
</div>

<!-- ============ DRAWER KERANJANG & CHECKOUT ============ -->
<div class="drawer" id="cartDrawer" role="dialog" aria-modal="true" aria-labelledby="cartTitle" aria-hidden="true">
    <div class="drawer-backdrop" data-close></div>
    <aside class="drawer-panel glass-strong">
        <header class="drawer-head">
            <button type="button" class="icon-btn" id="cartBack" aria-label="Kembali ke keranjang" hidden>
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
            </button>
            <h3 id="cartTitle">Keranjang</h3>
            <button type="button" class="icon-btn" data-close aria-label="Tutup keranjang">&times;</button>
        </header>

        <!-- Tampilan 1: isi keranjang -->
        <section class="drawer-view" id="viewCart">
            <div class="drawer-body">
                <ul class="cart-list" id="cartList"></ul>
                <section class="upsell" id="upsell" aria-label="Lengkapi pesananmu" hidden>
                    <h4>Lengkapi pesananmu</h4>
                    <div class="upsell-list" id="upsellList"></div>
                </section>
                <div class="cart-empty" id="cartEmpty" hidden>
                    <span class="ce-ico" aria-hidden="true">🍔</span>
                    <strong>Keranjangmu masih kosong</strong>
                    <p>Pilih burger favoritmu dulu, nanti muncul di sini.</p>
                    <a href="#menu" class="btn btn-primary btn-sm" data-close>Lihat menu</a>
                </div>
            </div>
            <div class="drawer-foot" id="cartFoot">
                <div class="meter">
                    <p id="meterText"></p>
                    <div class="meter-bar" aria-hidden="true"><i id="meterFill"></i></div>
                </div>
                <dl class="sum"><div><dt>Subtotal</dt><dd id="cartSub">Rp 0</dd></div></dl>
                <button type="button" class="btn btn-primary btn-block btn-glow" id="toCheckout">Lanjut ke checkout</button>
            </div>
        </section>

        <!-- Tampilan 2: checkout -->
        <section class="drawer-view" id="viewCheckout" hidden>
            <form id="checkoutForm" novalidate>
                <div class="drawer-body">
                    <p class="notice" id="closedNotice" hidden>Outlet sedang tutup (buka <?= sprintf('%02d.00-%02d.00', JAM_BUKA, JAM_TUTUP) ?> WIB). Pesanan tetap diterima dan diproses saat kami buka. Mau pasti? Pilih &ldquo;Jadwalkan&rdquo; di bawah.</p>
                    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

                    <fieldset class="group">
                        <legend>Tipe pesanan</legend>
                        <div class="seg">
                            <label><input type="radio" name="tipe" value="dine-in"><span><i aria-hidden="true">🍽️</i>Makan di tempat</span></label>
                            <label><input type="radio" name="tipe" value="takeaway" checked><span><i aria-hidden="true">🛍️</i>Bawa pulang</span></label>
                            <label><input type="radio" name="tipe" value="delivery"><span><i aria-hidden="true">🛵</i>Antar</span></label>
                        </div>
                        <small class="err" data-err="tipe"></small>
                    </fieldset>

                    <fieldset class="group">
                        <legend>Waktu <span id="waktuLabel">ambil</span></legend>
                        <div class="seg seg-2">
                            <label><input type="radio" name="waktu" value="asap" checked><span><i aria-hidden="true">⚡</i>Secepatnya</span></label>
                            <label><input type="radio" name="waktu" value="jadwal"><span><i aria-hidden="true">🕒</i>Jadwalkan</span></label>
                        </div>
                        <div class="field jadwal-field" id="jadwalField" hidden>
                            <label for="co_jadwal" class="sr-only">Pilih waktu</label>
                            <select id="co_jadwal" name="jadwal" disabled></select>
                            <small class="jadwal-hint" id="jadwalHint" role="status"></small>
                            <small class="err" data-err="jadwal"></small>
                        </div>
                    </fieldset>

                    <div class="field">
                        <label for="co_nama">Nama lengkap</label>
                        <input type="text" id="co_nama" name="nama_lengkap" placeholder="Contoh: Budi Santoso" autocomplete="name" required>
                        <small class="err" data-err="nama_lengkap"></small>
                    </div>
                    <div class="field">
                        <label for="co_wa">Nomor WhatsApp</label>
                        <input type="tel" id="co_wa" name="nomor_wa" placeholder="Contoh: 081234567890" autocomplete="tel" inputmode="tel" required>
                        <small class="err" data-err="nomor_wa"></small>
                    </div>
                    <div class="field" id="alamatField" hidden>
                        <label for="co_alamat">Alamat antar</label>
                        <textarea id="co_alamat" name="alamat" rows="2" maxlength="300" placeholder="Jalan, nomor rumah, patokan"></textarea>
                        <small class="err" data-err="alamat"></small>
                    </div>

                    <fieldset class="group">
                        <legend>Pembayaran</legend>
                        <div class="seg">
                            <label><input type="radio" name="pembayaran" value="tunai" checked><span><i aria-hidden="true">💵</i>Tunai</span></label>
                            <label><input type="radio" name="pembayaran" value="qris"><span><i aria-hidden="true">📱</i>QRIS</span></label>
                            <label><input type="radio" name="pembayaran" value="transfer"><span><i aria-hidden="true">🏦</i>Transfer</span></label>
                        </div>
                        <small class="err" data-err="pembayaran"></small>
                    </fieldset>

                    <div class="field">
                        <label for="co_catatan">Catatan pesanan <em>(opsional)</em></label>
                        <textarea id="co_catatan" name="catatan" rows="2" maxlength="300" placeholder="Contoh: titip sendok, jangan terlalu pedas"></textarea>
                        <small class="err" data-err="catatan"></small>
                    </div>

                    <div class="field">
                        <label for="promoInput">Kode promo</label>
                        <div class="promo-row">
                            <input type="text" id="promoInput" autocomplete="off" maxlength="30" placeholder="Masukkan kode">
                            <button type="button" class="btn btn-dark btn-sm" id="promoBtn">Pakai</button>
                        </div>
                        <small class="promo-msg" id="promoMsg" role="status"></small>
                    </div>

                    <dl class="sum" id="coSum"></dl>
                </div>
                <div class="drawer-foot">
                    <button type="submit" class="btn btn-primary btn-block btn-glow" id="coSubmit">
                        <span class="btn-label">Kirim pesanan <b id="coTotalBtn">Rp 0</b></span>
                        <span class="spinner" aria-hidden="true"></span>
                    </button>
                    <p class="foot-note">Harga sudah termasuk pajak. Tim kami akan menghubungi lewat WhatsApp.</p>
                </div>
            </form>
        </section>

        <!-- Tampilan 3: sukses -->
        <section class="drawer-view" id="viewSuccess" hidden>
            <div class="drawer-body modal-success">
                <div class="check-anim">
                    <svg viewBox="0 0 52 52" width="72" height="72" aria-hidden="true"><circle class="ck-circle" cx="26" cy="26" r="24" fill="none"/><path class="ck-mark" fill="none" d="M14 27l8 8 16-17"/></svg>
                </div>
                <h3>Pesanan terkirim!</h3>
                <p id="successMsg"></p>
                <div class="order-code">Kode pesanan: <strong id="orderCode">-</strong></div>
                <div class="pay-box" id="payBox" hidden></div>
                <dl class="sum" id="successSum"></dl>
            </div>
            <div class="drawer-foot">
                <a class="btn btn-wa btn-block" id="waBtn" href="#" target="_blank" rel="noopener">Konfirmasi lewat WhatsApp</a>
                <button type="button" class="btn btn-outline btn-block" id="trackNow">Lacak pesanan ini</button>
                <button type="button" class="btn btn-plain btn-block" data-close>Selesai</button>
            </div>
        </section>
    </aside>
</div>

<!-- ============ MODAL LACAK PESANAN ============ -->
<div class="modal" id="trackModal" role="dialog" aria-modal="true" aria-labelledby="trackTitle" aria-hidden="true">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-dialog glass-strong">
        <button type="button" class="modal-close" data-close aria-label="Tutup">&times;</button>
        <span class="eyebrow">Lacak Pesanan</span>
        <h3 id="trackTitle">Di mana pesananku?</h3>
        <p class="modal-sub">Masukkan kode pesanan dan 4 digit terakhir nomor WhatsApp yang kamu pakai.</p>

        <form id="trackForm" novalidate>
            <div class="field">
                <label for="tr_kode">Kode pesanan</label>
                <input type="text" id="tr_kode" placeholder="DVN-A1B2C3" maxlength="10" autocomplete="off" autocapitalize="characters" data-autofocus>
                <small class="err" data-err="kode"></small>
            </div>
            <div class="field">
                <label for="tr_wa">4 digit terakhir WhatsApp</label>
                <input type="text" id="tr_wa" placeholder="Contoh: 7890" maxlength="14" inputmode="numeric" autocomplete="off">
                <small class="err" data-err="wa"></small>
            </div>
            <button type="submit" class="btn btn-primary btn-block" id="trackBtn">
                <span class="btn-label">Cek status</span>
                <span class="spinner" aria-hidden="true"></span>
            </button>
        </form>

        <div class="track-result" id="trackResult" hidden>
            <div class="tr-head">
                <span class="tr-code" id="trCode"></span>
                <span class="chip-status" id="trStatus"></span>
            </div>
            <ol class="timeline" id="timeline" aria-label="Tahapan pesanan"></ol>
            <p class="tr-cancel" id="trCancel" hidden>Pesanan ini dibatalkan. Hubungi kami lewat WhatsApp bila ini keliru.</p>
            <ul class="track-items" id="trItems"></ul>
            <dl class="sum" id="trSum"></dl>
            <p class="tr-meta" id="trMeta"></p>
            <button type="button" class="btn btn-outline btn-sm btn-block" id="printBtn">🧾 Cetak struk</button>

            <form class="review" id="reviewForm" hidden novalidate>
                <h4>Bagaimana pesananmu?</h4>
                <div class="star-input" role="radiogroup" aria-label="Beri bintang">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="rating" id="rt<?= $i ?>" value="<?= $i ?>"><label for="rt<?= $i ?>" title="<?= $i ?> bintang"><span class="sr-only"><?= $i ?> bintang</span>★</label>
                    <?php endfor; ?>
                </div>
                <small class="err" data-err="rating"></small>
                <label class="sr-only" for="rv_text">Komentar</label>
                <textarea id="rv_text" rows="2" maxlength="300" placeholder="Ceritakan pengalamanmu (opsional)"></textarea>
                <button type="submit" class="btn btn-primary btn-block" id="rvBtn">
                    <span class="btn-label">Kirim ulasan</span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            </form>
            <p class="review-done" id="reviewDone" hidden>Terima kasih atas ulasanmu! 🧡</p>
        </div>
    </div>
</div>

<button type="button" class="cart-bar" id="cartBar" hidden>
    <span><b id="cbCount">0</b> item</span>
    <span class="cb-r">Lihat keranjang <strong id="cbTotal">Rp 0</strong></span>
</button>
<button type="button" class="to-top" id="toTop" aria-label="Kembali ke atas">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>

<!-- Toast -->
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
    window.DOVEN = <?= json_encode($cfgJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="script.js?v=<?= filemtime(__DIR__ . '/script.js') ?>"></script>
<script src="shop.js?v=<?= filemtime(__DIR__ . '/shop.js') ?>"></script>
<script src="pwa.js?v=<?= filemtime(__DIR__ . '/pwa.js') ?>"></script>
</body>
</html>
