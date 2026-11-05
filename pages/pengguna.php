<?php
$users = users_all();
usort($users, function ($a, $b) {
    $rank = ['admin' => 0, 'teknisi' => 1, 'pegawai' => 2];
    return [$rank[$a['role']] ?? 9, (int)$a['id']] <=> [$rank[$b['role']] ?? 9, (int)$b['id']];
});
$countRole = fn($r) => count(array_filter($users, fn($u) => $u['role'] === $r));
$viaLabel = ['auto' => 'Terdaftar otomatis', 'admin' => 'Ditambahkan admin'];
?>
<?php page_heading('MANAJEMEN', 'Manajemen Pengguna', 'Kelola akun pegawai, teknisi, dan administrator yang dapat mengakses sistem.'); ?>
<div class="stats-grid stats-three">
  <?php stat_card('Total akun', count($users), 'Seluruh akun terdaftar', '♙', 'blue'); ?>
  <?php stat_card('Pegawai', $countRole('pegawai'), 'Terdaftar otomatis saat pertama masuk', '♧', 'cyan'); ?>
  <?php stat_card('Tim IT', $countRole('teknisi'), 'Ditambahkan oleh Administrator', '⚒', 'green'); ?>
</div>
<section class="panel mt-24">
  <div class="panel-heading">
    <div><h3>Daftar pengguna</h3><p>Akun tersimpan permanen. Akun Pegawai tercatat otomatis (nama &amp; password) saat pertama kali masuk. Administrator dapat mengubah password atau menghapus akun; riwayat tiket dan bantuan tetap tersimpan.</p></div>
    <button class="btn btn-primary" type="button" data-open-dialog="addUserDialog">＋ Tambah Pengguna</button>
  </div>
  <div class="search-form user-search"><span>⌕</span><input id="userSearch" type="search" placeholder="Cari nama, username, unit, atau peran..." aria-label="Cari pengguna"></div>
  <div class="table-wrap"><table class="data-table" id="userTable">
    <thead><tr><th>Nama pengguna</th><th>Username</th><th>Password</th><th>Unit / penempatan</th><th>Peran</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $pu = public_user($u); $plain = user_password_plain($u); ?>
      <tr>
        <td><div class="person-mini"><?= render_avatar($pu) ?><span class="person-text"><strong><?= e($u['name']) ?></strong><?php if (isset($viaLabel[$u['created_via']])): ?><small><?= e($viaLabel[$u['created_via']]) ?></small><?php endif; ?></span></div></td>
        <td><?= e($u['username']) ?></td>
        <td>
          <?php if ($plain !== null): ?>
            <span class="pw-cell" title="<?= $u['pw_changed_at'] ? 'Diubah ' . e(format_datetime($u['pw_changed_at'])) . ' oleh ' . e($u['pw_changed_by'] === 'self' ? 'pemilik akun' : 'administrator') : 'Password awal' ?>">
              <code class="pw-mask">••••••••</code><code class="pw-text" hidden><?= e($plain) ?></code>
              <button type="button" class="pw-eye" data-pw-toggle aria-label="Lihat password <?= e($u['name']) ?>">Lihat</button>
            </span>
          <?php else: ?><span class="muted-text">—</span><?php endif; ?>
        </td>
        <td><?= e($u['unit']) ?></td>
        <td><span class="role-pill role-<?= e($u['role']) ?>"><?= e(role_label($u['role'])) ?></span></td>
        <td><span class="status-badge status-success"><i></i><?= !empty($u['active']) ? 'Aktif' : 'Nonaktif' ?></span></td>
        <td><div class="row-actions"><button class="table-action as-button" type="button" data-pass-user="<?= (int)$u['id'] ?>" data-pass-name="<?= e($u['name']) ?>" data-pass-username="<?= e($u['username']) ?>" data-pass-role="<?= e(role_label($u['role'])) ?>">Kelola</button><?php if ((int)$u['id'] !== (int)current_user()['id']): ?><button class="table-action table-danger as-button" type="button" data-delete-user="<?= (int)$u['id'] ?>" data-delete-name="<?= e($u['name']) ?>" data-delete-meta="<?= e('@' . $u['username'] . ' · ' . role_label($u['role'])) ?>" aria-label="Hapus akun <?= e($u['name']) ?>">Hapus</button><?php endif; ?></div></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
    <div class="empty-state" id="userEmpty" hidden><strong>Tidak ada pengguna yang cocok</strong><small>Coba kata kunci lain.</small></div>
  </div>
</section>

<!-- Dialog: tambah akun Tim IT -->
<dialog class="modal" id="addUserDialog" aria-labelledby="addUserTitle">
  <form method="post" action="<?= e(url('pengguna')) ?>" class="modal-form" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="admin_add_tim_it">
    <div class="modal-head"><div><h3 id="addUserTitle">Tambah akun Tim IT</h3><p>Akun Pegawai tidak ditambahkan manual; terdaftar otomatis saat pertama kali masuk.</p></div><button type="button" class="modal-x" data-close-dialog aria-label="Tutup">×</button></div>
    <label class="field-label" for="addName">Nama lengkap <b class="required-mark">*</b></label>
    <input class="form-control" id="addName" name="name" maxlength="60" required placeholder="Contoh: Budi Santoso">
    <label class="field-label" for="addUsername">Username <b class="required-mark">*</b></label>
    <input class="form-control" id="addUsername" name="username" maxlength="60" required placeholder="Contoh: teknisi02">
    <label class="field-label" for="addPassword">Password <b class="required-mark">*</b></label>
    <input class="form-control" id="addPassword" name="password" type="password" minlength="<?= PW_MIN ?>" maxlength="<?= PW_MAX ?>" required placeholder="Minimal <?= PW_MIN ?> karakter">
    <label class="check-line"><input type="checkbox" data-show-passwords> Tampilkan password</label>
    <label class="field-label" for="addUnit">Unit / penempatan <b class="required-mark">*</b></label>
    <input class="form-control" id="addUnit" name="unit" maxlength="80" required value="Tim IT">
    <label class="field-label">Peran</label>
    <input class="form-control" value="Tim IT" disabled>
    <div class="modal-actions"><button type="button" class="btn btn-outline" data-close-dialog>Batal</button><button class="btn btn-primary" type="submit">Simpan akun</button></div>
  </form>
</dialog>

<!-- Dialog: hapus pengguna -->
<dialog class="modal" id="deleteUserDialog" aria-labelledby="deleteUserTitle">
  <form method="post" action="<?= e(url('pengguna')) ?>" class="modal-form">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="admin_delete_user"><input type="hidden" name="user_id" id="deleteUserId">
    <div class="modal-head"><div><h3 id="deleteUserTitle">Hapus akun pengguna?</h3><p>Tindakan ini tidak dapat dibatalkan.</p></div><button type="button" class="modal-x" data-close-dialog aria-label="Tutup">×</button></div>
    <div class="danger-box"><strong id="deleteUserName">-</strong><small id="deleteUserMeta">-</small></div>
    <p class="danger-text">Akun dan akses masuknya akan dihapus permanen. Riwayat tiket dan bantuan tetap tersimpan.</p>
    <div class="modal-actions"><button type="button" class="btn btn-outline" data-close-dialog>Batal</button><button class="btn btn-danger" type="submit">Ya, hapus akun</button></div>
  </form>
</dialog>

<!-- Dialog: kelola (hanya ubah password) -->
<dialog class="modal" id="passDialog" aria-labelledby="passTitle">
  <form method="post" action="<?= e(url('pengguna')) ?>" class="modal-form" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="admin_set_password"><input type="hidden" name="user_id" id="passUserId">
    <div class="modal-head"><div><h3 id="passTitle">Ubah password akun</h3><p>Administrator hanya dapat mengubah password.</p></div><button type="button" class="modal-x" data-close-dialog aria-label="Tutup">×</button></div>
    <div class="pass-target"><span class="avatar avatar-blue" id="passAvatar">?</span><div><strong id="passName">-</strong><small id="passMeta">-</small></div></div>
    <label class="field-label" for="passNew">Password baru <b class="required-mark">*</b></label>
    <input class="form-control" id="passNew" name="new_password" type="password" minlength="<?= PW_MIN ?>" maxlength="<?= PW_MAX ?>" required placeholder="Minimal <?= PW_MIN ?> karakter">
    <label class="check-line"><input type="checkbox" data-show-passwords> Tampilkan password</label>
    <div class="modal-actions"><button type="button" class="btn btn-outline" data-close-dialog>Batal</button><button class="btn btn-primary" type="submit">Simpan password</button></div>
  </form>
</dialog>
