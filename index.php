<?php
require __DIR__ . '/config/bootstrap.php';
if (!current_user()) {
    require __DIR__ . '/landing.php';
    exit;
}

$routes = [
    'dashboard-pegawai' => ['Dashboard Pegawai', ['pegawai'], 'dashboard_pegawai.php'],
    'dashboard-it' => ['Dashboard Tim IT', ['teknisi'], 'dashboard_it.php'],
    'dashboard-admin' => ['Dashboard Admin', ['admin'], 'dashboard_admin.php'],
    'form-pengaduan' => ['Buat Pengaduan', ['pegawai'], 'form_pengaduan.php'],
    'pengaduan-saya' => ['Pengaduan Saya', ['pegawai'], 'pengaduan_saya.php'],
    'detail-tiket' => ['Detail Tiket', ['pegawai','teknisi','admin'], 'detail_tiket.php'],
    'antrean-fifo' => ['Antrean FIFO', ['teknisi','admin'], 'antrean_fifo.php'],
    'tiket-saya' => ['Tiket Saya', ['teknisi'], 'tiket_saya.php'],
    'penanganan' => ['Penanganan', ['teknisi'], 'penanganan.php'],
    'riwayat' => ['Riwayat Pengaduan', ['pegawai','teknisi','admin'], 'riwayat.php'],
    'pengguna' => ['Manajemen Pengguna', ['admin'], 'pengguna.php'],
    'master-data' => ['Master Data', ['admin'], 'master_data.php'],
    'monitoring' => ['Monitoring Pengaduan', ['admin'], 'monitoring.php'],
    'laporan' => ['Laporan Pengaduan', ['teknisi','admin'], 'laporan.php'],
    'profil' => ['Profil Saya', ['pegawai','teknisi','admin'], 'profil.php'],
    'bantuan' => ['Pusat Bantuan', ['pegawai','teknisi','admin'], 'bantuan.php'],
];

$defaultPage = ['pegawai' => 'dashboard-pegawai', 'teknisi' => 'dashboard-it', 'admin' => 'dashboard-admin'][current_user()['role']];
$page = $_GET['page'] ?? $defaultPage;
if (!isset($routes[$page])) $page = $defaultPage;
[$pageTitle, $allowedRoles, $pageFile] = $routes[$page];
require_role($allowedRoles);

// Demo action handlers — akan dipindahkan ke controller + MySQL pada tahap backend.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Unggahan melebihi post_max_size PHP: $_POST dikosongkan oleh PHP
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Ukuran berkas yang dikirim terlalu besar. Foto profil maksimal 2 MB.'];
        redirect(url($page));
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.'];
        redirect(url($page));
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $user = current_user();
        $name  = trim(preg_replace('/\s+/u', ' ', (string)($_POST['name'] ?? '')));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $unit  = trim((string)($_POST['unit'] ?? ''));
        $errors = [];
        if ($name === '' || mb_strlen($name, 'UTF-8') > 60) $errors[] = 'Nama lengkap wajib diisi (maksimal 60 karakter).';
        if ($email !== '' && (strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL))) $errors[] = 'Format email tidak valid.';
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) $errors[] = 'Nomor telepon hanya boleh berisi angka, spasi, +, - dan tanda kurung (6-20 karakter).';
        if ($unit === '' || mb_strlen($unit, 'UTF-8') > 80) $errors[] = 'Unit / penempatan wajib diisi (maksimal 80 karakter).';
        [$newAvatar, $uploadError] = process_avatar_upload($_FILES['avatar'] ?? null);
        if ($uploadError) $errors[] = $uploadError;
        if ($errors) {
            if ($newAvatar) delete_avatar_file($newAvatar);
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode(' ', $errors)];
            redirect(url('profil'));
        }
        $avatar = $user['avatar'] ?? '';
        if ($newAvatar) { delete_avatar_file($avatar); $avatar = $newAvatar; }
        elseif (!empty($_POST['remove_avatar'])) { delete_avatar_file($avatar); $avatar = ''; }
        user_update_profile((int)$user['id'], ['name' => $name, 'email' => $email, 'phone' => $phone, 'unit' => $unit, 'avatar' => $avatar]);
        $_SESSION['user'] = array_merge($user, ['name' => $name, 'email' => $email, 'phone' => $phone, 'unit' => $unit, 'avatar' => $avatar]);
        tickets_rename_user((int)$user['id'], $name); // selaraskan nama pada tiket milik pengguna ini
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profil berhasil diperbarui.'];
        redirect(url('profil'));
    }

    if ($action === 'change_password') {
        $user = current_user();
        if ($user['role'] === 'pegawai') {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Password akun Pegawai hanya dapat diubah oleh Administrator.'];
            redirect(url('profil'));
        }
        $rec = user_find_by_id((int)$user['id']);
        $cur = (string)($_POST['current_password'] ?? ''); $new = (string)($_POST['new_password'] ?? ''); $conf = (string)($_POST['confirm_password'] ?? '');
        if (!$rec || !password_verify($cur, (string)$rec['password_hash'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Password saat ini tidak sesuai.'];
        } elseif (!valid_password($new)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Password baru harus ' . PW_MIN . '-' . PW_MAX . ' karakter.'];
        } elseif ($new !== $conf) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Konfirmasi password baru tidak sama.'];
        } elseif ($new === $cur) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Password baru sama dengan password saat ini.'];
        } else {
            user_set_password((int)$user['id'], $new, 'self');
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password berhasil diubah dan tersimpan pada Daftar Pengguna.'];
        }
        redirect(url('profil'));
    }
    if ($action === 'admin_set_password' && current_user()['role'] === 'admin') {
        $target = user_find_by_id((int)($_POST['user_id'] ?? 0));
        $new = (string)($_POST['new_password'] ?? '');
        if (!$target) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Akun tidak ditemukan.'];
        } elseif (!valid_password($new)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Password baru harus ' . PW_MIN . '-' . PW_MAX . ' karakter.'];
        } else {
            user_set_password((int)$target['id'], $new, 'admin');
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password akun ' . $target['name'] . ' berhasil diperbarui.'];
        }
        redirect(url('pengguna'));
    }
    if ($action === 'admin_add_tim_it' && current_user()['role'] === 'admin') {
        [$rec, $err] = user_create_tim_it((string)($_POST['name'] ?? ''), (string)($_POST['username'] ?? ''), (string)($_POST['unit'] ?? ''), (string)($_POST['password'] ?? ''));
        $_SESSION['flash'] = $rec
            ? ['type' => 'success', 'message' => 'Akun Tim IT "' . $rec['name'] . '" berhasil ditambahkan.']
            : ['type' => 'danger', 'message' => $err];
        redirect(url('pengguna'));
    }
    if ($action === 'admin_delete_user' && current_user()['role'] === 'admin') {
        $result = user_delete((int)($_POST['user_id'] ?? 0), (int)current_user()['id']);
        if ($result['status'] === 'deleted') {
            delete_avatar_file($result['user']['avatar'] ?? '');
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Akun "' . $result['user']['name'] . '" berhasil dihapus. Riwayat tiket dan bantuan tetap tersimpan.'];
        } elseif ($result['status'] === 'self') {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Anda tidak dapat menghapus akun yang sedang digunakan.'];
        } elseif ($result['status'] === 'last_admin') {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Administrator aktif terakhir tidak dapat dihapus.'];
        } elseif ($result['status'] === 'storage_error') {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Akun tidak dapat dihapus karena penyimpanan data tidak tersedia.'];
        } else {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Akun tidak ditemukan atau sudah dihapus.'];
        }
        redirect(url('pengguna'));
    }
    if ($action === 'send_help') {
        $subject = (string)($_POST['subject'] ?? '');
        $message = trim((string)($_POST['message'] ?? ''));
        $len = mb_strlen($message, 'UTF-8');
        if (!isset(help_subjects()[$subject])) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pilih topik bantuan terlebih dahulu.'];
        } elseif ($len < 10 || $len > 600) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pesan harus berisi 10 sampai 600 karakter.'];
        } else {
            help_add(current_user(), $subject, $message);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pesan terkirim ke administrator. Balasan akan muncul pada halaman ini.'];
        }
        redirect(url('bantuan'));
    }
    if ($action === 'reply_help' && current_user()['role'] === 'admin') {
        $mode = ($_POST['mode'] ?? '') === 'close' ? 'close' : 'reply';
        $reply = trim((string)($_POST['reply'] ?? ''));
        if ($mode === 'reply' && ($reply === '' || mb_strlen($reply, 'UTF-8') > 600)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Isi balasan (maksimal 600 karakter) sebelum mengirim.'];
        } elseif (help_respond((string)($_POST['help_id'] ?? ''), $mode, $reply, current_user()['name'])) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => $mode === 'close' ? 'Pesan ditandai selesai.' : 'Balasan terkirim.'];
        } else {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Pesan tidak ditemukan.'];
        }
        redirect(url('bantuan'));
    }
    if ($action === 'create_complaint' && current_user()['role'] === 'pegawai') {
        $required = ['service_type','category','unit','location','device','description'];
        $valid = true;
        foreach ($required as $field) if (trim($_POST[$field] ?? '') === '') $valid = false;
        if (!$valid) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Lengkapi seluruh kolom wajib sebelum mengirim pengaduan.'];
            redirect(url('form-pengaduan'));
        }
        $newTicket = ticket_create(current_user(), [
            'unit' => trim($_POST['unit']), 'location' => trim($_POST['location']), 'category' => trim($_POST['category']),
            'device' => trim($_POST['device']), 'service_type' => trim($_POST['service_type']), 'description' => trim($_POST['description']),
        ]);
        $id = $newTicket['id'];
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pengaduan berhasil dibuat. Nomor tiket: ' . $newTicket['ticket']];
        redirect(url('detail-tiket', ['id' => $id]));
    }
    if ($action === 'delete_ticket' && current_user()['role'] === 'admin') {
        $removed = ticket_delete((int)($_POST['ticket_id'] ?? 0));
        $_SESSION['flash'] = $removed
            ? ['type' => 'success', 'message' => 'Tiket ' . $removed['ticket'] . ' berhasil dihapus.']
            : ['type' => 'warning', 'message' => 'Tiket tidak ditemukan atau sudah dihapus.'];
        redirect(url('laporan'));
    }
    if ($action === 'accept_next' && current_user()['role'] === 'teknisi') {
        $queue = sort_waiting_tickets();
        if ($queue) {
            $t = $queue[0];
            $t['status'] = 'diproses';
            $t['assignee_id'] = current_user()['id'];
            $t['assignee'] = current_user()['name'];
            $now = date('Y-m-d H:i:s');
            $t['history'][] = ['time' => $now, 'status' => 'Diproses', 'note' => 'Tiket diterima oleh ' . current_user()['name'] . '.'];
            save_ticket($t);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Tiket ' . $t['ticket'] . ' berhasil diterima.'];
        } else {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Belum ada tiket dengan status Menunggu.'];
        }
        redirect(url('antrean-fifo'));
    }
    if ($action === 'update_ticket' && in_array(current_user()['role'], ['teknisi','admin'], true)) {
        $id = (int)($_POST['ticket_id'] ?? 0);
        $ticket = find_ticket($id);
        $status = $_POST['status'] ?? '';
        $allowed = ['diproses','tindak_lanjut','selesai','ditolak','menunggu'];
        if ($ticket && in_array($status, $allowed, true)) {
            if (current_user()['role'] === 'teknisi' && $ticket['assignee_id'] !== current_user()['id']) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pada prototipe ini, teknisi hanya dapat mengubah tiket yang menjadi tanggung jawabnya.'];
                redirect(url('penanganan'));
            }
            $note = trim($_POST['note'] ?? 'Pembaruan status tiket.');
            $oldStatus = $ticket['status'];
            $ticket['status'] = $status;
            if ($status === 'menunggu' && $oldStatus === 'tindak_lanjut') $ticket['queue_entered_at'] = date('Y-m-d H:i:s');
            $ticket['notes'][] = $note;
            $ticket['history'][] = ['time' => date('Y-m-d H:i:s'), 'status' => status_label($status), 'note' => $note];
            save_ticket($ticket);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pembaruan tiket berhasil disimpan.'];
        }
        redirect(url('detail-tiket', ['id' => $id]));
    }
}

require_once __DIR__ . '/includes/components.php';
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

if ($page === 'detail-tiket') {
    $ticket = find_ticket((int)($_GET['id'] ?? 0));
    if (!$ticket) { $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Tiket tidak ditemukan.']; redirect(url(current_user()['role']==='pegawai'?'pengaduan-saya':(current_user()['role']==='teknisi'?'tiket-saya':'monitoring'))); }
    if (current_user()['role'] === 'pegawai' && (int)$ticket['reporter_id'] !== (int)current_user()['id']) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Anda hanya dapat melihat tiket milik sendiri.']; redirect(url('pengaduan-saya'));
    }
}

?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · ITcare BRI</title><link rel="icon" type="image/svg+xml" href="assets/img/logo-bri.svg"><link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="app-body">
<?php require __DIR__ . '/includes/header.php'; ?>
<?php require __DIR__ . '/pages/' . $pageFile; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
