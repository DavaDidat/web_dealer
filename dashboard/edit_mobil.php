<?php
require_once '../config/database.php';
require_once '../includes/upload.php';

cek_role(['admin_gudang']);

$mobil_id = (int)($_GET['id'] ?? 0);
if (!$mobil_id) {
    header("Location: kelola_mobil.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM mobil WHERE id = ?");
$stmt->execute([$mobil_id]);
$mobil = $stmt->fetch();

if (!$mobil) {
    header("Location: kelola_mobil.php");
    exit;
}

$pesan_error = '';
$pesan_sukses = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $nama = trim($_POST['nama'] ?? '');
    $cc = (int)$_POST['cc'];
    $tahun = (int)$_POST['tahun'];
    $warna = trim($_POST['warna'] ?? '');
    $harga = (float)$_POST['harga'];
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $stok = (int)$_POST['stok'];

    if (empty($nama) || empty($warna) || $harga <= 0) {
        $pesan_error = "Nama, warna, dan harga wajib diisi dengan benar.";
    } else {
        $foto = $mobil['foto'];
        if (isset($_FILES['foto'])) {
            $hasil = validasi_upload_gambar($_FILES['foto']);
            if ($hasil['status'] === 'ok') {
                $nama_baru = simpan_upload_gambar($_FILES['foto'], $hasil['ext'], '../assets/images/');
                if ($nama_baru) {
                    $foto = $nama_baru;
                    if ($mobil['foto'] && file_exists('../assets/images/' . $mobil['foto'])) {
                        unlink('../assets/images/' . $mobil['foto']);
                    }
                } else {
                    $pesan_error = "Gagal menyimpan file gambar.";
                }
            } elseif ($hasil['status'] === 'error') {
                $pesan_error = $hasil['pesan'];
            }
        }

        if (!$pesan_error) {
            $stmt_upd = $pdo->prepare("UPDATE mobil SET nama = ?, cc = ?, tahun = ?, warna = ?, harga = ?, deskripsi = ?, stok = ?, foto = ? WHERE id = ?");
            $stmt_upd->execute([$nama, $cc, $tahun, $warna, $harga, $deskripsi, $stok, $foto, $mobil_id]);

            if ($stok != $mobil['stok']) {
                $stmt_log = $pdo->prepare("INSERT INTO log_stok (item_id, tipe_item, user_id, jumlah_sebelum, jumlah_sesudah) VALUES (?, 'mobil', ?, ?, ?)");
                $stmt_log->execute([$mobil_id, $_SESSION['user_id'], $mobil['stok'], $stok]);
            }

            $pesan_sukses = "Mobil berhasil diperbarui.";
            $mobil['stok'] = $stok;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Edit Mobil</h2>

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
                <label>Nama Mobil</label>
                <input type="text" name="nama" required value="<?= htmlspecialchars($mobil['nama']) ?>">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>CC</label>
                    <input type="number" name="cc" required value="<?= $mobil['cc'] ?>">
                </div>
                <div class="form-group">
                    <label>Tahun</label>
                    <input type="number" name="tahun" required value="<?= $mobil['tahun'] ?>">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Warna</label>
                    <input type="text" name="warna" required value="<?= htmlspecialchars($mobil['warna']) ?>">
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" required min="0" value="<?= $mobil['stok'] ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Harga (Rp)</label>
                <input type="number" name="harga" required min="0" value="<?= $mobil['harga'] ?>">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="4"><?= htmlspecialchars($mobil['deskripsi']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Foto Saat Ini</label>
                <?php if ($mobil['foto']): ?>
                    <img src="../assets/images/<?= htmlspecialchars($mobil['foto']) ?>" style="max-width: 200px; border-radius: 4px;">
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
            <a href="kelola_mobil.php" style="margin-left: 1rem;">Kembali</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
