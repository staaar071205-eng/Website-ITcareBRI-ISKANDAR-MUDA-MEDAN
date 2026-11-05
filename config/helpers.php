<?php
/**
 * Fungsi pembantu ITcare BRI: penyimpanan JSON, profil + foto, pusat bantuan, notifikasi.
 * Data profil dan pesan bantuan disimpan di folder /data (JSON), foto di /uploads/avatars.
 */

/* ---------- Fallback bila ekstensi mbstring tidak aktif ---------- */
if (!function_exists('mb_strlen'))      { function mb_strlen($s, $enc = null) { return preg_match_all('/./us', (string)$s) ?: 0; } }
if (!function_exists('mb_substr'))      { function mb_substr($s, $start, $len = null, $enc = null) { preg_match_all('/./us', (string)$s, $m); return implode('', array_slice($m[0], $start, $len)); } }
if (!function_exists('mb_strtolower'))  { function mb_strtolower($s, $enc = null) { return strtolower((string)$s); } }
if (!function_exists('mb_strtoupper'))  { function mb_strtoupper($s, $enc = null) { return strtoupper((string)$s); } }
if (!function_exists('mb_strimwidth'))  { function mb_strimwidth($s, $start, $width, $trim = '', $enc = null) { return mb_strlen($s) > $width ? mb_substr($s, $start, max(0, $width - mb_strlen($trim))) . $trim : $s; } }

const DATA_DIR = __DIR__ . '/../data';
const AVATAR_DIR = __DIR__ . '/../uploads/avatars';
const AVATAR_MAX_BYTES = 2 * 1024 * 1024; // 2 MB

function ensure_dir(string $dir): void {
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
}

/* ---------- Penyimpanan JSON sederhana (dengan file lock) ---------- */
function json_load(string $name): array {
    $file = DATA_DIR . '/' . $name . '.json';
    if (!is_file($file)) return [];
    $data = json_decode((string)@file_get_contents($file), true);
    return is_array($data) ? $data : [];
}
/** Baca-ubah-tulis atomik: $fn menerima array lama dan mengembalikan array baru. */
function json_update(string $name, callable $fn): bool {
    ensure_dir(DATA_DIR);
    $fh = @fopen(DATA_DIR . '/' . $name . '.json', 'c+');
    if (!$fh) return false;
    flock($fh, LOCK_EX);
    $old = json_decode((string)stream_get_contents($fh), true);
    $new = $fn(is_array($old) ? $old : []);
    ftruncate($fh, 0); rewind($fh);
    fwrite($fh, json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    fflush($fh); flock($fh, LOCK_UN); fclose($fh);
    return true;
}

/* ---------- Profil pengguna ---------- */
/** Pesan bantuan milik pengguna (berdasarkan ID akun; pesan lama memakai kunci peran|username). */
function help_belongs(array $m, array $user): bool {
    if (isset($m['from_id'])) return (int)$m['from_id'] === (int)$user['id'];
    return ($m['from_key'] ?? '') === ($user['role'] . '|' . mb_strtolower($user['username'], 'UTF-8'));
}
function initials(string $name): string {
    $parts = array_slice(preg_split('/\s+/u', trim($name)) ?: [], 0, 2);
    $out = '';
    foreach ($parts as $p) $out .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8');
    return $out !== '' ? $out : '?';
}
function avatar_file_ok(?string $file): bool {
    return $file && preg_match('/^u_[a-f0-9]{16}\.(jpg|png)$/', $file) && is_file(AVATAR_DIR . '/' . $file);
}
function avatar_url(array $user): ?string {
    $f = $user['avatar'] ?? '';
    return avatar_file_ok($f) ? 'uploads/avatars/' . $f . '?v=' . filemtime(AVATAR_DIR . '/' . $f) : null;
}
function avatar_color(string $role): string {
    return $role === 'admin' ? 'avatar-violet' : ($role === 'teknisi' ? 'avatar-green' : 'avatar-blue');
}
/** HTML avatar: foto jika ada, jika tidak inisial. */
function render_avatar(array $user, string $extraClass = ''): string {
    $cls = trim('avatar ' . avatar_color($user['role'] ?? 'pegawai') . ' ' . $extraClass);
    $url = avatar_url($user);
    if ($url) return '<span class="' . e($cls) . ' has-photo"><img src="' . e($url) . '" alt="Foto profil ' . e($user['name'] ?? '') . '"></span>';
    return '<span class="' . e($cls) . '">' . e(initials((string)($user['name'] ?? ''))) . '</span>';
}
function delete_avatar_file(?string $file): void {
    if (avatar_file_ok($file)) @unlink(AVATAR_DIR . '/' . $file);
}
/**
 * Proses unggahan foto profil.
 * @return array [nama_file|null, pesan_error|null]  (null,null = tidak ada file diunggah)
 */
function process_avatar_upload(?array $file): array {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    $err = $file['error'];
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) return [null, 'Ukuran foto terlalu besar. Maksimal 2 MB.'];
    if ($err !== UPLOAD_ERR_OK) return [null, 'Foto gagal diunggah. Silakan coba lagi.'];
    $tmp = $file['tmp_name'];
    if (!is_uploaded_file($tmp)) return [null, 'Berkas unggahan tidak valid.'];
    if ($file['size'] > AVATAR_MAX_BYTES) return [null, 'Ukuran foto terlalu besar. Maksimal 2 MB.'];

    $info = @getimagesize($tmp);
    $mime = $info['mime'] ?? '';
    $allowed = ['image/jpeg', 'image/png'];
    if (function_exists('imagecreatefromwebp')) $allowed[] = 'image/webp';
    if (!$info || !in_array($mime, $allowed, true)) return [null, 'Format foto harus JPG, PNG' . (function_exists('imagecreatefromwebp') ? ' atau WEBP' : '') . '.'];
    if ($info[0] < 32 || $info[1] < 32 || $info[0] > 8000 || $info[1] > 8000) return [null, 'Dimensi foto tidak sesuai (minimal 32 px, maksimal 8000 px).'];

    ensure_dir(AVATAR_DIR);
    $base = 'u_' . bin2hex(random_bytes(8));

    if (extension_loaded('gd')) {
        $src = $mime === 'image/png' ? @imagecreatefrompng($tmp) : ($mime === 'image/webp' ? @imagecreatefromwebp($tmp) : @imagecreatefromjpeg($tmp));
        if (!$src) return [null, 'Foto tidak dapat dibaca. Gunakan berkas gambar lain.'];
        // Koreksi orientasi foto dari kamera ponsel
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $ex = @exif_read_data($tmp);
            $rot = [3 => 180, 6 => -90, 8 => 90][$ex['Orientation'] ?? 0] ?? 0;
            if ($rot) { $r = imagerotate($src, $rot, 0); if ($r) $src = $r; }
        }
        $w = imagesx($src); $h = imagesy($src); $side = min($w, $h);
        $dst = imagecreatetruecolor(400, 400);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), 400, 400, $side, $side);
        $name = $base . '.jpg';
        $ok = imagejpeg($dst, AVATAR_DIR . '/' . $name, 88);
        imagedestroy($src); imagedestroy($dst);
        return $ok ? [$name, null] : [null, 'Foto gagal disimpan di server. Periksa izin folder uploads.'];
    }
    // Tanpa GD: simpan apa adanya (hanya JPG/PNG)
    if ($mime === 'image/webp') return [null, 'Format WEBP memerlukan ekstensi GD. Gunakan JPG atau PNG.'];
    $name = $base . ($mime === 'image/png' ? '.png' : '.jpg');
    return @move_uploaded_file($tmp, AVATAR_DIR . '/' . $name) ? [$name, null] : [null, 'Foto gagal disimpan di server. Periksa izin folder uploads.'];
}
/* ---------- Pusat bantuan ---------- */
function help_subjects(): array {
    return ['akun' => 'Kendala akun / login', 'penggunaan' => 'Pertanyaan cara penggunaan', 'tiket' => 'Kendala pada tiket pengaduan', 'bug' => 'Melaporkan masalah aplikasi', 'lainnya' => 'Lainnya'];
}
function help_messages(): array {
    $rows = json_load('bantuan');
    usort($rows, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return $rows;
}
function help_add(array $user, string $subject, string $message): void {
    json_update('bantuan', function (array $rows) use ($user, $subject, $message) {
        $rows[] = [
            'id' => 'h_' . bin2hex(random_bytes(4)), 'created_at' => date('Y-m-d H:i:s'),
            'from_id' => (int)$user['id'], 'from_name' => $user['name'], 'from_role' => $user['role'],
            'subject' => $subject, 'message' => $message, 'status' => 'baru', 'reply' => '', 'replied_by' => '', 'replied_at' => '',
        ];
        return $rows;
    });
}
function help_respond(string $id, string $mode, string $reply, string $adminName): bool {
    $found = false;
    json_update('bantuan', function (array $rows) use ($id, $mode, $reply, $adminName, &$found) {
        foreach ($rows as &$r) {
            if ($r['id'] !== $id) continue;
            $found = true;
            if ($reply !== '') { $r['reply'] = $reply; $r['replied_by'] = $adminName; $r['replied_at'] = date('Y-m-d H:i:s'); }
            $r['status'] = $mode === 'close' ? 'selesai' : ($reply !== '' ? 'dibalas' : $r['status']);
        }
        return $rows;
    });
    return $found;
}
function help_status_label(string $s): array {
    return ['baru' => ['Menunggu balasan', 'warning'], 'dibalas' => ['Sudah dibalas', 'info'], 'selesai' => ['Selesai', 'success']][$s] ?? [ucfirst($s), 'muted'];
}
function help_new_count(): int {
    return count(array_filter(json_load('bantuan'), fn($r) => ($r['status'] ?? '') === 'baru'));
}

/* ---------- Notifikasi (lonceng) ---------- */
function notification_events(array $user): array {
    $ev = [];
    $role = $user['role'];
    foreach (all_tickets() as $t) {
        $visible = $role === 'admin'
            || ($role === 'pegawai' && (int)$t['reporter_id'] === (int)$user['id'])
            || ($role === 'teknisi' && ($t['status'] === 'menunggu' || (int)($t['assignee_id'] ?? 0) === (int)$user['id']));
        if (!$visible) continue;
        foreach ($t['history'] as $h) {
            $ev[] = ['time' => $h['time'], 'title' => $t['ticket'] . ' · ' . $h['status'], 'text' => $h['note'], 'url' => url('detail-tiket', ['id' => $t['id']])];
        }
    }
    foreach (json_load('bantuan') as $m) {
        if ($role === 'admin' && ($m['status'] ?? '') === 'baru') {
            $ev[] = ['time' => $m['created_at'], 'title' => 'Pesan bantuan baru', 'text' => $m['from_name'] . ': ' . (help_subjects()[$m['subject']] ?? $m['subject']), 'url' => url('bantuan')];
        }
        if ($role !== 'admin' && help_belongs($m, $user) && !empty($m['replied_at'])) {
            $ev[] = ['time' => $m['replied_at'], 'title' => 'Administrator membalas pesan Anda', 'text' => mb_strimwidth($m['reply'], 0, 80, '…', 'UTF-8'), 'url' => url('bantuan')];
        }
    }
    usort($ev, fn($a, $b) => strcmp($b['time'], $a['time']));
    return array_slice($ev, 0, 6);
}
function time_ago(string $time): string {
    $d = time() - (int)strtotime($time);
    if ($d < 60) return 'baru saja';
    if ($d < 3600) return intdiv($d, 60) . ' menit lalu';
    if ($d < 86400) return intdiv($d, 3600) . ' jam lalu';
    return format_datetime($time);
}
