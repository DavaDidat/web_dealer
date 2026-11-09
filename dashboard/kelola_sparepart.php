<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_nama'], ['admin_gudang'])) {
    die("Akses ditolak.");
}

$pesan = '';

// Proses update stok
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stok'])) {
    verify_csrf();
    $sparepart_id = (int)$_POST['sparepart_id'];
    $stok_baru = (int)$_POST['stok'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT stok FROM sparepart WHERE id = ?");
    $stmt->execute([$sparepart_id]);
    $sparepart = $stmt->fetch();
    
    if ($sparepart) {
        $stok_lama = $sparepart['stok'];
        
        if ($stok_lama != $stok_baru) {
            try {
                $pdo->beginTransaction();
                
                $stmt_upd = $pdo->prepare("UPDATE sparepart SET stok = ? WHERE id = ?");
                $stmt_upd->execute([$stok_baru, $sparepart_id]);
                
                $stmt_log = $pdo->prepare("INSERT INTO log_stok (item_id, tipe_item, user_id, jumlah_sebelum, jumlah_sesudah) VALUES (?, 'sparepart', ?, ?, ?)");
                $stmt_log->execute([$sparepart_id, $user_id, $stok_lama, $stok_baru]);

                if ($stok_baru < 5) {
                    $stmt_nama = $pdo->prepare("SELECT nama FROM sparepart WHERE id = ?");
                    $stmt_nama->execute([$sparepart_id]);
                    $nama_sparepart = $stmt_nama->fetchColumn();
                    notifikasi_ke_role('admin_gudang', "Stok sparepart {$nama_sparepart} menipis (sisa {$stok_baru} unit).");
                }

                $pdo->commit();
                $pesan = "Stok sparepart berhasil diperbarui.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $pesan = "Gagal memperbarui stok: " . $e->getMessage();
            }
        }
    }
}

// Ambil data sparepart
$stmt = $pdo->query("SELECT * FROM sparepart ORDER BY id DESC");
$sparepart_list = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Kelola Stok Sparepart</h2>
        <a href="tambah_sparepart.php" class="btn">+ Tambah Sparepart</a>
    </div>

    <?php if($pesan): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; margin-top: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background-color: var(--color-graphite-black); color: var(--color-warm-ivory);">
                <tr>
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Nama Sparepart</th>
                    <th style="padding: 1rem;">Harga</th>
                    <th style="padding: 1rem;">Stok</th>
                    <th style="padding: 1rem;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sparepart_list as $s): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;"><?= $s['id'] ?></td>
                        <td style="padding: 1rem;">
                            <strong><?= htmlspecialchars($s['nama']) ?></strong><br>
                            <small><?= $s['butuh_instalasi'] ? 'Butuh Instalasi' : 'Pemasangan Mandiri' ?></small>
                        </td>
                        <td style="padding: 1rem;">Rp <?= number_format($s['harga'], 0, ',', '.') ?></td>
                        <td style="padding: 1rem;">
                            <form method="POST" action="" style="display: flex; gap: 0.5rem; align-items: center;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="sparepart_id" value="<?= $s['id'] ?>">
                                <input type="number" name="stok" value="<?= $s['stok'] ?>" min="0" style="width: 70px; padding: 0.4rem; border: 1px solid #ccc; border-radius: 4px;">
                                <button type="submit" name="update_stok" class="btn" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">Update</button>
                            </form>
                        </td>
                        <td style="padding: 1rem;">
                            <a href="edit_sparepart.php?id=<?= $s['id'] ?>" style="margin-right: 0.5rem; font-size: 0.9rem;">Edit</a>
                            <a href="hapus_sparepart.php?id=<?= $s['id'] ?>" onclick="return confirm('Yakin ingin hapus sparepart ini?')" style="color: #e53e3e; font-size: 0.9rem;">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($sparepart_list)): ?>
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center;">Belum ada data sparepart.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
