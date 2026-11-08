<?php
/**
 * data.php - Sumber data tunggal DOVEN BURGER.
 *
 * Dipakai oleh index.php (tampilan), proses.php (pesanan), lacak.php dan admin.php.
 * Menu, harga, add-on, dan promo cukup diubah DI SINI. Harga selalu dihitung ulang
 * di server dari data ini, jadi tidak bisa dipalsukan dari browser.
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

/* ---------- Pengaturan toko ---------- */
const WA_TOKO       = '6281234567890'; // format internasional tanpa tanda +
const ONGKIR_FLAT   = 10000;           // biaya antar (delivery)
const GRATIS_ONGKIR = 120000;          // belanja minimal agar ongkir gratis
const JAM_BUKA      = 10;              // jam buka (WIB)
const JAM_TUTUP     = 22;              // jam tutup (WIB)
const MAKS_BARIS    = 15;              // maksimal jenis item per pesanan
const MAKS_QTY      = 20;              // maksimal jumlah per item
const JADWAL_MIN_MENIT = 30;           // pesanan terjadwal minimal sekian menit dari sekarang

// Info pembayaran yang tampil ke pelanggan setelah memesan. GANTI dengan data toko Anda.
const REKENING = ['bank' => 'BCA', 'nomor' => '1234567890', 'nama' => 'DOVEN BURGER'];
// Untuk QRIS: simpan gambar QR toko sebagai assets/qris.png (atau .webp / .jpg). Opsional.

const TIPE_PESANAN   = ['dine-in' => 'Makan di tempat', 'takeaway' => 'Bawa pulang', 'delivery' => 'Antar ke alamat'];
const METODE_BAYAR   = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer bank'];
const STATUS_PESANAN = ['baru' => 'Diterima', 'diproses' => 'Diproses', 'siap' => 'Siap', 'selesai' => 'Selesai', 'batal' => 'Dibatalkan'];

/* ---------- Menu ---------- */
function dataMenu(): array
{
    return [
        'free-throw' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Free Throw Burger', 'kategori' => 'burger', 'ikon' => '🍔', 'tag' => 'Klasik',
            'foto' => 'free-throw', 'warna' => '#fbbf24', 'addon' => true,
            'desc' => 'Single smash patty 100% premium beef, cheddar leleh, selada renyah, saus rahasia DOVEN di atas brioche panggang mentega.',
            'bahan' => ['Beef 90 gr', 'Cheddar', 'Selada', 'Saus DOVEN'],
            'harga' => ['Single' => 38000, 'Double' => 52000, 'Combo' => 68000],
            'svg' => ['patties' => 1, 'bacon' => false],
        ],
        'layup' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Layup Burger', 'kategori' => 'burger', 'ikon' => '🍔', 'tag' => 'Terlaris',
            'foto' => 'layup', 'warna' => '#f97316', 'addon' => true,
            'desc' => 'Double patty juicy, keju cheddar, bacon crispy, tomat segar, dan saus smoky. Gurih, tebal, dan bikin nagih.',
            'bahan' => ['Beef 2x90 gr', 'Bacon', 'Tomat', 'Saus smoky'],
            'harga' => ['Single' => 48000, 'Double' => 64000, 'Combo' => 82000],
            'svg' => ['patties' => 2, 'bacon' => true],
        ],
        'hook-shot' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Hook Shot Burger', 'kategori' => 'burger', 'ikon' => '🍔', 'tag' => 'Signature',
            'foto' => 'hook-shot', 'warna' => '#ef4444', 'addon' => true,
            'desc' => 'Edisi signature dengan triple patty wagyu-blend, triple cheese, bacon, truffle mayo, dan brioche hitam arang.',
            'bahan' => ['Wagyu-blend', 'Triple cheese', 'Truffle mayo', 'Bacon'],
            'harga' => ['Single' => 58000, 'Double' => 76000, 'Combo' => 96000],
            'svg' => ['patties' => 3, 'bacon' => true, 'bunL' => '#6b7280', 'bunD' => '#111827'],
        ],
        'alley-oop' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Alley-Oop Burger', 'kategori' => 'burger', 'ikon' => '🍔', 'tag' => 'Crispy',
            'foto' => 'alley-oop', 'warna' => '#d97706', 'addon' => true,
            'desc' => 'Fillet ayam krispi berbalut tepung renyah, saus creamy gurih, dan selada segar di atas brioche wijen. Renyah di luar, juicy di dalam.',
            'bahan' => ['Ayam crispy', 'Selada', 'Saus creamy', 'Brioche wijen'],
            'harga' => ['Single' => 40000, 'Double' => 56000, 'Combo' => 72000],
            'svg' => ['patties' => 1, 'bacon' => false],
        ],
        'buzzer-beater' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Buzzer Beater Burger', 'kategori' => 'burger', 'ikon' => '🍔', 'tag' => 'Pedas',
            'foto' => 'buzzer-beater', 'warna' => '#16a34a', 'addon' => true,
            'desc' => 'Smash patty beef dengan keju putih leleh, irisan jalapeño, dan saus cabai manis pedas. Pedasnya datang di detik terakhir.',
            'bahan' => ['Beef 90 gr', 'Keju leleh', 'Jalapeño', 'Saus cabai'],
            'harga' => ['Single' => 46000, 'Double' => 62000, 'Combo' => 80000],
            'svg' => ['patties' => 1, 'bacon' => false],
        ],
        'slam-dunk' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Slam Dunk Burger', 'kategori' => 'burger', 'ikon' => '🍔', 'tag' => 'Jumbo',
            'foto' => 'slam-dunk', 'warna' => '#9a3412', 'addon' => true,
            'desc' => 'Patty beef berlapis cheddar leleh, bacon crispy, dan onion ring renyah dengan saus BBQ. Tinggi, tebal, untuk yang lapar berat.',
            'bahan' => ['Beef 2x90 gr', 'Cheddar', 'Bacon', 'Onion ring', 'Saus BBQ'],
            'harga' => ['Single' => 54000, 'Double' => 72000, 'Combo' => 90000],
            'svg' => ['patties' => 2, 'bacon' => true],
        ],
        'fries' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Crispy Fries', 'kategori' => 'assist', 'ikon' => '🍟', 'tag' => 'Favorit',
            'foto' => 'fries', 'warna' => '#f59e0b', 'addon' => false,
            'desc' => 'Kentang goreng renyah berbumbu garam laut, disajikan panas. Teman paling setia untuk burger apa pun.',
            'bahan' => ['Kentang premium', 'Garam laut', 'Goreng sesaat'],
            'harga' => ['Reguler' => 22000, 'Large' => 30000],
            'svg' => null,
        ],
        'shake' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Choco Milkshake', 'kategori' => 'assist', 'ikon' => '🥤', 'tag' => 'Dingin',
            'foto' => 'milkshake', 'warna' => '#b45309', 'addon' => false,
            'desc' => 'Milkshake cokelat kental dengan whipped cream dan taburan cokelat. Dingin, manis, pas untuk menyeimbangkan gurihnya burger.',
            'bahan' => ['Cokelat', 'Susu segar', 'Whipped cream'],
            'harga' => ['Reguler' => 28000, 'Large' => 36000],
            'svg' => null,
        ],
        'sauce' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Saus Rahasia DOVEN', 'kategori' => 'assist', 'ikon' => '🥫', 'tag' => 'Rahasia',
            'foto' => 'sauce', 'warna' => '#ef4444', 'addon' => false,
            'desc' => 'Saus rumahan yang diracik setiap pagi: creamy, smoky, dan sedikit pedas. Cocok untuk cocolan kentang.',
            'bahan' => ['Creamy', 'Smoky', 'Sedikit pedas'],
            'harga' => ['Reguler' => 5000, 'Large' => 9000],
            'svg' => null,
        ],
        'onion-rings' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Onion Rings', 'kategori' => 'assist', 'ikon' => '🧅', 'tag' => 'Renyah',
            'foto' => 'onion-rings', 'warna' => '#d97706', 'addon' => false,
            'desc' => 'Irisan bawang bombay berbalut tepung emas yang digoreng renyah. Gurih manis, enak dicocol saus DOVEN.',
            'bahan' => ['Bawang bombay', 'Tepung renyah', 'Goreng sesaat'],
            'harga' => ['Reguler' => 24000, 'Large' => 32000],
            'svg' => null,
        ],
        'nuggets' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Chicken Nuggets', 'kategori' => 'assist', 'ikon' => '🍗', 'tag' => 'Gurih',
            'foto' => 'nuggets', 'warna' => '#eab308', 'addon' => false,
            'desc' => 'Nugget ayam berbalut tepung krispi, juicy di dalam, disajikan dengan dua saus cocolan. Cocok untuk berbagi.',
            'bahan' => ['Ayam pilihan', 'Tepung krispi', '2 saus cocolan'],
            'harga' => ['Reguler' => 26000, 'Large' => 38000],
            'svg' => null,
        ],
        'shake-vanila' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Vanilla Milkshake', 'kategori' => 'assist', 'ikon' => '🥤', 'tag' => 'Manis',
            'foto' => 'shake-vanila', 'warna' => '#e11d48', 'addon' => false,
            'desc' => 'Milkshake vanila lembut dengan whipped cream dan ceri di atasnya. Manis, dingin, dan creamy.',
            'bahan' => ['Vanila', 'Susu segar', 'Whipped cream', 'Ceri'],
            'harga' => ['Reguler' => 28000, 'Large' => 36000],
            'svg' => null,
        ],
        'lemonade' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Fresh Lemonade', 'kategori' => 'assist', 'ikon' => '🍋', 'tag' => 'Segar',
            'foto' => 'lemonade', 'warna' => '#facc15', 'addon' => false,
            'desc' => 'Lemonade dingin dengan irisan lemon segar dan daun mint. Asam manis yang menyegarkan, pas untuk mengimbangi burger.',
            'bahan' => ['Lemon segar', 'Mint', 'Es batu'],
            'harga' => ['Reguler' => 20000, 'Large' => 26000],
            'svg' => null,
        ],
        'lemon-tea' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Iced Lemon Tea', 'kategori' => 'assist', 'ikon' => '🥤', 'tag' => 'Dingin',
            'foto' => 'lemon-tea', 'warna' => '#ea580c', 'addon' => false,
            'desc' => 'Teh seduh dingin dengan irisan lemon segar. Ringan, wangi, dan menyegarkan.',
            'bahan' => ['Teh seduh', 'Lemon', 'Es batu'],
            'harga' => ['Reguler' => 18000, 'Large' => 24000],
            'svg' => null,
        ],
        'cookies-icecream' => [
            'tersedia' => true, // ubah ke false bila stok habis; menu tampil Habis dan tidak bisa dipesan
            'nama' => 'Cookies & Ice Cream', 'kategori' => 'assist', 'ikon' => '🍪', 'tag' => 'Dessert',
            'foto' => 'cookies-icecream', 'warna' => '#a16207', 'addon' => false,
            'desc' => 'Cookies cokelat chip dengan es krim vanila di atasnya. Manis, dingin, dan pas sebagai penutup.',
            'bahan' => ['Choco chip cookies', 'Es krim vanila', 'Disajikan di cup'],
            'harga' => ['Reguler' => 30000, 'Large' => 42000],
            'svg' => null,
        ],
    ];
}

/* ---------- Tambahan (khusus menu dengan 'addon' => true) ---------- */
function dataAddon(): array
{
    return [
        'extra-cheese' => ['nama' => 'Extra Cheddar',     'harga' => 6000],
        'extra-bacon'  => ['nama' => 'Extra Bacon',       'harga' => 9000],
        'extra-patty'  => ['nama' => 'Extra Patty',       'harga' => 18000],
        'jalapeno'     => ['nama' => 'Jalapeño',          'harga' => 4000],
        'extra-saus'   => ['nama' => 'Saus DOVEN Extra',  'harga' => 3000],
    ];
}

/* ---------- Kode promo ---------- */
function dataPromo(): array
{
    return [
        'DOVEN10'      => ['label' => 'Diskon 10% (maks. Rp 20.000)', 'jenis' => 'persen',  'nilai' => 10,    'maks' => 20000, 'min' => 50000],
        'GAMEDAY'      => ['label' => 'Potongan Rp 15.000',           'jenis' => 'nominal', 'nilai' => 15000, 'maks' => 15000, 'min' => 90000],
        'GRATISONGKIR' => ['label' => 'Gratis ongkir (delivery)',     'jenis' => 'ongkir',  'nilai' => 0,     'maks' => 0,     'min' => 60000],
    ];
}

/* ---------- Helper umum ---------- */
function rupiah(int $angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function e(string $teks): string
{
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

/** Ambil nilai skalar sebagai string; selain itu jadi string kosong (cegah "Array to string"). */
function teks(mixed $nilai): string
{
    return is_scalar($nilai) ? (string)$nilai : '';
}

function jsonBalas(bool $sukses, string $pesan, array $tambahan = [], int $kode = 200)
{
    http_response_code($kode);
    echo json_encode(array_merge(['sukses' => $sukses, 'pesan' => $pesan], $tambahan), JSON_UNESCAPED_UNICODE);
    exit;
}

/** Ubah +62 / 62 / 08 menjadi 08xxxxxxxxxx. Kembalikan '' jika tidak valid. */
function normalisasiWa(string $nomor): string
{
    $wa = preg_replace('/[\s\-\(\)\.]/', '', $nomor) ?? '';
    $wa = preg_replace('/^\+?62/', '0', $wa) ?? '';
    return preg_match('/^08[0-9]{8,12}$/', $wa) === 1 ? $wa : '';
}

/** "20 Okt 2026, 14.05 WIB" dari timestamp MySQL. */
function tanggalIndo(string $timestamp): string
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($timestamp) ?: time();
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y, H.i', $t) . ' WIB';
}

function tokoBuka(): bool
{
    $jam = (int)date('G');
    return $jam >= JAM_BUKA && $jam < JAM_TUTUP;
}

/* ---------- Pesanan terjadwal ---------- */

/**
 * Daftar waktu yang boleh dipilih (hari ini & besok, kelipatan 30 menit dalam jam buka,
 * paling cepat JADWAL_MIN_MENIT dari sekarang). Server adalah sumber kebenaran.
 * @return array<int,array{nilai:string,label:string}>
 */
function slotJadwal(?DateTimeImmutable $sekarang = null): array
{
    $sekarang ??= new DateTimeImmutable('now');
    $batas = $sekarang->modify('+' . JADWAL_MIN_MENIT . ' minutes');
    $namaHari = ['Hari ini', 'Besok'];
    $slot = [];
    for ($d = 0; $d < 2; $d++) {
        $tgl = $sekarang->setTime(0, 0)->modify('+' . $d . ' day');
        for ($m = JAM_BUKA * 60; $m <= JAM_TUTUP * 60 - 30; $m += 30) {
            $t = $tgl->setTime(intdiv($m, 60), $m % 60);
            if ($t < $batas) {
                continue;
            }
            $slot[] = ['nilai' => $t->format('Y-m-d H:i'), 'label' => $namaHari[$d] . ', ' . $t->format('H.i')];
        }
    }
    return $slot;
}

/** Kembalikan waktu bila termasuk slot yang sah; selain itu null. */
function validasiJadwal(string $nilai): ?string
{
    foreach (slotJadwal() as $s) {
        if ($s['nilai'] === $nilai) {
            return $nilai;
        }
    }
    return null;
}

/** "Hari ini, 14.30" / "Besok, 10.00" / "22 Okt, 10.00" dari timestamp MySQL. */
function labelJadwal(string $timestamp): string
{
    $t = strtotime($timestamp);
    if ($t === false) {
        return '';
    }
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $hari = date('Y-m-d', $t);
    if ($hari === date('Y-m-d')) {
        $awal = 'Hari ini';
    } elseif ($hari === date('Y-m-d', strtotime('+1 day'))) {
        $awal = 'Besok';
    } else {
        $awal = date('j', $t) . ' ' . $bulan[(int)date('n', $t)];
    }
    return $awal . ', ' . date('H.i', $t);
}

/* ---------- Pembayaran ---------- */

/** Gambar QRIS toko bila ada di assets/. */
function pathQris(): ?string
{
    foreach (['png', 'webp', 'jpg', 'jpeg'] as $ext) {
        $rel = 'assets/qris.' . $ext;
        if (is_file(__DIR__ . '/' . $rel)) {
            return $rel . '?v=' . filemtime(__DIR__ . '/' . $rel);
        }
    }
    return null;
}

/** Petunjuk pembayaran untuk layar sukses (null untuk tunai). */
function infoBayar(string $metode, int $total): ?array
{
    if ($metode === 'transfer') {
        return ['jenis' => 'transfer', 'bank' => REKENING['bank'], 'nomor' => REKENING['nomor'], 'nama' => REKENING['nama'], 'total' => $total];
    }
    if ($metode === 'qris') {
        return ['jenis' => 'qris', 'gambar' => pathQris(), 'total' => $total];
    }
    return null;
}

/* ---------- Perhitungan harga (SELALU dijalankan di server) ---------- */

/**
 * Validasi keranjang hasil json_decode lalu hitung harga tiap baris.
 * @return array{error:string}|array{baris:array<int,array<string,mixed>>,subtotal:int}
 */
function hitungKeranjang(mixed $mentah): array
{
    $menu  = dataMenu();
    $addon = dataAddon();

    if (!is_array($mentah) || $mentah === [] || array_keys($mentah) !== range(0, count($mentah) - 1)) {
        return ['error' => 'Keranjang masih kosong.'];
    }
    if (count($mentah) > MAKS_BARIS) {
        return ['error' => 'Maksimal ' . MAKS_BARIS . ' jenis item per pesanan.'];
    }

    $baris = [];
    $subtotal = 0;
    foreach ($mentah as $r) {
        if (!is_array($r)) {
            return ['error' => 'Data keranjang tidak valid.'];
        }
        $id     = teks($r['id'] ?? '');
        $varian = teks($r['varian'] ?? '');
        $qty    = (int)teks($r['qty'] ?? 0);

        if (!isset($menu[$id]) || !isset($menu[$id]['harga'][$varian])) {
            return ['error' => 'Ada menu yang tidak valid. Muat ulang halaman lalu coba lagi.'];
        }
        if (($menu[$id]['tersedia'] ?? true) === false) {
            return ['error' => $menu[$id]['nama'] . ' sedang habis. Hapus dari keranjang lalu coba lagi.'];
        }
        if ($qty < 1 || $qty > MAKS_QTY) {
            return ['error' => 'Jumlah per item harus antara 1 dan ' . MAKS_QTY . '.'];
        }

        $dipilih = is_array($r['addons'] ?? null) ? array_values(array_unique(array_filter($r['addons'], 'is_string'))) : [];
        $namaAddon = [];
        $hargaAddon = 0;
        foreach ($dipilih as $a) {
            if (!$menu[$id]['addon'] || !isset($addon[$a])) {
                return ['error' => 'Tambahan yang dipilih tidak valid.'];
            }
            $namaAddon[] = $addon[$a]['nama'];
            $hargaAddon += $addon[$a]['harga'];
        }

        $catatan = mb_substr(trim(teks($r['catatan'] ?? '')), 0, 120);
        $satuan  = (int)$menu[$id]['harga'][$varian] + $hargaAddon;
        $sub     = $satuan * $qty;
        $subtotal += $sub;

        $baris[] = [
            'id' => $id, 'nama' => $menu[$id]['nama'], 'varian' => $varian, 'qty' => $qty,
            'addons' => $namaAddon, 'catatan' => $catatan, 'satuan' => $satuan, 'subtotal' => $sub,
        ];
    }

    return ['baris' => $baris, 'subtotal' => $subtotal];
}

/** @return array{ok:bool,pesan:string,kode:string,diskon:int,gratis_ongkir:bool} */
function hitungPromo(string $kode, int $subtotal): array
{
    $kode = strtoupper(trim($kode));
    $gagal = static fn(string $pesan): array => ['ok' => false, 'pesan' => $pesan, 'kode' => '', 'diskon' => 0, 'gratis_ongkir' => false];

    if ($kode === '') {
        return $gagal('Masukkan kode promo terlebih dahulu.');
    }
    $daftar = dataPromo();
    if (!isset($daftar[$kode])) {
        return $gagal('Kode promo tidak ditemukan.');
    }
    $p = $daftar[$kode];
    if ($subtotal < $p['min']) {
        return $gagal('Kode ' . $kode . ' berlaku untuk belanja minimal ' . rupiah($p['min']) . '.');
    }

    $diskon = 0;
    if ($p['jenis'] === 'persen') {
        $diskon = min((int)floor($subtotal * $p['nilai'] / 100), $p['maks']);
    } elseif ($p['jenis'] === 'nominal') {
        $diskon = min($p['nilai'], $subtotal);
    }

    return ['ok' => true, 'pesan' => 'Kode ' . $kode . ' dipakai: ' . $p['label'] . '.', 'kode' => $kode, 'diskon' => $diskon, 'gratis_ongkir' => $p['jenis'] === 'ongkir'];
}

/** @return array{subtotal:int,diskon:int,ongkir:int,total:int} */
function hitungTotal(int $subtotal, string $tipe, ?array $promo): array
{
    $diskon = (int)($promo['diskon'] ?? 0);
    $ongkir = 0;
    if ($tipe === 'delivery') {
        $gratis = $subtotal >= GRATIS_ONGKIR || !empty($promo['gratis_ongkir']);
        $ongkir = $gratis ? 0 : ONGKIR_FLAT;
    }
    return ['subtotal' => $subtotal, 'diskon' => $diskon, 'ongkir' => $ongkir, 'total' => max(0, $subtotal - $diskon + $ongkir)];
}
