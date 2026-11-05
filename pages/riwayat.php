<?php
$role = current_user()['role'];
$rows = $role==='pegawai'?get_user_tickets('pegawai'):($role==='teknisi'?get_user_tickets('teknisi'):all_tickets());
usort($rows,fn($a,$b)=>strcmp($b['created_at'],$a['created_at']));
?>
<?php page_heading('DOKUMENTASI','Riwayat Pengaduan','Rekam jejak pengaduan dan proses penanganan berdasarkan hak akses akun Anda.'); ?>
<section class="panel"><div class="toolbar"><div class="filter-summary"><span class="filter-dot"></span><?= count($rows) ?> catatan aktivitas tersedia</div><span class="period-chip">TERBARU</span></div><?php ticket_table($rows,$role!=='pegawai','Belum ada riwayat pengaduan.'); ?></section>
