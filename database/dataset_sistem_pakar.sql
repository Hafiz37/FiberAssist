-- =========================================================
-- DATABASE SETUP & SEED DATASET SISTEM PAKAR FORWARD CHAINING
-- Kasus: Troubleshooting Gangguan Internet Modem ZTE F609
-- =========================================================

CREATE DATABASE IF NOT EXISTS db_sistem_pakar;
USE db_sistem_pakar;

-- ---------------------------------------------------------
-- 1. STRUKTUR TABEL GEJALA
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS gejala (
    id_gejala INT AUTO_INCREMENT PRIMARY KEY,
    kode_gejala VARCHAR(10) UNIQUE NOT NULL,
    nama_gejala VARCHAR(255) NOT NULL,
    pertanyaan_wa TEXT NOT NULL
);

-- ---------------------------------------------------------
-- 2. STRUKTUR TABEL KERUSAKAN & SOLUSI
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS kerusakan (
    id_kerusakan INT AUTO_INCREMENT PRIMARY KEY,
    kode_kerusakan VARCHAR(10) UNIQUE NOT NULL,
    nama_kerusakan VARCHAR(255) NOT NULL,
    solusi TEXT NOT NULL,
    butuh_teknisi TINYINT(1) DEFAULT 0
);

-- ---------------------------------------------------------
-- 3. STRUKTUR TABEL ATURAN FORWARD CHAINING
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS aturan (
    id_aturan INT AUTO_INCREMENT PRIMARY KEY,
    kode_rule VARCHAR(10) UNIQUE NOT NULL,
    id_gejala_1 VARCHAR(10) NOT NULL,
    id_gejala_2 VARCHAR(10) NOT NULL,
    id_kerusakan VARCHAR(10) NOT NULL,
    FOREIGN KEY (id_gejala_1) REFERENCES gejala(kode_gejala) ON DELETE CASCADE,
    FOREIGN KEY (id_gejala_2) REFERENCES gejala(kode_gejala) ON DELETE CASCADE,
    FOREIGN KEY (id_kerusakan) REFERENCES kerusakan(kode_kerusakan) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- 4. STRUKTUR TABEL SESI DIAGNOSA WHATSAPP
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS sesi_diagnosa (
    id_sesi INT AUTO_INCREMENT PRIMARY KEY,
    no_wa VARCHAR(20) NOT NULL,
    step INT DEFAULT 1,
    fakta_terkumpul JSON,
    status ENUM('ACTIVE', 'COMPLETED') DEFAULT 'ACTIVE',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- 5. STRUKTUR TABEL TIKET GANGGUAN
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS tiket_gangguan (
    id_tiket INT AUTO_INCREMENT PRIMARY KEY,
    no_tiket VARCHAR(30) UNIQUE NOT NULL,
    no_wa VARCHAR(20) NOT NULL,
    kode_kerusakan VARCHAR(10) NOT NULL,
    detail_diagnosa TEXT NOT NULL,
    status_tiket ENUM('OPEN', 'IN_PROGRESS', 'RESOLVED') DEFAULT 'OPEN',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kode_kerusakan) REFERENCES kerusakan(kode_kerusakan)
);

-- ---------------------------------------------------------
-- 6. STRUKTUR TABEL PENGGUNA (CS & ADMINISTRATOR)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('ADMIN', 'CS') NOT NULL DEFAULT 'CS',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- SEED DATASET INITIAL
-- =========================================================

-- 1. DATA TABEL GEJALA
INSERT INTO gejala (kode_gejala, nama_gejala, pertanyaan_wa) VALUES
('G01', 'Internet Mati Total', 'Apakah koneksi internet Anda mati total sama sekali?'),
('G02', 'Internet Lambat / Buffering', 'Apakah koneksi internet Anda terasa sangat lambat atau sering buffering?'),
('G03', 'Lampu LOS Berkedip Merah', 'Apakah indikator lampu LOS pada modem ZTE F609 Anda berkedip warna merah?'),
('G04', 'Lampu PON Mati / Off', 'Apakah lampu indikator PON pada modem dalam kondisi mati (Off)?'),
('G05', 'Lampu Power Mati / Off', 'Apakah lampu indikator Power pada modem mati total (Off)?'),
('G06', 'Kendala di Semua Perangkat', 'Apakah kendala ini dirasakan pada SEMUA perangkat (HP, Laptop, TV)?'),
('G07', 'Kendala di 1 Perangkat Saja', 'Apakah kendala ini HANYA terjadi pada 1 perangkat saja?'),
('G08', 'Lampu WLAN / WiFi Off', 'Apakah lampu indikator WLAN/WiFi di modem dalam kondisi mati (Off)?'),
('G09', 'DNS Error / Web tertentu', 'Apakah kendala terjadi saat membuka website tertentu (DNS Error)?'),
('G10', 'Status Tagihan / Isolir', 'Apakah status akun paket bulanan Anda memiliki tunggakan/isolir?')
ON DUPLICATE KEY UPDATE nama_gejala=VALUES(nama_gejala), pertanyaan_wa=VALUES(pertanyaan_wa);

-- 2. DATA TABEL KERUSAKAN & SOLUSI
INSERT INTO kerusakan (kode_kerusakan, nama_kerusakan, solusi, butuh_teknisi) VALUES
('K01', 'Kabel Fiber Optik Putus / Redaman High dBm', 'Lakukan perbaikan kabel fiber optik (splicing) & pengecekan nilai redaman di ODP/Kabel Rumah.', 1),
('K02', 'Loss Sinyal OLT / Gangguan Massal Pusat', 'Cek port OLT pusat dan verifikasi status binding registrasi ONT pada OLT.', 1),
('K03', 'Power Adapter Router Off / Bermasalah', 'Periksa kabel colokan adaptor ke stopkontak atau ganti adaptor modem ZTE F609 (12V 1A).', 0),
('K04', 'Kendala pada Perangkat Client', 'Lakukan Forget Network WiFi pada HP/Laptop, lalu sambungkan ulang atau restart perangkat client.', 0),
('K05', 'FUP Terlampaui / Network Congestion', 'Lakukan restart modem ZTE F609 dan periksa sisa kuota FUP paket bulanan melalui portal ISP.', 0),
('K06', 'Fitur WiFi Modem Non-Aktif', 'Tekan tombol WLAN di bagian samping modem ZTE F609 selama 3 detik hingga indikator WLAN menyala.', 0),
('K07', 'Server DNS ISP Bermasalah', 'Ganti DNS pada router atau perangkat client ke Google DNS (8.8.8.8 & 8.8.4.4).', 0),
('K08', 'Akun Terisolir (Belum Bayar Tagihan)', 'Bayar tagihan bulanan melalui kanal pembayaran resmi atau alihkan ke Customer Service Billing.', 1)
ON DUPLICATE KEY UPDATE nama_kerusakan=VALUES(nama_kerusakan), solusi=VALUES(solusi), butuh_teknisi=VALUES(butuh_teknisi);

-- 3. DATA TABEL ATURAN FORWARD CHAINING (Gejala 1 + Gejala 2 = Masalah)
INSERT INTO aturan (kode_rule, id_gejala_1, id_gejala_2, id_kerusakan) VALUES
('R01', 'G01', 'G03', 'K01'),
('R02', 'G01', 'G04', 'K02'),
('R03', 'G01', 'G05', 'K03'),
('R04', 'G02', 'G07', 'K04'),
('R05', 'G02', 'G06', 'K05'),
('R06', 'G01', 'G08', 'K06'),
('R07', 'G02', 'G09', 'K07'),
('R08', 'G01', 'G10', 'K08')
ON DUPLICATE KEY UPDATE id_gejala_1=VALUES(id_gejala_1), id_gejala_2=VALUES(id_gejala_2), id_kerusakan=VALUES(id_kerusakan);
