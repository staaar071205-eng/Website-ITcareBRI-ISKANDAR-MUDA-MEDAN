<?php
$rows = get_user_tickets('pegawai');
$q = trim($_GET['q'] ?? '');
if ($q !== '') $rows = array_values(array_filter($rows, fn($t)=>stripos($t['ticket'].' '.$t['category'].' '.$t['device'].' '.$t['description'], $q)!==false));
?>
<?php page_heading('PORTAL PEGAWAI','Pengaduan Saya','Kelola dan pantau seluruh pengaduan yang telah Anda sampaikan.', 'Buat Pengaduan', url('form-pengaduan')); ?>
<section class="panel"><div class="toolbar"><form method="get" class="search-form"><input type="hidden" name="page" value="pengaduan-saya"><span>⌕</span><input name="q" value="<?= e($q) ?>" placeholder="Cari nomor tiket atau gangguan..."><button class="btn btn-outline btn-sm">Cari</button></form><span class="result-count"><?= count($rows) ?> pengaduan ditemukan</span></div><?php ticket_table($rows,false,'Belum ada pengaduan yang Anda buat.'); ?></section>
