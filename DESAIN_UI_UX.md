# Dokumen Wireframe dan UI/UX — ITcare BRI

## A. Arah visual

- **Gaya:** Modern Enterprise ITcare BRI, minimalis, informatif, dan berorientasi tugas.
- **Warna utama:** navy `#142B4A`; biru aksi `#2764D8`; latar `#F5F7FB`; permukaan `#FFFFFF`.
- **Warna status:** Menunggu amber; Diproses cyan; Selesai hijau; Tindak Lanjut oranye; Ditolak merah; status netral abu-abu.
- **Tipografi:** Manrope untuk heading dan DM Sans untuk isi. Font menggunakan Google Fonts sebagai peningkatan visual; jika internet tidak tersedia, browser memakai fallback sans-serif.
- **Grid:** sidebar desktop 252 px, topbar 66 px, konten responsif, kartu statistik 4 kolom desktop dan 2 kolom mobile.
- **Aksesibilitas:** label formulir eksplisit, fokus input terlihat, tombol memiliki teks yang jelas, status memakai teks dan warna (bukan warna saja).

## B. Pola layout

### Halaman autentikasi
- Panel kiri berisi identitas aplikasi, proposisi manfaat, dan 3 fitur utama.
- Panel kanan berisi form username/password, tombol masuk, dan akun demo untuk pratinjau.

### Halaman aplikasi
- Sidebar kiri: identitas aplikasi, konteks unit, navigasi sesuai peran, bantuan, identitas akun dan logout.
- Topbar: breadcrumb, indikator mode demo, notifikasi visual, dan identitas pengguna.
- Area konten: peringatan prototype, judul halaman, deskripsi, aksi utama, kartu statistik, tabel/form/timeline.
- Footer: identitas prototype.

## C. Detail layar prioritas

### 1. Login
Tujuan: masuk berdasarkan akun masing-masing. Komponen: username, password dengan toggle, tombol masuk, kartu akun demo, pesan error. Layout dua kolom di desktop dan satu kolom di mobile.

### 2. Dashboard Pegawai
Tujuan: melihat ringkasan pengaduan sendiri dan membuat pengaduan baru. Komponen: welcome panel, kartu total/menunggu/diproses/selesai, tabel pengaduan terbaru, kartu tindakan cepat.

### 3. Dashboard Tim IT
Tujuan: melihat beban antrean dan tiket pribadi. Komponen: kartu antrean menunggu/tiket saya/tindak lanjut/selesai, tiket FIFO berikutnya, tombol terima tiket berikutnya, ringkasan status, tabel tiket yang sedang ditangani.

### 4. Dashboard Admin
Tujuan: pengawasan menyeluruh. Komponen: kartu total/menunggu/diproses/selesai, grafik batang kategori, ringkasan pengguna berdasarkan peran, tabel pengaduan terbaru.

### 5. Form Pengaduan
Tujuan: menerima data yang cukup untuk ditindaklanjuti. Bagian form: jenis layanan; kategori; unit dan lokasi detail; jenis perangkat; kode aset opsional; deskripsi; bukti opsional. Komponen pendamping: tips pengisian dan penjelasan alur setelah submit.

## D. Sepuluh layar pendukung

6. **Pengaduan Saya:** pencarian nomor tiket/kategori/deskripsi dan tabel tiket milik pelapor.
7. **Detail Tiket:** status, progress, deskripsi, detail lokasi/perangkat, identitas pelapor/teknisi, timeline dan form update untuk teknisi berwenang.
8. **Antrean FIFO:** banner jumlah antrean, tabel nomor urut, waktu masuk antrean, dan aksi menerima tiket berikutnya.
9. **Tiket Saya:** daftar tiket yang ditugaskan ke teknisi beserta ringkasan status.
10. **Penanganan Gangguan:** daftar tiket aktif yang perlu didiagnosis dan diperbarui.
11. **Riwayat Pengaduan:** arsip berdasarkan lingkup hak akses akun.
12. **Manajemen Pengguna:** tabel pengguna, unit, peran, dan status akun.
13. **Master Data:** satu halaman bertab untuk kategori gangguan, lokasi/unit, dan jenis perangkat.
14. **Monitoring Pengaduan:** ringkasan statistik, pencarian, dan tabel seluruh tiket.
15. **Laporan Pengaduan:** filter periode, ringkasan metrik, distribusi kategori/lokasi, tabel rincian, ekspor CSV.

## E. Prinsip UX yang perlu dipertahankan

1. Status tiket harus konsisten pada dashboard, tabel, detail, dan laporan.
2. Pegawai hanya melihat tiket miliknya sendiri.
3. Teknisi menerima tiket melalui backend berdasarkan urutan FIFO, bukan menentukan tiket dari browser.
4. Waktu pengaduan awal tidak boleh diubah. Jika tiket masuk kembali ke antrean setelah tindak lanjut, simpan waktu antrean aktif terpisah.
5. Aksi penting (terima tiket, ubah status, selesai) harus menghasilkan konfirmasi dan riwayat.
6. Form memakai validasi, petunjuk, dan pesan error yang spesifik.
7. Hindari menampilkan data sensitif pada tabel atau notifikasi.

## F. Status prototipe

Antarmuka dan alur demo telah disediakan. Data demo disimpan dalam PHP session. Unggah file, autentikasi produksi, manajemen master data yang persisten, dan integrasi MySQL belum diaktifkan. Itu merupakan pekerjaan tahap backend selanjutnya.
