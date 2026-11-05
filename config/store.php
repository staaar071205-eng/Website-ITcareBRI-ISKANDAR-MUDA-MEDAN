<?php
/**
 * Penyimpanan permanen ITcare BRI (file JSON di folder /data):
 *  - users.json   : akun Admin, Tim IT, dan Pegawai (password di-hash; salinan terenkripsi untuk ditampilkan ke Admin)
 *  - tickets.json : seluruh tiket pengaduan (riwayat tetap ada saat pengguna masuk kembali)
 */
const PW_MIN = 6;
const PW_MAX = 64;

/* =====================================================================
 *  Enkripsi salinan password (agar Admin dapat melihat password pada Daftar Pengguna)
 * ===================================================================== */
function app_key(): string {
    static $key = null;
    if ($key !== null) return $key;
    ensure_dir(DATA_DIR);
    $file = DATA_DIR . '/app.key';
    if (!is_file($file)) @file_put_contents($file, base64_encode(random_bytes(32)), LOCK_EX);
    $raw = base64_decode((string)@file_get_contents($file), true);
    return $key = ($raw !== false && strlen($raw) === 32) ? $raw : hash('sha256', __DIR__, true);
}
function pw_encrypt(string $plain): string {
    if (!function_exists('openssl_encrypt')) return '';
    $iv = random_bytes(12); $tag = '';
    $ct = openssl_encrypt($plain, 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return $ct === false ? '' : base64_encode($iv . $tag . $ct);
}
function pw_decrypt(string $blob): ?string {
    if ($blob === '' || !function_exists('openssl_decrypt')) return null;
    $raw = base64_decode($blob, true);
    if ($raw === false || strlen($raw) < 29) return null;
    $out = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $out === false ? null : $out;
}

/* =====================================================================
 *  Akun
 * ===================================================================== */
function seed_accounts(): array {
    $now = date('Y-m-d H:i:s');
    $defs = [
        [1, 'Rani Putri',     'pegawai01', 'pegawai', 'KC Iskandar Muda', 'rani.putri@example.test',     'pegawai01'],
        [2, 'Fajar Pratama',  'teknisi01', 'teknisi', 'Tim IT',           'fajar.pratama@example.test',  'teknisi01'],
        [3, 'Admin Sistem',   'admin',     'admin',   'Administrator',    'admin@example.test',          'admin123'],
        [4, 'Dimas Saputra',  'pegawai02', 'pegawai', 'Unit Medan Baru',  'dimas.saputra@example.test',  'pegawai02'],
    ];
    $old = json_load('profiles'); // migrasi profil/foto dari versi sebelumnya bila ada
    $rows = [];
    foreach ($defs as [$id, $name, $username, $role, $unit, $email, $pw]) {
        $rec = [
            'id' => $id, 'name' => $name, 'username' => $username, 'role' => $role, 'unit' => $unit,
            'email' => $email, 'phone' => '', 'avatar' => '', 'active' => true,
            'password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'pw_enc' => pw_encrypt($pw),
            'created_via' => 'seed', 'created_at' => $now, 'pw_changed_at' => '', 'pw_changed_by' => '',
        ];
        foreach ([$role . '|' . mb_strtolower($username, 'UTF-8'), $role . '|' . mb_strtolower($name, 'UTF-8')] as $k) {
            if (isset($old[$k])) {
                foreach (['name', 'email', 'phone', 'unit', 'avatar'] as $f) if (!empty($old[$k][$f])) $rec[$f] = $old[$k][$f];
                break;
            }
        }
        $rows[] = $rec;
    }
    return $rows;
}
function users_all(): array {
    static $ready = false;
    if (!$ready) {
        if (!is_file(DATA_DIR . '/users.json')) json_update('users', fn($r) => $r ?: seed_accounts());
        $ready = true;
    }
    return array_values(json_load('users'));
}
function user_find_by_id(int $id): ?array {
    foreach (users_all() as $u) if ((int)$u['id'] === $id) return $u;
    return null;
}
function normalize_identifier(string $s): string {
    return trim(preg_replace('/\s+/u', ' ', $s));
}
function valid_username(string $s): bool {
    return (bool)preg_match('/^[\p{L}\p{N} ._\'\-]{3,60}$/u', $s);
}
function valid_password(string $pw): bool {
    return strlen($pw) >= PW_MIN && strlen($pw) <= PW_MAX;
}
/** Akun yang cocok dengan username ATAU nama lengkap (tanpa membedakan huruf besar/kecil). */
function users_by_identifier(string $ident): array {
    $needle = mb_strtolower(normalize_identifier($ident), 'UTF-8');
    return array_values(array_filter(users_all(), fn($u) =>
        mb_strtolower($u['username'], 'UTF-8') === $needle || mb_strtolower($u['name'], 'UTF-8') === $needle));
}
/** Data akun tanpa field rahasia (aman disimpan di session). */
function public_user(array $rec): array {
    $out = [];
    foreach (['id', 'name', 'username', 'role', 'unit', 'email', 'phone', 'avatar', 'active', 'created_via'] as $k) $out[$k] = $rec[$k] ?? '';
    $out['id'] = (int)$out['id'];
    return $out;
}
function user_password_plain(array $rec): ?string {
    return pw_decrypt((string)($rec['pw_enc'] ?? ''));
}
function user_set_password(int $id, string $password, string $by): bool {
    $found = false;
    json_update('users', function (array $rows) use ($id, $password, $by, &$found) {
        foreach ($rows as &$r) {
            if ((int)$r['id'] !== $id) continue;
            $r['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $r['pw_enc'] = pw_encrypt($password);
            $r['pw_changed_at'] = date('Y-m-d H:i:s');
            $r['pw_changed_by'] = $by;
            $found = true;
        }
        return $rows;
    });
    return $found;
}
/** Mencatat ID tertinggi akun agar ID yang pernah dipakai tidak digunakan kembali. */
function user_id_advance_counter(int $id, array $rows): bool {
    $maxId = $id;
    foreach ($rows as $row) $maxId = max($maxId, (int)($row['id'] ?? 0));
    return json_update('user_ids', fn(array $counter) => [
        'last_id' => max((int)($counter['last_id'] ?? 0), $maxId),
    ]);
}
/** Mengalokasikan ID baru. Pemanggil harus memanggilnya di dalam penguncian users.json. */
function user_allocate_id(array $rows): ?int {
    $maxId = 0;
    foreach ($rows as $row) $maxId = max($maxId, (int)($row['id'] ?? 0));
    $id = null;
    $saved = json_update('user_ids', function (array $counter) use ($maxId, &$id) {
        $id = max((int)($counter['last_id'] ?? 0), $maxId) + 1;
        return ['last_id' => $id];
    });
    return $saved ? $id : null;
}
/**
 * Menghapus akun tanpa menghapus riwayat tiket atau bantuan.
 * @return array{status:string,user:?array}
 */
function user_delete(int $id, int $actorId): array {
    $status = 'not_found';
    $removed = null;
    $saved = json_update('users', function (array $rows) use ($id, $actorId, &$status, &$removed) {
        $target = null;
        foreach ($rows as $row) {
            if ((int)($row['id'] ?? 0) === $id) { $target = $row; break; }
        }
        if ($id < 1 || !$target) return $rows;
        if ($id === $actorId) { $status = 'self'; return $rows; }

        $activeAdmins = count(array_filter($rows, fn($row) =>
            ($row['role'] ?? '') === 'admin' && !empty($row['active'])));
        if (($target['role'] ?? '') === 'admin' && !empty($target['active']) && $activeAdmins <= 1) {
            $status = 'last_admin';
            return $rows;
        }
        if (!user_id_advance_counter($id, $rows)) {
            $status = 'storage_error';
            return $rows;
        }

        $removed = $target;
        $status = 'deleted';
        return array_values(array_filter($rows, fn($row) => (int)($row['id'] ?? 0) !== $id));
    });
    if (!$saved) $status = 'storage_error';
    return ['status' => $status, 'user' => $removed];
}
function user_update_profile(int $id, array $fields): void {
    $allowed = array_intersect_key($fields, array_flip(['name', 'email', 'phone', 'unit', 'avatar']));
    json_update('users', function (array $rows) use ($id, $allowed) {
        foreach ($rows as &$r) if ((int)$r['id'] === $id) $r = array_merge($r, $allowed);
        return $rows;
    });
}
function new_user_record(int $id, string $name, string $username, string $role, string $unit, string $password, string $via): array {
    return [
        'id' => $id, 'name' => $name, 'username' => $username, 'role' => $role, 'unit' => $unit,
        'email' => '', 'phone' => '', 'avatar' => '', 'active' => true,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'pw_enc' => pw_encrypt($password),
        'created_via' => $via, 'created_at' => date('Y-m-d H:i:s'), 'pw_changed_at' => '', 'pw_changed_by' => '',
    ];
}
/** Admin menambah akun Tim IT. @return array [rekaman|null, pesan_error|null] */
function user_create_tim_it(string $name, string $username, string $unit, string $password): array {
    $name = normalize_identifier($name); $username = normalize_identifier($username); $unit = normalize_identifier($unit);
    if ($name === '' || mb_strlen($name, 'UTF-8') > 60) return [null, 'Nama lengkap wajib diisi (maksimal 60 karakter).'];
    if (!valid_username($username)) return [null, 'Username 3-60 karakter: huruf, angka, spasi, titik, garis bawah, atau strip.'];
    if ($unit === '' || mb_strlen($unit, 'UTF-8') > 80) return [null, 'Unit / penempatan wajib diisi (maksimal 80 karakter).'];
    if (!valid_password($password)) return [null, 'Password harus ' . PW_MIN . '-' . PW_MAX . ' karakter.'];
    $created = null; $error = null;
    json_update('users', function (array $rows) use ($name, $username, $unit, $password, &$created, &$error) {
        $taken = [];
        $staff = [];
        foreach ($rows as $r) {
            $taken[mb_strtolower($r['username'], 'UTF-8')] = true;
            $taken[mb_strtolower($r['name'], 'UTF-8')] = true;
            if ($r['role'] !== 'pegawai') { $staff[mb_strtolower($r['username'], 'UTF-8')] = true; $staff[mb_strtolower($r['name'], 'UTF-8')] = true; }
        }
        if (isset($taken[mb_strtolower($username, 'UTF-8')])) { $error = 'Username "' . $username . '" sudah digunakan akun lain.'; return $rows; }
        if (isset($staff[mb_strtolower($name, 'UTF-8')])) { $error = 'Nama "' . $name . '" sudah digunakan akun Tim IT / Administrator lain.'; return $rows; }
        $id = user_allocate_id($rows);
        if ($id === null) { $error = 'Akun gagal disimpan. Penyimpanan data tidak tersedia.'; return $rows; }
        $created = new_user_record($id, $name, $username, 'teknisi', $unit, $password, 'admin');
        $rows[] = $created;
        return $rows;
    });
    return [$created, $error];
}

/**
 * Alur masuk:
 *  1. Username/nama + password cocok dengan akun yang ada  -> masuk ke akun tersebut (riwayat tampil).
 *  2. Username/nama milik akun Admin / Tim IT tetapi password salah -> ditolak.
 *  3. Selain itu (belum ada / password berbeda pada akun Pegawai) -> akun Pegawai baru dibuat otomatis.
 * @return array [status, rekaman|null, pesan|null]   status: ok | created | created_dup | denied | invalid
 */
function account_login(string $ident, string $password): array {
    $ident = normalize_identifier($ident);
    $matches = users_by_identifier($ident);
    foreach ($matches as $rec) {
        if (!empty($rec['active']) && password_verify($password, (string)$rec['password_hash'])) return ['ok', $rec, null];
    }
    foreach ($matches as $rec) {
        if ($rec['role'] !== 'pegawai') return ['denied', null, 'Username atau password salah.'];
    }
    if (!valid_username($ident)) return ['invalid', null, 'Username 3-60 karakter: huruf, angka, spasi, titik, garis bawah, atau strip.'];
    if (!valid_password($password)) return ['invalid', null, 'Untuk membuat akun baru, password harus ' . PW_MIN . '-' . PW_MAX . ' karakter.'];
    $created = null;
    json_update('users', function (array $rows) use ($ident, $password, &$created) {
        $id = user_allocate_id($rows);
        if ($id === null) return $rows;
        $created = new_user_record($id, $ident, $ident, 'pegawai', 'Kantor Cabang / Unit', $password, 'auto');
        $rows[] = $created;
        return $rows;
    });
    if (!$created) return ['invalid', null, 'Akun gagal disimpan. Penyimpanan data tidak tersedia.'];
    return [$matches ? 'created_dup' : 'created', $created, null];
}

/* =====================================================================
 *  Tiket (permanen)
 * ===================================================================== */
function seed_tickets(): array {
    $d = date('Y-m-d');
    $mk = function (int $id, int $rid, string $rname, string $unit, string $loc, string $cat, string $dev, string $svc, string $desc, string $t, string $status, ?int $aid, ?string $aname, array $notes, array $hist) use ($d) {
        return [
            'id' => $id, 'ticket' => 'IT-' . date('Ymd') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT), 'reporter_id' => $rid, 'reporter' => $rname,
            'unit' => $unit, 'location' => $loc, 'category' => $cat, 'device' => $dev, 'service_type' => $svc, 'description' => $desc,
            'created_at' => "$d $t:00", 'queue_entered_at' => "$d $t:00", 'status' => $status, 'assignee_id' => $aid, 'assignee' => $aname,
            'notes' => $notes, 'history' => array_map(fn($h) => ['time' => "$d {$h[0]}:00", 'status' => $h[1], 'note' => $h[2]], $hist),
        ];
    };
    return [
        $mk(1, 1, 'Rani Putri', 'KC Iskandar Muda', 'Lantai 2 - Bagian Operasional', 'Komputer / Laptop', 'Laptop Lenovo ThinkPad', 'Gangguan', 'Laptop sering mengalami restart sendiri ketika digunakan untuk pekerjaan administrasi.', '08:15', 'menunggu', null, null, [], [['08:15', 'Menunggu', 'Pengaduan dibuat oleh pelapor.']]),
        $mk(2, 4, 'Dimas Saputra', 'Unit Medan Baru', 'Ruang Teller', 'Printer', 'Printer laser', 'Gangguan', 'Printer tidak menarik kertas dan hasil cetak belum keluar.', '08:32', 'menunggu', null, null, [], [['08:32', 'Menunggu', 'Pengaduan dibuat oleh pelapor.']]),
        $mk(3, 1, 'Rani Putri', 'KC Iskandar Muda', 'Ruang Monitoring', 'CCTV', 'CCTV IP Camera', 'Gangguan', 'Salah satu kamera tidak menampilkan gambar pada monitor pemantauan.', '09:10', 'diproses', 2, 'Fajar Pratama', ['Pemeriksaan koneksi dan adaptor daya sedang dilakukan.'], [['09:10', 'Menunggu', 'Pengaduan dibuat oleh pelapor.'], ['09:25', 'Diproses', 'Tiket diterima Fajar Pratama.']]),
        $mk(4, 4, 'Dimas Saputra', 'Unit Medan Baru', 'Ruang Layanan', 'Jaringan & Internet', 'PC layanan', 'Gangguan', 'Koneksi jaringan pada satu komputer terputus secara berkala.', '09:30', 'tindak_lanjut', 2, 'Fajar Pratama', ['Menunggu pemeriksaan lanjutan pada titik jaringan.'], [['09:30', 'Menunggu', 'Pengaduan dibuat oleh pelapor.'], ['09:50', 'Menunggu Tindak Lanjut', 'Pemeriksaan lanjutan diperlukan.']]),
        $mk(5, 4, 'Dimas Saputra', 'Unit Medan Baru', 'Ruang Administrasi', 'Software & Sistem Operasi', 'PC Desktop', 'Permintaan Layanan', 'Permintaan pemeriksaan aplikasi kerja yang tidak dapat dibuka.', '10:05', 'selesai', 2, 'Fajar Pratama', ['Pemeriksaan konfigurasi aplikasi selesai dilakukan.'], [['10:05', 'Menunggu', 'Pengaduan dibuat oleh pelapor.'], ['10:15', 'Diproses', 'Tiket diterima teknisi.'], ['10:45', 'Selesai', 'Pemeriksaan selesai.']]),
    ];
}
function tickets_ensure_seeded(): void {
    static $done = false;
    if ($done) return;
    if (!is_file(DATA_DIR . '/tickets.json')) json_update('tickets', fn($r) => $r ?: seed_tickets());
    $done = true;
}
function tickets_load(): array {
    tickets_ensure_seeded();
    return array_values(json_load('tickets'));
}
function ticket_save(array $updated): void {
    json_update('tickets', function (array $rows) use ($updated) {
        foreach ($rows as $i => $t) if ((int)$t['id'] === (int)$updated['id']) { $rows[$i] = $updated; break; }
        return $rows;
    });
}
/** Membuat tiket baru; ID & nomor ditentukan di dalam kunci file dan TIDAK dipakai ulang walau tiket dihapus. */
function ticket_create(array $user, array $f): array {
    tickets_ensure_seeded();
    $created = null;
    json_update('tickets', function (array $rows) use ($user, $f, &$created) {
        $day = date('Ymd'); $prefix = 'IT-' . $day . '-'; $id = 0; $seq = 0;
        json_update('counters', function (array $c) use ($rows, $day, $prefix, &$id, &$seq) {
            $maxId = 0; $maxSeq = 0;
            foreach ($rows as $t) {
                $maxId = max($maxId, (int)$t['id']);
                if (str_starts_with($t['ticket'], $prefix)) $maxSeq = max($maxSeq, (int)substr($t['ticket'], strlen($prefix)));
            }
            $id = max((int)($c['last_id'] ?? 0), $maxId) + 1;
            $seq = max((int)($c['seq'][$day] ?? 0), $maxSeq) + 1;
            return ['last_id' => $id, 'seq' => [$day => $seq]];
        });
        $now = date('Y-m-d H:i:s');
        $created = [
            'id' => $id, 'ticket' => $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT), 'reporter_id' => (int)$user['id'], 'reporter' => $user['name'],
            'unit' => $f['unit'], 'location' => $f['location'], 'category' => $f['category'], 'device' => $f['device'],
            'service_type' => $f['service_type'], 'description' => $f['description'], 'created_at' => $now, 'queue_entered_at' => $now,
            'status' => 'menunggu', 'assignee_id' => null, 'assignee' => null,
            'notes' => [], 'history' => [['time' => $now, 'status' => 'Menunggu', 'note' => 'Pengaduan dibuat oleh pelapor.']],
        ];
        $rows[] = $created;
        return $rows;
    });
    return $created;
}
/** Menghapus tiket. @return array|null data tiket yang dihapus (null bila tidak ditemukan) */
function ticket_delete(int $id): ?array {
    tickets_ensure_seeded();
    $removed = null;
    json_update('tickets', function (array $rows) use ($id, &$removed) {
        $keep = [];
        foreach ($rows as $t) { if ((int)$t['id'] === $id && $removed === null) $removed = $t; else $keep[] = $t; }
        return $keep;
    });
    return $removed;
}
/** Selaraskan nama pelapor/teknisi pada tiket saat nama profil diganti. */
function tickets_rename_user(int $userId, string $name): void {
    tickets_ensure_seeded();
    json_update('tickets', function (array $rows) use ($userId, $name) {
        foreach ($rows as &$t) {
            if ((int)$t['reporter_id'] === $userId) $t['reporter'] = $name;
            if ((int)($t['assignee_id'] ?? 0) === $userId) $t['assignee'] = $name;
        }
        return $rows;
    });
}
