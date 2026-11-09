<?php
// includes/header.php
require_once __DIR__ . '/icons.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT Yang Penting Jalan - Showroom Mobil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/project_PTYangpentingMaju/assets/css/style.css?v=6">
    <script>document.documentElement.className += ' js';</script>
</head>
<body>
    <header class="auto-header">
        <div class="container" style="display: flex; align-items: center; gap: 1rem; justify-content: space-between; flex-wrap: wrap;">
            <a href="/project_PTYangpentingMaju/index.php" aria-label="PT Yangpenting Maju - Beranda" style="display: inline-flex; align-items: center;"><img src="/project_PTYangpentingMaju/assets/images/logo-ypj.png" alt="YPJ - Yang Penting Jalan" class="brand-logo" onerror="this.outerHTML='<b class=brand-fallback>YPJ</b>'"></a>
            <nav class="auto-pill-nav">
                <ul>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="/project_PTYangpentingMaju/katalog_mobil.php">Katalog Mobil</a></li>
                        <li><a href="/project_PTYangpentingMaju/katalog_sparepart.php">Katalog Sparepart</a></li>
                        <li><a href="/project_PTYangpentingMaju/servis.php">Servis</a></li>
                        <li><a href="/project_PTYangpentingMaju/auth/login.php" class="btn">Login</a></li>
                    <?php elseif ($_SESSION['role_nama'] == 'customer'): ?>
                        <li><a href="/project_PTYangpentingMaju/katalog_mobil.php">Katalog Mobil</a></li>
                        <li><a href="/project_PTYangpentingMaju/katalog_sparepart.php">Katalog Sparepart</a></li>
                        <li><a href="/project_PTYangpentingMaju/servis.php">Servis</a></li>
                        <li><a href="/project_PTYangpentingMaju/keranjang.php">Keranjang</a></li>
                        <li><a href="/project_PTYangpentingMaju/chat.php">Chat</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/pesanan_saya.php">Pesanan Saya</a></li>
                        <li><?php require_once __DIR__ . '/notifikasi.php'; ?></li>
                        <li><a href="/project_PTYangpentingMaju/auth/logout.php">Logout</a></li>
                    <?php elseif ($_SESSION['role_nama'] == 'marketing'): ?>
                        <li><a href="/project_PTYangpentingMaju/dashboard/stok_readonly.php">Stok Read-Only</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/chat_antrian.php">Antrian Chat</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/chat_aktif.php">Chat Aktif</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/pengajuan_beli.php">Pengajuan Beli</a></li>
                        <li><?php require_once __DIR__ . '/notifikasi.php'; ?></li>
                        <li><a href="/project_PTYangpentingMaju/auth/logout.php">Logout</a></li>
                    <?php elseif ($_SESSION['role_nama'] == 'admin_gudang'): ?>
                        <li><a href="/project_PTYangpentingMaju/dashboard/kelola_mobil.php">Kelola Stok Mobil</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/kelola_sparepart.php">Kelola Stok Sparepart</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/log_stok.php">Log Stok</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/kelola_user.php">Kelola User</a></li>
                        <li><?php require_once __DIR__ . '/notifikasi.php'; ?></li>
                        <li><a href="/project_PTYangpentingMaju/auth/logout.php">Logout</a></li>
                    <?php elseif ($_SESSION['role_nama'] == 'teknisi'): ?>
                        <li><a href="/project_PTYangpentingMaju/dashboard/jadwal_servis_saya.php">Jadwal Servis Saya</a></li>
                        <li><?php require_once __DIR__ . '/notifikasi.php'; ?></li>
                        <li><a href="/project_PTYangpentingMaju/auth/logout.php">Logout</a></li>
                    <?php elseif ($_SESSION['role_nama'] == 'admin_servis'): ?>
                        <li><a href="/project_PTYangpentingMaju/dashboard/kelola_jadwal.php">Kelola Jadwal</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/chat_servis.php">Chat Servis</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/kelola_transaksi.php">Transaksi Sparepart</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/stok_readonly.php">Stok Read-Only</a></li>
                        <li><?php require_once __DIR__ . '/notifikasi.php'; ?></li>
                        <li><a href="/project_PTYangpentingMaju/auth/logout.php">Logout</a></li>
                    <?php elseif ($_SESSION['role_nama'] == 'owner'): ?>
                        <li><a href="/project_PTYangpentingMaju/dashboard/laporan_penjualan.php">Laporan Penjualan</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/performa_marketing.php">Performa Marketing</a></li>
                        <li><a href="/project_PTYangpentingMaju/dashboard/laporan_keuangan.php">Laporan Keuangan</a></li>
                        <li><?php require_once __DIR__ . '/notifikasi.php'; ?></li>
                        <li><a href="/project_PTYangpentingMaju/auth/logout.php">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>
