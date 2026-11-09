<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_nama'] != 'customer') {
    header("Location: auth/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

// Hapus item dari keranjang
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND customer_id = ?");
    $stmt->execute([$hapus_id, $customer_id]);
    header("Location: keranjang.php");
    exit;
}

// Proses checkout
$pesan_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    verify_csrf();
    $metode_ambil = $_POST['metode_ambil'] ?? '';
    $tanggal_servis = $_POST['tanggal_servis'] ?? '';
    $jam_servis = $_POST['jam_servis'] ?? '';

    try {
        if (!in_array($metode_ambil, ['toko', 'servis'], true)) {
            throw new Exception("Metode pengambilan tidak valid.");
        }

        $waktu_servis = null;
        if ($metode_ambil === 'servis') {
            if (empty($tanggal_servis) || empty($jam_servis)) {
                throw new Exception("Tanggal dan jam servis wajib diisi untuk metode pemasangan saat servis.");
            }
            if ($tanggal_servis < date('Y-m-d')) {
                throw new Exception("Tanggal servis tidak boleh di masa lalu.");
            }
            $waktu_servis = $tanggal_servis . ' ' . $jam_servis . ':00';
        }

        $pdo->beginTransaction();

        // Hitung total dan ambil item
        $stmt_items = $pdo->prepare("SELECT k.*, s.nama, s.harga, s.stok FROM keranjang k JOIN sparepart s ON k.sparepart_id = s.id WHERE k.customer_id = ? FOR UPDATE");
        $stmt_items->execute([$customer_id]);
        $items = $stmt_items->fetchAll();

        if (empty($items)) {
            throw new Exception("Keranjang kosong.");
        }

        $total = 0;
        foreach ($items as $item) {
            if ($item['jumlah'] > $item['stok']) {
                throw new Exception("Stok untuk beberapa barang tidak mencukupi.");
            }
            $total += $item['harga'] * $item['jumlah'];
        }

        // Buat transaksi
        $stmt_trans = $pdo->prepare("INSERT INTO transaksi_sparepart (customer_id, metode_ambil, status, total) VALUES (?, ?, 'menunggu', ?)");
        $stmt_trans->execute([$customer_id, $metode_ambil, $total]);
        $transaksi_id = (int)$pdo->lastInsertId();

        // Pindahkan ke transaksi detail dan update stok (stok berkurang saat checkout berhasil)
        $stmt_detail = $pdo->prepare("INSERT INTO transaksi_sparepart_detail (transaksi_id, sparepart_id, jumlah, harga_satuan) VALUES (?, ?, ?, ?)");
        $stmt_upd_stok = $pdo->prepare("UPDATE sparepart SET stok = stok - ? WHERE id = ?");
        $ringkasan_item = [];

        foreach ($items as $item) {
            $stmt_detail->execute([$transaksi_id, $item['sparepart_id'], $item['jumlah'], $item['harga']]);
            $stmt_upd_stok->execute([$item['jumlah'], $item['sparepart_id']]);
            $ringkasan_item[] = $item['jumlah'] . 'x ' . $item['nama'];
        }

        // Reservasi pemasangan: buat jadwal servis yang terhubung ke transaksi
        if ($metode_ambil === 'servis') {
            $catatan_servis = "Reservasi sparepart Order #" . str_pad((string)$transaksi_id, 5, '0', STR_PAD_LEFT) . " | " . implode(', ', $ringkasan_item);
            $stmt_jadwal = $pdo->prepare("
                INSERT INTO jadwal_servis (customer_id, transaksi_sparepart_id, waktu_mulai, status, catatan)
                VALUES (?, ?, ?, 'dijadwalkan', ?)
            ");
            $stmt_jadwal->execute([$customer_id, $transaksi_id, $waktu_servis, $catatan_servis]);
        }

        // Kosongkan keranjang
        $stmt_clear = $pdo->prepare("DELETE FROM keranjang WHERE customer_id = ?");
        $stmt_clear->execute([$customer_id]);

        $pdo->commit();

        if ($metode_ambil === 'servis') {
            try {
                notifikasi_ke_role('admin_servis', "Reservasi sparepart baru (Order #" . str_pad((string)$transaksi_id, 5, '0', STR_PAD_LEFT) . ") menunggu penjadwalan.");
            } catch (Throwable $e) {
            }
        }

        header("Location: dashboard/pesanan_saya.php?sukses=1");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pesan_error = $e->getMessage();
    }
}

// Tampilkan isi keranjang
$stmt = $pdo->prepare("SELECT k.id as keranjang_id, k.jumlah, s.id as sparepart_id, s.nama, s.harga, s.foto, s.butuh_instalasi FROM keranjang k JOIN sparepart s ON k.sparepart_id = s.id WHERE k.customer_id = ?");
$stmt->execute([$customer_id]);
$keranjang = $stmt->fetchAll();

$total_belanja = 0;
$ada_instalasi = false;
$servis_dipilih = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['metode_ambil'] ?? '') === 'servis');
$tanggal_servis_value = $_POST['tanggal_servis'] ?? '';
$jam_servis_value = $_POST['jam_servis'] ?? '';

require_once 'includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Keranjang Belanja</h2>
    
    <?php if($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <?php if(empty($keranjang)): ?>
        <div style="background: #fff; padding: 3rem; border-radius: 8px; text-align: center; margin-top: 2rem;">
            <p style="margin-bottom: 1rem;">Keranjang Anda masih kosong.</p>
            <a href="katalog_sparepart.php" class="btn">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem;">
            <div>
                <?php foreach ($keranjang as $item): ?>
                    <?php 
                    $subtotal = $item['harga'] * $item['jumlah']; 
                    $total_belanja += $subtotal;
                    if ($item['butuh_instalasi']) $ada_instalasi = true;
                    ?>
                    <div style="display: flex; gap: 1.5rem; background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 1rem;">
                        <div style="width: 100px; height: 100px; background: #eee; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                            <?php if ($item['foto']): ?>
                                <img src="assets/images/<?= htmlspecialchars($item['foto']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: #999;">No Image</span>
                            <?php endif; ?>
                        </div>
                        <div style="flex-grow: 1;">
                            <h4 style="margin-bottom: 0.5rem; font-size: 1.1rem;"><?= htmlspecialchars($item['nama']) ?></h4>
                            <p style="color: var(--color-slate-grey); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                Rp <?= number_format($item['harga'], 0, ',', '.') ?> x <?= $item['jumlah'] ?>
                            </p>
                            <?php if($item['butuh_instalasi']): ?>
                                <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #e2e8f0; font-size: 0.8rem; border-radius: 4px; color: var(--color-slate-grey);">Butuh Instalasi</span>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right; display: flex; flex-direction: column; justify-content: space-between;">
                            <strong style="font-size: 1.1rem;">Rp <?= number_format($subtotal, 0, ',', '.') ?></strong>
                            <a href="keranjang.php?hapus=<?= $item['keranjang_id'] ?>" style="color: #e53e3e; font-size: 0.9rem;">Hapus</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #eee;">Ringkasan Pesanan</h3>
                <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; font-size: 1.2rem; font-weight: 600;">
                    <span>Total</span>
                    <span style="color: var(--color-brass-gold);">Rp <?= number_format($total_belanja, 0, ',', '.') ?></span>
                </div>
                
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Metode Pengambilan</label>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: normal; cursor: pointer;">
                                <input type="radio" name="metode_ambil" value="toko" required <?= $servis_dipilih ? '' : 'checked' ?>> Ambil di Toko (Tanpa Pemasangan)
                            </label>
                            <?php if ($ada_instalasi): ?>
                                <label style="display: block; font-weight: normal; cursor: pointer;">
                                    <input type="radio" name="metode_ambil" value="servis" required <?= $servis_dipilih ? 'checked' : '' ?>> Sekalian Pasang saat Servis
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($ada_instalasi): ?>
                        <div id="jadwalServisFields" style="display: <?= $servis_dipilih ? 'block' : 'none' ?>; margin-top: 1rem; padding: 1rem; background: #fafafa; border: 1px solid #eee; border-radius: 4px;">
                            <p style="font-size: 0.85rem; color: var(--color-slate-grey); margin-bottom: 0.75rem;">Pilih jadwal pemasangan. Admin Servis akan meng-assign teknisi.</p>
                            <div class="form-group">
                                <label>Tanggal Servis</label>
                                <input type="date" name="tanggal_servis" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($tanggal_servis_value) ?>">
                            </div>
                            <div class="form-group">
                                <label>Jam Servis</label>
                                <select name="jam_servis">
                                    <option value="">-- Pilih Jam --</option>
                                    <?php foreach (['08:00','09:00','10:00','11:00','13:00','14:00','15:00','16:00'] as $jam): ?>
                                        <option value="<?= $jam ?>" <?= $jam_servis_value === $jam ? 'selected' : '' ?>><?= $jam ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                    <button type="submit" name="checkout" class="btn" style="width: 100%; margin-top: 1rem;" onclick="return confirm('Yakin ingin checkout? Item akan dipesan dan keranjang dikosongkan.');">Checkout</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($ada_instalasi): ?>
<script>
function toggleJadwalServis() {
    const servis = document.querySelector('input[name="metode_ambil"][value="servis"]');
    const box = document.getElementById('jadwalServisFields');
    if (!box) return;
    box.style.display = (servis && servis.checked) ? 'block' : 'none';
}
document.querySelectorAll('input[name="metode_ambil"]').forEach(function (radio) {
    radio.addEventListener('change', toggleJadwalServis);
});
toggleJadwalServis();
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
