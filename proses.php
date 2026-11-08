<?php
/**
 * proses.php - Cek promo & simpan pesanan DOVEN BURGER (Prepared Statements)
 *
 * Menerima POST dari fetch() di shop.js dan membalas JSON.
 *   aksi=promo  -> cek kode promo untuk isi keranjang saat ini
 *   aksi=pesan  -> validasi semua data, hitung ulang harga di server, simpan ke database
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonBalas(false, 'Metode permintaan tidak diizinkan.', [], 405);
}

// Honeypot anti-bot: field "website" harus kosong
if (!empty($_POST['website'] ?? '')) {
    jsonBalas(true, 'Pesanan diterima.');
}

$aksi = teks($_POST['aksi'] ?? 'pesan');
$tipe = teks($_POST['tipe'] ?? 'takeaway');

// Daftar waktu untuk pesanan terjadwal (tidak butuh keranjang)
if ($aksi === 'slot') {
    $slot = slotJadwal();
    jsonBalas(true, $slot ? 'OK' : 'Tidak ada waktu tersedia saat ini.', ['slot' => $slot]);
}

// Keranjang dikirim sebagai JSON; semua harga dihitung ulang dari data.php
$hitung = hitungKeranjang(json_decode(teks($_POST['keranjang'] ?? ''), true));
if (isset($hitung['error'])) {
    jsonBalas(false, $hitung['error'], [], 422);
}
if (!isset(TIPE_PESANAN[$tipe])) {
    jsonBalas(false, 'Tipe pesanan tidak valid.', ['errors' => ['tipe' => 'Pilih tipe pesanan.']], 422);
}

/* ---------- Cek promo ---------- */
if ($aksi === 'promo') {
    $promo = hitungPromo(teks($_POST['kode_promo'] ?? ''), $hitung['subtotal']);
    $total = hitungTotal($hitung['subtotal'], $tipe, $promo['ok'] ? $promo : null);
    jsonBalas($promo['ok'], $promo['pesan'], [
        'kode'          => $promo['kode'],
        'diskon'        => $promo['diskon'],
        'gratis_ongkir' => $promo['gratis_ongkir'],
        'ringkasan'     => $total,
    ], $promo['ok'] ? 200 : 422);
}

/* ---------- Validasi data pemesan ---------- */
$namaLengkap = trim(teks($_POST['nama_lengkap'] ?? ''));
$alamat      = trim(teks($_POST['alamat'] ?? ''));
$bayar       = teks($_POST['pembayaran'] ?? '');
$catatan     = trim(teks($_POST['catatan'] ?? ''));
$kodePromo   = strtoupper(trim(teks($_POST['kode_promo'] ?? '')));
$wa          = normalisasiWa(teks($_POST['nomor_wa'] ?? ''));
$jadwalIn    = trim(teks($_POST['jadwal'] ?? ''));

$errors = [];

if (mb_strlen($namaLengkap) < 3 || mb_strlen($namaLengkap) > 100) {
    $errors['nama_lengkap'] = 'Nama lengkap harus 3-100 karakter.';
} elseif (!preg_match('/^[\p{L}\p{M}\s\.\'\-]+$/u', $namaLengkap)) {
    $errors['nama_lengkap'] = 'Nama hanya boleh berisi huruf dan spasi.';
}
if ($wa === '') {
    $errors['nomor_wa'] = 'Nomor WhatsApp tidak valid (contoh: 081234567890).';
}
if ($tipe === 'delivery') {
    if (mb_strlen($alamat) < 10 || mb_strlen($alamat) > 300) {
        $errors['alamat'] = 'Alamat antar minimal 10 karakter (jalan, nomor, patokan).';
    }
} else {
    $alamat = '';
}
if (!isset(METODE_BAYAR[$bayar])) {
    $errors['pembayaran'] = 'Pilih metode pembayaran.';
}
if (mb_strlen($catatan) > 300) {
    $errors['catatan'] = 'Catatan maksimal 300 karakter.';
}

$jadwal = null;
if ($jadwalIn !== '') {
    $jadwal = validasiJadwal($jadwalIn);
    if ($jadwal === null) {
        $errors['jadwal'] = 'Waktu yang dipilih sudah lewat atau tidak tersedia. Pilih waktu lain.';
    }
}

$promo = null;
if ($kodePromo !== '') {
    $cekPromo = hitungPromo($kodePromo, $hitung['subtotal']);
    if ($cekPromo['ok']) {
        $promo = $cekPromo;
    } else {
        $errors['kode_promo'] = $cekPromo['pesan'];
    }
}

if ($errors) {
    jsonBalas(false, 'Mohon periksa kembali data Anda.', ['errors' => $errors], 422);
}

$total = hitungTotal($hitung['subtotal'], $tipe, $promo);

/* ---------- Simpan ke database ---------- */
require_once __DIR__ . '/koneksi.php';

$ip         = substr(teks($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
$alamatDb   = $alamat !== '' ? $alamat : null;
$promoDb    = $promo !== null ? $promo['kode'] : null;
$catatanDb  = $catatan !== '' ? $catatan : null;
$jadwalDb   = $jadwal !== null ? $jadwal . ':00' : null;
$kodePesan  = '';
$pesananId  = 0;

try {
    // Batasi spam: maksimal 5 pesanan per 10 menit untuk satu IP
    $cek = $koneksi->prepare('SELECT COUNT(*) FROM pesanan WHERE ip_address = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)');
    $cek->bind_param('s', $ip);
    $cek->execute();
    $cek->bind_result($jumlahBaru);
    $cek->fetch();
    $cek->close();
    if ($jumlahBaru >= 5) {
        jsonBalas(false, 'Terlalu banyak pesanan dalam waktu singkat. Coba lagi beberapa menit lagi.', [], 429);
    }

    $koneksi->begin_transaction();

    for ($coba = 0; $coba < 3; $coba++) {
        $kodePesan = 'DVN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        try {
            $stmt = $koneksi->prepare(
                'INSERT INTO pesanan (kode_pesanan, nama_lengkap, nomor_wa, tipe, alamat, pembayaran, kode_promo, subtotal, diskon, ongkir, total, catatan, jadwal, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'sssssssiiiisss',
                $kodePesan, $namaLengkap, $wa, $tipe, $alamatDb, $bayar, $promoDb,
                $total['subtotal'], $total['diskon'], $total['ongkir'], $total['total'], $catatanDb, $jadwalDb, $ip
            );
            $stmt->execute();
            $pesananId = (int)$stmt->insert_id;
            $stmt->close();
            break;
        } catch (mysqli_sql_exception $e) {
            // 1062 = kode kembar (sangat jarang): coba kode lain
            if ($e->getCode() !== 1062 || $coba === 2) {
                throw $e;
            }
        }
    }

    $si = $koneksi->prepare(
        'INSERT INTO pesanan_item (pesanan_id, menu_id, nama_menu, varian, qty, harga_satuan, addons, catatan, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($hitung['baris'] as $b) {
        $addonTeks = $b['addons'] ? implode(', ', $b['addons']) : null;
        $catItem   = $b['catatan'] !== '' ? $b['catatan'] : null;
        $si->bind_param('isssiissi', $pesananId, $b['id'], $b['nama'], $b['varian'], $b['qty'], $b['satuan'], $addonTeks, $catItem, $b['subtotal']);
        $si->execute();
    }
    $si->close();

    $koneksi->commit();
    $koneksi->close();
} catch (mysqli_sql_exception $e) {
    try {
        $koneksi->rollback();
    } catch (Throwable $ignore) {
        // koneksi sudah putus; tidak ada yang perlu dibatalkan
    }
    error_log('[DOVEN BURGER] Gagal simpan pesanan: ' . $e->getMessage());
    jsonBalas(false, 'Pesanan gagal disimpan. Silakan coba lagi.', [], 500);
}

/* ---------- Pesan konfirmasi WhatsApp ---------- */
$baris = [];
foreach ($hitung['baris'] as $b) {
    $teksItem = '- ' . $b['qty'] . 'x ' . $b['nama'] . ' (' . $b['varian'] . ')';
    if ($b['addons']) {
        $teksItem .= ' + ' . implode(', ', $b['addons']);
    }
    $teksItem .= ' = ' . rupiah($b['subtotal']);
    if ($b['catatan'] !== '') {
        $teksItem .= "\n  Catatan: " . $b['catatan'];
    }
    $baris[] = $teksItem;
}

$pesanWa = "Halo DOVEN BURGER, saya ingin konfirmasi pesanan.\n\n"
    . 'Kode: ' . $kodePesan . "\n"
    . 'Nama: ' . $namaLengkap . "\n"
    . 'Tipe: ' . TIPE_PESANAN[$tipe] . "\n"
    . ($jadwalDb !== null ? 'Waktu: ' . labelJadwal($jadwalDb) . " WIB\n" : '')
    . ($alamat !== '' ? 'Alamat: ' . $alamat . "\n" : '')
    . "\nPesanan:\n" . implode("\n", $baris) . "\n\n"
    . 'Subtotal: ' . rupiah($total['subtotal']) . "\n"
    . ($total['diskon'] > 0 ? 'Diskon (' . $promoDb . '): -' . rupiah($total['diskon']) . "\n" : '')
    . ($tipe === 'delivery' ? 'Ongkir: ' . ($total['ongkir'] > 0 ? rupiah($total['ongkir']) : 'Gratis') . "\n" : '')
    . 'Total: ' . rupiah($total['total']) . "\n"
    . 'Pembayaran: ' . METODE_BAYAR[$bayar]
    . ($catatan !== '' ? "\nCatatan pesanan: " . $catatan : '');

jsonBalas(true, 'Pesanan berhasil dikirim! Tim DOVEN akan menghubungi Anda via WhatsApp.', [
    'kode_pesanan' => $kodePesan,
    'tipe'         => $tipe,
    'jadwal_label' => $jadwalDb !== null ? labelJadwal($jadwalDb) : '',
    'pembayaran'   => $bayar,
    'info_bayar'   => infoBayar($bayar, $total['total']),
    'ringkasan'    => $total,
    'wa_url'       => 'https://wa.me/' . WA_TOKO . '?text=' . rawurlencode($pesanWa),
]);
