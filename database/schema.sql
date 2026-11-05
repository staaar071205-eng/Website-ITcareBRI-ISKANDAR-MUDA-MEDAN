CREATE DATABASE IF NOT EXISTS dbhelpdesk
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dbhelpdesk;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE locations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  location_type ENUM('cabang','unit','lainnya') NOT NULL DEFAULT 'unit',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_location_name (name)
) ENGINE=InnoDB;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  employee_code VARCHAR(60) NULL,
  full_name VARCHAR(150) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(160) NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
  CONSTRAINT fk_users_location FOREIGN KEY (location_id) REFERENCES locations(id)
) ENGINE=InnoDB;

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  category_type ENUM('gangguan','permintaan','keduanya') NOT NULL DEFAULT 'keduanya',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE devices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  device_type VARCHAR(80) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE complaints (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_number VARCHAR(40) NOT NULL UNIQUE,
  reporter_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  device_id BIGINT UNSIGNED NULL,
  service_type ENUM('gangguan','permintaan_layanan') NOT NULL,
  detailed_location VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('menunggu','diproses','tindak_lanjut','selesai','ditolak','dibatalkan','ditutup') NOT NULL DEFAULT 'menunggu',
  created_at DATETIME NOT NULL,
  resolved_at DATETIME NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_complaints_reporter FOREIGN KEY (reporter_id) REFERENCES users(id),
  CONSTRAINT fk_complaints_location FOREIGN KEY (location_id) REFERENCES locations(id),
  CONSTRAINT fk_complaints_category FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_complaints_device FOREIGN KEY (device_id) REFERENCES devices(id),
  KEY idx_complaints_reporter (reporter_id),
  KEY idx_complaints_status_created (status, created_at),
  KEY idx_complaints_location (location_id)
) ENGINE=InnoDB;

-- Satu tiket dapat memiliki lebih dari satu penugasan jika kebijakan operasional mengizinkan.
CREATE TABLE assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  complaint_id BIGINT UNSIGNED NOT NULL,
  technician_id BIGINT UNSIGNED NOT NULL,
  assigned_at DATETIME NOT NULL,
  released_at DATETIME NULL,
  assignment_status ENUM('aktif','selesai','dialihkan') NOT NULL DEFAULT 'aktif',
  CONSTRAINT fk_assignments_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id),
  CONSTRAINT fk_assignments_technician FOREIGN KEY (technician_id) REFERENCES users(id),
  KEY idx_assignments_technician_status (technician_id, assignment_status),
  KEY idx_assignments_complaint (complaint_id)
) ENGINE=InnoDB;

-- Waktu antrean aktif dapat berubah ketika tiket diaktifkan kembali setelah tindak lanjut.
-- Waktu pengaduan awal tetap tersimpan di complaints.created_at.
CREATE TABLE ticket_queues (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  complaint_id BIGINT UNSIGNED NOT NULL,
  queue_entered_at DATETIME NOT NULL,
  queue_state ENUM('waiting','claimed','removed') NOT NULL DEFAULT 'waiting',
  claimed_by BIGINT UNSIGNED NULL,
  claimed_at DATETIME NULL,
  CONSTRAINT fk_queues_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id),
  CONSTRAINT fk_queues_claimed_by FOREIGN KEY (claimed_by) REFERENCES users(id),
  KEY idx_queue_order (queue_state, queue_entered_at, id),
  KEY idx_queue_complaint (complaint_id)
) ENGINE=InnoDB;

CREATE TABLE complaint_histories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  complaint_id BIGINT UNSIGNED NOT NULL,
  actor_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(40) NULL,
  new_status VARCHAR(40) NOT NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_history_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id),
  CONSTRAINT fk_history_actor FOREIGN KEY (actor_id) REFERENCES users(id),
  KEY idx_history_complaint_time (complaint_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  complaint_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_attachments_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id),
  CONSTRAINT fk_attachments_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT INTO roles (name) VALUES ('pegawai'), ('teknisi'), ('admin');
INSERT INTO categories (name, category_type) VALUES
('Komputer / Laptop','keduanya'),('Printer','keduanya'),('CCTV','gangguan'),
('Jaringan & Internet','keduanya'),('Software & Sistem Operasi','keduanya'),
('Perangkat Pendukung','keduanya'),('Permintaan Instalasi','permintaan'),('Lainnya','keduanya');
INSERT INTO devices (name, device_type) VALUES
('PC Desktop','Komputer'),('Laptop','Komputer'),('Printer','Periferal'),
('CCTV IP Camera','Keamanan'),('Router / Switch','Jaringan'),('Monitor','Periferal'),('Perangkat lainnya','Lainnya');
