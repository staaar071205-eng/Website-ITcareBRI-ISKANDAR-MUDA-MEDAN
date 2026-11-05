<?php
require_once __DIR__ . '/config/bootstrap.php';
if (current_user()) redirect('index.php');

// Ikon SVG sederhana (stroke) untuk kartu fitur
function ic(string $name): string {
    $p = [
        'ticket' => '<path d="M4 8a2 2 0 0 0 0 4v0a2 2 0 0 0 0 4v1a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-1a2 2 0 0 1 0-4v0a2 2 0 0 1 0-4V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1z"/><path d="M14 6v12" stroke-dasharray="2 2.5"/>',
        'queue'  => '<path d="M4 6h16M4 12h12M4 18h8"/><circle cx="19" cy="17" r="2.2"/>',
        'track'  => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'pdf'    => '<path d="M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'shield' => '<path d="M12 3l7 3v5.5c0 4.4-3 8-7 9.5-4-1.5-7-5.1-7-9.5V6l7-3z"/><path d="M9 12l2.2 2.2L15.5 10"/>',
        'chart'  => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'check'  => '<path d="M5 12.5l4.2 4.2L19 7"/>',
        'user'   => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20c.8-3.6 3.6-5.4 7-5.4s6.2 1.8 7 5.4"/>',
        'wrench' => '<path d="M14.5 6.5a4 4 0 0 0-5 5L4 17l3 3 5.5-5.5a4 4 0 0 0 5-5l-2.6 2.6-2.4-.6-.6-2.4z"/>',
        'admin'  => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h8M8 17h4"/>',
        'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ][$name] ?? '';
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#1758bb">
  <meta name="description" content="ITcare BRI — portal pengaduan dan pengelolaan layanan teknologi informasi.">
  <title>ITcare BRI  — Layanan IT yang cepat, tertib, dan terukur</title>
  <link rel="icon" type="image/svg+xml" href="assets/img/logo-bri.svg">
  <script>document.documentElement.classList.add('js')</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="landing-body">
  <div class="scroll-progress" aria-hidden="true"><span id="scrollBar"></span></div>

  <header class="site-header" id="siteHeader">
    <a class="site-brand" href="index.php" aria-label="ITcare BRI beranda">
      <img class="brand-logo" src="assets/img/logo-bri.svg" alt="Logo BRI" width="42" height="42">
      <span class="site-brand-copy"><strong>ITcare BRI KC Iskandar Muda</strong><small>IT SERVICE DESK</small></span>
    </a>
    <nav class="site-nav" aria-label="Navigasi utama">
      <a href="#fitur">Fitur</a>
      <a href="#workflow">Alur layanan</a>
      <a href="#akses">Hak akses</a>
    </nav>
    <a class="header-login" href="login.php">Masuk <?= ic('arrow') ?></a>
  </header>

  <main>
    <!-- ================= HERO ================= -->
    <section class="hero" id="platform">
      <div class="hero-bg" aria-hidden="true">
        <img class="hero-mark" src="assets/img/logo-bri.svg" alt="" data-parallax="0.12">
        <span class="blob blob-a" data-parallax="-0.08"></span>
        <span class="blob blob-b" data-parallax="0.1"></span>
        <span class="grid-lines"></span>
      </div>

      <div class="hero-copy">
        <div class="eyebrow-pill" data-reveal="up"><span class="live-dot"></span> ITcare BRI · KC MEDAN ISKANDAR MUDA</div>
        <h1 data-reveal="up" data-delay="1">Layanan IT yang <em>cepat, tertib,</em> dan terukur.</h1>
        <p class="hero-description" data-reveal="up" data-delay="2">Satu portal untuk melaporkan gangguan, mengelola antrean berdasarkan urutan masuk, dan memantau penanganan dari tiket dibuat hingga selesai.</p>
        <div class="hero-actions" data-reveal="up" data-delay="3">
          <a class="btn-primary" href="login.php">Masuk ke portal <?= ic('arrow') ?></a>
          <a class="btn-ghost" href="#workflow">Lihat alur layanan</a>
        </div>
        <ul class="hero-chips" data-reveal="up" data-delay="4">
          <li><?= ic('check') ?> Antrean FIFO</li>
          <li><?= ic('check') ?> Laporan PDF berkop</li>
          <li><?= ic('check') ?> 3 peran akses</li>
        </ul>
      </div>

      <div class="hero-visual" data-reveal="zoom" data-delay="2" aria-label="Pratinjau dashboard ITcare BRI">
        <div class="dash" data-tilt>
          <div class="dash-top">
            <div class="dash-brand"><img src="assets/img/logo-bri.svg" alt="" width="22" height="22"><span>ITcare BRI KC Iskandar Muda<small>WORKSPACE</small></span></div>
            <div class="dash-dots"><i></i><i></i><i></i></div>
          </div>
          <div class="dash-body">
            <div class="dash-title"><div><small>RINGKASAN LAYANAN</small><strong>Dashboard Tim IT</strong></div><span class="dash-live"><b></b> Pratinjau</span></div>
            <div class="dash-metrics">
              <div class="metric"><span>Menunggu</span><strong data-count="8" data-pad="2">08</strong><i class="bar amber"><b style="--w:62%"></b></i></div>
              <div class="metric"><span>Diproses</span><strong data-count="3" data-pad="2">03</strong><i class="bar blue"><b style="--w:34%"></b></i></div>
              <div class="metric"><span>Selesai</span><strong data-count="24" data-pad="2">24</strong><i class="bar green"><b style="--w:86%"></b></i></div>
            </div>
            <div class="dash-queue">
              <div class="dash-queue-head"><strong>Antrean berikutnya</strong><span class="chip">FIFO</span></div>
              <div class="trow first"><span class="num">01</span><span class="tcopy"><strong>Printer tidak merespons</strong><small>IT-20261102-0001 · KC Iskandar Muda</small></span><span class="time">08:15</span></div>
              <div class="trow"><span class="num">02</span><span class="tcopy"><strong>Gangguan konektivitas</strong><small>IT-20261102-0002 · Unit layanan</small></span><span class="time">08:32</span></div>
              <div class="trow"><span class="num">03</span><span class="tcopy"><strong>Monitor CCTV tidak tampil</strong><small>IT-20261102-0003 · Ruang monitoring</small></span><span class="time">09:10</span></div>
            </div>
          </div>
        </div>
        <div class="float-card fc-top"><span class="fc-ic ok"><?= ic('check') ?></span><span><strong>Tiket diterima</strong><small>Nomor tiket dibuat otomatis</small></span></div>
        <div class="float-card fc-bottom"><span class="fc-ic pdf"><?= ic('pdf') ?></span><span><strong>Laporan PDF</strong><small>Unduh dengan satu klik</small></span></div>
      </div>

      <a class="scroll-cue" href="#stats" aria-label="Gulir ke bawah"><span></span> Gulir untuk menjelajah</a>
    </section>

    <!-- ================= STATISTIK ================= -->
    <section class="stats" id="stats">
      <div class="stat" data-reveal="up"><strong><span data-count="3">3</span></strong><small>peran pengguna</small></div>
      <div class="stat" data-reveal="up" data-delay="1"><strong><span data-count="14">14</span></strong><small>halaman kerja</small></div>
      <div class="stat" data-reveal="up" data-delay="2"><strong><span data-count="7">7</span></strong><small>status tiket tercatat</small></div>
      <div class="stat" data-reveal="up" data-delay="3"><strong><span data-count="1">1</span> klik</strong><small>unduh laporan PDF</small></div>
    </section>

    <!-- ================= FITUR ================= -->
    <section class="section" id="fitur">
      <div class="section-head" data-reveal="up">
        <span class="kicker">FITUR UTAMA</span>
        <h2>Semua kebutuhan layanan IT, <em>dalam satu tempat.</em></h2>
        <p>Dirancang agar setiap laporan tercatat rapi, ditangani adil, dan mudah dipertanggungjawabkan.</p>
      </div>
      <div class="feature-grid">
        <article class="feature" data-reveal="up" data-tilt><span class="f-icon"><?= ic('ticket') ?></span><h3>Pengaduan terpusat</h3><p>Formulir terstruktur memuat jenis layanan, kategori, lokasi, perangkat, dan deskripsi. Nomor tiket dibuat otomatis.</p></article>
        <article class="feature" data-reveal="up" data-delay="1" data-tilt><span class="f-icon"><?= ic('queue') ?></span><h3>Antrean FIFO yang adil</h3><p>Tiket diterima menurut waktu masuk paling awal. Teknisi tidak memilih sendiri, sehingga urutan tetap konsisten.</p></article>
        <article class="feature" data-reveal="up" data-delay="2" data-tilt><span class="f-icon"><?= ic('track') ?></span><h3>Pelacakan status</h3><p>Pelapor memantau progres dari Menunggu hingga Selesai lengkap dengan linimasa dan catatan teknisi.</p></article>
        <article class="feature" data-reveal="up" data-tilt><span class="f-icon"><?= ic('pdf') ?></span><h3>Laporan PDF berkop</h3><p>Rekap status, kategori, dan lokasi beserta rincian tiket, siap dicetak dalam format kop surat resmi.</p></article>
        <article class="feature" data-reveal="up" data-delay="1" data-tilt><span class="f-icon"><?= ic('shield') ?></span><h3>Hak akses per peran</h3><p>Pegawai, Tim IT, dan Administrator hanya melihat menu dan data yang sesuai dengan tanggung jawabnya.</p></article>
        <article class="feature" data-reveal="up" data-delay="2" data-tilt><span class="f-icon"><?= ic('chart') ?></span><h3>Monitoring menyeluruh</h3><p>Dashboard administrator menampilkan beban antrean, distribusi kategori, dan aktivitas terbaru secara ringkas.</p></article>
      </div>
    </section>

    <!-- ================= ALUR ================= -->
    <section class="section dark" id="workflow">
      <div class="dark-bg" aria-hidden="true"><img src="assets/img/logo-bri-white.svg" alt="" data-parallax="0.1"></div>
      <div class="section-head" data-reveal="up">
        <span class="kicker light">ALUR LAYANAN</span>
        <h2>Dari laporan hingga <em>tuntas.</em></h2>
        <p>Empat langkah sederhana yang dapat ditelusuri di setiap tahap.</p>
      </div>
      <ol class="steps" id="steps">
        <span class="steps-line" aria-hidden="true"><b id="stepsFill"></b></span>
        <li class="step" data-reveal="up"><span class="step-no">01</span><span class="step-dot"></span><h3>Buat pengaduan</h3><p>Pegawai mengisi formulir dan menerima nomor tiket.</p></li>
        <li class="step" data-reveal="up" data-delay="1"><span class="step-no">02</span><span class="step-dot"></span><h3>Masuk antrean</h3><p>Tiket berstatus Menunggu dan diurutkan berdasarkan waktu masuk.</p></li>
        <li class="step" data-reveal="up" data-delay="2"><span class="step-no">03</span><span class="step-dot"></span><h3>Ditangani Tim IT</h3><p>Teknisi menerima tiket, memeriksa, dan mencatat setiap tindakan.</p></li>
        <li class="step" data-reveal="up" data-delay="3"><span class="step-no">04</span><span class="step-dot"></span><h3>Selesai &amp; dilaporkan</h3><p>Status diperbarui dan seluruh data masuk ke laporan.</p></li>
      </ol>
    </section>

    <!-- ================= PERAN ================= -->
    <section class="section" id="akses">
      <div class="section-head" data-reveal="up">
        <span class="kicker">SATU PLATFORM, TIGA PERAN</span>
        <h2>Tampilan yang sesuai <em>tugas masing-masing.</em></h2>
      </div>
      <div class="role-grid">
        <article class="role" data-reveal="up" data-tilt>
          <span class="r-icon blue"><?= ic('user') ?></span><h3>Pegawai / Pelapor</h3><p class="r-sub">Menyampaikan dan memantau kendala</p>
          <ul><li><?= ic('check') ?> Buat pengaduan dan unggah bukti</li><li><?= ic('check') ?> Pantau status dan riwayat tiket</li><li><?= ic('check') ?> Hubungi administrator lewat Pusat Bantuan</li></ul>
        </article>
        <article class="role featured" data-reveal="up" data-delay="1" data-tilt>
          <span class="r-badge">PALING AKTIF</span>
          <span class="r-icon orange"><?= ic('wrench') ?></span><h3>Tim IT / Teknisi</h3><p class="r-sub">Menangani tiket sesuai antrean</p>
          <ul><li><?= ic('check') ?> Terima tiket berikutnya (FIFO)</li><li><?= ic('check') ?> Perbarui status dan catatan teknis</li><li><?= ic('check') ?> Lihat tiket dan laporan operasional</li></ul>
        </article>
        <article class="role" data-reveal="up" data-delay="2" data-tilt>
          <span class="r-icon navy"><?= ic('admin') ?></span><h3>Administrator</h3><p class="r-sub">Mengawasi seluruh layanan</p>
          <ul><li><?= ic('check') ?> Monitoring seluruh pengaduan</li><li><?= ic('check') ?> Kelola pengguna dan master data</li><li><?= ic('check') ?> Ekspor laporan PDF berkop</li></ul>
        </article>
      </div>
    </section>

    <!-- ================= CTA ================= -->
    <section class="cta" data-reveal="zoom">
      <img class="cta-mark" src="assets/img/logo-bri-white.svg" alt="" aria-hidden="true" data-parallax="-0.1">
      <span class="kicker light">SIAP DIGUNAKAN</span>
      <h2>Setiap laporan layak <em>ditangani dengan baik.</em></h2>
      <p>Masuk ke portal untuk membuat pengaduan, mengelola antrean, atau memantau layanan IT.</p>
      <a class="btn-orange" href="login.php">Masuk ke ITcare BRI <?= ic('arrow') ?></a>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-brand"><img src="assets/img/logo-bri-white.svg" alt="Logo BRI" width="40" height="40"><div><strong>ITcare BRI KC Iskandar Muda</strong><small>Kantor Cabang BRI Medan Iskandar Muda<br>Jln. Iskandar Muda No. 173 Medan</small></div></div>
    <p class="footer-note">Brilian way · Integrity · Collaborative · Accountability · Growth mindset · Customer focus.</p>
    <span class="footer-flag">SISTEM RESMI KC. BRI ISKANDAR MUDA · 2026</span>
  </footer>

  <script src="assets/js/landing.js"></script>
</body>
</html>
