<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_nama'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT r.*, j.waktu_mulai as waktu_servis, j.status as status_jadwal, u.nama as nama_teknisi
    FROM riwayat_servis r
    JOIN jadwal_servis j ON r.jadwal_servis_id = j.id
    LEFT JOIN users u ON j.teknisi_id = u.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$customer_id]);
$riwayat = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Riwayat Servis</h2>

    <div style="margin-top: 2rem;">
        <?php if (empty($riwayat)): ?>
            <div style="background: #fff; padding: 3rem; border-radius: 8px; text-align: center;">
                <p style="color: var(--color-slate-grey);">Belum ada riwayat servis.</p>
                <a href="../servis.php" class="btn" style="margin-top: 1rem;">Ajukan Servis</a>
            </div>
        <?php else: ?>
            <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee;">
                            <th style="text-align: left; padding: 0.75rem;">Tanggal</th>
                            <th style="text-align: left; padding: 0.75rem;">Teknisi</th>
                            <th style="text-align: left; padding: 0.75rem;">Sparepart Terpasang</th>
                            <th style="text-align: right; padding: 0.75rem;">Biaya Jasa</th>
                            <th style="text-align: left; padding: 0.75rem;">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $r): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 0.75rem;"><?= date('d M Y H:i', strtotime($r['waktu_servis'])) ?></td>
                                <td style="padding: 0.75rem;"><?= htmlspecialchars($r['nama_teknisi'] ?? '-') ?></td>
                                <td style="padding: 0.75rem;"><?= htmlspecialchars($r['sparepart_terpasang'] ?? '-') ?></td>
                                <td style="padding: 0.75rem; text-align: right;">Rp <?= number_format($r['biaya_jasa'], 0, ',', '.') ?></td>
                                <td style="padding: 0.75rem; max-width: 300px;"><?= htmlspecialchars($r['catatan'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
