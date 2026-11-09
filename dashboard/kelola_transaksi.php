<?php
require_once '../config/database.php';

cek_role(['admin_servis']);

$pesan = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    verify_csrf();
    $transaksi_id = (int)$_POST['transaksi_id'];
    $status_baru = $_POST['status_baru'];
    $status_valid = ['menunggu', 'selesai', 'dibatalkan'];

    if (!in_array($status_baru, $status_valid, true)) {
        $pesan_error = "Status tidak valid.";
    } elseif (!in_array($_SESSION['role_nama'], ['admin_servis'], true)) {
        $pesan_error = "Hanya Admin Servis yang dapat mengubah status transaksi.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt_cur = $pdo->prepare("SELECT * FROM transaksi_sparepart WHERE id = ? FOR UPDATE");
            $stmt_cur->execute([$transaksi_id]);
            $transaksi = $stmt_cur->fetch();

            if (!$transaksi) {
                throw new Exception("Transaksi tidak ditemukan.");
            }

            $status_lama = $transaksi['status'];

            if ($status_lama !== $status_baru) {
                if ($status_baru === 'dibatalkan' || $status_lama === 'dibatalkan') {
                    $stmt_det = $pdo->prepare("
                        SELECT td.sparepart_id, td.jumlah, s.nama, s.stok
                        FROM transaksi_sparepart_detail td
                        JOIN sparepart s ON td.sparepart_id = s.id
                        WHERE td.transaksi_id = ?
                    ");
                    $stmt_det->execute([$transaksi_id]);
                    $details = $stmt_det->fetchAll();

                    $stmt_upd_stok = $pdo->prepare("UPDATE sparepart SET stok = ? WHERE id = ?");
                    $stmt_log = $pdo->prepare("INSERT INTO log_stok (item_id, tipe_item, user_id, jumlah_sebelum, jumlah_sesudah) VALUES (?, 'sparepart', ?, ?, ?)");

                    foreach ($details as $d) {
                        $stok_sekarang = (int)$d['stok'];
                        $jumlah = (int)$d['jumlah'];

                        if ($status_baru === 'dibatalkan') {
                            $stok_baru = $stok_sekarang + $jumlah;
                        } else {
                            $stok_baru = max(0, $stok_sekarang - $jumlah);
                        }

                        if ($stok_baru !== $stok_sekarang) {
                            $stmt_upd_stok->execute([$stok_baru, $d['sparepart_id']]);
                            $stmt_log->execute([$d['sparepart_id'], $_SESSION['user_id'], $stok_sekarang, $stok_baru]);
                        }
                    }
                }

                if ($status_baru === 'dibatalkan') {
                    $pdo->prepare("UPDATE jadwal_servis SET status = 'batal' WHERE transaksi_sparepart_id = ?")
                        ->execute([$transaksi_id]);
                } elseif ($status_lama === 'dibatalkan') {
                    $pdo->prepare("UPDATE jadwal_servis SET status = 'dijadwalkan' WHERE transaksi_sparepart_id = ? AND status = 'batal'")
                        ->execute([$transaksi_id]);
                }

                $stmt_upd = $pdo->prepare("UPDATE transaksi_sparepart SET status = ? WHERE id = ?");
                $stmt_upd->execute([$status_baru, $transaksi_id]);
            }

            $pdo->commit();
            $pesan = "Status transaksi berhasil diperbarui.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $pesan_error = "Gagal memperbarui transaksi: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("
    SELECT t.*, u.nama AS nama_customer, u.email AS email_customer
    FROM transaksi_sparepart t
    JOIN users u ON t.customer_id = u.id
    ORDER BY t.waktu DESC
");
$transaksi_list = $stmt->fetchAll();

$stmt_detail = $pdo->prepare("
    SELECT td.*, s.nama
    FROM transaksi_sparepart_detail td
    JOIN sparepart s ON td.sparepart_id = s.id
    WHERE td.transaksi_id = ?
");

$bisa_ubah = in_array($_SESSION['role_nama'], ['admin_servis'], true);

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Kelola Transaksi Sparepart</h2>

    <?php if ($pesan): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <?php if (empty($transaksi_list)): ?>
        <div style="background: #fff; padding: 3rem; border-radius: 8px; text-align: center; margin-top: 2rem;">
            <p>Belum ada transaksi sparepart.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 1.25rem; margin-top: 2rem;">
            <?php foreach ($transaksi_list as $t): ?>
                <?php
                $stmt_detail->execute([$t['id']]);
                $details = $stmt_detail->fetchAll();
                ?>
                <div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                        <div>
                            <strong>Order #<?= str_pad($t['id'], 5, '0', STR_PAD_LEFT) ?></strong>
                            <p style="font-size: 0.9rem; color: var(--color-slate-grey); margin: 0.25rem 0 0;">
                                <?= htmlspecialchars($t['nama_customer']) ?> &bull; <?= htmlspecialchars($t['email_customer']) ?><br>
                                <?= date('d M Y H:i', strtotime($t['waktu'])) ?> &bull; Pengambilan: <?= ucfirst($t['metode_ambil']) ?>
                            </p>
                        </div>
                        <span style="display: inline-block; padding: 0.3rem 0.75rem; border-radius: 4px; font-size: 0.85rem; white-space: nowrap; background: <?= $t['status'] == 'menunggu' ? '#fef3c7' : ($t['status'] == 'selesai' ? '#dcfce3' : '#fee2e2') ?>; color: <?= $t['status'] == 'menunggu' ? '#92400e' : ($t['status'] == 'selesai' ? '#166534' : '#991b1b') ?>;">
                            <?= ucfirst($t['status']) ?>
                        </span>
                    </div>

                    <ul style="list-style: none; padding: 0; margin: 0 0 1rem;">
                        <?php foreach ($details as $d): ?>
                            <li style="display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 0.4rem;">
                                <span><?= $d['jumlah'] ?>x <?= htmlspecialchars($d['nama']) ?></span>
                                <span>Rp <?= number_format($d['harga_satuan'] * $d['jumlah'], 0, ',', '.') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; border-top: 1px dashed #ccc; padding-top: 1rem; flex-wrap: wrap;">
                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--color-brass-gold);">
                            Total: Rp <?= number_format($t['total'], 0, ',', '.') ?>
                        </div>

                        <?php if ($bisa_ubah): ?>
                            <form method="POST" action="" style="display: flex; gap: 0.5rem; align-items: center;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="transaksi_id" value="<?= $t['id'] ?>">
                                <select name="status_baru" style="padding: 0.45rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem;">
                                    <option value="menunggu" <?= $t['status'] == 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="selesai" <?= $t['status'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="dibatalkan" <?= $t['status'] == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                </select>
                                <button type="submit" name="update_status" class="btn" style="padding: 0.45rem 0.9rem; font-size: 0.85rem;">Update</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
