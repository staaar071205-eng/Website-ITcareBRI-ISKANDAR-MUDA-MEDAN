<?php
$u = current_user(); $role = $u['role'];
$subjects = help_subjects();
$all = help_messages();
$mine = array_values(array_filter($all, fn($m) => help_belongs($m, $u)));

$faq = [
  ['Bagaimana cara masuk ke aplikasi?', 'Buka halaman Masuk lalu isi username (atau nama lengkap) dan password akun Anda. Akun Tim IT dan Administrator dibuat oleh Administrator.', 'all'],
  ['Saya Pegawai baru, bagaimana membuat akun?', 'Tidak perlu mendaftar. Isi username dan password pilihan Anda (minimal 6 karakter) di halaman Masuk; akun dibuat otomatis. Gunakan username dan password yang sama untuk masuk kembali dan melihat riwayat pengaduan Anda.', 'pegawai'],
  ['Bagaimana jika lupa atau ingin mengganti password?', 'Password akun Pegawai hanya dapat diubah oleh Administrator. Kirim permintaan melalui formulir Hubungi administrator di halaman ini.', 'pegawai'],
  ['Bagaimana mengganti password saya?', 'Buka Profil Saya, pada bagian Keamanan akun isi password saat ini dan password baru. Password baru otomatis tersimpan pada Daftar Pengguna di akun Administrator.', 'teknisi'],
  ['Bagaimana mengganti password saya?', 'Buka Profil Saya lalu gunakan bagian Keamanan akun. Untuk mengubah password akun lain, buka Manajemen Pengguna lalu pilih Kelola.', 'admin'],
  ['Bagaimana menambah akun Tim IT?', 'Buka Manajemen Pengguna lalu tekan Tambah Pengguna. Isi nama, username, password, dan unit. Akun Pegawai tidak ditambahkan manual karena terdaftar otomatis saat pertama kali masuk.', 'admin'],
  ['Apa itu antrean FIFO?', 'FIFO (First In, First Out) berarti tiket yang masuk lebih dulu ditangani lebih dulu. Teknisi tidak memilih tiket sendiri; sistem mengambil tiket berstatus Menunggu dengan waktu antrean paling awal.', 'all'],
  ['Apa arti status tiket?', 'Menunggu: belum diterima teknisi. Diproses: sedang ditangani. Menunggu Tindak Lanjut: butuh pemeriksaan lanjutan. Selesai: sudah dituntaskan. Ditolak: tidak dapat ditindaklanjuti.', 'all'],
  ['Bagaimana mengubah nama atau foto profil?', 'Klik foto/nama Anda di pojok kanan atas (atau menu Profil Saya), ubah data yang diperlukan, lalu tekan Simpan perubahan.', 'all'],
  ['Bagaimana membuat pengaduan?', 'Buka Buat Pengaduan, pilih jenis layanan, kategori, unit, lokasi detail, perangkat, lalu jelaskan masalahnya. Setelah dikirim, nomor tiket muncul otomatis.', 'pegawai'],
  ['Bagaimana memantau tiket saya?', 'Buka Pengaduan Saya lalu pilih Detail pada tiket. Di sana tampak status, progres, dan riwayat penanganan.', 'pegawai'],
  ['Bagaimana menerima tiket?', 'Buka Antrean FIFO lalu tekan "Terima Tiket Berikutnya". Tiket akan pindah ke daftar Tiket Saya dengan status Diproses.', 'teknisi'],
  ['Bagaimana memperbarui status tiket?', 'Buka detail tiket yang ditugaskan kepada Anda, pilih status baru, tulis catatan teknisi, lalu simpan. Setiap perubahan tercatat pada riwayat.', 'teknisi'],
  ['Kapan memilih "Menunggu Tindak Lanjut"?', 'Pilih status ini jika penanganan butuh pemeriksaan lanjutan. Tiket dapat dimasukkan kembali ke antrean dan memperoleh waktu antrean baru.', 'teknisi'],
  ['Bagaimana mengunduh laporan PDF?', 'Buka Laporan Pengaduan lalu tekan Ekspor PDF. Dokumen berkop surat langsung terunduh berisi ringkasan dan rincian tiket.', 'admin'],
  ['Bagaimana membalas pesan bantuan?', 'Pesan dari pengguna muncul di Kotak masuk bantuan pada halaman ini. Tulis balasan lalu tekan Kirim balasan, atau tandai selesai.', 'admin'],
];
$faq = array_values(array_filter($faq, fn($f) => $f[2] === 'all' || $f[2] === $role));
?>
<?php page_heading('PUSAT BANTUAN', 'Pusat Bantuan', 'Temukan jawaban cepat atau kirim pesan langsung kepada administrator sistem.'); ?>
<div class="help-layout">
  <div class="help-col">
    <section class="panel">
      <div class="panel-heading"><div><h3>Pertanyaan yang sering diajukan</h3><p>Panduan singkat sesuai peran Anda (<?= e(role_label($role)) ?>).</p></div></div>
      <div class="search-form faq-search"><span>⌕</span><input id="faqSearch" type="search" placeholder="Cari panduan, misalnya: antrean, status, foto..." aria-label="Cari panduan"></div>
      <div class="faq-list" id="faqList">
        <?php foreach ($faq as $i => [$q, $a]): ?>
          <details class="faq-item" <?= $i === 0 ? 'open' : '' ?>><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details>
        <?php endforeach; ?>
        <div class="empty-state" id="faqEmpty" hidden><strong>Tidak ada panduan yang cocok</strong><small>Coba kata kunci lain atau kirim pesan kepada administrator.</small></div>
      </div>
    </section>

    <?php if ($role === 'admin'): ?>
    <section class="panel mt-24">
      <div class="panel-heading"><div><h3>Kotak masuk bantuan</h3><p>Pesan dari pegawai dan Tim IT.</p></div><span class="period-chip"><?= help_new_count() ?> BARU</span></div>
      <?php if (!$all): ?><div class="empty-state"><span class="empty-icon">✉</span><strong>Belum ada pesan</strong><small>Pesan dari pengguna akan tampil di sini.</small></div><?php endif; ?>
      <?php foreach ($all as $m): [$lbl, $cls] = help_status_label($m['status']); ?>
        <article class="help-msg">
          <div class="help-msg-top"><div><strong><?= e($m['from_name']) ?></strong> <span class="role-pill role-<?= e($m['from_role']) ?>"><?= e(role_label($m['from_role'])) ?></span><small><?= e($subjects[$m['subject']] ?? $m['subject']) ?> · <?= e(format_datetime($m['created_at'])) ?></small></div><span class="status-badge status-<?= e($cls) ?>"><i></i><?= e($lbl) ?></span></div>
          <p><?= nl2br(e($m['message'])) ?></p>
          <?php if ($m['reply'] !== ''): ?><div class="help-reply"><small>Balasan <?= e($m['replied_by']) ?> · <?= e(format_datetime($m['replied_at'])) ?></small><p><?= nl2br(e($m['reply'])) ?></p></div><?php endif; ?>
          <?php if ($m['status'] !== 'selesai'): ?>
          <form method="post" action="<?= e(url('bantuan')) ?>" class="help-reply-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="reply_help"><input type="hidden" name="help_id" value="<?= e($m['id']) ?>">
            <textarea class="form-control" name="reply" rows="2" maxlength="600" placeholder="Tulis balasan untuk pengguna..."></textarea>
            <div class="help-reply-actions"><button class="btn btn-primary btn-sm" name="mode" value="reply">Kirim balasan</button><button class="btn btn-outline btn-sm" name="mode" value="close">Tandai selesai</button></div>
          </form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>
  </div>

  <aside class="help-col">
    <?php if ($role !== 'admin'): ?>
    <section class="panel">
      <div class="panel-heading"><div><h3>Hubungi administrator</h3><p>Tidak menemukan jawabannya? Kirim pesan kepada kami.</p></div></div>
      <form method="post" action="<?= e(url('bantuan')) ?>" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="send_help">
        <label class="field-label" for="subject">Topik <b class="required-mark">*</b></label>
        <select class="form-control" id="subject" name="subject" required><option value="">Pilih topik bantuan</option><?php foreach ($subjects as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?></select>
        <label class="field-label" for="helpMessage">Pesan <b class="required-mark">*</b></label>
        <textarea class="form-control" id="helpMessage" name="message" rows="5" minlength="10" maxlength="600" required placeholder="Jelaskan kendala Anda. Jangan menuliskan password atau data rahasia."></textarea>
        <div class="textarea-footer"><small class="field-help">Minimal 10 karakter.</small><small id="helpCount">0 / 600</small></div>
        <button class="btn btn-primary btn-block" type="submit">Kirim pesan</button>
      </form>
    </section>
    <section class="panel mt-24">
      <div class="panel-heading"><div><h3>Pesan saya</h3><p>Riwayat pesan dan balasan administrator.</p></div></div>
      <?php if (!$mine): ?><div class="empty-state"><span class="empty-icon">✉</span><strong>Belum ada pesan</strong><small>Pesan yang Anda kirim akan tampil di sini.</small></div><?php endif; ?>
      <?php foreach ($mine as $m): [$lbl, $cls] = help_status_label($m['status']); ?>
        <article class="help-msg">
          <div class="help-msg-top"><div><strong><?= e($subjects[$m['subject']] ?? $m['subject']) ?></strong><small><?= e(format_datetime($m['created_at'])) ?></small></div><span class="status-badge status-<?= e($cls) ?>"><i></i><?= e($lbl) ?></span></div>
          <p><?= nl2br(e($m['message'])) ?></p>
          <?php if ($m['reply'] !== ''): ?><div class="help-reply"><small>Balasan <?= e($m['replied_by']) ?> · <?= e(format_datetime($m['replied_at'])) ?></small><p><?= nl2br(e($m['reply'])) ?></p></div><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
    <?php else: ?>
    <section class="panel tip-panel"><div class="tip-illustration">✧</div><h3>Tips untuk administrator</h3><ul><li>Balas pesan dengan singkat dan jelas.</li><li>Tandai selesai setelah masalah teratasi.</li><li>Jumlah pesan baru tampil pada lencana menu Pusat Bantuan.</li></ul></section>
    <?php endif; ?>
    <section class="panel mt-24 contact-card"><h3>Lokasi layanan</h3><p>Kantor Cabang BRI Medan Iskandar Muda<br>Jln. Iskandar Muda No. 173 Medan</p><small class="field-help">Prototipe akademik, bukan kanal resmi BRI.</small></section>
  </aside>
</div>
