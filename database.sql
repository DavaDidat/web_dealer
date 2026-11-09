CREATE DATABASE IF NOT EXISTS dealer_mobil;
USE dealer_mobil;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(50) NOT NULL
);

INSERT INTO roles (id, nama_role) VALUES 
(1, 'admin_gudang'), 
(2, 'marketing'), 
(3, 'teknisi'), 
(4, 'admin_servis'), 
(5, 'customer'), 
(6, 'owner');

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

CREATE TABLE mobil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    cc INT NOT NULL,
    tahun INT NOT NULL,
    warna VARCHAR(50) NOT NULL,
    harga DECIMAL(15,2) NOT NULL,
    deskripsi TEXT,
    stok INT NOT NULL DEFAULT 0,
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sparepart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    kompatibilitas TEXT,
    harga DECIMAL(15,2) NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    butuh_instalasi BOOLEAN NOT NULL DEFAULT FALSE,
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE log_stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    tipe_item ENUM('mobil', 'sparepart') NOT NULL,
    user_id INT NOT NULL,
    jumlah_sebelum INT NOT NULL,
    jumlah_sesudah INT NOT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE chat_room (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    staff_id INT,
    role_staff ENUM('marketing', 'admin_servis'),
    status ENUM('antri', 'aktif', 'selesai') DEFAULT 'antri',
    waktu_mulai TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waktu_selesai TIMESTAMP NULL,
    waktu_aktivitas TIMESTAMP NULL,
    typing_customer TIMESTAMP NULL,
    typing_staff TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE chat_pesan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_room_id INT NOT NULL,
    pengirim_id INT NOT NULL,
    isi_pesan TEXT NOT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_baca BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (chat_room_id) REFERENCES chat_room(id) ON DELETE CASCADE,
    FOREIGN KEY (pengirim_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE pengajuan_beli_mobil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    mobil_id INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    status ENUM('menunggu', 'diproses', 'selesai', 'dibatalkan') DEFAULT 'menunggu',
    catatan TEXT,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mobil_id) REFERENCES mobil(id) ON DELETE CASCADE
);

CREATE TABLE keranjang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    sparepart_id INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sparepart_id) REFERENCES sparepart(id) ON DELETE CASCADE
);

CREATE TABLE transaksi_sparepart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    metode_ambil ENUM('toko', 'servis') NOT NULL,
    status ENUM('menunggu', 'selesai', 'dibatalkan') DEFAULT 'menunggu',
    total DECIMAL(15,2) NOT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE transaksi_sparepart_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    sparepart_id INT NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi_sparepart(id) ON DELETE CASCADE,
    FOREIGN KEY (sparepart_id) REFERENCES sparepart(id) ON DELETE RESTRICT
);

CREATE TABLE jadwal_servis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    teknisi_id INT,
    admin_servis_id INT NULL,
    transaksi_sparepart_id INT NULL,
    waktu_mulai DATETIME NOT NULL,
    durasi_menit INT NOT NULL DEFAULT 60,
    status ENUM('dijadwalkan', 'berjalan', 'selesai', 'batal') DEFAULT 'dijadwalkan',
    catatan TEXT,
    biaya_jasa DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teknisi_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_servis_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (transaksi_sparepart_id) REFERENCES transaksi_sparepart(id) ON DELETE SET NULL
);

CREATE TABLE riwayat_servis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    jadwal_servis_id INT NOT NULL,
    sparepart_terpasang TEXT,
    biaya_jasa DECIMAL(15,2) NOT NULL DEFAULT 0,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (jadwal_servis_id) REFERENCES jadwal_servis(id) ON DELETE CASCADE
);

CREATE TABLE notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pesan TEXT NOT NULL,
    dibaca BOOLEAN DEFAULT FALSE,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE password_reset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    berhasil BOOLEAN NOT NULL DEFAULT FALSE,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_email (email),
    INDEX idx_login_ip (ip_address),
    INDEX idx_login_waktu (waktu)
);
