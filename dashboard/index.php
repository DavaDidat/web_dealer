<?php
require_once '../config/database.php';
require_once '../includes/dashboard_data.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$role = $_SESSION['role_nama'];
$user_id = (int)$_SESSION['user_id'];
$bulan = date('Y-m');
$owner_bulan = $bulan;
if ($role === 'owner' && isset($_GET['bulan']) && ypj_valid_month($_GET['bulan'])) {
    $owner_bulan = (string)$_GET['bulan'];
}
$display_bulan = $role === 'owner' ? $owner_bulan : $bulan;

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

$badge_class = function ($status) {
    $map = [
        'selesai' => 'selesai', 'disetujui' => 'disetujui',
        'diproses' => 'diproses', 'dijadwalkan' => 'dijadwalkan',
        'berjalan' => 'diproses', 'menunggu' => 'menunggu', 'antri' => 'antri',
        'dibatalkan' => 'dibatalkan', 'ditolak' => 'ditolak', 'batal' => 'dibatalkan',
    ];
    $key = strtolower((string)$status);
    return 'status-badge ' . ($map[$key] ?? '');
};

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

$owner = $role === 'owner' ? ownerStats($pdo, $owner_bulan) : null;
$customer = $role === 'customer' ? customerStats($pdo, $user_id, $bulan) : null;
$gudang = $role === 'admin_gudang' ? gudangStats($pdo) : null;
$marketing = $role === 'marketing' ? marketingStats($pdo, $user_id, $bulan) : null;
$servis = $role === 'admin_servis' ? adminServisStats($pdo) : null;
$teknisi = $role === 'teknisi' ? teknisiStats($pdo, $user_id, $bulan) : null;

require_once '../includes/header.php';
?>

<section class="auto-hero" style="padding: 2.5rem 0;">
    <div class="container">
        <p class="auto-eyebrow hero-in" style="--d:.05s;">DASHBOARD</p>
        <h2 class="auto-section-title hero-in" style="--d:.12s; color: #fff;"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))) ?></h2>
        <span class="brand-rule on-dark hero-in" style="--d:.16s;"></span>
        <p class="hero-in" style="--d:.2s; color: var(--auto-text-dim); margin-top: 0.5rem;">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>. Ringkasan <?= htmlspecialchars(ypj_month_label($display_bulan)) ?>.</p>
    </div>
</section>

<section class="auto-section-light" style="padding-top: 2.5rem;">
<div class="container">

<?php if ($owner): ?>
    <div class="dash-wrap">
        <form class="dash-panel dash-filter" method="GET">
            <label for="bulan">Periode</label>
            <input type="month" id="bulan" name="bulan" value="<?= htmlspecialchars($owner_bulan) ?>">
            <button class="btn btn-sm" type="submit">Tampilkan</button>
            <span class="note">Menampilkan <?= htmlspecialchars(ypj_month_label($owner_bulan)) ?></span>
        </form>
        <div class="dash-kpi-grid">
            <div class="dash-kpi is-accent reveal">
                <div class="ic"><?= $icon['keuangan'] ?></div>
                <div>
                    <p class="label">Total Pendapatan</p>
                    <p class="val"><?= ypj_rupiah($owner['total_revenue']) ?></p>
                    <p class="sub">Mobil + Sparepart + Jasa Servis</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['mobil'] ?></div>
                <div>
                    <p class="label">Penjualan Mobil</p>
                    <p class="val"><?= ypj_rupiah($owner['mobil_revenue']) ?></p>
                    <p class="sub"><?= $owner['mobil_units'] ?> unit &middot; <?= $owner['mobil_orders'] ?> transaksi selesai</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['sparepart'] ?></div>
                <div>
                    <p class="label">Penjualan Sparepart</p>
                    <p class="val"><?= ypj_rupiah($owner['sparepart_revenue']) ?></p>
                    <p class="sub"><?= $owner['sparepart_count'] ?> transaksi</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['teknisi'] ?></div>
                <div>
                    <p class="label">Pendapatan Jasa Servis</p>
                    <p class="val"><?= ypj_rupiah($owner['jasa_revenue']) ?></p>
                    <p class="sub"><?= $owner['servis_count'] ?> servis selesai</p>
                </div>
            </div>
        </div>

        <?php
        $traffic_total = array_sum($owner['mobil_series']) + array_sum($owner['sparepart_series']);
        $servis_total = array_sum($owner['servis_series']);
        ?>
        <div class="dash-cols">
            <div class="dash-panel reveal">
                <div class="dash-panel-head">
                    <h3>Trafik Penjualan</h3>
                    <a href="laporan_penjualan.php?bulan=<?= htmlspecialchars($owner_bulan) ?>">Detail <?= $icon['panah'] ?></a>
                </div>
                <?php if ($traffic_total === 0): ?>
                    <div class="dash-empty">
                        <strong>Belum ada transaksi pada periode ini</strong>
                        <span>Grafik akan tampil otomatis begitu ada pengajuan mobil atau transaksi sparepart.</span>
                    </div>
                <?php else: ?>
                    <div class="dash-chart"><canvas id="chartTraffic" role="img" aria-label="Grafik trafik penjualan mobil dan sparepart harian bulan <?= htmlspecialchars(ypj_month_label($owner_bulan)) ?>"></canvas></div>
                    <div class="chart-fallback" id="fallbackTraffic" hidden>
                        <table>
                            <caption class="visually-hidden">Ringkasan trafik penjualan periode ini</caption>
                            <thead><tr><th>Jenis</th><th>Jumlah</th></tr></thead>
                            <tbody>
                                <tr><td>Pengajuan mobil</td><td><?= array_sum($owner['mobil_series']) ?></td></tr>
                                <tr><td>Transaksi sparepart</td><td><?= array_sum($owner['sparepart_series']) ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dash-panel reveal">
                <div class="dash-panel-head">
                    <h3>Distribusi Pendapatan</h3>
                    <a href="laporan_keuangan.php?bulan=<?= htmlspecialchars($owner_bulan) ?>">Detail <?= $icon['panah'] ?></a>
                </div>
                <?php if ($owner['total_revenue'] <= 0): ?>
                    <div class="dash-empty"><strong>Belum ada pemasukan</strong><span>Belum ada transaksi selesai yang tercatat pada periode ini.</span></div>
                <?php else: ?>
                    <div class="dash-chart sm"><canvas id="chartFinance" role="img" aria-label="Diagram distribusi pendapatan dari mobil, sparepart, dan jasa servis"></canvas></div>
                    <div class="chart-fallback" id="fallbackFinance" hidden>
                        <table>
                            <thead><tr><th>Sumber</th><th>Pendapatan</th></tr></thead>
                            <tbody>
                                <tr><td>Mobil</td><td><?= ypj_rupiah($owner['mobil_revenue']) ?></td></tr>
                                <tr><td>Sparepart</td><td><?= ypj_rupiah($owner['sparepart_revenue']) ?></td></tr>
                                <tr><td>Jasa Servis</td><td><?= ypj_rupiah($owner['jasa_revenue']) ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-cols equal">
            <div class="dash-panel reveal">
                <div class="dash-panel-head">
                    <h3>Keramaian Servis</h3>
                    <a href="laporan_keuangan.php?bulan=<?= htmlspecialchars($owner_bulan) ?>">Detail <?= $icon['panah'] ?></a>
                </div>
                <?php if ($servis_total === 0): ?>
                    <div class="dash-empty"><strong>Belum ada jadwal servis</strong><span>Tidak ada jadwal servis pada periode ini.</span></div>
                <?php else: ?>
                    <div class="dash-chart sm"><canvas id="chartServis" role="img" aria-label="Grafik jumlah jadwal servis per hari pada periode ini"></canvas></div>
                <?php endif; ?>
            </div>
            <div class="dash-panel reveal">
                <div class="dash-panel-head">
                    <h3>Top Marketing</h3>
                    <a href="performa_marketing.php?bulan=<?= htmlspecialchars($owner_bulan) ?>">Detail <?= $icon['panah'] ?></a>
                </div>
                <?php if (empty($owner['marketing'])): ?>
                    <div class="dash-empty"><strong>Belum ada data</strong><span>Belum ada aktivitas marketing pada periode ini.</span></div>
                <?php else: ?>
                    <ul class="dash-list">
                        <?php foreach ($owner['marketing'] as $m): ?>
                            <li>
                                <div class="main">
                                    <b><?= htmlspecialchars($m['nama']) ?></b>
                                    <span><?= (int)$m['total_chat'] ?> chat ditangani</span>
                                </div>
                                <div class="side"><?= (int)$m['closing'] ?> closing</div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel reveal">
            <div class="dash-panel-head">
                <h3>Transaksi Terbaru</h3>
                <a href="laporan_keuangan.php?bulan=<?= htmlspecialchars($owner_bulan) ?>">Lihat semua <?= $icon['panah'] ?></a>
            </div>
            <?php if (empty($owner['recent'])): ?>
                <div class="dash-empty"><strong>Belum ada transaksi</strong><span>Transaksi terbaru akan muncul di sini.</span></div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #EEF1F7;">
                                <th style="text-align: left; padding: 0.5rem;">Tanggal</th>
                                <th style="text-align: left; padding: 0.5rem;">Jenis</th>
                                <th style="text-align: right; padding: 0.5rem;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($owner['recent'] as $t): ?>
                                <tr style="border-bottom: 1px solid #EEF1F7;">
                                    <td style="padding: 0.55rem 0.5rem;"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                                    <td style="padding: 0.55rem 0.5rem;">
                                        <span class="status-badge <?= $t['tipe'] == 'mobil' ? 'selesai' : ($t['tipe'] == 'jasa' ? 'diproses' : '') ?>">
                                            <?= $t['tipe'] == 'jasa' ? 'Jasa Servis' : ucfirst($t['tipe']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; padding: 0.55rem 0.5rem; font-weight: 600;"><?= ypj_rupiah($t['jumlah']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/project_PTYangpentingMaju/assets/js/vendor/chart.umd.min.js"></script>
    <script>
    (function () {
        var trafficCanvas = document.getElementById('chartTraffic');
        var financeCanvas = document.getElementById('chartFinance');
        var servisCanvas = document.getElementById('chartServis');

        function showFallback(canvas, fallbackId) {
            if (canvas) {
                var wrap = canvas.closest('.dash-chart');
                if (wrap) wrap.style.display = 'none';
            }
            var fb = document.getElementById(fallbackId);
            if (fb) fb.hidden = false;
        }

        if (typeof Chart === 'undefined') {
            showFallback(trafficCanvas, 'fallbackTraffic');
            showFallback(financeCanvas, 'fallbackFinance');
            return;
        }

        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#5A6478';

        if (trafficCanvas) {
            new Chart(trafficCanvas, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($owner['labels']) ?>,
                    datasets: [
                        { label: 'Mobil', data: <?= json_encode(array_map('intval', $owner['mobil_series'])) ?>, backgroundColor: '#2E7DD1', borderRadius: 4, maxBarThickness: 18 },
                        { label: 'Sparepart', data: <?= json_encode(array_map('intval', $owner['sparepart_series'])) ?>, backgroundColor: '#F5821F', borderRadius: 4, maxBarThickness: 18 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }

        if (financeCanvas) {
            new Chart(financeCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Mobil', 'Sparepart', 'Jasa Servis'],
                    datasets: [{
                        data: [<?= (float)$owner['mobil_revenue'] ?>, <?= (float)$owner['sparepart_revenue'] ?>, <?= (float)$owner['jasa_revenue'] ?>],
                        backgroundColor: ['#2E7DD1', '#F5821F', '#1E7A54'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        if (servisCanvas) {
            new Chart(servisCanvas, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($owner['labels']) ?>,
                    datasets: [{ label: 'Jadwal Servis', data: <?= json_encode(array_map('intval', $owner['servis_series'])) ?>, backgroundColor: '#1E7A54', borderRadius: 4, maxBarThickness: 16 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }
    })();
    </script>

<?php elseif ($customer): ?>
    <div class="dash-wrap">
        <div class="dash-kpi-grid">
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['mobil'] ?></div>
                <div>
                    <p class="label">Pesanan Mobil</p>
                    <p class="val"><?= $customer['mobil_total'] ?></p>
                    <p class="sub"><?= $customer['mobil_active'] ?> sedang diproses</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['paket'] ?></div>
                <div>
                    <p class="label">Pesanan Sparepart</p>
                    <p class="val"><?= $customer['sparepart_total'] ?></p>
                    <p class="sub"><?= $customer['sparepart_active'] ?> menunggu</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['kalender'] ?></div>
                <div>
                    <p class="label">Servis Mendatang</p>
                    <p class="val"><?= $customer['service_count'] ?></p>
                    <p class="sub"><?= $customer['next_service'] ? date('d M Y H:i', strtotime($customer['next_service']['waktu_mulai'])) : 'Belum ada jadwal' ?></p>
                </div>
            </div>
            <div class="dash-kpi is-accent reveal">
                <div class="ic"><?= $icon['keuangan'] ?></div>
                <div>
                    <p class="label">Belanja Bulan Ini</p>
                    <p class="val"><?= ypj_rupiah($customer['spend_month']) ?></p>
                    <p class="sub"><?= htmlspecialchars(ypj_month_label($bulan)) ?></p>
                </div>
            </div>
        </div>

        <div class="dash-cols equal">
            <div class="dash-panel reveal">
                <div class="dash-panel-head">
                    <h3>Jadwal Servis Terdekat</h3>
                    <a href="riwayat_servis.php">Riwayat <?= $icon['panah'] ?></a>
                </div>
                <?php if (!$customer['next_service']): ?>
                    <div class="dash-empty"><strong>Belum ada jadwal servis</strong><span>Ajukan booking servis agar kendaraan Anda terawat.</span><a href="../servis.php" class="btn btn-sm" style="margin-top: 0.5rem;">Ajukan Servis</a></div>
                <?php else: $ns = $customer['next_service']; ?>
                    <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start;">
                        <div>
                            <strong style="font-size: 1.05rem; display: block;"><?= date('d M Y H:i', strtotime($ns['waktu_mulai'])) ?></strong>
                            <span style="font-size: 0.85rem; color: var(--color-slate-grey);"><?= (int)$ns['durasi_menit'] ?> menit &middot; Teknisi: <?= htmlspecialchars($ns['nama_teknisi'] ?? 'Belum ditentukan') ?></span>
                            <?php if (!empty($ns['catatan'])): ?>
                                <p style="font-size: 0.88rem; margin-top: 0.6rem;"><?= nl2br(htmlspecialchars($ns['catatan'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="<?= $badge_class($ns['status']) ?>"><?= ucfirst(str_replace('_', ' ', $ns['status'])) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dash-panel reveal">
                <div class="dash-panel-head">
                    <h3>Pesanan Terbaru</h3>
                    <a href="pesanan_saya.php">Lihat semua <?= $icon['panah'] ?></a>
                </div>
                <?php if (empty($customer['recent'])): ?>
                    <div class="dash-empty"><strong>Belum ada pesanan</strong><span>Mulai jelajahi katalog mobil dan sparepart kami.</span></div>
                <?php else: ?>
                    <ul class="dash-list">
                        <?php foreach ($customer['recent'] as $r): ?>
                            <li>
                                <div class="main">
                                    <b><?= htmlspecialchars($r['judul']) ?></b>
                                    <span><?= $r['tipe'] == 'mobil' ? 'Mobil' : 'Sparepart' ?> &middot; <?= date('d M Y', strtotime($r['waktu'])) ?></span>
                                </div>
                                <div class="side">
                                    <span class="<?= $badge_class($r['status']) ?>"><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel reveal">
            <div class="dash-panel-head"><h3>Aksi Cepat</h3></div>
            <div class="dash-actions">
                <a class="dash-action" href="../katalog_mobil.php"><span class="ic"><?= $icon['mobil'] ?></span> Katalog Mobil</a>
                <a class="dash-action" href="../katalog_sparepart.php"><span class="ic"><?= $icon['sparepart'] ?></span> Katalog Sparepart</a>
                <a class="dash-action" href="../servis.php"><span class="ic"><?= $icon['kalender'] ?></span> Booking Servis</a>
                <a class="dash-action" href="../chat.php"><span class="ic"><?= $icon['chat'] ?></span> Chat</a>
                <a class="dash-action" href="pesanan_saya.php"><span class="ic"><?= $icon['paket'] ?></span> Pesanan Saya</a>
                <a class="dash-action" href="../keranjang.php"><span class="ic"><?= $icon['dokumen'] ?></span> Keranjang (<?= $customer['cart_count'] ?>)</a>
            </div>
        </div>
    </div>

<?php elseif ($gudang): ?>
    <div class="dash-wrap">
        <div class="dash-kpi-grid">
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['mobil'] ?></div>
                <div>
                    <p class="label">Total Stok Mobil</p>
                    <p class="val"><?= $gudang['mobil_stock'] ?></p>
                    <p class="sub"><?= $gudang['mobil_items'] ?> model</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['sparepart'] ?></div>
                <div>
                    <p class="label">Total Stok Sparepart</p>
                    <p class="val"><?= $gudang['sparepart_stock'] ?></p>
                    <p class="sub"><?= $gudang['sparepart_items'] ?> jenis</p>
                </div>
            </div>
            <div class="dash-kpi is-accent reveal">
                <div class="ic"><?= $icon['log'] ?></div>
                <div>
                    <p class="label">Stok Menipis (&lt; 5)</p>
                    <p class="val"><?= $gudang['low_count'] ?></p>
                    <p class="sub">Perlu segera ditambah</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['dokumen'] ?></div>
                <div>
                    <p class="label">Menunggu Proses</p>
                    <p class="val"><?= $gudang['pending_mobil'] + $gudang['pending_sparepart'] ?></p>
                    <p class="sub"><?= $gudang['pending_mobil'] ?> pengajuan &middot; <?= $gudang['pending_sparepart'] ?> transaksi</p>
                </div>
            </div>
        </div>

        <div class="dash-cols equal">
            <div class="dash-panel reveal">
                <div class="dash-panel-head"><h3>Stok Menipis</h3><a href="kelola_mobil.php">Kelola <?= $icon['panah'] ?></a></div>
                <?php if (empty($gudang['low_stock'])): ?>
                    <div class="dash-empty"><strong>Stok aman</strong><span>Semua item memiliki stok 5 atau lebih.</span></div>
                <?php else: ?>
                    <ul class="dash-list">
                        <?php foreach ($gudang['low_stock'] as $s): ?>
                            <li>
                                <div class="main">
                                    <b><?= htmlspecialchars($s['nama']) ?></b>
                                    <span><?= $s['tipe'] == 'mobil' ? 'Mobil' : 'Sparepart' ?></span>
                                </div>
                                <div class="side <?= (int)$s['stok'] <= 0 ? 'trend-down' : '' ?>"><?= (int)$s['stok'] ?> unit</div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="dash-panel reveal">
                <div class="dash-panel-head"><h3>Log Stok Terbaru</h3><a href="log_stok.php">Lihat semua <?= $icon['panah'] ?></a></div>
                <?php if (empty($gudang['recent_logs'])): ?>
                    <div class="dash-empty"><strong>Belum ada log</strong><span>Perubahan stok akan tercatat di sini.</span></div>
                <?php else: ?>
                    <ul class="dash-list">
                        <?php foreach ($gudang['recent_logs'] as $l): ?>
                            <li>
                                <div class="main">
                                    <b><?= htmlspecialchars($l['nama_item'] ?? ('Item #' . $l['item_id'])) ?></b>
                                    <span><?= date('d M Y H:i', strtotime($l['waktu'])) ?> &middot; <?= htmlspecialchars($l['nama_user'] ?? '-') ?></span>
                                </div>
                                <div class="side"><?= (int)$l['jumlah_sebelum'] ?> &rarr; <?= (int)$l['jumlah_sesudah'] ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif ($marketing): ?>
    <div class="dash-wrap">
        <div class="dash-kpi-grid">
            <div class="dash-kpi is-accent reveal">
                <div class="ic"><?= $icon['chat'] ?></div>
                <div>
                    <p class="label">Antrian Chat</p>
                    <p class="val"><?= $marketing['queue'] ?></p>
                    <p class="sub">Customer menunggu dibalas</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['user'] ?></div>
                <div>
                    <p class="label">Chat Aktif Saya</p>
                    <p class="val"><?= $marketing['active'] ?></p>
                    <p class="sub">Percakapan sedang berjalan</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['dokumen'] ?></div>
                <div>
                    <p class="label">Pengajuan Menunggu</p>
                    <p class="val"><?= $marketing['pending'] ?></p>
                    <p class="sub">Perlu ditindaklanjuti</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['piala'] ?></div>
                <div>
                    <p class="label">Closing Bulan Ini</p>
                    <p class="val"><?= $marketing['closing'] ?></p>
                    <p class="sub"><?= $marketing['chats_month'] ?> chat ditangani</p>
                </div>
            </div>
        </div>

        <div class="dash-panel reveal">
            <div class="dash-panel-head"><h3>Aksi Cepat</h3></div>
            <div class="dash-actions">
                <a class="dash-action" href="chat_antrian.php"><span class="ic"><?= $icon['chat'] ?></span> Antrian Chat</a>
                <a class="dash-action" href="chat_aktif.php"><span class="ic"><?= $icon['user'] ?></span> Chat Aktif</a>
                <a class="dash-action" href="pengajuan_beli.php"><span class="ic"><?= $icon['dokumen'] ?></span> Pengajuan Beli</a>
                <a class="dash-action" href="stok_readonly.php"><span class="ic"><?= $icon['mobil'] ?></span> Stok Read-Only</a>
            </div>
        </div>
    </div>

<?php elseif ($servis): ?>
    <div class="dash-wrap">
        <div class="dash-kpi-grid">
            <div class="dash-kpi is-accent reveal">
                <div class="ic"><?= $icon['kalender'] ?></div>
                <div>
                    <p class="label">Jadwal Hari Ini</p>
                    <p class="val"><?= $servis['today'] ?></p>
                    <p class="sub"><?= date('d M Y') ?></p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['teknisi'] ?></div>
                <div>
                    <p class="label">Belum Assign Teknisi</p>
                    <p class="val"><?= $servis['unassigned'] ?></p>
                    <p class="sub">Perlu ditugaskan</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['paket'] ?></div>
                <div>
                    <p class="label">Servis Berjalan</p>
                    <p class="val"><?= $servis['running'] ?></p>
                    <p class="sub">Sedang dikerjakan</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['sparepart'] ?></div>
                <div>
                    <p class="label">Transaksi Sparepart Menunggu</p>
                    <p class="val"><?= $servis['pending_sparepart'] ?></p>
                    <p class="sub">Perlu diproses</p>
                </div>
            </div>
        </div>

        <div class="dash-panel reveal">
            <div class="dash-panel-head"><h3>Jadwal Servis Hari Ini</h3><a href="kelola_jadwal.php">Kelola <?= $icon['panah'] ?></a></div>
            <?php if (empty($servis['today_schedule'])): ?>
                <div class="dash-empty"><strong>Tidak ada jadwal hari ini</strong><span>Jadwal servis yang masuk akan tampil di sini.</span></div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #EEF1F7;">
                                <th style="text-align: left; padding: 0.5rem;">Jam</th>
                                <th style="text-align: left; padding: 0.5rem;">Customer</th>
                                <th style="text-align: left; padding: 0.5rem;">Teknisi</th>
                                <th style="text-align: left; padding: 0.5rem;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($servis['today_schedule'] as $j): ?>
                                <tr style="border-bottom: 1px solid #EEF1F7;">
                                    <td style="padding: 0.55rem 0.5rem;"><?= date('H:i', strtotime($j['waktu_mulai'])) ?></td>
                                    <td style="padding: 0.55rem 0.5rem;"><?= htmlspecialchars($j['nama_customer']) ?></td>
                                    <td style="padding: 0.55rem 0.5rem;"><?= htmlspecialchars($j['nama_teknisi'] ?? 'Belum ada') ?></td>
                                    <td style="padding: 0.55rem 0.5rem;"><span class="<?= $badge_class($j['status']) ?>"><?= ucfirst(str_replace('_', ' ', $j['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <div class="dash-actions" style="margin-top: 1.25rem;">
                <a class="dash-action" href="kelola_jadwal.php"><span class="ic"><?= $icon['kalender'] ?></span> Kelola Jadwal</a>
                <a class="dash-action" href="chat_servis.php"><span class="ic"><?= $icon['chat'] ?></span> Chat Servis</a>
                <a class="dash-action" href="kelola_transaksi.php"><span class="ic"><?= $icon['paket'] ?></span> Transaksi Sparepart</a>
                <a class="dash-action" href="stok_readonly.php"><span class="ic"><?= $icon['mobil'] ?></span> Stok Read-Only</a>
            </div>
        </div>
    </div>

<?php elseif ($teknisi): ?>
    <div class="dash-wrap">
        <div class="dash-kpi-grid">
            <div class="dash-kpi is-accent reveal">
                <div class="ic"><?= $icon['kalender'] ?></div>
                <div>
                    <p class="label">Jadwal Hari Ini</p>
                    <p class="val"><?= $teknisi['today'] ?></p>
                    <p class="sub"><?= date('d M Y') ?></p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['teknisi'] ?></div>
                <div>
                    <p class="label">Sedang Berjalan</p>
                    <p class="val"><?= $teknisi['running'] ?></p>
                    <p class="sub">Servis dikerjakan</p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['paket'] ?></div>
                <div>
                    <p class="label">Selesai Bulan Ini</p>
                    <p class="val"><?= $teknisi['done_month'] ?></p>
                    <p class="sub"><?= htmlspecialchars(ypj_month_label($bulan)) ?></p>
                </div>
            </div>
            <div class="dash-kpi reveal">
                <div class="ic"><?= $icon['keuangan'] ?></div>
                <div>
                    <p class="label">Pendapatan Jasa Bulan Ini</p>
                    <p class="val"><?= ypj_rupiah($teknisi['jasa_month']) ?></p>
                    <p class="sub">Dari servis selesai</p>
                </div>
            </div>
        </div>

        <div class="dash-panel reveal">
            <div class="dash-panel-head"><h3>Pekerjaan Aktif</h3><a href="jadwal_servis_saya.php">Kelola <?= $icon['panah'] ?></a></div>
            <?php if (empty($teknisi['jobs'])): ?>
                <div class="dash-empty"><strong>Tidak ada pekerjaan aktif</strong><span>Jadwal yang ditugaskan ke Anda akan muncul di sini.</span></div>
            <?php else: ?>
                <ul class="dash-list">
                    <?php foreach ($teknisi['jobs'] as $j): ?>
                        <li>
                            <div class="main">
                                <b><?= htmlspecialchars($j['nama_customer']) ?></b>
                                <span><?= date('d M Y H:i', strtotime($j['waktu_mulai'])) ?> &middot; <?= (int)$j['durasi_menit'] ?> menit</span>
                            </div>
                            <div class="side"><span class="<?= $badge_class($j['status']) ?>"><?= ucfirst(str_replace('_', ' ', $j['status'])) ?></span></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="dash-actions" style="margin-top: 1.25rem;">
                <a class="dash-action" href="jadwal_servis_saya.php"><span class="ic"><?= $icon['teknisi'] ?></span> Jadwal Servis Saya</a>
            </div>
        </div>
    </div>
<?php endif; ?>

    <div style="margin-top: 2.5rem;">
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
</div>
</section>

<?php require_once '../includes/footer.php'; ?>
