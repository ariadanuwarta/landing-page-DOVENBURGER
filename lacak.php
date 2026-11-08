<?php
/**
 * lacak.php - Status pesanan untuk pelanggan.
 * Menerima POST {kode, wa}. Pelanggan harus tahu kode pesanan DAN nomor WhatsApp-nya
 * (boleh 4 digit terakhir saja), sehingga pesanan orang lain tidak bisa diintip.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonBalas(false, 'Metode permintaan tidak diizinkan.', [], 405);
}

// Batasi percobaan menebak: maksimal 12 permintaan per 5 menit per sesi browser
session_name('doven_lacak');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();
$sekarang = time();
$jendela  = $_SESSION['lacak'] ?? ['mulai' => $sekarang, 'n' => 0];
if ($sekarang - (int)$jendela['mulai'] > 300) {
    $jendela = ['mulai' => $sekarang, 'n' => 0];
}
$jendela['n'] = (int)$jendela['n'] + 1;
$_SESSION['lacak'] = $jendela;
session_write_close();
if ($jendela['n'] > 12) {
    jsonBalas(false, 'Terlalu banyak percobaan. Coba lagi beberapa menit lagi.', [], 429);
}

$kode  = strtoupper(trim(teks($_POST['kode'] ?? '')));
$waIn  = preg_replace('/\D/', '', teks($_POST['wa'] ?? '')) ?? '';

if (!preg_match('/^DVN-[A-Z0-9]{6}$/', $kode)) {
    jsonBalas(false, 'Format kode pesanan tidak valid (contoh: DVN-A1B2C3).', ['errors' => ['kode' => 'Contoh: DVN-A1B2C3']], 422);
}
if (strlen($waIn) < 4) {
    jsonBalas(false, 'Masukkan 4 digit terakhir nomor WhatsApp.', ['errors' => ['wa' => 'Minimal 4 digit terakhir.']], 422);
}

require_once __DIR__ . '/koneksi.php';

$tidakKetemu = 'Pesanan tidak ditemukan. Periksa kode pesanan dan nomor WhatsApp Anda.';

try {
    $stmt = $koneksi->prepare(
        'SELECT id, kode_pesanan, nama_lengkap, nomor_wa, tipe, pembayaran, kode_promo, subtotal, diskon, ongkir, total, jadwal, status, created_at, updated_at
         FROM pesanan WHERE kode_pesanan = ? LIMIT 1'
    );
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$p) {
        jsonBalas(false, $tidakKetemu, [], 404);
    }

    // Cocokkan nomor WA: 4 digit terakhir ATAU nomor lengkap
    $nomor = (string)$p['nomor_wa'];
    $cocok = strlen($waIn) === 4
        ? hash_equals(substr($nomor, -4), $waIn)
        : hash_equals($nomor, normalisasiWa($waIn));
    if (!$cocok) {
        jsonBalas(false, $tidakKetemu, [], 404);
    }

    $items = [];
    $si = $koneksi->prepare('SELECT nama_menu, varian, qty, addons, subtotal FROM pesanan_item WHERE pesanan_id = ? ORDER BY id');
    $pid = (int)$p['id'];
    $si->bind_param('i', $pid);
    $si->execute();
    $res = $si->get_result();
    while ($r = $res->fetch_assoc()) {
        $items[] = [
            'nama'     => $r['nama_menu'],
            'varian'   => $r['varian'],
            'qty'      => (int)$r['qty'],
            'addons'   => $r['addons'] ?? '',
            'subtotal' => (int)$r['subtotal'],
        ];
    }
    $si->close();
    $koneksi->close();
} catch (mysqli_sql_exception $e) {
    error_log('[DOVEN BURGER] Gagal lacak pesanan: ' . $e->getMessage());
    jsonBalas(false, 'Status pesanan belum bisa dimuat. Coba lagi sebentar.', [], 500);
}

$namaDepan = explode(' ', trim((string)$p['nama_lengkap']))[0];

jsonBalas(true, 'OK', [
    'kode'         => $p['kode_pesanan'],
    'nama'         => $namaDepan,
    'status'       => $p['status'],
    'status_label' => STATUS_PESANAN[$p['status']] ?? $p['status'],
    'tipe'         => $p['tipe'],
    'tipe_label'   => TIPE_PESANAN[$p['tipe']] ?? $p['tipe'],
    'pembayaran'   => METODE_BAYAR[$p['pembayaran']] ?? $p['pembayaran'],
    'kode_promo'   => $p['kode_promo'] ?? '',
    'jadwal_label' => $p['jadwal'] !== null ? labelJadwal((string)$p['jadwal']) : '',
    'dibuat'       => tanggalIndo((string)$p['created_at']),
    'diperbarui'   => date('H.i', strtotime((string)$p['updated_at'])) . ' WIB',
    'items'        => $items,
    'ringkasan'    => [
        'subtotal' => (int)$p['subtotal'],
        'diskon'   => (int)$p['diskon'],
        'ongkir'   => (int)$p['ongkir'],
        'total'    => (int)$p['total'],
    ],
]);
