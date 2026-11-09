<?php
require_once '../config/database.php';
require_once '../includes/dashboard_data.php';

cek_role(['owner']);

$bulan = $_GET['bulan'] ?? date('Y-m');
if (!ypj_valid_month($bulan)) {
    $bulan = date('Y-m');
}

$stmt_mobil = $pdo->prepare("
    SELECT DATE_FORMAT(waktu, '%Y-%m-%d') as tanggal, COUNT(*) as jumlah
    FROM pengajuan_beli_mobil
    WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'
    GROUP BY DATE_FORMAT(waktu, '%Y-%m-%d')
    ORDER BY tanggal ASC
");
$stmt_mobil->execute([$bulan]);
$data_mobil = $stmt_mobil->fetchAll();

$stmt_sparepart = $pdo->prepare("
    SELECT DATE_FORMAT(waktu, '%Y-%m-%d') as tanggal, COUNT(*) as jumlah, SUM(total) as total
    FROM transaksi_sparepart
    WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'
    GROUP BY DATE_FORMAT(waktu, '%Y-%m-%d')
    ORDER BY tanggal ASC
");
$stmt_sparepart->execute([$bulan]);
$data_sparepart = $stmt_sparepart->fetchAll();

$stmt_total_mobil = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_beli_mobil WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'");
$stmt_total_mobil->execute([$bulan]);
$total_mobil = (int)$stmt_total_mobil->fetchColumn();

$stmt_total_sparepart = $pdo->prepare("SELECT COUNT(*) as total_count, COALESCE(SUM(total), 0) as total_sum FROM transaksi_sparepart WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'");
$stmt_total_sparepart->execute([$bulan]);
$total_sparepart = $stmt_total_sparepart->fetch();

$stmt_pendapatan_mobil = $pdo->prepare("
    SELECT COALESCE(SUM(m.harga * p.jumlah), 0)
    FROM pengajuan_beli_mobil p
    JOIN mobil m ON p.mobil_id = m.id
    WHERE DATE_FORMAT(p.waktu, '%Y-%m') = ? AND p.status = 'selesai'
");
$stmt_pendapatan_mobil->execute([$bulan]);
$pendapatan_mobil = (float)$stmt_pendapatan_mobil->fetchColumn();

$stmt_status = $pdo->prepare("
    SELECT status, COUNT(*) as jumlah FROM pengajuan_beli_mobil
    WHERE DATE_FORMAT(waktu, '%Y-%m') = ?
    GROUP BY status
");
$stmt_status->execute([$bulan]);
$status_mobil = $stmt_status->fetchAll();

$labels = dailyLabels($bulan);
$mobil_series = buildDailySeries($data_mobil, $bulan, 'jumlah');
$sparepart_series = buildDailySeries($data_sparepart, $bulan, 'jumlah');
$traffic_total = array_sum($mobil_series) + array_sum($sparepart_series);

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem; padding-bottom: 3rem;">
    <div class="page-head">
        <h2>Laporan Penjualan</h2>
        <form method="GET" action="" style="display: flex; gap: 0.5rem; align-items: center;">
            <label style="font-weight: 500;">Bulan:</label>
            <input type="month" name="bulan" value="<?= htmlspecialchars($bulan) ?>" style="padding: 0.5rem; border: 1px solid #C4CBD8; border-radius: 8px;">
            <button type="submit" class="btn" style="padding: 0.5rem 1rem;">Tampilkan</button>
        </form>
    </div>

    <div class="stat-grid">
        <div class="stat-card reveal">
            <p class="label">Pengajuan Mobil</p>
            <p class="num"><?= $total_mobil ?></p>
        </div>
        <div class="stat-card reveal">
            <p class="label">Transaksi Sparepart</p>
            <p class="num"><?= (int)$total_sparepart['total_count'] ?></p>
        </div>
        <div class="stat-card reveal">
            <p class="label">Pendapatan Sparepart</p>
            <p class="num orange"><?= ypj_rupiah($total_sparepart['total_sum']) ?></p>
        </div>
        <div class="stat-card reveal">
            <p class="label">Pendapatan Mobil</p>
            <p class="num"><?= ypj_rupiah($pendapatan_mobil) ?></p>
        </div>
    </div>

    <?php if ($traffic_total === 0): ?>
        <div class="dash-panel reveal" style="margin-top: 1.5rem;">
            <div class="dash-empty">
                <strong>Belum ada trafik penjualan</strong>
                <span>Tidak ada pengajuan mobil atau transaksi sparepart pada <?= htmlspecialchars(ypj_month_label($bulan)) ?>.</span>
            </div>
        </div>
    <?php else: ?>
        <div class="dash-cols equal" style="margin-top: 1.5rem;">
            <div class="dash-panel reveal">
                <div class="dash-panel-head"><h3>Traffic Pengajuan Mobil</h3></div>
                <div class="dash-chart sm"><canvas id="chartMobil" role="img" aria-label="Grafik pengajuan mobil harian"></canvas></div>
                <div class="chart-fallback" id="fallbackMobil" hidden>
                    <table>
                        <thead><tr><th>Hari</th><th>Pengajuan</th></tr></thead>
                        <tbody>
                            <?php foreach ($labels as $i => $day): ?>
                                <tr><td><?= $day ?></td><td><?= (int)$mobil_series[$i] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dash-panel reveal">
                <div class="dash-panel-head"><h3>Traffic Sparepart</h3></div>
                <div class="dash-chart sm"><canvas id="chartSparepart" role="img" aria-label="Grafik transaksi sparepart harian"></canvas></div>
                <div class="chart-fallback" id="fallbackSparepart" hidden>
                    <table>
                        <thead><tr><th>Hari</th><th>Transaksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($labels as $i => $day): ?>
                                <tr><td><?= $day ?></td><td><?= (int)$sparepart_series[$i] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="table-card reveal">
        <div style="padding: 1.5rem 1.5rem 0;">
            <h3 style="margin: 0;">Status Pengajuan Mobil</h3>
        </div>
        <?php if (empty($status_mobil)): ?>
            <p style="color: var(--color-slate-grey); padding: 1.5rem;">Tidak ada data.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($status_mobil as $s): ?>
                        <tr>
                            <td><?= ucfirst(str_replace('_', ' ', $s['status'])) ?></td>
                            <td><?= (int)$s['jumlah'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($traffic_total > 0): ?>
<script src="/project_PTYangpentingMaju/assets/js/vendor/chart.umd.min.js"></script>
<script>
(function () {
    function showFallback(canvasId, fallbackId) {
        var c = document.getElementById(canvasId);
        if (c) { var w = c.closest('.dash-chart'); if (w) w.style.display = 'none'; }
        var f = document.getElementById(fallbackId);
        if (f) f.hidden = false;
    }

    if (typeof Chart === 'undefined') {
        showFallback('chartMobil', 'fallbackMobil');
        showFallback('chartSparepart', 'fallbackSparepart');
        return;
    }

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#5A6478';

    var labels = <?= json_encode($labels) ?>;

    new Chart(document.getElementById('chartMobil'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{ label: 'Pengajuan Mobil', data: <?= json_encode(array_map('intval', $mobil_series)) ?>, backgroundColor: '#F5821F', borderRadius: 4, maxBarThickness: 40 }]
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

    new Chart(document.getElementById('chartSparepart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{ label: 'Transaksi Sparepart', data: <?= json_encode(array_map('intval', $sparepart_series)) ?>, backgroundColor: '#2E7DD1', borderRadius: 4, maxBarThickness: 40 }]
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
})();
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
