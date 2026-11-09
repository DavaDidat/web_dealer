<?php
require_once '../config/database.php';
require_once '../includes/upload.php';

cek_role(['admin_gudang']);

$sparepart_id = (int)($_GET['id'] ?? 0);
if (!$sparepart_id) {
    header("Location: kelola_sparepart.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM sparepart WHERE id = ?");
$stmt->execute([$sparepart_id]);
$sparepart = $stmt->fetch();

if (!$sparepart) {
    header("Location: kelola_sparepart.php");
    exit;
}

$pesan_error = '';
$pesan_sukses = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $nama = trim($_POST['nama'] ?? '');
    $kompatibilitas = trim($_POST['kompatibilitas'] ?? '');
    $harga = (float)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $butuh_instalasi = isset($_POST['butuh_instalasi']) ? 1 : 0;

    if (empty($nama) || $harga <= 0) {
        $pesan_error = "Nama dan harga wajib diisi dengan benar.";
    } else {
        $foto = $sparepart['foto'];
        if (isset($_FILES['foto'])) {
            $hasil = validasi_upload_gambar($_FILES['foto']);
            if ($hasil['status'] === 'ok') {
                $nama_baru = simpan_upload_gambar($_FILES['foto'], $hasil['ext'], '../assets/images/');
                if ($nama_baru) {
                    $foto = $nama_baru;
                    if ($sparepart['foto'] && file_exists('../assets/images/' . $sparepart['foto'])) {
                        unlink('../assets/images/' . $sparepart['foto']);
                    }
                } else {
                    $pesan_error = "Gagal menyimpan file gambar.";
                }
            } elseif ($hasil['status'] === 'error') {
                $pesan_error = $hasil['pesan'];
            }
        }

        if (!$pesan_error) {
            $stmt_upd = $pdo->prepare("UPDATE sparepart SET nama = ?, kompatibilitas = ?, harga = ?, stok = ?, butuh_instalasi = ?, foto = ? WHERE id = ?");
            $stmt_upd->execute([$nama, $kompatibilitas, $harga, $stok, $butuh_instalasi, $foto, $sparepart_id]);

            if ($stok != $sparepart['stok']) {
                $stmt_log = $pdo->prepare("INSERT INTO log_stok (item_id, tipe_item, user_id, jumlah_sebelum, jumlah_sesudah) VALUES (?, 'sparepart', ?, ?, ?)");
                $stmt_log->execute([$sparepart_id, $_SESSION['user_id'], $sparepart['stok'], $stok]);
            }

            $pesan_sukses = "Sparepart berhasil diperbarui.";
            $sparepart['stok'] = $stok;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Edit Sparepart</h2>

    <?php if ($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>
    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan_sukses) ?></div>
    <?php endif; ?>

    <div style="max-width: 600px; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 2rem;">
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Nama Sparepart</label>
                <input type="text" name="nama" required value="<?= htmlspecialchars($sparepart['nama']) ?>">
            </div>
            <div class="form-group">
                <label>Kompatibilitas</label>
                <input type="text" name="kompatibilitas" value="<?= htmlspecialchars($sparepart['kompatibilitas']) ?>">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" required min="0" value="<?= $sparepart['harga'] ?>">
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" required min="0" value="<?= $sparepart['stok'] ?>">
                </div>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="butuh_instalasi" style="width: auto;" <?= $sparepart['butuh_instalasi'] ? 'checked' : '' ?>>
                    <span>Butuh Instalasi</span>
                </label>
            </div>
            <div class="form-group">
                <label>Foto Saat Ini</label>
                <?php if ($sparepart['foto']): ?>
                    <img src="../assets/images/<?= htmlspecialchars($sparepart['foto']) ?>" style="max-width: 200px; border-radius: 4px;">
                <?php else: ?>
                    <p style="color: var(--color-slate-grey);">Tidak ada foto.</p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Ganti Foto</label>
                <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
                <small style="color: var(--color-slate-grey);">JPG/PNG/WEBP, maks 2MB</small>
            </div>
            <button type="submit" class="btn">Simpan Perubahan</button>
            <a href="kelola_sparepart.php" style="margin-left: 1rem;">Kembali</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
