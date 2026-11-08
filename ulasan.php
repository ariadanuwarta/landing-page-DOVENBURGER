<?php
/**
 * ulasan.php - Ulasan pelanggan DOVEN BURGER.
 *
 * Menerima POST {kode, wa, rating, komentar}. Pelanggan harus tahu kode pesanan DAN
 * nomor WhatsApp-nya (4 digit terakhir cukup), pesanan harus berstatus "selesai", dan
 * satu pesanan hanya bisa diulas sekali. Ulasan baru TIDAK langsung tampil: pengelola
 * menyetujuinya lebih dulu di admin.php (tab Ulasan).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonBalas(false, 'Metode permintaan tidak diizinkan.', [], 405);
}

// Batasi percobaan: maksimal 10 permintaan per 5 menit per sesi browser
session_name('doven_ulasan');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();
$sekarang = time();
$jendela  = $_SESSION['ulasan'] ?? ['mulai' => $sekarang, 'n' => 0];
if ($sekarang - (int)$jendela['mulai'] > 300) {
    $jendela = ['mulai' => $sekarang, 'n' => 0];
}
$jendela['n'] = (int)$jendela['n'] + 1;
$_SESSION['ulasan'] = $jendela;
session_write_close();
if ($jendela['n'] > 10) {
    jsonBalas(false, 'Terlalu banyak percobaan. Coba lagi beberapa menit lagi.', [], 429);
}

$kode     = strtoupper(trim(teks($_POST['kode'] ?? '')));
$waIn     = preg_replace('/\D/', '', teks($_POST['wa'] ?? '')) ?? '';
$rating   = (int)teks($_POST['rating'] ?? 0);
$komentar = trim(teks($_POST['komentar'] ?? ''));

$errors = [];
if (!preg_match('/^DVN-[A-Z0-9]{6}$/', $kode) || strlen($waIn) < 4) {
    jsonBalas(false, 'Data pesanan tidak valid. Lacak pesananmu lagi lalu coba kirim ulasan.', [], 422);
}
if ($rating < 1 || $rating > 5) {
    $errors['rating'] = 'Pilih jumlah bintang 1 sampai 5.';
}
if (mb_strlen($komentar) > 300) {
    $errors['komentar'] = 'Komentar maksimal 300 karakter.';
}
if ($errors) {
    jsonBalas(false, 'Mohon periksa kembali ulasanmu.', ['errors' => $errors], 422);
}

require_once __DIR__ . '/koneksi.php';

$tidakKetemu = 'Pesanan tidak ditemukan. Periksa kode pesanan dan nomor WhatsApp Anda.';

try {
    $stmt = $koneksi->prepare('SELECT id, nama_lengkap, nomor_wa, status FROM pesanan WHERE kode_pesanan = ? LIMIT 1');
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$p) {
        jsonBalas(false, $tidakKetemu, [], 404);
    }

    $nomor = (string)$p['nomor_wa'];
    $cocok = strlen($waIn) === 4
        ? hash_equals(substr($nomor, -4), $waIn)
        : hash_equals($nomor, normalisasiWa($waIn));
    if (!$cocok) {
        jsonBalas(false, $tidakKetemu, [], 404);
    }
    if ($p['status'] !== 'selesai') {
        jsonBalas(false, 'Ulasan bisa dikirim setelah pesanan selesai.', [], 409);
    }

    // Tampilkan nama depan + inisial belakang, mis. "Budi S."
    $kata = preg_split('/\s+/u', trim((string)$p['nama_lengkap'])) ?: [];
    $nama = (string)($kata[0] ?? 'Pelanggan');
    if (count($kata) > 1) {
        $nama .= ' ' . mb_strtoupper(mb_substr((string)end($kata), 0, 1)) . '.';
    }
    $nama = mb_substr($nama, 0, 60);

    $pid = (int)$p['id'];
    try {
        $ins = $koneksi->prepare('INSERT INTO ulasan (pesanan_id, nama, rating, komentar) VALUES (?, ?, ?, ?)');
        $ins->bind_param('isis', $pid, $nama, $rating, $komentar);
        $ins->execute();
        $ins->close();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            jsonBalas(false, 'Pesanan ini sudah pernah diulas. Terima kasih!', ['sudah' => true], 409);
        }
        throw $e;
    }
    $koneksi->close();
} catch (mysqli_sql_exception $e) {
    error_log('[DOVEN BURGER] Gagal simpan ulasan: ' . $e->getMessage());
    jsonBalas(false, 'Ulasan belum bisa dikirim. Coba lagi sebentar.', [], 500);
}

jsonBalas(true, 'Terima kasih! Ulasanmu akan tampil di website setelah ditinjau tim kami.');
