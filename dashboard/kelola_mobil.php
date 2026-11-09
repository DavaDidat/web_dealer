<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_nama'], ['admin_gudang'])) {
    die("Akses ditolak.");
}

$pesan = '';

// Proses update stok
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stok'])) {
    verify_csrf();
    $mobil_id = (int)$_POST['mobil_id'];
    $stok_baru = (int)$_POST['stok'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT stok FROM mobil WHERE id = ?");
    $stmt->execute([$mobil_id]);
    $mobil = $stmt->fetch();
    
    if ($mobil) {
        $stok_lama = $mobil['stok'];
        
        if ($stok_lama != $stok_baru) {
            try {
                $pdo->beginTransaction();
                
                $stmt_upd = $pdo->prepare("UPDATE mobil SET stok = ? WHERE id = ?");
                $stmt_upd->execute([$stok_baru, $mobil_id]);
                
                $stmt_log = $pdo->prepare("INSERT INTO log_stok (item_id, tipe_item, user_id, jumlah_sebelum, jumlah_sesudah) VALUES (?, 'mobil', ?, ?, ?)");
                $stmt_log->execute([$mobil_id, $user_id, $stok_lama, $stok_baru]);

                if ($stok_baru < 5) {
                    $stmt_nama = $pdo->prepare("SELECT nama FROM mobil WHERE id = ?");
                    $stmt_nama->execute([$mobil_id]);
                    $nama_mobil = $stmt_nama->fetchColumn();
                    notifikasi_ke_role('admin_gudang', "Stok mobil {$nama_mobil} menipis (sisa {$stok_baru} unit).");
                }

                $pdo->commit();
                $pesan = "Stok mobil berhasil diperbarui.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $pesan = "Gagal memperbarui stok: " . $e->getMessage();
            }
        }
    }
}

// Ambil data mobil
$stmt = $pdo->query("SELECT * FROM mobil ORDER BY id DESC");
$mobil_list = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Kelola Stok Mobil</h2>
        <a href="tambah_mobil.php" class="btn">+ Tambah Mobil</a>
    </div>

    <?php if($pesan): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; margin-top: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background-color: var(--color-graphite-black); color: var(--color-warm-ivory);">
                <tr>
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Nama Mobil</th>
                    <th style="padding: 1rem;">Harga</th>
                    <th style="padding: 1rem;">Stok</th>
                    <th style="padding: 1rem;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mobil_list as $m): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;"><?= $m['id'] ?></td>
                        <td style="padding: 1rem;">
                            <strong><?= htmlspecialchars($m['nama']) ?></strong><br>
                            <small><?= $m['tahun'] ?> • <?= $m['warna'] ?> • <?= $m['cc'] ?>cc</small>
                        </td>
                        <td style="padding: 1rem;">Rp <?= number_format($m['harga'], 0, ',', '.') ?></td>
                        <td style="padding: 1rem;">
                            <form method="POST" action="" style="display: flex; gap: 0.5rem; align-items: center;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="mobil_id" value="<?= $m['id'] ?>">
                                <input type="number" name="stok" value="<?= $m['stok'] ?>" min="0" style="width: 70px; padding: 0.4rem; border: 1px solid #ccc; border-radius: 4px;">
                                <button type="submit" name="update_stok" class="btn" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">Update</button>
                            </form>
                        </td>
                        <td style="padding: 1rem;">
                            <a href="edit_mobil.php?id=<?= $m['id'] ?>" style="margin-right: 0.5rem; font-size: 0.9rem;">Edit</a>
                            <a href="hapus_mobil.php?id=<?= $m['id'] ?>" onclick="return confirm('Yakin ingin hapus mobil ini?')" style="color: #e53e3e; font-size: 0.9rem;">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($mobil_list)): ?>
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center;">Belum ada data mobil.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
