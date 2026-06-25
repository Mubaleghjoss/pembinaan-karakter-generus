-- PKG DB schema (MySQL 8, InnoDB, utf8mb4)
SET NAMES utf8mb4;

DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS pelaporan_detail;
DROP TABLE IF EXISTS pelaporan;
DROP TABLE IF EXISTS indikator;
DROP TABLE IF EXISTS presensi;
DROP TABLE IF EXISTS kegiatan;
DROP TABLE IF EXISTS materi;
DROP TABLE IF EXISTS berita;
DROP TABLE IF EXISTS siswa_ortu;
DROP TABLE IF EXISTS ortu;
DROP TABLE IF EXISTS siswa;
DROP TABLE IF EXISTS kelas;
DROP TABLE IF EXISTS pamong;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(32) UNIQUE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(120) UNIQUE NOT NULL,
  email VARCHAR(160) UNIQUE NULL,
  phone VARCHAR(30) NULL,
  password VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE pamong (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  nama VARCHAR(120) NOT NULL,
  phone VARCHAR(30), email VARCHAR(160),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE kelas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL,
  tingkat VARCHAR(20),
  pamong_id INT,
  FOREIGN KEY (pamong_id) REFERENCES pamong(id)
) ENGINE=InnoDB;

CREATE TABLE siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nis VARCHAR(30) UNIQUE,
  nama VARCHAR(120) NOT NULL,
  jk ENUM('L','P'),
  tgl_lahir DATE,
  alamat VARCHAR(255),
  kelas_id INT,
  foto_path VARCHAR(255),
  status ENUM('active','inactive') DEFAULT 'active',
  qr_secret_salt VARCHAR(64),
  FOREIGN KEY (kelas_id) REFERENCES kelas(id)
) ENGINE=InnoDB;

CREATE TABLE ortu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  hubungan VARCHAR(40),
  phone VARCHAR(30),
  email VARCHAR(160)
) ENGINE=InnoDB;

CREATE TABLE siswa_ortu (
  siswa_id INT NOT NULL,
  ortu_id INT NOT NULL,
  tipe VARCHAR(30),
  PRIMARY KEY (siswa_id, ortu_id),
  FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
  FOREIGN KEY (ortu_id) REFERENCES ortu(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE berita (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(180) NOT NULL,
  slug VARCHAR(200) UNIQUE NOT NULL,
  isi MEDIUMTEXT NOT NULL,
  cover_path VARCHAR(255),
  tags VARCHAR(200),
  status ENUM('draft','published') DEFAULT 'draft',
  published_at DATETIME NULL,
  author_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE materi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bulan TINYINT NOT NULL,
  tahun SMALLINT NOT NULL,
  judul VARCHAR(160) NOT NULL,
  ringkasan TEXT,
  file_path VARCHAR(255),
  video_url VARCHAR(255),
  publik TINYINT(1) DEFAULT 1,
  versi VARCHAR(20) DEFAULT 'v1',
  author_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE kegiatan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  tanggal DATE NOT NULL,
  lokasi VARCHAR(120),
  kelas_id INT,
  tipe VARCHAR(40),
  status VARCHAR(20) DEFAULT 'aktif',
  FOREIGN KEY (kelas_id) REFERENCES kelas(id)
) ENGINE=InnoDB;

CREATE TABLE presensi (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kegiatan_id INT NOT NULL,
  siswa_id INT NOT NULL,
  waktu_scan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  mode ENUM('MASUK','KELUAR','LAINNYA') DEFAULT 'MASUK',
  device VARCHAR(60),
  valid TINYINT(1) DEFAULT 1,
  petugas_id INT,
  keterangan VARCHAR(255),
  uniq_key VARCHAR(100) NOT NULL,
  FOREIGN KEY (kegiatan_id) REFERENCES kegiatan(id) ON DELETE CASCADE,
  FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
  FOREIGN KEY (petugas_id) REFERENCES users(id),
  UNIQUE KEY uk_presensi (uniq_key),
  INDEX idx_presensi (kegiatan_id, siswa_id, waktu_scan)
) ENGINE=InnoDB;

CREATE TABLE indikator (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10) UNIQUE NOT NULL,
  nama VARCHAR(120) NOT NULL,
  bobot DECIMAL(5,2) DEFAULT 1.00,
  deskripsi TEXT,
  aktif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE pelaporan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  kelas_id INT NOT NULL,
  pamong_id INT NOT NULL,
  kegiatan_id INT NULL,
  catatan TEXT,
  FOREIGN KEY (kelas_id) REFERENCES kelas(id),
  FOREIGN KEY (pamong_id) REFERENCES pamong(id),
  FOREIGN KEY (kegiatan_id) REFERENCES kegiatan(id)
) ENGINE=InnoDB;

CREATE TABLE pelaporan_detail (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  pelaporan_id INT NOT NULL,
  siswa_id INT NOT NULL,
  indikator_id INT NOT NULL,
  skor TINYINT NOT NULL,
  catatan TEXT,
  evidence_path VARCHAR(255),
  FOREIGN KEY (pelaporan_id) REFERENCES pelaporan(id) ON DELETE CASCADE,
  FOREIGN KEY (siswa_id) REFERENCES siswa(id),
  FOREIGN KEY (indikator_id) REFERENCES indikator(id)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  aksi VARCHAR(40),
  objek_tabel VARCHAR(40),
  objek_id BIGINT,
  ip VARCHAR(45),
  user_agent VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit (user_id, created_at)
) ENGINE=InnoDB;

-- seed minimal roles
INSERT INTO roles(name) VALUES ('admin'),('user');
