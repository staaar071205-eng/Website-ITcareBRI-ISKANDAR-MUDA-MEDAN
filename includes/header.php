<?php
$user = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';
$initials = implode('', array_map(fn($p) => strtoupper(substr($p, 0, 1)), array_slice(preg_split('/\s+/', $user['name']), 0, 2)));
$navGroups = [];
if ($user['role'] === 'pegawai') {
    $navGroups = [
        'MENU UTAMA' => [
            ['dashboard-pegawai', 'Overview', '⌂'],
            ['form-pengaduan', 'Buat Pengaduan', '+'],
            ['pengaduan-saya', 'Pengaduan Saya', '▤'],
        ],
        'AKUN' => [['riwayat', 'Riwayat Pengaduan', '◷']],
    ];
} elseif ($user['role'] === 'teknisi') {
    $navGroups = [
        'WORKSPACE' => [
            ['dashboard-it', 'Dashboard', '⌂'],
            ['antrean-fifo', 'Antrean FIFO', '≋'],
            ['tiket-saya', 'Tiket Saya', '▣'],
            ['penanganan', 'Penanganan', '⚒'],
        ],
        'AKTIVITAS' => [['riwayat', 'Riwayat Perbaikan', '◷'], ['laporan', 'Laporan Operasional', '▥']],
    ];
} else {
    $navGroups = [
        'OVERVIEW' => [['dashboard-admin', 'Dashboard', '⌂'], ['monitoring', 'Monitoring Pengaduan', '◉']],
        'MANAJEMEN' => [['pengguna', 'Manajemen Pengguna', '♙'], ['master-data', 'Master Data', '▦']],
        'PELAPORAN' => [['riwayat', 'Riwayat Aktivitas', '◷'], ['laporan', 'Laporan Pengaduan', '▥']],
    ];
}
$navGroups['PENGATURAN'] = [['profil', 'Profil Saya', '☺'], ['bantuan', 'Pusat Bantuan', '?']];
$helpBadge = $user['role'] === 'admin' ? help_new_count() : 0;
$notifEvents = notification_events($user);
$notifSeen = $_SESSION['notif_seen'] ?? '';
$notifUnseen = count(array_filter($notifEvents, fn($ev) => $ev['time'] > $notifSeen));
?>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand sidebar-brand" href="index.php"><span class="brand-mark"><img src="assets/img/logo-bri.svg" alt="Logo BRI"></span><span><strong>ITcare BRI</strong><small>IT SERVICE MANAGEMENT</small></span></a>
    <div class="sidebar-org"><span class="org-dot"></span><span><strong>ITcare BRI</strong><small>Iskandar Muda · Prototype</small></span><span class="chevron">⌄</span></div>
    <nav class="sidebar-nav">
      <?php foreach ($navGroups as $group => $items): ?>
        <div class="nav-label"><?= e($group) ?></div>
        <?php foreach ($items as [$key, $label, $icon]): ?>
          <a class="nav-link <?= $page === $key ? 'active' : '' ?>" href="<?= e(url($key)) ?>"><span class="nav-icon"><?= e($icon) ?></span><span><?= e($label) ?></span>
            <?php if ($key === 'antrean-fifo' && count(sort_waiting_tickets()) > 0): ?><span class="nav-count"><?= count(sort_waiting_tickets()) ?></span><?php endif; ?>
            <?php if ($key === 'bantuan' && $helpBadge > 0): ?><span class="nav-count"><?= $helpBadge ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
      <a class="sidebar-help" href="<?= e(url('bantuan')) ?>"><div class="help-icon">?</div><div><strong>Butuh bantuan?</strong><small>Panduan &amp; hubungi administrator.</small></div><span class="help-arrow">→</span></a>
      <div class="sidebar-user"><a class="user-link" href="<?= e(url('profil')) ?>" title="Profil saya"><?= render_avatar($user) ?><span class="user-meta"><strong><?= e($user['name']) ?></strong><small><?= e(role_label($user['role'])) ?></small></span></a><a class="logout-icon" href="logout.php" title="Keluar">↗</a></div>
    </div>
  </aside>
  <div class="main-column">
    <header class="topbar">
      <div class="topbar-left"><button class="icon-button mobile-menu" id="mobileMenu" aria-label="Buka menu">☰</button><div class="breadcrumb">Workspace <span>/</span> <strong><?= e($pageTitle) ?></strong></div></div>
      <div class="topbar-right"><span class="environment-pill"><i></i> MODE DEMO</span>
        <div class="notif" id="notif" data-csrf="<?= e($csrf ?? '') ?>">
          <button class="icon-button" id="notifBtn" type="button" title="Notifikasi" aria-haspopup="true" aria-expanded="false" aria-label="Notifikasi">♧<?php if ($notifUnseen > 0): ?><span class="notification-dot" id="notifDot"></span><?php endif; ?></button>
          <div class="notif-menu" id="notifMenu" hidden>
            <div class="notif-head"><strong>Notifikasi</strong><small><?= count($notifEvents) ?> aktivitas terbaru</small></div>
            <?php if (!$notifEvents): ?><div class="notif-empty">Belum ada aktivitas.</div><?php endif; ?>
            <?php foreach ($notifEvents as $ev): ?><a class="notif-item <?= $ev['time'] > $notifSeen ? 'unseen' : '' ?>" href="<?= e($ev['url']) ?>"><strong><?= e($ev['title']) ?></strong><span><?= e($ev['text']) ?></span><small><?= e(time_ago($ev['time'])) ?></small></a><?php endforeach; ?>
          </div>
        </div>
        <div class="topbar-divider"></div><a class="topbar-profile" href="<?= e(url('profil')) ?>" title="Buka profil saya"><?= render_avatar($user, 'avatar-small') ?><span><?= e($user['name']) ?></span></a></div>
    </header>
    <main class="content-area">
      <div class="prototype-notice"><span class="notice-symbol">i</span><span><strong>Prototype akademik:</strong> akun dan tiket tersimpan di berkas lokal (folder data), belum memakai database. Jangan gunakan data rahasia atau informasi operasional internal.</span></div>
      <?php if ($flash = flash_message()): ?><div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
