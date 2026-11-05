<?php
// Prototipe ITcare BRI — akun & tiket disimpan permanen di folder /data (JSON); master data demo di session.
// Tahap berikutnya: migrasi ke MySQL.
date_default_timezone_set('Asia/Jakarta');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const APP_NAME = 'ITcare BRI';
const APP_SUBTITLE = 'IT Service Management';

if (!isset($_SESSION['demo_categories'])) {
    $_SESSION['demo_categories'] = ['Komputer / Laptop', 'Printer', 'CCTV', 'Jaringan & Internet', 'Software & Sistem Operasi', 'Perangkat Pendukung', 'Permintaan Instalasi', 'Lainnya'];
}
if (!isset($_SESSION['demo_locations'])) {
    $_SESSION['demo_locations'] = ['KC Iskandar Muda', 'Unit Medan Baru', 'Unit Setia Budi', 'Unit Sunggal', 'Unit lainnya'];
}
if (!isset($_SESSION['demo_devices'])) {
    $_SESSION['demo_devices'] = ['PC Desktop', 'Laptop', 'Printer', 'CCTV IP Camera', 'Router / Switch', 'Monitor', 'Perangkat lainnya'];
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function role_label(string $role): string {
    return ['pegawai' => 'Pegawai', 'teknisi' => 'Tim IT', 'admin' => 'Administrator'][$role] ?? ucfirst($role);
}
function status_label(string $status): string {
    return [
        'menunggu' => 'Menunggu', 'diproses' => 'Diproses', 'tindak_lanjut' => 'Menunggu Tindak Lanjut',
        'selesai' => 'Selesai', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan', 'ditutup' => 'Ditutup'
    ][$status] ?? ucfirst($status);
}
function status_class(string $status): string {
    return [
        'menunggu' => 'warning', 'diproses' => 'info', 'tindak_lanjut' => 'orange',
        'selesai' => 'success', 'ditolak' => 'danger', 'dibatalkan' => 'muted', 'ditutup' => 'muted'
    ][$status] ?? 'muted';
}
function format_datetime(?string $value): string {
    if (!$value) return '-';
    try { return (new DateTime($value))->format('d M Y, H:i'); } catch (Throwable $ex) { return $value; }
}
function all_tickets(): array { return tickets_load(); }
function find_ticket(int $id): ?array {
    foreach (all_tickets() as $ticket) if ((int)$ticket['id'] === $id) return $ticket;
    return null;
}
function save_ticket(array $updated): void { ticket_save($updated); }
function sort_waiting_tickets(): array {
    $rows = array_values(array_filter(all_tickets(), fn($t) => $t['status'] === 'menunggu'));
    usort($rows, function($a, $b) {
        $cmp = strcmp($a['queue_entered_at'] ?? $a['created_at'], $b['queue_entered_at'] ?? $b['created_at']);
        return $cmp !== 0 ? $cmp : ((int)$a['id'] <=> (int)$b['id']);
    });
    return $rows;
}
function ticket_counts(?int $reporterId = null): array {
    $rows = all_tickets();
    if ($reporterId !== null) $rows = array_values(array_filter($rows, fn($t) => (int)$t['reporter_id'] === $reporterId));
    $counts = ['total' => count($rows), 'menunggu' => 0, 'diproses' => 0, 'selesai' => 0, 'tindak_lanjut' => 0];
    foreach ($rows as $row) if (isset($counts[$row['status']])) $counts[$row['status']]++;
    return $counts;
}
function url(string $page, array $extra = []): string {
    return 'index.php?' . http_build_query(array_merge(['page' => $page], $extra));
}
function redirect(string $to): never { header('Location: ' . $to); exit; }
function require_role(array $roles): void {
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Anda tidak memiliki akses ke halaman tersebut.'];
        redirect('index.php');
    }
}
function flash_message(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

require_once __DIR__ . "/helpers.php";
require_once __DIR__ . "/store.php";

// Muat ulang data akun dari penyimpanan pada setiap permintaan (akun dihapus / dinonaktifkan -> otomatis keluar)
if (!empty($_SESSION['user']['id'])) {
    $__rec = user_find_by_id((int)$_SESSION['user']['id']);
    if (!$__rec || empty($__rec['active'])) unset($_SESSION['user']);
    else $_SESSION['user'] = public_user($__rec);
    unset($__rec);
}
