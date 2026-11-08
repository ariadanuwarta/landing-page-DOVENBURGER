# DOVEN BURGER v3

## Pasang
1. Instalasi baru: import `database.sql` lewat phpMyAdmin (membuat tabel `pesanan`, `pesanan_item`, `ulasan`).
   Upgrade dari v2 (data sudah ada): jalankan `migrasi_v3.sql` **sekali**.
2. Sesuaikan kredensial MySQL di `koneksi.php`.
3. Buka `admin.php` sekali, buat password (min. 10 karakter), salin hash yang tampil ke
   `ADMIN_PASSWORD_HASH` di `koneksi.php`, lalu muat ulang dan masuk.
4. Di `data.php` ubah `WA_TOKO` (format 62812..., tanpa +) dan `REKENING` (info transfer).
5. Opsional: simpan gambar QRIS toko sebagai `assets/qris.png`.

Butuh PHP 8.1+ dengan ekstensi mysqli + mysqlnd (standar di XAMPP/Laragon/hosting umum).

## Fitur baru di v3
- **Favorit**: ikon hati di tiap menu + tab "Favorit" (tersimpan di browser pelanggan).
- **Pesan lagi**: banner di atas menu untuk mengulang pesanan terakhir dengan satu klik.
- **Saran tambahan (upsell)** di keranjang: menawarkan kentang/minuman bila baru ada burger.
- **Pesanan terjadwal**: pelanggan memilih "Secepatnya" atau jam tertentu (hari ini/besok,
  kelipatan 30 menit, dalam jam buka). Divalidasi di server, tampil di WhatsApp, lacak, dan admin.
- **Info pembayaran** setelah memesan: nomor rekening + tombol salin, atau gambar QRIS.
- **Ulasan pelanggan**: muncul di hasil lacak saat pesanan "Selesai" (1 ulasan per pesanan).
  Ulasan baru **menunggu persetujuan** di admin (tab Ulasan) sebelum tampil di website,
  lengkap dengan rata-rata bintang dan data terstruktur untuk Google.
- **Cetak struk** dari hasil lacak (format 80 mm).
- **Notifikasi perubahan status** saat pelanggan memantau pesanan.
- **Stok habis**: set `'tersedia' => false` pada menu di `data.php`; kartu menampilkan "Habis"
  dan server menolak pesanan menu tersebut.
- **PWA**: bisa dipasang di layar utama (tombol di footer), halaman offline, cache aset.
  Service worker hanya aktif lewat HTTPS (atau localhost).
- **Admin**: grafik omzet 7 hari, menu terlaris 30 hari, ekspor CSV (mengikuti filter),
  moderasi ulasan, dan penanda jadwal di kartu pesanan.

## Mengubah isi toko
Menu, harga, add-on, kode promo, ongkir, jam buka, stok, dan rekening ada di `data.php`.
Harga dihitung ulang di server, jadi tampilan dan database selalu konsisten.
Testimoni bawaan di `index.php` (`$testiStatis`) hanya mengisi slot yang belum terisi ulasan asli.

## Berkas
- `index.php`, `style.css`, `script.js` : halaman, gaya, interaksi umum
- `shop.js`    : keranjang, checkout, jadwal, favorit, pesan ulang, lacak, ulasan, struk
- `pwa.js`, `sw.js`, `manifest.webmanifest`, `offline.html`, `assets/icons/` : PWA
- `proses.php` : cek promo, daftar jadwal, simpan pesanan (transaksi database)
- `lacak.php`  : status pesanan untuk pelanggan
- `ulasan.php` : kirim ulasan (diverifikasi kode pesanan + nomor WA)
- `admin.php`  : dashboard pengelola
- `data.php`   : sumber data tunggal
- `.htaccess`  : tipe manifest + blokir akses ke .sql/.md (Apache)

## Pembaruan menu (The Line Up)
- Menu baru: **Alley-Oop**, **Buzzer Beater**, **Slam Dunk** (burger) serta **Onion Rings**, **Chicken Nuggets**,
  **Vanilla Milkshake**, **Fresh Lemonade**, **Iced Lemon Tea**, **Cookies & Ice Cream** (Assist).
  Foto ada di `assets/` (format WebP transparan, sudah dioptimalkan).
- Kategori "Pendamping" diganti **Assist** (kunci kategori di `data.php` kini `'assist'`).
- Navigasi "Menu Utama" dan judul section menu kini bernama **The Line Up**.
- Harga, deskripsi, dan bahan menu baru adalah isian awal: sesuaikan di `data.php`.
- `sw.js` dinaikkan ke `doven-v5` agar cache lama pengunjung dibersihkan.
