<?php
$rows=all_tickets();
$counts=ticket_counts();
$byStatus=[]; $byCategory=[]; $byLocation=[];
foreach($rows as $t){$byStatus[$t['status']]=($byStatus[$t['status']]??0)+1;$byCategory[$t['category']]=($byCategory[$t['category']]??0)+1;$byLocation[$t['unit']]=($byLocation[$t['unit']]??0)+1;}
arsort($byCategory);arsort($byLocation);
?>
<?php page_heading('ANALITIK & PELAPORAN','Laporan Pengaduan','Ringkasan pengaduan berdasarkan status, kategori, dan lokasi pelayanan.'); ?>
<div class="report-toolbar"><div><strong>Periode laporan</strong><small>Pratinjau data simulasi saat ini.</small></div><div class="report-filters"><input class="form-control" type="date" value="<?= e(date('Y-m-d')) ?>"><input class="form-control" type="date" value="<?= e(date('Y-m-d')) ?>"><a class="btn btn-outline" href="export.php">↓ Ekspor PDF</a></div></div>
<div class="stats-grid stats-four"><?php stat_card('Total pengaduan',$counts['total'],'Dalam data demo','▤','blue'); ?><?php stat_card('Menunggu',$counts['menunggu'],'Belum ditangani','◷','amber'); ?><?php stat_card('Diproses',$counts['diproses'],'Sedang ditangani','⚙','cyan'); ?><?php stat_card('Selesai',$counts['selesai'],'Sudah selesai','✓','green'); ?></div>
<div class="section-grid section-grid-main mt-24"><section class="panel"><div class="panel-heading"><div><h3>Pengaduan berdasarkan kategori</h3><p>Distribusi tiket per kategori gangguan.</p></div></div><?php $max=max(1,...array_values($byCategory)); ?><div class="bar-chart"><?php foreach($byCategory as $label=>$value): ?><div class="bar-row"><div class="bar-label"><span><?= e($label) ?></span><strong><?= $value ?></strong></div><div class="bar-track"><div class="bar-fill" style="width:<?= round($value/$max*100) ?>%"></div></div></div><?php endforeach; ?></div></section><section class="panel"><div class="panel-heading"><div><h3>Pengaduan per lokasi</h3><p>Persebaran laporan berdasarkan unit.</p></div></div><?php foreach($byLocation as $label=>$value): ?><div class="location-metric"><div><span><?= e($label) ?></span><strong><?= $value ?></strong></div><div class="bar-track"><div class="bar-fill" style="width:<?= round($value/max(1,max($byLocation))*100) ?>%"></div></div></div><?php endforeach; ?></section></div>
<section class="panel mt-24"><div class="panel-heading"><div><h3>Rincian data pengaduan</h3><p>Daftar tiket yang menjadi dasar rekapitulasi.</p></div></div><?php ticket_table($rows,true,'Belum ada tiket untuk ditampilkan.',true); ?></section>
<?php if (current_user()['role'] === 'admin'): ?>
<dialog class="modal" id="deleteTicketDialog" aria-labelledby="delTitle">
  <form method="post" action="<?= e(url('laporan')) ?>" class="modal-form">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="delete_ticket"><input type="hidden" name="ticket_id" id="delTicketId">
    <div class="modal-head"><div><h3 id="delTitle">Hapus data pengaduan?</h3><p>Tindakan ini tidak dapat dibatalkan.</p></div><button type="button" class="modal-x" data-close-dialog aria-label="Tutup">×</button></div>
    <div class="danger-box"><strong id="delNo">-</strong><small id="delInfo">-</small></div>
    <p class="danger-text">Tiket beserta riwayat dan catatan penanganannya akan dihapus permanen dari sistem dan dari laporan.</p>
    <div class="modal-actions"><button type="button" class="btn btn-outline" data-close-dialog>Batal</button><button class="btn btn-danger" type="submit">Ya, hapus tiket</button></div>
  </form>
</dialog>
<?php endif; ?>
