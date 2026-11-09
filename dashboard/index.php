<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$role = $_SESSION['role_nama'];

// Ikon SVG inline (offline, tanpa CDN maupun emoji) — gaya stroke 24px.
// Seluruh ikon di dashboard memakai $icon[...] agar konsisten.
$svg_open = '<svg class="ypj-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
$svg_close = '</svg>';
$icon = [
    'mobil'     => $svg_open . '<path d="M5 11l1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11"/><rect x="3" y="11" width="18" height="5" rx="2"/><circle cx="7.5" cy="18" r="1.6"/><circle cx="16.5" cy="18" r="1.6"/>' . $svg_close,
    'sparepart' => $svg_open . '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/>' . $svg_close,
    'log'       => $svg_open . '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>' . $svg_close,
    'user'      => $svg_open . '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' . $svg_close,
    'chat'      => $svg_open . '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8z"/>' . $svg_close,
    'dokumen'   => $svg_open . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>' . $svg_close,
    'teknisi'   => $svg_open . '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>' . $svg_close,
    'kalender'  => $svg_open . '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M10 15l2 2 4-4"/>' . $svg_close,
    'grafik'    => $svg_open . '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>' . $svg_close,
    'piala'     => $svg_open . '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>' . $svg_close,
    'keuangan'  => $svg_open . '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' . $svg_close,
    'paket'     => $svg_open . '<path d="M16.5 9.4L7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>' . $svg_close,
    'panah'     => '<svg class="ypj-icon ypj-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
];

$menu = [];
if ($role == 'admin_gudang') {
    $menu = [
        'Menu Admin Gudang' => [
            ['Kelola Stok Mobil', 'kelola_mobil.php', $icon['mobil']],
            ['Kelola Stok Sparepart', 'kelola_sparepart.php', $icon['sparepart']],
            ['Lihat Log Perubahan Stok', 'log_stok.php', $icon['log']],
            ['Kelola User', 'kelola_user.php', $icon['user']],
        ],
    ];
} elseif ($role == 'marketing') {
    $menu = [
        'Menu Marketing' => [
            ['Antrian Chat', 'chat_antrian.php', $icon['chat']],
            ['Daftar Pengajuan Pembelian', 'pengajuan_beli.php', $icon['dokumen']],
        ],
    ];
} elseif ($role == 'teknisi') {
    $menu = [
        'Menu Teknisi' => [
            ['Jadwal Servis Saya', 'jadwal_servis_saya.php', $icon['teknisi']],
        ],
    ];
} elseif ($role == 'admin_servis') {
    $menu = [
        'Menu Admin Servis' => [
            ['Kelola Jadwal Servis', 'kelola_jadwal.php', $icon['kalender']],
            ['Chat Servis', 'chat_servis.php', $icon['chat']],
            ['Transaksi Sparepart', 'kelola_transaksi.php', $icon['paket']],
        ],
    ];
} elseif ($role == 'owner') {
    $menu = [
        'Laporan Owner' => [
            ['Laporan Penjualan', 'laporan_penjualan.php', $icon['grafik']],
            ['Performa Marketing', 'performa_marketing.php', $icon['piala']],
            ['Laporan Keuangan', 'laporan_keuangan.php', $icon['keuangan']],
        ],
    ];
} else {
    $menu = [
        'Menu Customer' => [
            ['Pesanan Saya', 'pesanan_saya.php', $icon['paket']],
            ['Riwayat Servis', 'riwayat_servis.php', $icon['dokumen']],
        ],
    ];
}

require_once '../includes/header.php';
?>

<section class="auto-hero" style="padding: 2.5rem 0;">
    <div class="container">
        <p class="auto-eyebrow hero-in" style="--d:.05s;">DASHBOARD</p>
        <h2 class="auto-section-title hero-in" style="--d:.12s; color: #fff;"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))) ?></h2>
        <span class="brand-rule on-dark hero-in" style="--d:.16s;"></span>
        <p class="hero-in" style="--d:.2s; color: var(--auto-text-dim); margin-top: 0.5rem;">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>.</p>
    </div>
</section>

<section class="auto-section-light" style="padding-top: 2.5rem;">
<div class="container">
    <?php foreach ($menu as $judul => $items): ?>
        <h3 style="margin-bottom: 1.5rem;"><?= htmlspecialchars($judul) ?></h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.25rem;">
            <?php foreach ($items as $it): ?>
                <a href="<?= htmlspecialchars($it[1]) ?>" class="auto-feature-item reveal" style="text-decoration: none; color: inherit;">
                    <div class="fic"><?= $it[2] ?></div>
                    <div><b><?= htmlspecialchars($it[0]) ?></b><br><span style="font-size: 0.82rem; color: #6B7699; display: inline-flex; align-items: center; gap: 0.3rem;">Buka <?= $icon['panah'] ?></span></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
</section>

<?php require_once '../includes/footer.php'; ?>
