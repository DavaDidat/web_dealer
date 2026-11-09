<?php
require_once '../config/database.php';

cek_role(['owner']);

$bulan = $_GET['bulan'] ?? date('Y-m');

$stmt_mobil = $pdo->prepare("
    SELECT DATE_FORMAT(waktu, '%Y-%m-%d') as tanggal, COUNT(*) as jumlah
    FROM pengajuan_beli_mobil
    WHERE DATE_FORMAT(waktu, '%Y-%m') = ?
    GROUP BY DATE_FORMAT(waktu, '%Y-%m-%d')
    ORDER BY tanggal ASC
");
$stmt_mobil->execute([$bulan]);
$data_mobil = $stmt_mobil->fetchAll();

$stmt_sparepart = $pdo->prepare("
    SELECT DATE_FORMAT(waktu, '%Y-%m-%d') as tanggal, COUNT(*) as jumlah, SUM(total) as total
    FROM transaksi_sparepart
    WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status != 'dibatalkan'
    GROUP BY DATE_FORMAT(waktu, '%Y-%m-%d')
    ORDER BY tanggal ASC
");
$stmt_sparepart->execute([$bulan]);
$data_sparepart = $stmt_sparepart->fetchAll();

$stmt_total_mobil = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_beli_mobil WHERE DATE_FORMAT(waktu, '%Y-%m') = ?");
$stmt_total_mobil->execute([$bulan]);
$total_mobil = $stmt_total_mobil->fetchColumn();

$stmt_total_sparepart = $pdo->prepare("SELECT COUNT(*) as total_count, COALESCE(SUM(total), 0) as total_sum FROM transaksi_sparepart WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status != 'dibatalkan'");
$stmt_total_sparepart->execute([$bulan]);
$total_sparepart = $stmt_total_sparepart->fetch();

$stmt_status = $pdo->prepare("
    SELECT status, COUNT(*) as jumlah FROM pengajuan_beli_mobil
    WHERE DATE_FORMAT(waktu, '%Y-%m') = ?
    GROUP BY status
");
$stmt_status->execute([$bulan]);
$status_mobil = $stmt_status->fetchAll();

$labels_mobil = json_encode(array_map(fn($d) => date('d', strtotime($d['tanggal'])), $data_mobil));
$data_mobil_chart = json_encode(array_map(fn($d) => $d['jumlah'], $data_mobil));
$labels_sparepart = json_encode(array_map(fn($d) => date('d', strtotime($d['tanggal'])), $data_sparepart));
$data_sparepart_chart = json_encode(array_map(fn($d) => $d['jumlah'], $data_sparepart));

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem; padding-bottom: 3rem;">
    <div class="page-head">
        <h2>Laporan Penjualan</h2>
        <form method="GET" action="" style="display: flex; gap: 0.5rem; align-items: center;">
            <label style="font-weight: 500;">Bulan:</label>
            <input type="month" name="bulan" value="<?= $bulan ?>" style="padding: 0.5rem; border: 1px solid #C4CBD8; border-radius: 8px;">
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
            <p class="num"><?= $total_sparepart['total_count'] ?></p>
        </div>
        <div class="stat-card reveal">
            <p class="label">Pendapatan Sparepart</p>
            <p class="num orange">Rp <?= number_format($total_sparepart['total_sum'], 0, ',', '.') ?></p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
        <div class="stat-card reveal" style="padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Traffic Pengajuan Mobil</h3>
            <canvas id="chartMobil" height="200"></canvas>
        </div>

        <div class="stat-card reveal" style="padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Traffic Sparepart</h3>
            <canvas id="chartSparepart" height="200"></canvas>
        </div>
    </div>

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
                            <td><?= $s['jumlah'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctxMobil = document.getElementById('chartMobil').getContext('2d');
new Chart(ctxMobil, {
    type: 'bar',
    data: {
        labels: <?= $labels_mobil ?>,
        datasets: [{
            label: 'Pengajuan Mobil',
            data: <?= $data_mobil_chart ?>,
            backgroundColor: '#F5821F',
            borderRadius: 4,
            maxBarThickness: 40
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

const ctxSparepart = document.getElementById('chartSparepart').getContext('2d');
new Chart(ctxSparepart, {
    type: 'bar',
    data: {
        labels: <?= $labels_sparepart ?>,
        datasets: [{
            label: 'Transaksi Sparepart',
            data: <?= $data_sparepart_chart ?>,
            backgroundColor: '#2E7DD1',
            borderRadius: 4,
            maxBarThickness: 40
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
