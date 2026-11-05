# ITcare BRI - KC Iskandar Muda Medan - Program Riski Amanda&Zaid Almubaro Simangunsong

Prototype akademik untuk rancangan **Sistem Informasi ITcare BRI Pengaduan Gangguan dan Permintaan Layanan Teknologi Informasi**. Antarmuka dirancang untuk tiga peran: Pegawai, Tim IT/Teknisi, dan Admin.

> **Penting:** ini adalah prototype akademik, bukan aplikasi resmi BRI dan bukan sistem produksi. Seluruh data awal bersifat simulasi. Jangan memasukkan data nasabah, kredensial, alamat IP internal, konfigurasi jaringan, atau informasi rahasia perusahaan.

## 0. Yang baru di Versi 3

- **Logo BRI** menggantikan semua logo "N" (landing, login, sidebar Pegawai/Tim IT/Admin, favicon, kop PDF). Tersedia sebagai SVG (`assets/img/logo-bri.svg`, `logo-bri-white.svg`) dan PNG.
- **Landing page & login** didesain ulang bergaya korporat BRI (biru, putih, aksen oranye) dengan animasi saat scroll: elemen muncul bertahap, parallax, angka berhitung, garis alur yang terisi, kartu miring mengikuti kursor, dan bilah progres scroll. Mendukung `prefers-reduced-motion`.
- **Profil berfungsi**: klik foto/nama di pojok kanan atas atau menu *Profil Saya* untuk mengubah nama, email, telepon, unit, serta mengunggah/menghapus foto. Disimpan permanen di `data/profiles.json` (kunci: peran + username), jadi tetap ada saat masuk kembali.
- **Pusat Bantuan** (kartu "Butuh bantuan?" dan menu *Pusat Bantuan*): FAQ sesuai peran dengan pencarian, formulir pesan ke administrator, dan kotak masuk administrator untuk membalas atau menandai selesai. Tersimpan di `data/bantuan.json`.
- **Notifikasi** (ikon lonceng): aktivitas tiket terbaru dan pesan bantuan.
- **Ekspor PDF** berkop surat pada Laporan Pengaduan (library FPDF di `lib/fpdf`).

Folder yang harus bisa ditulis: `data/` dan `uploads/avatars/` (di XAMPP Windows umumnya sudah otomatis; keduanya dilindungi `.htaccess`). Ekstensi PHP yang disarankan: `gd`, `fileinfo`, `mbstring`, `iconv` (standar di XAMPP).

## 0b. Akun tetap & alur masuk 

Akun disimpan permanen di `data/users.json` dan tiket di `data/tickets.json`, sehingga riwayat pengaduan tetap ada ketika pengguna masuk kembali, dan tiket terlihat lintas pengguna (pegawai, Tim IT, admin).

**Aturan**
- **Pegawai**: tidak perlu didaftarkan. Isi username + password pilihan sendiri (minimal 6 karakter); jika belum ada, akun dibuat otomatis dan langsung tercatat pada *Manajemen Pengguna → Daftar Pengguna*. Username/password yang sama di lain waktu masuk ke akun yang sama beserta riwayat pengaduannya. Pegawai **tidak dapat mengganti password**; hanya Administrator.
- **Tim IT & Administrator**: dapat mengganti password sendiri di *Profil Saya → Keamanan akun*; perubahan langsung tampil di Daftar Pengguna.
- **Administrator**: menambah akun **Tim IT** lewat *+ Tambah Pengguna*. Pada akun lain (termasuk Pegawai) Administrator dapat **mengubah password** lewat tombol *Kelola* atau **menghapus akun** lewat tombol *Hapus* di sampingnya. Penghapusan memerlukan konfirmasi; akun yang sedang digunakan dan Administrator aktif terakhir tidak dapat dihapus. Riwayat tiket dan bantuan tetap tersimpan, dan ID akun yang dihapus tidak digunakan kembali. Akun Pegawai tidak ditambahkan manual.
- Jika username milik akun Admin/Tim IT dimasukkan dengan password salah, login ditolak (tidak membuat akun baru). Setelah 5 kali gagal dalam 1 menit, login ditahan sementara.
- **Hapus data pengaduan (Admin)**: pada *Laporan Pengaduan → Rincian data pengaduan* tersedia tombol **Hapus** di samping *Detail*, dengan dialog konfirmasi. Penghapusan permanen (tiket beserta riwayat dan catatannya) dan hanya dapat dilakukan Administrator. Nomor tiket yang sudah dihapus tidak dipakai ulang (penghitung di `data/counters.json`).
- Keamanan: password disimpan sebagai hash (bcrypt) untuk proses login. Agar Administrator dapat melihatnya di Daftar Pengguna, tersimpan pula salinan terenkripsi (AES-256-GCM, kunci di `data/app.key`). Untuk penggunaan produksi sebaiknya fitur melihat password dihapus.
- Jangan hapus `data/app.key` bila `data/users.json` masih dipakai; tanpa kunci tersebut password di Daftar Pengguna tidak dapat ditampilkan (login tetap berfungsi).

## 1. Alur tampilan

1. Buka `http://localhost/ITcare BRI` untuk melihat landing page utama.
2. Klik tombol **Masuk** pada landing page.
3. Halaman login hanya meminta **Username** dan **Password**.
4. Username boleh diisi nama apa saja (maksimal 60 karakter).
5. Password demo menentukan peran yang digunakan setelah login.

## 2. Password demo berdasarkan peran

| Peran            | Password demo |
|------------------|--------------|
| Pegawai          | `pegawai123` |
| Tim IT / Teknisi | `teknisi123` |
| Administrator    | `admin123`   |

Contoh: masukkan username `Amanda` dan password `pegawai123` untuk masuk sebagai Pegawai. Masukkan username `Riski` dan password `teknisi123` untuk masuk sebagai Tim IT.

**Catatan keamanan:** mekanisme ini sengaja dibuat sederhana untuk pengujian prototipe sesuai kebutuhan demonstrasi. Kata sandi bersama yang menentukan peran tidak aman untuk penggunaan nyata. Pada tahap produksi, setiap orang harus memiliki akun individual yang diverifikasi terhadap database, password disimpan menggunakan `password_hash()` dan diverifikasi menggunakan `password_verify()`, serta peran ditetapkan oleh administrator di database (bukan dipilih dari password yang dibagikan).

## 3. Teknologi

- PHP 8.0 atau lebih baru
- Apache melalui XAMPP
- HTML5, CSS3, JavaScript
- MySQL 8 (skema awal tersedia di `database/schema.sql`, belum terhubung pada prototype)

Google Fonts digunakan untuk tipografi landing dan login. Jika komputer offline, halaman tetap dapat digunakan dengan font fallback.

## 4. Cara menjalankan di Windows dengan XAMPP

1. Ekstrak ZIP.
2. Salin folder `hITcare BRI` ke `C:\xampp\htdocs\`.
3. Buka XAMPP Control Panel dan jalankan **Apache**.
4. MySQL belum diperlukan untuk prototype berbasis PHP Session ini.
5. Buka `http://localhost/ITcare BRI/`.
6. Klik **Masuk**, isi username bebas, lalu gunakan password demo sesuai peran pada tabel di atas.

## 5. Fitur prototype

- Landing page dengan layout responsif, watermark dekoratif, gradient, animasi ambient, floating dashboard preview, dan reduced-motion support.
- Halaman login terpisah dengan dua input utama: username dan password.
- Username bebas; peran demo ditentukan oleh password.
- Dashboard dan menu dibedakan berdasarkan peran.
- Pegawai membuat pengaduan dan memperoleh nomor tiket.
- Tiket baru masuk antrean menunggu.
- Teknisi menerima tiket menunggu yang paling awal berdasarkan FIFO.
- Teknisi memperbarui status tiket dan menambahkan catatan.
- Pelapor melihat status dan riwayat tiket miliknya.
- Admin memantau data simulasi, master data, pengguna, dan laporan.
- Laporan dapat diekspor sebagai CSV.

Data demo disimpan dalam PHP Session. Data dapat kembali ke kondisi awal ketika sesi browser berakhir atau session server dibersihkan. Pengelolaan akun dan master data masih berupa tampilan demo. File bukti pada versi ini masih berupa pratinjau frontend dan belum diunggah ke penyimpanan server.

## 6. Daftar halaman aplikasi setelah login

1. Dashboard Pegawai
2. Dashboard Tim IT
3. Dashboard Admin
4. Form Pengaduan
5. Pengaduan Saya
6. Detail Tiket
7. Antrean FIFO
8. Tiket Saya
9. Penanganan Gangguan
10. Riwayat Pengaduan
11. Manajemen Pengguna
12. Master Data (tab kategori, lokasi, dan perangkat)
13. Monitoring Pengaduan
14. Laporan Pengaduan

Halaman landing dan halaman login berada sebelum 14 halaman kerja di atas.

## 7. Struktur folder

```text
Folder Program ITcare BRI/
├── assets/
│   ├── css/
│   │   ├── app.css
│   │   ├── landing.css
│   │   └── auth.css
│   └── js/
│       ├── app.js
│       ├── landing.js
│       └── auth.js
├── config/bootstrap.php
├── database/schema.sql
├── includes/
├── pages/
├── index.php
├── landing.php
├── login.php
└── logout.php
```

## 8. Pengembangan backend sebelum digunakan secara nyata

- Hubungkan aplikasi dengan MySQL menggunakan PDO dan prepared statements.
- Ganti password demo dengan autentikasi akun individual dan `password_hash()` / `password_verify()`.
- Tetapkan peran pengguna di database dan validasi otorisasi di setiap endpoint.
- Tambahkan CSRF protection pada semua form, pembatasan percobaan login, validasi server-side, dan audit log.
- Tambahkan unggah file dengan validasi MIME, ukuran, nama acak, penyimpanan di luar direktori publik, dan kontrol akses unduhan.
- Implementasikan penerimaan tiket FIFO secara atomik dengan transaksi dan row locking yang sesuai versi MySQL.
- Lakukan pengujian Black Box, pengujian FIFO multi-teknisi, pengujian keamanan, dan evaluasi pengguna.
- Pastikan izin penelitian serta persetujuan penggunaan data institusi telah diperoleh.
