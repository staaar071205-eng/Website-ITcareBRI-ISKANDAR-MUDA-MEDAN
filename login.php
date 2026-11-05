<?php
require __DIR__ . '/config/bootstrap.php';
if (current_user()) redirect('index.php');
$error = '';
if (empty($_SESSION['login_csrf'])) $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ident = normalize_identifier((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $token = (string)($_POST['csrf_token'] ?? '');
    $fail = $_SESSION['login_fail'] ?? ['n' => 0, 't' => 0];
    if (time() - $fail['t'] > 60) $fail = ['n' => 0, 't' => time()];

    if (!hash_equals($_SESSION['login_csrf'], $token)) {
        $error = 'Sesi login tidak valid. Muat ulang halaman dan coba kembali.';
    } elseif ($fail['n'] >= 5) {
        $error = 'Terlalu banyak percobaan gagal. Tunggu sekitar 1 menit lalu coba lagi.';
    } elseif ($ident === '' || mb_strlen($ident, 'UTF-8') > 60) {
        $error = 'Masukkan username dengan panjang maksimal 60 karakter.';
    } elseif ($password === '' || strlen($password) > PW_MAX) {
        $error = 'Masukkan password Anda (maksimal ' . PW_MAX . ' karakter).';
    } else {
        [$status, $rec, $msg] = account_login($ident, $password);
        if ($rec) {
            session_regenerate_id(true);
            $_SESSION['user'] = public_user($rec);
            $_SESSION['notif_seen'] = date('Y-m-d H:i:s');
            unset($_SESSION['login_csrf'], $_SESSION['login_fail']);
            if ($status === 'created') {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Akun baru berhasil dibuat untuk "' . $rec['name'] . '". Gunakan username dan password yang sama untuk masuk kembali dan melihat riwayat pengaduan Anda.'];
            } elseif ($status === 'created_dup') {
                $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Username "' . $rec['name'] . '" sudah pernah dipakai dengan password lain, sehingga akun BARU dibuat. Jika Anda sudah punya akun, keluar lalu masuk dengan password yang benar.'];
            }
            redirect('index.php');
        }
        $error = $msg ?: 'Username atau password salah.';
        $_SESSION['login_fail'] = ['n' => $fail['n'] + 1, 't' => $fail['t'] ?: time()];
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#1758bb">
  <title>Masuk · ITcare BRI</title>
  <link rel="icon" type="image/svg+xml" href="assets/img/logo-bri.svg">
  <script>document.documentElement.classList.add('js')</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">
  <div class="auth-shell">
    <!-- Panel kiri: showcase -->
    <aside class="auth-showcase" id="showcase">
      <div class="sc-bg" aria-hidden="true">
        <img class="sc-mark" src="assets/img/logo-bri-white.svg" alt="" data-depth="14">
        <span class="sc-ring r1" data-depth="-10"></span><span class="sc-ring r2" data-depth="8"></span><span class="sc-glow" data-depth="-18"></span>
        <span class="sc-grid"></span>
      </div>
      <a class="sc-brand" href="index.php" data-reveal="up">
        <img src="assets/img/logo-bri-white.svg" alt="Logo BRI" width="46" height="46">
        <span><strong>ITcare BRI KC Iskandar Muda</strong><small>IT SERVICE DESK</small></span>
      </a>
      <div class="sc-content">
        <span class="sc-kicker" data-reveal="up" data-delay="1">ITcare BRI · KC MEDAN ISKANDAR MUDA</span>
        <h2 data-reveal="up" data-delay="2">Satu portal untuk <em>seluruh layanan IT.</em></h2>
        <ul class="sc-list">
          <li data-reveal="up" data-delay="3"><span class="sc-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h12M4 18h8"/></svg></span><span><strong>Antrean FIFO</strong><small>Tiket ditangani sesuai urutan masuk.</small></span></li>
          <li data-reveal="up" data-delay="4"><span class="sc-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg></span><span><strong>Status terpantau</strong><small>Dari Menunggu hingga Selesai, lengkap riwayatnya.</small></span></li>
          <li data-reveal="up" data-delay="5"><span class="sc-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg></span><span><strong>Laporan PDF berkop</strong><small>Rekap layanan siap cetak dalam satu klik.</small></span></li>
        </ul>
        <div class="sc-ticket" data-reveal="zoom" data-delay="6" data-depth="6">
          <div class="sc-ticket-top"><span class="sc-dot"></span><strong>IT-20261102-0001</strong><span class="sc-badge">Diproses</span></div>
          <p>Laptop sering restart sendiri saat digunakan.</p>
          <div class="sc-prog"><b></b></div>
        </div>
      </div>
      <div class="sc-foot">© 2026 ITcare BRI KC Iskandar Muda · MEDAN</div>
    </aside>

    <!-- Panel kanan: form -->
    <main class="auth-panel">
      <a class="auth-back" href="index.php">← Kembali ke beranda</a>
      <section class="auth-card" data-reveal="up">
        <div class="auth-logo"><img src="assets/img/logo-bri.svg" alt="Logo BRI" width="54" height="54"></div>
        <span class="auth-eyebrow">PORTAL LOGIN</span>
        <h1>Selamat datang <em>kembali.</em></h1>
        <p class="auth-description">Masuk ke ruang kerja layanan IT Anda.</p>
        <?php if ($error): ?><div class="auth-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="auth-form" autocomplete="on" id="loginForm">
          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['login_csrf']) ?>">
          <label for="username">Username</label>
          <div class="auth-input"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.6"/><path d="M5 20c.8-3.6 3.6-5.4 7-5.4s6.2 1.8 7 5.4"/></svg><input id="username" name="username" type="text" maxlength="60" placeholder="Username atau nama lengkap" autocomplete="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>"></div>
          <label for="password">Password</label>
          <div class="auth-input"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg><input id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required><button class="auth-show" type="button" id="togglePassword" aria-label="Tampilkan password">Tampilkan</button></div>
          <button class="auth-submit" type="submit" id="submitBtn"><span class="lbl">Masuk</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
        </form>
        <p class="auth-footnote">Sudah punya akun? Masuk dengan username dan password Anda.<br><b>Pegawai baru:</b> isi username &amp; password pilihan Anda, akun dibuat otomatis.</p>
      </section>
      <div class="auth-bottom"><span>Sistem resmi BRI KC Iskandar Muda</span><span>Data dimiliki Oleh Tim IT BRI</span></div>
    </main>
  </div>
  <script src="assets/js/auth.js"></script>
</body>
</html>
