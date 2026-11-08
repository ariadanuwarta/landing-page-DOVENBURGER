-- DOVEN BURGER - migrasi dari v2 ke v3
-- KHUSUS bila database v2 Anda sudah berisi data. Instalasi baru cukup import database.sql
-- (atau buka install.php). Skrip ini AMAN dijalankan berulang kali: kolom/tabel yang sudah
-- ada dilewati, tidak muncul galat "Duplicate column name".
-- Jalankan di phpMyAdmin: pilih database db_doven, tab SQL, tempel, lalu Kirim.

-- 1) Tambah kolom "jadwal" hanya bila belum ada
SET @ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pesanan' AND COLUMN_NAME = 'jadwal');
SET @perintah := IF(@ada = 0,
    'ALTER TABLE `pesanan` ADD COLUMN `jadwal` DATETIME NULL AFTER `catatan`',
    'SELECT ''Kolom jadwal sudah ada, dilewati'' AS info');
PREPARE langkah FROM @perintah;
EXECUTE langkah;
DEALLOCATE PREPARE langkah;

-- 2) Buat tabel ulasan bila belum ada
CREATE TABLE IF NOT EXISTS `ulasan` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pesanan_id`  INT UNSIGNED NOT NULL,
  `nama`        VARCHAR(60)  NOT NULL,
  `rating`      TINYINT UNSIGNED NOT NULL,
  `komentar`    VARCHAR(300) NOT NULL DEFAULT '',
  `tampil`      TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ulasan_pesanan` (`pesanan_id`),
  KEY `idx_tampil` (`tampil`, `id`),
  CONSTRAINT `fk_ulasan_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
