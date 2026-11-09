<?php
require_once '../config/database.php';
require_once '../includes/upload.php';

cek_role(['admin_gudang']);

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
        $foto = '';
        if (isset($_FILES['foto'])) {
            $hasil = validasi_upload_gambar($_FILES['foto']);
            if ($hasil['status'] === 'ok') {
                $foto = simpan_upload_gambar($_FILES['foto'], $hasil['ext'], '../assets/images/') ?? '';
                if ($foto === '') {
                    $pesan_error = "Gagal menyimpan file gambar.";
                }
            } elseif ($hasil['status'] === 'error') {
                $pesan_error = $hasil['pesan'];
            }
        }

        if (!$pesan_error) {
            $stmt = $pdo->prepare("INSERT INTO sparepart (nama, kompatibilitas, harga, stok, butuh_instalasi, foto) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $kompatibilitas, $harga, $stok, $butuh_instalasi, $foto]);

            $sparepart_id = $pdo->lastInsertId();
            $stmt_log = $pdo->prepare("INSERT INTO log_stok (item_id, tipe_item, user_id, jumlah_sebelum, jumlah_sesudah) VALUES (?, 'sparepart', ?, 0, ?)");
            $stmt_log->execute([$sparepart_id, $_SESSION['user_id'], $stok]);

            $pesan_sukses = "Sparepart berhasil ditambahkan.";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Tambah Sparepart Baru</h2>

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
                <input type="text" name="nama" required placeholder="Contoh: Oli Mesin Synthetic">
            </div>
            <div class="form-group">
                <label>Kompatibilitas</label>
                <input type="text" name="kompatibilitas" placeholder="Toyota Avanza, Daihatsu Xenia, dll">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" required min="0" placeholder="150000">
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" required min="0" value="1">
                </div>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="butuh_instalasi" style="width: auto;">
                    <span>Butuh Instalasi</span>
                </label>
            </div>
            <div class="form-group">
                <label>Foto</label>
                <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
                <small style="color: var(--color-slate-grey);">JPG/PNG/WEBP, maks 2MB</small>
            </div>
            <button type="submit" class="btn">Tambah Sparepart</button>
            <a href="kelola_sparepart.php" style="margin-left: 1rem;">Kembali</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
