<?php
$rows = array_values(array_filter(get_user_tickets('teknisi'), fn($t)=>in_array($t['status'],['diproses','tindak_lanjut'],true)));
?>
<?php page_heading('WORKSPACE TEKNISI','Penanganan Gangguan','Kelola pemeriksaan, diagnosis, dan pembaruan status tiket yang menjadi tanggung jawab Anda.'); ?>
<div class="info-callout"><span>⚒</span><p>Setiap tindakan penting sebaiknya dicatat pada detail tiket agar riwayat penanganan dapat ditelusuri dan menjadi dokumentasi layanan.</p></div>
<section class="panel mt-24"><div class="panel-heading"><div><h3>Tiket aktif</h3><p>Tiket berstatus Diproses atau Menunggu Tindak Lanjut.</p></div><span class="period-chip"><?= count($rows) ?> TIKET</span></div><?php ticket_table($rows,true,'Tidak ada tiket aktif pada akun Anda.'); ?></section>
