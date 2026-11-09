<?php
require_once '../config/database.php';

cek_role(['marketing']);

$pesan = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    verify_csrf();
    $pengajuan_id = (int)$_POST['pengajuan_id'];
    $status_baru = $_POST['status_baru'];
    $status_valid = ['menunggu', 'diproses', 'selesai', 'dibatalkan'];

    if (!in_array($status_baru, $status_valid, true)) {
        $pesan_error = "Status tidak valid.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt_cur = $pdo->prepare("
                SELECT p.status, p.mobil_id, p.jumlah, m.stok, m.nama
                FROM pengajuan_beli_mobil p
                JOIN mobil m ON p.mobil_id = m.id
                WHERE p.id = ?
                FOR UPDATE
            ");
            $stmt_cur->execute([$pengajuan_id]);
            $cur = $stmt_cur->fetch();

            if (!$cur) {
                throw new Exception("Pengajuan tidak ditemukan.");
            }

            $stmt_upd = $pdo->prepare("UPDATE pengajuan_beli_mobil SET status = ? WHERE id = ?");
            $stmt_upd->execute([$status_baru, $pengajuan_id]);

            $stok_sekarang = (int)$cur['stok'];
            $jumlah_beli = max(1, (int)$cur['jumlah']);
            $perlu_ubah_stok = false;
            $stok_baru = $stok_sekarang;

            if ($status_baru === 'selesai' && $cur['status'] !== 'selesai') {
                $stok_baru = max(0, $stok_sekarang - $jumlah_beli);
                $perlu_ubah_stok = true;
            } elseif ($cur['status'] === 'selesai' && $status_baru !== 'selesai') {
                $stok_baru = $stok_sekarang + $jumlah_beli;
                $perlu_ubah_stok = true;
            }

            if ($perlu_ubah_stok) {
                $pdo->prepare("UPDATE mobil SET stok = ? WHERE id = ?")
                    ->execute([$stok_baru, $cur['mobil_id']]);
                $pdo->prepare("INSERT INTO log_stok (item_id, tipe_item, user_id, jumlah_sebelum, jumlah_sesudah) VALUES (?, 'mobil', ?, ?, ?)")
                    ->execute([$cur['mobil_id'], $_SESSION['user_id'], $stok_sekarang, $stok_baru]);
            }

            $pdo->commit();
            $pesan = "Status pengajuan berhasil diperbarui.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $pesan_error = "Gagal memperbarui status: " . $e->getMessage();
        }
    }
}

$filter = $_GET['filter'] ?? 'semua';
$sql = "
    SELECT p.*, u.nama as nama_customer, u.email as email_customer, m.nama as nama_mobil, m.harga
    FROM pengajuan_beli_mobil p
    JOIN users u ON p.customer_id = u.id
    JOIN mobil m ON p.mobil_id = m.id
";
if ($filter != 'semua') {
    $sql .= " WHERE p.status = ?";
}
$sql .= " ORDER BY p.waktu DESC";

$stmt = $pdo->prepare($sql);
if ($filter != 'semua') {
    $stmt->execute([$filter]);
} else {
    $stmt->execute();
}
$pengajuan_list = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Daftar Pengajuan Pembelian Mobil</h2>

    <?php if ($pesan): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <div style="margin: 1.5rem 0;">
        <form method="GET" action="" style="display: flex; gap: 0.5rem; align-items: center;">
            <label style="font-weight: 500;">Filter:</label>
            <select name="filter" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                <option value="semua" <?= $filter == 'semua' ? 'selected' : '' ?>>Semua</option>
                <option value="menunggu" <?= $filter == 'menunggu' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                <option value="diproses" <?= $filter == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                <option value="selesai" <?= $filter == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                <option value="dibatalkan" <?= $filter == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
        </form>
    </div>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background-color: var(--color-graphite-black); color: var(--color-warm-ivory);">
                <tr>
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Customer</th>
                    <th style="padding: 1rem;">Mobil</th>
                    <th style="padding: 1rem;">Jumlah</th>
                    <th style="padding: 1rem;">Harga</th>
                    <th style="padding: 1rem;">Status</th>
                    <th style="padding: 1rem;">Tanggal</th>
                    <th style="padding: 1rem;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pengajuan_list as $p): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;"><?= $p['id'] ?></td>
                        <td style="padding: 1rem;">
                            <strong><?= htmlspecialchars($p['nama_customer']) ?></strong><br>
                            <small><?= htmlspecialchars($p['email_customer']) ?></small>
                        </td>
                        <td style="padding: 1rem;"><?= htmlspecialchars($p['nama_mobil']) ?></td>
                        <td style="padding: 1rem;"><?= (int)$p['jumlah'] ?> unit</td>
                        <td style="padding: 1rem;">Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                        <td style="padding: 1rem;">
                            <?php
                            $warna = [
                                'menunggu' => '#fef3c7',
                                'diproses' => '#dbeafe',
                                'selesai' => '#dcfce3',
                                'dibatalkan' => '#fee2e2'
                            ][$p['status']] ?? '#e2e8f0';
                            ?>
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; background: <?= $warna ?>; border-radius: 4px; font-size: 0.85rem;">
                                <?= ucfirst(str_replace('_', ' ', $p['status'])) ?>
                            </span>
                        </td>
                        <td style="padding: 1rem;"><?= date('d M Y H:i', strtotime($p['waktu'])) ?></td>
                        <td style="padding: 1rem;">
                            <form method="POST" action="" style="display: flex; gap: 0.5rem; align-items: center;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="pengajuan_id" value="<?= $p['id'] ?>">
                                <select name="status_baru" style="padding: 0.4rem; font-size: 0.85rem; border: 1px solid #ccc; border-radius: 4px;">
                                    <option value="menunggu" <?= $p['status'] == 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="diproses" <?= $p['status'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                    <option value="selesai" <?= $p['status'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="dibatalkan" <?= $p['status'] == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                </select>
                                <button type="submit" name="update_status" class="btn" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pengajuan_list)): ?>
                    <tr>
                        <td colspan="8" style="padding: 2rem; text-align: center;">Belum ada pengajuan pembelian.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
