<?php
require_once '../config/database.php';

cek_role(['teknisi']);

$teknisi_id = $_SESSION['user_id'];

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    if (isset($_POST['update_status'])) {
        $jadwal_id = (int)$_POST['jadwal_id'];
        $status_baru = $_POST['status_baru'];
        $biaya_jasa = max(0, (float)($_POST['biaya_jasa'] ?? 0));
        $status_valid = ['dijadwalkan', 'berjalan', 'selesai'];

        if (!in_array($status_baru, $status_valid, true)) {
            $pesan_error = "Status tidak valid.";
        } else {
            $stmt_cek = $pdo->prepare("SELECT status, transaksi_sparepart_id FROM jadwal_servis WHERE id = ? AND teknisi_id = ?");
            $stmt_cek->execute([$jadwal_id, $teknisi_id]);
            $jadwal = $stmt_cek->fetch();

            if (!$jadwal) {
                $pesan_error = "Jadwal tidak ditemukan atau bukan milik Anda.";
            } else {
                try {
                    $pdo->beginTransaction();

                    if ($status_baru === 'selesai') {
                        $stmt = $pdo->prepare("UPDATE jadwal_servis SET status = ?, biaya_jasa = ? WHERE id = ? AND teknisi_id = ?");
                        $stmt->execute([$status_baru, $biaya_jasa, $jadwal_id, $teknisi_id]);

                        $sparepart_terpasang = trim($_POST['sparepart_terpasang'] ?? '');
                        $catatan = trim($_POST['catatan'] ?? '');

                        if ($jadwal['status'] !== 'selesai') {
                            $stmt_riwayat = $pdo->prepare("
                                INSERT INTO riwayat_servis (customer_id, jadwal_servis_id, sparepart_terpasang, biaya_jasa, catatan)
                                SELECT customer_id, ?, ?, ?, ? FROM jadwal_servis WHERE id = ?
                            ");
                            $stmt_riwayat->execute([$jadwal_id, $sparepart_terpasang, $biaya_jasa, $catatan, $jadwal_id]);
                        }

                        if ($jadwal['transaksi_sparepart_id']) {
                            $pdo->prepare("UPDATE transaksi_sparepart SET status = 'selesai' WHERE id = ? AND status = 'menunggu'")
                                ->execute([$jadwal['transaksi_sparepart_id']]);
                        }
                    } else {
                        $stmt = $pdo->prepare("UPDATE jadwal_servis SET status = ? WHERE id = ? AND teknisi_id = ?");
                        $stmt->execute([$status_baru, $jadwal_id, $teknisi_id]);
                    }

                    $pdo->commit();
                    $pesan_sukses = "Status servis berhasil diperbarui.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $pesan_error = "Gagal memperbarui status: " . $e->getMessage();
                }
            }
        }
    }
}

$stmt = $pdo->prepare("
    SELECT j.*, u.nama as nama_customer, u.email as email_customer,
        (SELECT GROUP_CONCAT(CONCAT(td.jumlah, 'x ', s.nama) SEPARATOR ', ')
         FROM transaksi_sparepart_detail td
         JOIN sparepart s ON td.sparepart_id = s.id
         WHERE td.transaksi_id = j.transaksi_sparepart_id) AS sparepart_reservasi
    FROM jadwal_servis j
    JOIN users u ON j.customer_id = u.id
    WHERE j.teknisi_id = ? AND j.status != 'selesai' AND j.status != 'batal'
    ORDER BY j.waktu_mulai ASC
");
$stmt->execute([$teknisi_id]);
$jadwal_aktif = $stmt->fetchAll();

$stmt_riwayat = $pdo->prepare("
    SELECT r.*, j.waktu_mulai as waktu_servis, u.nama as nama_customer
    FROM riwayat_servis r
    JOIN jadwal_servis j ON r.jadwal_servis_id = j.id
    JOIN users u ON j.customer_id = u.id
    WHERE j.teknisi_id = ?
    ORDER BY r.created_at DESC
");
$stmt_riwayat->execute([$teknisi_id]);
$riwayat = $stmt_riwayat->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Jadwal Servis Saya</h2>

    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan_sukses) ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
        <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 1.5rem;">Jadwal Aktif</h3>
            <?php if (empty($jadwal_aktif)): ?>
                <p style="color: var(--color-slate-grey);">Tidak ada jadwal servis aktif.</p>
            <?php else: ?>
                <?php foreach ($jadwal_aktif as $j): ?>
                    <div style="padding: 1.5rem; border: 1px solid #eee; border-radius: 8px; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <strong style="font-size: 1.1rem;"><?= htmlspecialchars($j['nama_customer']) ?></strong>
                                <p style="color: var(--color-slate-grey); font-size: 0.9rem; margin: 0.25rem 0;">
                                    <?= date('d M Y H:i', strtotime($j['waktu_mulai'])) ?>
                                </p>
                            </div>
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; background: #fef3c7; color: #92400e; font-size: 0.8rem; border-radius: 4px;">
                                <?= ucfirst(str_replace('_', ' ', $j['status'])) ?>
                            </span>
                        </div>
                        <p style="margin: 0.75rem 0; font-size: 0.95rem;"><?= nl2br(htmlspecialchars($j['catatan'])) ?></p>
                        <?php if ($j['sparepart_reservasi']): ?>
                            <p style="margin: 0 0 0.75rem; font-size: 0.9rem; color: var(--color-brass-gold);">
                                Sparepart dipesan: <?= htmlspecialchars($j['sparepart_reservasi']) ?>
                            </p>
                        <?php endif; ?>

                        <form method="POST" action="" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="jadwal_id" value="<?= $j['id'] ?>">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status_baru" required>
                                    <option value="dijadwalkan" <?= $j['status'] == 'dijadwalkan' ? 'selected' : '' ?>>Dijadwalkan</option>
                                    <option value="berjalan" <?= $j['status'] == 'berjalan' ? 'selected' : '' ?>>Berjalan</option>
                                    <option value="selesai" <?= $j['status'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Biaya Jasa (Rp)</label>
                                <input type="number" name="biaya_jasa" min="0" step="1000" value="<?= (float)$j['biaya_jasa'] ?>" placeholder="Contoh: 150000">
                            </div>
                            <div class="form-group">
                                <label>Sparepart Terpasang</label>
                                <input type="text" name="sparepart_terpasang" placeholder="Contoh: Oli mesin, Filter udara">
                            </div>
                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea name="catatan" rows="2" placeholder="Catatan servis..."></textarea>
                            </div>
                            <button type="submit" name="update_status" class="btn" style="padding: 0.5rem 1rem;">Update Status</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 1.5rem;">Riwayat Servis Selesai</h3>
            <?php if (empty($riwayat)): ?>
                <p style="color: var(--color-slate-grey);">Belum ada riwayat servis selesai.</p>
            <?php else: ?>
                <?php foreach ($riwayat as $r): ?>
                    <div style="padding: 1rem; border-left: 4px solid var(--color-slate-grey); background: #fafafa; margin-bottom: 1rem; border-radius: 0 4px 4px 0;">
                        <strong><?= htmlspecialchars($r['nama_customer']) ?></strong>
                        <p style="font-size: 0.9rem; color: var(--color-slate-grey); margin: 0.25rem 0;">
                            <?= date('d M Y H:i', strtotime($r['waktu_servis'])) ?>
                        </p>
                        <?php if ($r['sparepart_terpasang']): ?>
                            <p style="font-size: 0.85rem; color: var(--color-brass-gold); margin: 0.25rem 0;">
                                Sparepart: <?= htmlspecialchars($r['sparepart_terpasang']) ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($r['biaya_jasa'] > 0): ?>
                            <p style="font-size: 0.85rem; color: var(--color-slate-grey); margin: 0.25rem 0;">
                                Biaya jasa: Rp <?= number_format($r['biaya_jasa'], 0, ',', '.') ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($r['catatan']): ?>
                            <p style="font-size: 0.85rem; color: var(--color-slate-grey); margin: 0.25rem 0;">
                                <?= htmlspecialchars($r['catatan']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
