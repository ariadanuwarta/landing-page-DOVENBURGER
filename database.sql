-- DOVEN BURGER - skema database (v3: keranjang multi-item, pesanan terjadwal, ulasan)
-- Jalankan di phpMyAdmin (tab SQL) atau: mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS `db_doven`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_doven`;

-- Satu baris = satu transaksi (bisa berisi banyak item)
CREATE TABLE IF NOT EXISTS `pesanan` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_pesanan`  VARCHAR(20)  NOT NULL,
  `nama_lengkap`  VARCHAR(100) NOT NULL,
  `nomor_wa`      VARCHAR(20)  NOT NULL,
  `tipe`          ENUM('dine-in','takeaway','delivery') NOT NULL DEFAULT 'takeaway',
  `alamat`        TEXT         NULL,
  `pembayaran`    ENUM('tunai','qris','transfer') NOT NULL DEFAULT 'tunai',
  `kode_promo`    VARCHAR(30)  NULL,
  `subtotal`      INT UNSIGNED NOT NULL DEFAULT 0,
  `diskon`        INT UNSIGNED NOT NULL DEFAULT 0,
  `ongkir`        INT UNSIGNED NOT NULL DEFAULT 0,
  `total`         INT UNSIGNED NOT NULL DEFAULT 0,
  `catatan`       TEXT         NULL,
  `jadwal`        DATETIME     NULL COMMENT 'Waktu ambil/antar bila dijadwalkan; NULL = secepatnya',
  `status`        ENUM('baru','diproses','siap','selesai','batal') NOT NULL DEFAULT 'baru',
  `ip_address`    VARCHAR(45)  NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kode_pesanan` (`kode_pesanan`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip_created` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rincian item per pesanan
CREATE TABLE IF NOT EXISTS `pesanan_item` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pesanan_id`    INT UNSIGNED NOT NULL,
  `menu_id`       VARCHAR(40)  NOT NULL,
  `nama_menu`     VARCHAR(100) NOT NULL,
  `varian`        VARCHAR(30)  NOT NULL,
  `qty`           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `harga_satuan`  INT UNSIGNED NOT NULL DEFAULT 0,
  `addons`        VARCHAR(255) NULL,
  `catatan`       VARCHAR(200) NULL,
  `subtotal`      INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pesanan` (`pesanan_id`),
  CONSTRAINT `fk_item_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ulasan pelanggan (satu ulasan per pesanan; tampil di website setelah disetujui di admin.php)
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

-- TABEL LAMA (versi satu-menu-per-pesanan). Tidak dipakai lagi oleh website,
-- dibiarkan agar data pesanan lama Anda tidak hilang.
CREATE TABLE IF NOT EXISTS `pesanan_burger` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_pesanan`  VARCHAR(20)  NOT NULL,
  `nama_menu`     VARCHAR(100) NOT NULL,
  `varian`        ENUM('Single','Double','Combo') NOT NULL DEFAULT 'Single',
  `harga`         INT UNSIGNED NOT NULL DEFAULT 0,
  `nama_lengkap`  VARCHAR(100) NOT NULL,
  `nomor_wa`      VARCHAR(20)  NOT NULL,
  `catatan`       TEXT         NULL,
  `status`        ENUM('baru','diproses','selesai','batal') NOT NULL DEFAULT 'baru',
  `ip_address`    VARCHAR(45)  NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kode_pesanan` (`kode_pesanan`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
