<?php
$rows = get_user_tickets('teknisi');
?>
<?php page_heading('WORKSPACE TEKNISI','Tiket Saya','Pengaduan yang telah ditugaskan kepada akun teknisi Anda.'); ?>
<div class="stats-grid stats-three"><?php stat_card('Total ditugaskan',count($rows),'Seluruh tiket Anda','▣','blue'); ?><?php stat_card('Sedang diproses',count(array_filter($rows,fn($t)=>$t['status']==='diproses')),'Masih dalam penanganan','⚙','cyan'); ?><?php stat_card('Selesai',count(array_filter($rows,fn($t)=>$t['status']==='selesai')),'Telah diselesaikan','✓','green'); ?></div>
<section class="panel mt-24"><div class="panel-heading"><div><h3>Daftar tiket teknisi</h3><p>Pilih detail untuk melanjutkan pembaruan penanganan.</p></div></div><?php ticket_table($rows,true,'Belum ada tiket yang ditugaskan kepada Anda.'); ?></section>
