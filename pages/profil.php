<?php
$u = current_user();
$locs = $_SESSION['demo_locations'];
$rec = user_find_by_id((int)$u['id']);
$createdAt = $rec['created_at'] ?? null;
$ticketCount = $u['role'] === 'admin' ? count(all_tickets()) : count(get_user_tickets($u['role']));
$ticketLabel = ['pegawai' => 'Pengaduan dibuat', 'teknisi' => 'Tiket ditangani', 'admin' => 'Total tiket sistem'][$u['role']];
$hasPhoto = (bool)avatar_url($u);
if ($u['role'] === 'pegawai' && !in_array($u['unit'], $locs, true)) array_unshift($locs, $u['unit']);
?>
<?php page_heading('AKUN', 'Profil Saya', 'Perbarui data diri dan foto profil. Perubahan tersimpan dan tetap ada saat Anda masuk kembali dengan username yang sama.'); ?>
<div class="profile-layout">
<form method="post" action="<?= e(url('profil')) ?>" enctype="multipart/form-data" class="profile-form" id="profileForm" novalidate>
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
  <input type="hidden" name="action" value="update_profile">
  <section class="panel profile-main">
    <div class="panel-heading"><div><h3>Foto profil</h3><p>JPG, PNG<?= function_exists('imagecreatefromwebp') ? ' atau WEBP' : '' ?> · maksimal 2 MB · otomatis dipotong persegi.</p></div></div>
    <div class="photo-row">
      <div class="photo-preview" id="photoPreview" data-initials="<?= e(initials($u['name'])) ?>" data-color="<?= e(avatar_color($u['role'])) ?>"><?= render_avatar($u, 'avatar-xl') ?></div>
      <div class="photo-actions">
        <label class="btn btn-primary btn-sm" for="avatar">⇧ Pilih foto baru</label>
        <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg<?= function_exists('imagecreatefromwebp') ? ',image/webp' : '' ?>" hidden>
        <?php if ($hasPhoto): ?><label class="check-line"><input type="checkbox" name="remove_avatar" value="1" id="removeAvatar"> Hapus foto saat ini</label><?php endif; ?>
        <small class="field-help" id="photoHint">Foto baru disimpan setelah Anda menekan "Simpan perubahan".</small>
      </div>
    </div>
    <div class="divider"></div>
    <div class="panel-heading"><div><h3>Data diri</h3><p>Kolom bertanda <b class="required-mark">*</b> wajib diisi.</p></div></div>
    <div class="form-grid">
      <div class="field-group"><label for="name">Nama lengkap <b>*</b></label><input class="form-control" id="name" name="name" maxlength="60" required value="<?= e($u['name']) ?>"></div>
      <div class="field-group"><label for="username">Username</label><input class="form-control" id="username" value="<?= e($u['username']) ?>" disabled><small class="field-help">Username adalah identitas masuk dan tidak dapat diubah.</small></div>
      <div class="field-group"><label for="email">Email</label><input class="form-control" id="email" name="email" type="email" maxlength="120" placeholder="nama@contoh.test" value="<?= e($u['email'] ?? '') ?>"></div>
      <div class="field-group"><label for="phone">Nomor telepon</label><input class="form-control" id="phone" name="phone" type="tel" maxlength="20" placeholder="08xx-xxxx-xxxx" value="<?= e($u['phone'] ?? '') ?>"></div>
      <div class="field-group"><label for="unit">Unit / penempatan <b>*</b></label>
        <?php if ($u['role'] === 'pegawai'): ?>
          <select class="form-control" id="unit" name="unit" required><?php foreach ($locs as $loc): ?><option <?= $u['unit'] === $loc ? 'selected' : '' ?>><?= e($loc) ?></option><?php endforeach; ?></select>
        <?php else: ?>
          <input class="form-control" id="unit" name="unit" maxlength="80" required value="<?= e($u['unit']) ?>">
        <?php endif; ?>
      </div>
      <div class="field-group"><label for="role">Peran</label><input class="form-control" id="role" value="<?= e(role_label($u['role'])) ?>" disabled><small class="field-help">Peran ditentukan oleh administrator sistem.</small></div>
    </div>
    <div class="form-actions"><a class="btn btn-outline" href="<?= e(url($u['role'] === 'pegawai' ? 'dashboard-pegawai' : ($u['role'] === 'teknisi' ? 'dashboard-it' : 'dashboard-admin'))) ?>">Batal</a><button class="btn btn-primary" type="submit">Simpan perubahan</button></div>
  </section>
</form>
  <aside class="profile-side">
    <section class="panel profile-card">
      <div class="profile-card-avatar"><?= render_avatar($u, 'avatar-lg') ?></div>
      <h3><?= e($u['name']) ?></h3>
      <span class="role-pill role-<?= e($u['role']) ?>"><?= e(role_label($u['role'])) ?></span>
      <div class="side-info"><small><?= e($ticketLabel) ?></small><strong><?= (int)$ticketCount ?> tiket</strong></div>
      <div class="side-info"><small>Unit / penempatan</small><strong><?= e($u['unit']) ?></strong></div>
      <div class="side-info"><small>Akun dibuat</small><strong><?= $createdAt ? e(format_datetime($createdAt)) : '-' ?></strong></div>
    </section>
    <section class="panel">
      <div class="panel-heading"><div><h3>Keamanan akun</h3><?php if ($u['role'] === 'pegawai'): ?><p>Password dikelola oleh Administrator.</p><?php else: ?><p>Password baru langsung tersimpan pada Daftar Pengguna di akun Administrator.</p><?php endif; ?></div></div>
      <?php if ($u['role'] === 'pegawai'): ?>
        <div class="lock-note"><span>🔒</span><p>Password akun Pegawai <b>hanya dapat diubah oleh Administrator</b>. Untuk mengganti atau jika lupa password, kirim permintaan melalui <a class="text-link" href="<?= e(url('bantuan')) ?>">Pusat Bantuan</a>.</p></div>
      <?php else: ?>
        <form method="post" action="<?= e(url('profil')) ?>" class="form-stack" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="change_password">
          <label class="field-label" for="curPw">Password saat ini</label><input class="form-control" id="curPw" name="current_password" type="password" required autocomplete="current-password">
          <label class="field-label" for="newPw">Password baru</label><input class="form-control" id="newPw" name="new_password" type="password" minlength="<?= PW_MIN ?>" maxlength="<?= PW_MAX ?>" required autocomplete="new-password" placeholder="Minimal <?= PW_MIN ?> karakter">
          <label class="field-label" for="confPw">Ulangi password baru</label><input class="form-control" id="confPw" name="confirm_password" type="password" required autocomplete="new-password">
          <label class="check-line"><input type="checkbox" data-show-passwords> Tampilkan password</label>
          <button class="btn btn-primary btn-block" type="submit">Ubah password</button>
        </form>
      <?php endif; ?>
    </section>
    <section class="panel tip-panel"><h3>Catatan</h3><ul><li>Nama baru ikut diperbarui pada tiket yang Anda buat atau tangani.</li><li>Gunakan foto yang sopan dan jelas wajahnya.</li><li>Butuh bantuan? Buka <a class="text-link" href="<?= e(url('bantuan')) ?>">Pusat Bantuan</a>.</li></ul></section>
  </aside>
</div>
