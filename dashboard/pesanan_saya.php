<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_nama'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/header.php';

$customer_id = $_SESSION['user_id'];

// Ambil pesanan mobil
$stmt_mobil = $pdo->prepare("SELECT p.*, m.nama as nama_mobil, m.harga FROM pengajuan_beli_mobil p JOIN mobil m ON p.mobil_id = m.id WHERE p.customer_id = ? ORDER BY p.waktu DESC");
$stmt_mobil->execute([$customer_id]);
$pesanan_mobil = $stmt_mobil->fetchAll();

// Ambil pesanan sparepart
$stmt_sparepart = $pdo->prepare("
    SELECT t.*, j.waktu_mulai AS jadwal_servis, j.status AS status_jadwal
    FROM transaksi_sparepart t
    LEFT JOIN jadwal_servis j ON j.transaksi_sparepart_id = t.id
    WHERE t.customer_id = ?
    ORDER BY t.waktu DESC
");
$stmt_sparepart->execute([$customer_id]);
$pesanan_sparepart = $stmt_sparepart->fetchAll();
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Pesanan Saya</h2>
    
    <?php if (isset($_GET['sukses'])): ?>
        <div class="alert alert-success">Pesanan berhasil dibuat! Anda dapat melihat detailnya di bawah ini.</div>
    <?php endif; ?>

    <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem;">Pengajuan Pembelian Mobil</h3>
    <?php if(empty($pesanan_mobil)): ?>
        <p style="color: var(--color-slate-grey);">Belum ada pengajuan pembelian mobil.</p>
    <?php else: ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($pesanan_mobil as $pm): ?>
                <div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin-bottom: 0.25rem;"><?= htmlspecialchars($pm['nama_mobil']) ?></h4>
                        <p style="font-size: 0.9rem; color: var(--color-slate-grey);">Diajukan pada: <?= date('d M Y H:i', strtotime($pm['waktu'])) ?> &bull; Jumlah: <?= (int)$pm['jumlah'] ?> unit</p>
                        <?php if($pm['catatan']): ?>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem; font-style: italic;">"<?= htmlspecialchars($pm['catatan']) ?>"</p>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: right;">
                        <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 4px; font-weight: 500; font-size: 0.9rem; background-color: <?= $pm['status'] == 'menunggu' ? '#fef3c7' : ($pm['status'] == 'diproses' ? '#e0e7ff' : ($pm['status'] == 'selesai' ? '#dcfce3' : '#fee2e2')) ?>; color: <?= $pm['status'] == 'menunggu' ? '#92400e' : ($pm['status'] == 'diproses' ? '#3730a3' : ($pm['status'] == 'selesai' ? '#166534' : '#991b1b')) ?>;">
                            <?= ucfirst($pm['status']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h3 style="margin-top: 3rem; margin-bottom: 1rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem;">Pembelian Sparepart</h3>
    <?php if(empty($pesanan_sparepart)): ?>
        <p style="color: var(--color-slate-grey);">Belum ada riwayat pembelian sparepart.</p>
    <?php else: ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($pesanan_sparepart as $ps): ?>
                <div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 1rem;">
                        <div>
                            <span style="font-weight: 600; display: block; margin-bottom: 0.25rem;">Order #<?= str_pad($ps['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            <span style="font-size: 0.9rem; color: var(--color-slate-grey);"><?= date('d M Y H:i', strtotime($ps['waktu'])) ?> &bull; Pengambilan: <?= $ps['metode_ambil'] == 'servis' ? 'Pasang saat servis' : 'Ambil di toko' ?></span>
                            <?php if ($ps['metode_ambil'] == 'servis' && $ps['jadwal_servis']): ?>
                                <br><span style="font-size: 0.85rem; color: var(--color-brass-gold);">Jadwal servis: <?= date('d M Y H:i', strtotime($ps['jadwal_servis'])) ?> (<?= ucfirst(str_replace('_', ' ', $ps['status_jadwal'])) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right;">
                            <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 4px; font-weight: 500; font-size: 0.9rem; background-color: <?= $ps['status'] == 'menunggu' ? '#fef3c7' : ($ps['status'] == 'selesai' ? '#dcfce3' : '#fee2e2') ?>; color: <?= $ps['status'] == 'menunggu' ? '#92400e' : ($ps['status'] == 'selesai' ? '#166534' : '#991b1b') ?>;">
                                <?= ucfirst($ps['status']) ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($ps['status'] == 'menunggu'): ?>
                        <p style="font-size: 0.85rem; color: var(--color-slate-grey); margin-bottom: 0.75rem; padding: 0.5rem; background: #fffbeb; border-radius: 4px;">
                            <?= $ps['metode_ambil'] == 'servis' ? 'Pesanan menunggu dipasang saat servis sesuai jadwal.' : 'Pesanan menunggu diambil di toko.' ?>
                        </p>
                    <?php endif; ?>
                    <div>
                        <?php
                        $stmt_det = $pdo->prepare("SELECT td.*, s.nama FROM transaksi_sparepart_detail td JOIN sparepart s ON td.sparepart_id = s.id WHERE td.transaksi_id = ?");
                        $stmt_det->execute([$ps['id']]);
                        $details = $stmt_det->fetchAll();
                        ?>
                        <ul style="list-style: none; padding-left: 0;">
                            <?php foreach($details as $det): ?>
                                <li style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.95rem;">
                                    <span><?= $det['jumlah'] ?>x <?= htmlspecialchars($det['nama']) ?></span>
                                    <span>Rp <?= number_format($det['harga_satuan'] * $det['jumlah'], 0, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div style="display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #ccc; font-weight: 600;">
                            <span>Total</span>
                            <span style="color: var(--color-brass-gold);">Rp <?= number_format($ps['total'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
