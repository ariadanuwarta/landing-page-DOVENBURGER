<?php
/**
 * koneksi.php - Koneksi MySQLi untuk DOVEN BURGER
 * Sesuaikan kredensial dengan server Anda.
 */

declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'db_doven';

/**
 * Hash password dashboard admin (admin.php).
 * Dibiarkan kosong = dashboard nonaktif. Buka admin.php sekali, buat password,
 * lalu salin hash yang ditampilkan ke sini.
 */
const ADMIN_PASSWORD_HASH = '$2y$10$NqD7zBKEpmfGk7GcAC68nOBQiXoy1wZ/e/u.AmSpxv56/ZNzF33ci';

// Laporkan error sebagai exception agar bisa ditangani dengan rapi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $koneksi->set_charset('utf8mb4');
    $koneksi->query("SET time_zone = '+07:00'"); // WIB, sama dengan zona waktu PHP
} catch (mysqli_sql_exception $e) {
    // Jangan bocorkan detail error ke pengguna; simpan ke log server
    error_log('[DOVEN BURGER] Koneksi gagal: ' . $e->getMessage());
    // Mode lunak (index.php): halaman tetap tampil tanpa data database, tidak menghentikan skrip
    if (defined('KONEKSI_LUNAK')) {
        $koneksi = null;
        return;
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sukses' => false,
        'pesan'  => 'Server sedang bermasalah. Silakan coba beberapa saat lagi.',
    ]);
    exit;
}
