<?php
require_once '../config/database.php';
require_once '../includes/dashboard_data.php';

cek_role(['owner']);

$bulan = $_GET['bulan'] ?? date('Y-m');
if (!ypj_valid_month($bulan)) {
    $bulan = date('Y-m');
}

$stmt = $pdo->prepare("
    SELECT u.id, u.nama,
        COUNT(DISTINCT cr.id) as total_chat,
        COUNT(DISTINCT CASE WHEN cr.status = 'selesai' THEN cr.id END) as chat_selesai,
        COUNT(DISTINCT p.id) as total_pengajuan,
        COUNT(DISTINCT CASE WHEN p.status = 'selesai' THEN p.id END) as pengajuan_berhasil
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN chat_room cr ON cr.staff_id = u.id AND cr.role_staff = 'marketing' AND DATE_FORMAT(cr.waktu_mulai, '%Y-%m') = ?
    LEFT JOIN pengajuan_beli_mobil p ON p.customer_id IN (
        SELECT customer_id FROM chat_room WHERE staff_id = u.id AND role_staff = 'marketing'
    ) AND DATE_FORMAT(p.waktu, '%Y-%m') = ?
    WHERE r.nama_role = 'marketing'
    GROUP BY u.id, u.nama
    ORDER BY total_chat DESC
");
$stmt->execute([$bulan, $bulan]);
$performa = $stmt->fetchAll();

$stmt_total = $pdo->prepare("
    SELECT COUNT(*) FROM chat_room WHERE role_staff = 'marketing' AND DATE_FORMAT(waktu_mulai, '%Y-%m') = ?
");
$stmt_total->execute([$bulan]);
$total_chat_bulan = (int)$stmt_total->fetchColumn();

$stmt_berhasil = $pdo->prepare("
    SELECT COUNT(*) FROM pengajuan_beli_mobil WHERE status = 'selesai' AND DATE_FORMAT(waktu, '%Y-%m') = ?
");
$stmt_berhasil->execute([$bulan]);
$total_berhasil_bulan = (int)$stmt_berhasil->fetchColumn();

$chart_labels = array_map(fn($p) => $p['nama'], $performa);
$chart_values = array_map(fn($p) => $p['total_chat'] > 0 ? round(($p['pengajuan_berhasil'] / $p['total_chat']) * 100, 1) : 0, $performa);

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem; padding-bottom: 3rem;">
    <div class="page-head">
        <h2>Performa Marketing</h2>
        <form method="GET" action="" style="display: flex; gap: 0.5rem; align-items: center;">
            <label style="font-weight: 500;">Bulan:</label>
            <input type="month" name="bulan" value="<?= htmlspecialchars($bulan) ?>" style="padding: 0.5rem; border: 1px solid #C4CBD8; border-radius: 8px;">
            <button type="submit" class="btn" style="padding: 0.5rem 1rem;">Tampilkan</button>
        </form>
    </div>

    <div class="stat-grid">
        <div class="stat-card reveal">
            <p class="label">Total Chat Ditangani</p>
            <p class="num"><?= $total_chat_bulan ?></p>
        </div>
        <div class="stat-card highlight reveal">
            <p class="label">Pengajuan Berhasil (Closing)</p>
            <p class="num"><?= $total_berhasil_bulan ?></p>
        </div>
    </div>

    <div class="dash-panel reveal" style="margin-top: 1.5rem;">
        <div class="dash-panel-head"><h3>Grafik Konversi per Marketing</h3></div>
        <?php if (empty($performa)): ?>
            <div class="dash-empty">
                <strong>Belum ada data marketing</strong>
                <span>Belum ada aktivitas marketing pada <?= htmlspecialchars(ypj_month_label($bulan)) ?>.</span>
            </div>
        <?php else: ?>
            <div class="dash-chart sm"><canvas id="chartKonversi" role="img" aria-label="Grafik persentase konversi closing per marketing"></canvas></div>
            <div class="chart-fallback" id="fallbackKonversi" hidden>
                <table>
                    <thead><tr><th>Marketing</th><th>Konversi</th></tr></thead>
                    <tbody>
                        <?php foreach ($performa as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nama']) ?></td>
                                <td><?= $p['total_chat'] > 0 ? round(($p['pengajuan_berhasil'] / $p['total_chat']) * 100, 1) : 0 ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="table-card reveal">
        <div style="padding: 1.5rem 1.5rem 0;">
            <h3 style="margin: 0;">Detail per Marketing</h3>
        </div>
        <?php if (empty($performa)): ?>
            <p style="color: var(--color-slate-grey); padding: 1.5rem;">Tidak ada data marketing.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th style="text-align: center;">Total Chat</th>
                            <th style="text-align: center;">Chat Selesai</th>
                            <th style="text-align: center;">Pengajuan</th>
                            <th style="text-align: center;">Closing</th>
                            <th style="text-align: center;">Konversi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($performa as $p): ?>
                            <?php $konversi = $p['total_chat'] > 0 ? round(($p['pengajuan_berhasil'] / $p['total_chat']) * 100, 1) : 0; ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nama']) ?></td>
                                <td style="text-align: center;"><?= (int)$p['total_chat'] ?></td>
                                <td style="text-align: center;"><?= (int)$p['chat_selesai'] ?></td>
                                <td style="text-align: center;"><?= (int)$p['total_pengajuan'] ?></td>
                                <td style="text-align: center;"><?= (int)$p['pengajuan_berhasil'] ?></td>
                                <td style="text-align: center;">
                                    <span class="status-badge <?= $konversi >= 50 ? 'selesai' : ($konversi >= 25 ? 'menunggu' : 'ditolak') ?>">
                                        <?= $konversi ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($performa)): ?>
<script src="/project_PTYangpentingMaju/assets/js/vendor/chart.umd.min.js"></script>
<script>
(function () {
    var canvas = document.getElementById('chartKonversi');
    if (typeof Chart === 'undefined') {
        if (canvas) { var w = canvas.closest('.dash-chart'); if (w) w.style.display = 'none'; }
        var fb = document.getElementById('fallbackKonversi');
        if (fb) fb.hidden = false;
        return;
    }

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#5A6478';

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Konversi (%)',
                data: <?= json_encode(array_map('floatval', $chart_values)) ?>,
                backgroundColor: '#2E7DD1',
                borderRadius: 4,
                maxBarThickness: 64
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } } }
            }
        }
    });
})();
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
