<?php
// Menandai notifikasi sudah dibaca (dipanggil via fetch dari tombol lonceng).
require __DIR__ . '/config/bootstrap.php';
if (!current_user() || $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403); exit;
}
$_SESSION['notif_seen'] = date('Y-m-d H:i:s');
http_response_code(204);
