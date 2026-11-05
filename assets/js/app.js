document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.togglePassword);
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      button.textContent = input.type === 'password' ? 'Lihat' : 'Sembunyikan';
    });
  });

  const menuButton = document.getElementById('mobileMenu');
  const sidebar = document.getElementById('sidebar');
  if (menuButton && sidebar) menuButton.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', (event) => {
    if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(event.target) && !event.target.closest('#mobileMenu')) sidebar.classList.remove('open');
  });

  document.querySelectorAll('[data-demo-action]').forEach((button) => {
    button.addEventListener('click', () => window.alert(button.dataset.demoAction));
  });

  document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      const value = button.dataset.copy || '';
      try {
        await navigator.clipboard.writeText(value);
        const old = button.textContent;
        button.textContent = 'Tersalin ✓';
        setTimeout(() => button.textContent = old, 1600);
      } catch (_) {
        window.prompt('Salin nomor tiket berikut:', value);
      }
    });
  });

  const description = document.getElementById('description');
  const charCount = document.getElementById('charCount');
  if (description && charCount) {
    const updateCount = () => charCount.textContent = `${description.value.length} / 1000`;
    description.addEventListener('input', updateCount);
    updateCount();
  }

  const fileInput = document.getElementById('evidence');
  const chooseFile = document.getElementById('chooseFile');
  const fileName = document.getElementById('fileName');
  const uploadZone = document.getElementById('uploadZone');
  if (chooseFile && fileInput) chooseFile.addEventListener('click', () => fileInput.click());
  if (fileInput && fileName) fileInput.addEventListener('change', () => {
    const file = fileInput.files && fileInput.files[0];
    fileName.textContent = file ? `File dipilih: ${file.name} (pratinjau; belum diunggah)` : '';
  });
  if (uploadZone && fileInput) {
    ['dragenter', 'dragover'].forEach(eventName => uploadZone.addEventListener(eventName, event => {
      event.preventDefault(); uploadZone.classList.add('dragover');
    }));
    ['dragleave', 'drop'].forEach(eventName => uploadZone.addEventListener(eventName, event => {
      event.preventDefault(); uploadZone.classList.remove('dragover');
    }));
    uploadZone.addEventListener('drop', event => {
      if (event.dataTransfer && event.dataTransfer.files.length) {
        fileInput.files = event.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
      }
    });
  }
});

/* ===== Versi 3: notifikasi, pratinjau foto profil, FAQ, penghitung pesan ===== */
document.addEventListener('DOMContentLoaded', () => {
  // Notifikasi (lonceng)
  const notif = document.getElementById('notif');
  const btn = document.getElementById('notifBtn');
  const menu = document.getElementById('notifMenu');
  if (notif && btn && menu) {
    const close = () => { menu.hidden = true; btn.setAttribute('aria-expanded', 'false'); };
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = menu.hidden;
      menu.hidden = !open;
      btn.setAttribute('aria-expanded', String(open));
      const dot = document.getElementById('notifDot');
      if (open && dot) {
        dot.remove();
        const body = new URLSearchParams({ csrf_token: notif.dataset.csrf || '' });
        fetch('notif_seen.php', { method: 'POST', body, credentials: 'same-origin' }).catch(() => {});
      }
    });
    document.addEventListener('click', (e) => { if (!notif.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  }

  // Pratinjau foto profil
  const fileInput = document.getElementById('avatar');
  const preview = document.getElementById('photoPreview');
  const hint = document.getElementById('photoHint');
  const removeBox = document.getElementById('removeAvatar');
  if (fileInput && preview) {
    const original = preview.innerHTML;
    const showInitials = () => {
      preview.innerHTML = '<span class="avatar avatar-xl ' + preview.dataset.color + '">' + preview.dataset.initials + '</span>';
    };
    fileInput.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0];
      hint.classList.remove('photo-hint-error');
      if (!file) { preview.innerHTML = original; return; }
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
        hint.textContent = 'Format harus JPG, PNG atau WEBP.'; hint.classList.add('photo-hint-error'); fileInput.value = ''; return;
      }
      if (file.size > 2 * 1024 * 1024) {
        hint.textContent = 'Ukuran foto ' + (file.size / 1048576).toFixed(1) + ' MB melebihi batas 2 MB.'; hint.classList.add('photo-hint-error'); fileInput.value = ''; return;
      }
      const url = URL.createObjectURL(file);
      preview.innerHTML = '<span class="avatar avatar-xl has-photo"><img alt="Pratinjau foto baru" src="' + url + '"></span>';
      hint.textContent = 'Pratinjau: ' + file.name + '. Tekan "Simpan perubahan" untuk menyimpan.';
      if (removeBox) removeBox.checked = false;
    });
    if (removeBox) removeBox.addEventListener('change', () => {
      if (removeBox.checked) { showInitials(); fileInput.value = ''; } else preview.innerHTML = original;
    });
  }

  // Pencarian FAQ
  const faqSearch = document.getElementById('faqSearch');
  if (faqSearch) {
    const items = [...document.querySelectorAll('.faq-item')];
    const empty = document.getElementById('faqEmpty');
    faqSearch.addEventListener('input', () => {
      const q = faqSearch.value.trim().toLowerCase();
      let shown = 0;
      items.forEach((it) => {
        const match = !q || it.textContent.toLowerCase().includes(q);
        it.hidden = !match; if (match) shown++;
        if (q && match) it.open = true;
      });
      if (empty) empty.hidden = shown !== 0;
    });
  }

  // Penghitung karakter pesan bantuan
  const helpMsg = document.getElementById('helpMessage');
  const helpCount = document.getElementById('helpCount');
  if (helpMsg && helpCount) {
    const upd = () => helpCount.textContent = helpMsg.value.length + ' / 600';
    helpMsg.addEventListener('input', upd); upd();
  }
});

/* ===== Versi 4: dialog, lihat password, tampilkan password, cari pengguna ===== */
document.addEventListener('DOMContentLoaded', () => {
  // Dialog
  document.querySelectorAll('[data-open-dialog]').forEach((b) => b.addEventListener('click', () => {
    const d = document.getElementById(b.dataset.openDialog); if (d && d.showModal) d.showModal();
  }));
  document.querySelectorAll('dialog.modal').forEach((d) => {
    d.addEventListener('click', (e) => { if (e.target === d) d.close(); });
    d.querySelectorAll('[data-close-dialog]').forEach((b) => b.addEventListener('click', () => d.close()));
    d.addEventListener('close', () => { d.querySelectorAll('input[type=password],input[type=text].was-password').forEach((i) => { i.value = ''; i.type = 'password'; });
      d.querySelectorAll('[data-show-passwords]').forEach((c) => c.checked = false); });
  });

  // Dialog kelola password
  const passDialog = document.getElementById('passDialog');
  document.querySelectorAll('[data-pass-user]').forEach((b) => b.addEventListener('click', () => {
    if (!passDialog) return;
    document.getElementById('passUserId').value = b.dataset.passUser;
    document.getElementById('passName').textContent = b.dataset.passName;
    document.getElementById('passMeta').textContent = '@' + b.dataset.passUsername + ' · ' + b.dataset.passRole;
    const av = document.getElementById('passAvatar');
    av.textContent = (b.dataset.passName.trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('') || '?').toUpperCase();
    passDialog.showModal();
    const inp = document.getElementById('passNew'); if (inp) inp.focus();
  }));

  // Tampilkan / sembunyikan password pada formulir
  document.querySelectorAll('[data-show-passwords]').forEach((c) => c.addEventListener('change', () => {
    const scope = c.closest('form'); if (!scope) return;
    scope.querySelectorAll('input.pw-field, input[type=password], input.was-password').forEach((i) => {
      i.type = c.checked ? 'text' : 'password'; i.classList.add('was-password');
    });
  }));

  // Lihat password pada daftar pengguna
  document.querySelectorAll('[data-pw-toggle]').forEach((b) => b.addEventListener('click', () => {
    const cell = b.closest('.pw-cell'); const mask = cell.querySelector('.pw-mask'); const text = cell.querySelector('.pw-text');
    const show = text.hidden; text.hidden = !show; mask.hidden = show; b.textContent = show ? 'Sembunyikan' : 'Lihat';
  }));

  // Cari pengguna
  const us = document.getElementById('userSearch');
  if (us) {
    const rows = Array.from(document.querySelectorAll('#userTable tbody tr')); const empty = document.getElementById('userEmpty');
    us.addEventListener('input', () => {
      const q = us.value.trim().toLowerCase(); let n = 0;
      rows.forEach((r) => { const hit = !q || r.textContent.toLowerCase().includes(q); r.hidden = !hit; if (hit) n++; });
      if (empty) empty.hidden = n !== 0;
    });
  }
});

/* ===== Hapus tiket (dialog konfirmasi) ===== */
document.addEventListener('DOMContentLoaded', () => {
  const dlg = document.getElementById('deleteTicketDialog');
  if (dlg) document.querySelectorAll('[data-delete-ticket]').forEach((b) => b.addEventListener('click', () => {
    document.getElementById('delTicketId').value = b.dataset.deleteTicket;
    document.getElementById('delNo').textContent = b.dataset.deleteNo;
    document.getElementById('delInfo').textContent = b.dataset.deleteInfo;
    dlg.showModal();
  }));
  const userDlg = document.getElementById('deleteUserDialog');
  if (userDlg) document.querySelectorAll('[data-delete-user]').forEach((b) => b.addEventListener('click', () => {
    document.getElementById('deleteUserId').value = b.dataset.deleteUser;
    document.getElementById('deleteUserName').textContent = b.dataset.deleteName;
    document.getElementById('deleteUserMeta').textContent = b.dataset.deleteMeta;
    userDlg.showModal();
  }));
});
