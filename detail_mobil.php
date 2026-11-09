<?php
require_once 'config/database.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM mobil WHERE id = ?");
$stmt->execute([$id]);
$mobil = $stmt->fetch();

if (!$mobil) {
    die("Mobil tidak ditemukan.");
}

// Logic untuk pengajuan pembelian
$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_beli'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth/login.php");
        exit;
    }
    verify_csrf();
    
    $customer_id = $_SESSION['user_id'];
    $catatan = trim($_POST['catatan'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 1);

    if ($jumlah < 1 || $jumlah > (int)$mobil['stok']) {
        $pesan_error = "Jumlah pengajuan tidak valid. Stok tersedia: " . (int)$mobil['stok'] . " unit.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO pengajuan_beli_mobil (customer_id, mobil_id, jumlah, catatan) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$customer_id, $mobil['id'], $jumlah, $catatan])) {
            $pesan_sukses = "Pengajuan pembelian berhasil dikirim! Tim marketing kami akan segera menghubungi Anda.";
        } else {
            $pesan_error = "Terjadi kesalahan saat mengajukan pembelian.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container" style="margin-top: 2rem; padding-bottom: 3rem;">
    <?php if($pesan_sukses): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan_sukses) ?></div>
    <?php endif; ?>
    <?php if($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <p class="auto-muted reveal" style="margin-bottom: 1.25rem; font-size: 0.88rem;">
        <a href="katalog_mobil.php">Katalog Mobil</a> &nbsp;/&nbsp; <span><?= htmlspecialchars($mobil['nama']) ?></span>
    </p>

    <div class="landing-grid-2" style="align-items: start;">
        <div class="reveal" style="background: #fff; border: 1px solid #E6EAF5; border-radius: 14px; overflow: hidden; height: 400px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(16,23,40,0.08);">
            <?php if ($mobil['foto']): ?>
                <img src="assets/images/<?= htmlspecialchars($mobil['foto']) ?>" width="600" height="400" alt="<?= htmlspecialchars($mobil['nama']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <span style="color: var(--color-slate-grey);">Foto tidak tersedia</span>
            <?php endif; ?>
        </div>

        <div class="reveal">
            <h2 style="font-size: 2.3rem; margin-bottom: 0.25rem;"><?= htmlspecialchars($mobil['nama']) ?></h2>
            <span class="brand-rule"></span>
            <p style="font-size: 1.6rem; font-weight: 800; color: var(--brand-blue-deep); margin-bottom: 2rem; font-family: var(--font-heading);">
                Rp <?= number_format($mobil['harga'], 0, ',', '.') ?>
            </p>

            <div style="background: #fff; padding: 1.5rem; border: 1px solid #E6EAF5; border-radius: 12px; margin-bottom: 2rem;">
                <h3 style="font-size: 1.15rem; margin-bottom: 1rem; border-bottom: 1px solid #EEF1F7; padding-bottom: 0.5rem;">Spesifikasi</h3>
                <ul style="list-style: none; display: grid; gap: 0.75rem;">
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-slate-grey);">Kapasitas Mesin (CC)</span>
                        <strong><?= htmlspecialchars($mobil['cc']) ?> CC</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-slate-grey);">Tahun Pembuatan</span>
                        <strong><?= htmlspecialchars($mobil['tahun']) ?></strong>
                    </li>
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-slate-grey);">Warna</span>
                        <strong><?= htmlspecialchars($mobil['warna']) ?></strong>
                    </li>
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-slate-grey);">Stok Tersedia</span>
                        <strong><?= htmlspecialchars($mobil['stok']) ?> unit</strong>
                    </li>
                </ul>
            </div>

            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Deskripsi</h3>
                <p style="color: var(--color-slate-grey); line-height: 1.7;">
                    <?= nl2br(htmlspecialchars($mobil['deskripsi'] ?: 'Belum ada deskripsi.')) ?>
                </p>
            </div>

            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: stretch; margin-bottom: 3rem;">
                <?php if ($mobil['stok'] > 0): ?>
                    <form method="POST" action="" style="flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: 1rem;">
                        <?= csrf_field() ?>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <label for="jumlah">Jumlah unit:</label>
                            <input type="number" id="jumlah" name="jumlah" value="1" min="1" max="<?= (int)$mobil['stok'] ?>" style="width: 90px; padding: 0.5rem; border: 1px solid #C4CBD8; border-radius: 8px;">
                        </div>
                        <textarea name="catatan" placeholder="Tambahkan catatan untuk marketing (opsional)" style="padding: 0.75rem; border: 1px solid #C4CBD8; border-radius: 8px; font-family: inherit;" rows="2"></textarea>
                        <button type="submit" name="ajukan_beli" class="btn" style="width: 100%;">Ajukan Pembelian</button>
                    </form>
                <?php else: ?>
                    <button class="btn" style="flex: 1; min-width: 220px; background-color: #ccc; cursor: not-allowed;" disabled>Stok Habis</button>
                <?php endif; ?>

                <div style="flex: 1; min-width: 220px; display: flex; flex-direction: column; justify-content: flex-end;">
                    <a href="chat.php?channel=marketing&start=1" class="btn" style="background-color: #fff; color: var(--brand-blue-deep); border: 1.5px solid var(--brand-blue); width: 100%; box-sizing: border-box; text-align: center;">Chat Marketing</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
