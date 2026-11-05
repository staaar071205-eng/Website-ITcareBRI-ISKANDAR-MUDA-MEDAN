<?php
function page_heading(string $eyebrow, string $title, string $description, ?string $actionLabel = null, ?string $actionUrl = null): void { ?>
  <div class="page-heading"><div><span class="eyebrow"><?= e($eyebrow) ?></span><h1><?= e($title) ?></h1><p><?= e($description) ?></p></div><?php if ($actionLabel && $actionUrl): ?><a class="btn btn-primary" href="<?= e($actionUrl) ?>"><span>＋</span> <?= e($actionLabel) ?></a><?php endif; ?></div>
<?php }
function stat_card(string $label, $value, string $hint, string $symbol, string $color = 'blue'): void { ?>
  <div class="stat-card"><div class="stat-top"><span><?= e($label) ?></span><span class="stat-icon stat-<?= e($color) ?>"><?= e($symbol) ?></span></div><div class="stat-value"><?= e($value) ?></div><div class="stat-hint"><?= e($hint) ?></div></div>
<?php }
function status_badge(string $status): void { ?><span class="status-badge status-<?= e(status_class($status)) ?>"><i></i><?= e(status_label($status)) ?></span><?php }
function ticket_table(array $tickets, bool $showReporter = true, string $emptyText = 'Belum ada tiket untuk ditampilkan.', bool $allowDelete = false): void {
  $canDelete = $allowDelete && (current_user()['role'] ?? '') === 'admin'; ?>
  <div class="table-wrap"><table class="data-table"><thead><tr><th>Nomor tiket</th><?php if ($showReporter): ?><th>Pelapor</th><?php endif; ?><th>Gangguan / perangkat</th><th>Lokasi</th><th>Waktu masuk</th><th>Status</th><th></th></tr></thead><tbody>
  <?php if (!$tickets): ?><tr><td colspan="7"><div class="empty-state"><span class="empty-icon">▤</span><strong><?= e($emptyText) ?></strong><small>Data akan muncul setelah pengaduan dibuat.</small></div></td></tr><?php endif; ?>
  <?php foreach ($tickets as $t): ?><tr><td><a class="ticket-link" href="<?= e(url('detail-tiket',['id'=>$t['id']])) ?>"><?= e($t['ticket']) ?></a><small class="table-subline"><?= e($t['service_type']) ?></small></td><?php if ($showReporter): ?><td><?= e($t['reporter']) ?></td><?php endif; ?><td><strong><?= e($t['category']) ?></strong><small class="table-subline"><?= e($t['device']) ?></small></td><td><?= e($t['unit']) ?></td><td><?= e(format_datetime($t['created_at'])) ?></td><td><?php status_badge($t['status']); ?></td><td><div class="row-actions"><a class="table-action" href="<?= e(url('detail-tiket',['id'=>$t['id']])) ?>">Detail ↗</a><?php if ($canDelete): ?><button type="button" class="table-action table-danger as-button" data-delete-ticket="<?= (int)$t['id'] ?>" data-delete-no="<?= e($t['ticket']) ?>" data-delete-info="<?= e($t['category'] . ' · ' . $t['reporter']) ?>" aria-label="Hapus tiket <?= e($t['ticket']) ?>">Hapus</button><?php endif; ?></div></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php }
function ticket_progress(string $status): void {
    $steps = ['menunggu'=>'Diterima', 'diproses'=>'Diproses', 'selesai'=>'Selesai'];
    $active = ['menunggu'=>0,'diproses'=>1,'tindak_lanjut'=>1,'selesai'=>2,'ditolak'=>0,'dibatalkan'=>0,'ditutup'=>2][$status] ?? 0;
    ?><div class="ticket-progress"><?php $i=0; foreach ($steps as $key=>$label): ?><div class="progress-step <?= $i <= $active ? 'done' : '' ?> <?= $key===$status?'current':'' ?>"><span class="step-dot"><?= $i < $active ? '✓' : ($i+1) ?></span><span><?= e($label) ?></span></div><?php $i++; endforeach; ?></div><?php
}
function get_user_tickets(string $role): array {
    $user = current_user();
    $rows = all_tickets();
    if ($role === 'pegawai') $rows = array_values(array_filter($rows, fn($t)=>(int)$t['reporter_id']===(int)$user['id']));
    if ($role === 'teknisi') $rows = array_values(array_filter($rows, fn($t)=>(int)($t['assignee_id'] ?? 0)===(int)$user['id']));
    usort($rows, fn($a,$b)=>strcmp($b['created_at'],$a['created_at']));
    return $rows;
}
?>
