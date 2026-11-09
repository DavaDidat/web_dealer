<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_nama'], ['admin_gudang', 'owner'])) {
    die("Akses ditolak.");
}

// Ambil log stok
$stmt = $pdo->query("
    SELECT l.*, u.nama as nama_user,
    CASE 
        WHEN l.tipe_item = 'mobil' THEN (SELECT nama FROM mobil WHERE id = l.item_id)
        WHEN l.tipe_item = 'sparepart' THEN (SELECT nama FROM sparepart WHERE id = l.item_id)
    END as nama_item
    FROM log_stok l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.waktu DESC
");
$logs = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Log Perubahan Stok (Audit Trail)</h2>
    <p style="color: var(--color-slate-grey);">Memantau seluruh aktivitas penambahan atau pengurangan stok di gudang.</p>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; margin-top: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background-color: var(--color-graphite-black); color: var(--color-warm-ivory);">
                <tr>
                    <th style="padding: 1rem;">Waktu</th>
                    <th style="padding: 1rem;">Petugas (User)</th>
                    <th style="padding: 1rem;">Tipe & Item</th>
                    <th style="padding: 1rem; text-align: center;">Perubahan</th>
                    <th style="padding: 1rem; text-align: center;">Selisih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php 
                        $selisih = $log['jumlah_sesudah'] - $log['jumlah_sebelum']; 
                        $warna_selisih = $selisih > 0 ? '#166534' : ($selisih < 0 ? '#991b1b' : 'gray');
                        $tanda_selisih = $selisih > 0 ? '+' : '';
                    ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;"><?= date('d M Y H:i', strtotime($log['waktu'])) ?></td>
                        <td style="padding: 1rem;"><?= htmlspecialchars($log['nama_user']) ?></td>
                        <td style="padding: 1rem;">
                            <span style="font-size: 0.8rem; text-transform: uppercase; background: #e2e8f0; padding: 0.2rem 0.4rem; border-radius: 4px; display: inline-block; margin-bottom: 0.25rem;">
                                <?= $log['tipe_item'] ?>
                            </span><br>
                            <strong><?= htmlspecialchars($log['nama_item'] ?: 'Item Dihapus') ?></strong>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <span style="color: var(--color-slate-grey);"><?= $log['jumlah_sebelum'] ?></span> 
                            &rarr; 
                            <strong style="font-size: 1.1rem;"><?= $log['jumlah_sesudah'] ?></strong>
                        </td>
                        <td style="padding: 1rem; text-align: center; font-weight: bold; color: <?= $warna_selisih ?>;">
                            <?= $tanda_selisih . $selisih ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center;">Belum ada log perubahan stok.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
