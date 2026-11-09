<?php
require_once 'config/database.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM sparepart WHERE id = ?");
$stmt->execute([$id]);
$part = $stmt->fetch();

if (!$part) {
    die("Sparepart tidak ditemukan.");
}

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_keranjang'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth/login.php");
        exit;
    }
    verify_csrf();
    
    $customer_id = $_SESSION['user_id'];
    $jumlah = (int)$_POST['jumlah'];
    
    if ($jumlah > $part['stok']) {
        $pesan_error = "Jumlah pesanan melebihi stok yang tersedia.";
    } elseif ($jumlah > 0) {
        // Cek apakah barang sudah ada di keranjang
        $stmt_cek = $pdo->prepare("SELECT id, jumlah FROM keranjang WHERE customer_id = ? AND sparepart_id = ?");
        $stmt_cek->execute([$customer_id, $part['id']]);
        $keranjang_item = $stmt_cek->fetch();
        
        if ($keranjang_item) {
            $jumlah_baru = $keranjang_item['jumlah'] + $jumlah;
            if ($jumlah_baru > $part['stok']) {
                $pesan_error = "Total pesanan di keranjang Anda melebihi stok yang tersedia.";
            } else {
                $stmt_upd = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ?");
                $stmt_upd->execute([$jumlah_baru, $keranjang_item['id']]);
                $pesan_sukses = "Sparepart berhasil ditambahkan ke keranjang!";
            }
        } else {
            $stmt_ins = $pdo->prepare("INSERT INTO keranjang (customer_id, sparepart_id, jumlah) VALUES (?, ?, ?)");
            $stmt_ins->execute([$customer_id, $part['id'], $jumlah]);
            $pesan_sukses = "Sparepart berhasil ditambahkan ke keranjang!";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container" style="margin-top: 2rem; padding-bottom: 3rem;">
    <?php if($pesan_sukses): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan_sukses) ?> <a href="keranjang.php">Lihat Keranjang</a></div>
    <?php endif; ?>
    <?php if($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <p class="auto-muted reveal" style="margin-bottom: 1.25rem; font-size: 0.88rem;">
        <a href="katalog_sparepart.php">Katalog Sparepart</a> &nbsp;/&nbsp; <span><?= htmlspecialchars($part['nama']) ?></span>
    </p>

    <div class="landing-grid-2" style="align-items: start;">
        <div class="reveal" style="background: #fff; border: 1px solid #E6EAF5; border-radius: 14px; overflow: hidden; height: 350px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(16,23,40,0.08);">
            <?php if ($part['foto']): ?>
                <img src="assets/images/<?= htmlspecialchars($part['foto']) ?>" width="600" height="350" alt="<?= htmlspecialchars($part['nama']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <span style="color: var(--color-slate-grey);">Foto tidak tersedia</span>
            <?php endif; ?>
        </div>

        <div class="reveal">
            <h2 style="font-size: 2.3rem; margin-bottom: 0.25rem;"><?= htmlspecialchars($part['nama']) ?></h2>
            <span class="brand-rule"></span>
            <p style="font-size: 1.6rem; font-weight: 800; color: var(--brand-blue-deep); margin-bottom: 1.5rem; font-family: var(--font-heading);">
                Rp <?= number_format($part['harga'], 0, ',', '.') ?>
            </p>

            <div style="background: #fff; padding: 1.5rem; border: 1px solid #E6EAF5; border-radius: 12px; margin-bottom: 2rem;">
                <h3 style="font-size: 1.15rem; margin-bottom: 1rem; border-bottom: 1px solid #EEF1F7; padding-bottom: 0.5rem;">Spesifikasi &amp; Kompatibilitas</h3>
                <ul style="list-style: none; display: grid; gap: 0.75rem;">
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-slate-grey);">Stok Tersedia</span>
                        <strong><?= htmlspecialchars($part['stok']) ?> unit</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-slate-grey);">Butuh Instalasi di Bengkel?</span>
                        <strong><?= $part['butuh_instalasi'] ? 'Ya' : 'Tidak (Bisa dipasang sendiri)' ?></strong>
                    </li>
                </ul>
                <div style="margin-top: 1rem; color: var(--color-slate-grey); line-height: 1.6;">
                    <strong>Kompatibilitas:</strong><br>
                    <?= nl2br(htmlspecialchars($part['kompatibilitas'] ?: '-')) ?>
                </div>
            </div>

            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: stretch; margin-bottom: 3rem;">
                <?php if ($part['stok'] > 0): ?>
                    <form method="POST" action="" style="flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: 1rem;">
                        <?= csrf_field() ?>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <label for="jumlah">Jumlah:</label>
                            <input type="number" id="jumlah" name="jumlah" value="1" min="1" max="<?= $part['stok'] ?>" style="width: 80px; padding: 0.5rem; border: 1px solid #C4CBD8; border-radius: 8px;">
                        </div>
                        <button type="submit" name="tambah_keranjang" class="btn" style="width: 100%;">Tambah ke Keranjang</button>
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
