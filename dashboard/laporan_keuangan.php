<?php
require_once '../config/database.php';
require_once '../includes/dashboard_data.php';

cek_role(['owner']);

$bulan = $_GET['bulan'] ?? date('Y-m');
if (!ypj_valid_month($bulan)) {
    $bulan = date('Y-m');
}

$stmt_sparepart = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0) as total_sparepart
    FROM transaksi_sparepart
    WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'
");
$stmt_sparepart->execute([$bulan]);
$total_sparepart = (float)$stmt_sparepart->fetchColumn();

$stmt_servis = $pdo->prepare("
    SELECT COUNT(*) as total_servis
    FROM jadwal_servis
    WHERE DATE_FORMAT(waktu_mulai, '%Y-%m') = ? AND status = 'selesai'
");
$stmt_servis->execute([$bulan]);
$total_servis = (int)$stmt_servis->fetchColumn();

$stmt_jasa = $pdo->prepare("
    SELECT COALESCE(SUM(biaya_jasa), 0) as total_jasa
    FROM jadwal_servis
    WHERE DATE_FORMAT(waktu_mulai, '%Y-%m') = ? AND status = 'selesai'
");
$stmt_jasa->execute([$bulan]);
$total_jasa = (float)$stmt_jasa->fetchColumn();

$stmt_mobil = $pdo->prepare("
    SELECT COALESCE(SUM(m.harga * p.jumlah), 0) as total_mobil
    FROM pengajuan_beli_mobil p
    JOIN mobil m ON p.mobil_id = m.id
    WHERE DATE_FORMAT(p.waktu, '%Y-%m') = ? AND p.status = 'selesai'
");
$stmt_mobil->execute([$bulan]);
$total_mobil = (float)$stmt_mobil->fetchColumn();

$total_keseluruhan = $total_sparepart + $total_mobil + $total_jasa;

$stmt_weekly = $pdo->prepare("
    SELECT
        DATE_FORMAT(waktu, '%Y-%m-%d') as tanggal,
        'sparepart' as tipe,
        total as jumlah
    FROM transaksi_sparepart
    WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'
    UNION ALL
    SELECT
        DATE_FORMAT(p.waktu, '%Y-%m-%d') as tanggal,
        'mobil' as tipe,
        (m.harga * p.jumlah) as jumlah
    FROM pengajuan_beli_mobil p
    JOIN mobil m ON p.mobil_id = m.id
    WHERE DATE_FORMAT(p.waktu, '%Y-%m') = ? AND p.status = 'selesai'
    UNION ALL
    SELECT
        DATE_FORMAT(j.waktu_mulai, '%Y-%m-%d') as tanggal,
        'jasa' as tipe,
        j.biaya_jasa as jumlah
    FROM jadwal_servis j
    WHERE DATE_FORMAT(j.waktu_mulai, '%Y-%m') = ? AND j.status = 'selesai' AND j.biaya_jasa > 0
    ORDER BY tanggal ASC
");
$stmt_weekly->execute([$bulan, $bulan, $bulan]);
$transaksi = $stmt_weekly->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem; padding-bottom: 3rem;">
    <div class="page-head">
        <h2>Laporan Keuangan</h2>
        <form method="GET" action="" style="display: flex; gap: 0.5rem; align-items: center;">
            <label style="font-weight: 500;">Bulan:</label>
            <input type="month" name="bulan" value="<?= htmlspecialchars($bulan) ?>" style="padding: 0.5rem; border: 1px solid #C4CBD8; border-radius: 8px;">
            <button type="submit" class="btn" style="padding: 0.5rem 1rem;">Tampilkan</button>
        </form>
    </div>

    <div class="stat-grid">
        <div class="stat-card reveal">
            <p class="label">Penjualan Mobil</p>
            <p class="num"><?= ypj_rupiah($total_mobil) ?></p>
        </div>
        <div class="stat-card reveal">
            <p class="label">Penjualan Sparepart</p>
            <p class="num"><?= ypj_rupiah($total_sparepart) ?></p>
        </div>
        <div class="stat-card reveal">
            <p class="label">Pendapatan Jasa Servis</p>
            <p class="num"><?= ypj_rupiah($total_jasa) ?></p>
        </div>
        <div class="stat-card reveal">
            <p class="label">Servis Selesai</p>
            <p class="num orange"><?= $total_servis ?>x</p>
        </div>
        <div class="stat-card highlight reveal">
            <p class="label">Total Pendapatan</p>
            <p class="num"><?= ypj_rupiah($total_keseluruhan) ?></p>
        </div>
    </div>

    <div class="dash-panel reveal" style="margin-top: 1.5rem;">
        <div class="dash-panel-head"><h3>Distribusi Pendapatan</h3></div>
        <?php if ($total_keseluruhan <= 0): ?>
            <div class="dash-empty">
                <strong>Belum ada pemasukan</strong>
                <span>Tidak ada transaksi selesai pada <?= htmlspecialchars(ypj_month_label($bulan)) ?>.</span>
            </div>
        <?php else: ?>
            <div class="dash-chart" style="max-width: 420px; margin: 0 auto;">
                <canvas id="chartKeuangan" role="img" aria-label="Diagram distribusi pendapatan mobil, sparepart, dan jasa servis"></canvas>
            </div>
            <div class="chart-fallback" id="fallbackKeuangan" hidden style="max-width: 420px; margin: 0 auto;">
                <table>
                    <thead><tr><th>Sumber</th><th>Pendapatan</th></tr></thead>
                    <tbody>
                        <tr><td>Mobil</td><td><?= ypj_rupiah($total_mobil) ?></td></tr>
                        <tr><td>Sparepart</td><td><?= ypj_rupiah($total_sparepart) ?></td></tr>
                        <tr><td>Jasa Servis</td><td><?= ypj_rupiah($total_jasa) ?></td></tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="table-card reveal">
        <div style="padding: 1.5rem 1.5rem 0;">
            <h3 style="margin: 0;">Riwayat Transaksi</h3>
        </div>
        <?php if (empty($transaksi)): ?>
            <p style="color: var(--color-slate-grey); padding: 1.5rem;">Tidak ada transaksi bulan ini.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th style="text-align: right;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transaksi as $t): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                            <td>
                                <span class="status-badge <?= $t['tipe'] == 'mobil' ? 'selesai' : ($t['tipe'] == 'jasa' ? 'diproses' : '') ?>">
                                    <?= $t['tipe'] == 'jasa' ? 'Jasa Servis' : ucfirst($t['tipe']) ?>
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 600;"><?= ypj_rupiah($t['jumlah']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($total_keseluruhan > 0): ?>
<script src="/project_PTYangpentingMaju/assets/js/vendor/chart.umd.min.js"></script>
<script>
(function () {
    var canvas = document.getElementById('chartKeuangan');
    if (typeof Chart === 'undefined') {
        if (canvas) { var w = canvas.closest('.dash-chart'); if (w) w.style.display = 'none'; }
        var fb = document.getElementById('fallbackKeuangan');
        if (fb) fb.hidden = false;
        return;
    }

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#5A6478';

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: ['Mobil', 'Sparepart', 'Jasa Servis'],
            datasets: [{
                data: [<?= $total_mobil ?>, <?= $total_sparepart ?>, <?= $total_jasa ?>],
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
})();
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
