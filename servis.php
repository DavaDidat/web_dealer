<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_nama'] != 'customer') {
    header("Location: auth/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

$pesan_error = '';
$pesan_sukses = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_servis'])) {
    verify_csrf();
    $jenis_servis = trim($_POST['jenis_servis'] ?? '');
    $kendaraan = trim($_POST['kendaraan'] ?? '');
    $keluhan = trim($_POST['keluhan'] ?? '');
    $tanggal_diinginkan = $_POST['tanggal_diinginkan'] ?? '';
    $jam_diinginkan = $_POST['jam_diinginkan'] ?? '';
    $durasi_menit = (int)($_POST['durasi_menit'] ?? 60);
    $durasi_valid = [30, 60, 90, 120];

    if (!in_array($durasi_menit, $durasi_valid, true)) {
        $durasi_menit = 60;
    }

    if (empty($jenis_servis) || empty($kendaraan) || empty($keluhan) || empty($tanggal_diinginkan) || empty($jam_diinginkan)) {
        $pesan_error = "Semua field wajib diisi.";
    } else {
        $waktu_mulai = $tanggal_diinginkan . ' ' . $jam_diinginkan . ':00';

        if (cekJadwalBentrok($pdo, $waktu_mulai, $durasi_menit, 'customer_id', $customer_id)) {
            $pesan_error = "Anda sudah memiliki jadwal servis lain pada waktu tersebut.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO jadwal_servis (customer_id, admin_servis_id, waktu_mulai, durasi_menit, status, catatan) VALUES (?, NULL, ?, ?, 'dijadwalkan', ?)");
            $stmt->execute([$customer_id, $waktu_mulai, $durasi_menit, "Jenis: $jenis_servis | Kendaraan: $kendaraan | Keluhan: $keluhan"]);

            notifikasi_ke_role('admin_servis', "Booking servis baru dari customer.");

            header("Location: servis.php?berhasil=1");
            exit;
        }
    }
}

$stmt = $pdo->prepare("
    SELECT j.*, u.nama as nama_teknisi, u.nama as nama_admin_servis,
        (SELECT GROUP_CONCAT(CONCAT(td.jumlah, 'x ', s.nama) SEPARATOR ', ')
         FROM transaksi_sparepart_detail td
         JOIN sparepart s ON td.sparepart_id = s.id
         WHERE td.transaksi_id = j.transaksi_sparepart_id) AS sparepart_reservasi
    FROM jadwal_servis j
    LEFT JOIN users u ON j.teknisi_id = u.id
    WHERE j.customer_id = ?
    ORDER BY j.waktu_mulai DESC
");
$stmt->execute([$customer_id]);
$jadwal_saya = $stmt->fetchAll();

$stmt_riwayat = $pdo->prepare("
    SELECT r.*, j.waktu_mulai as waktu_servis, u.nama as nama_teknisi
    FROM riwayat_servis r
    JOIN jadwal_servis j ON r.jadwal_servis_id = j.id
    LEFT JOIN users u ON j.teknisi_id = u.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
$stmt_riwayat->execute([$customer_id]);
$riwayat = $stmt_riwayat->fetchAll();

require_once 'includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Servis Kendaraan</h2>

    <?php if ($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['berhasil'])): ?>
        <div class="alert alert-success">Pengajuan servis berhasil dibuat. Admin Servis akan menghubungi Anda via chat untuk konfirmasi jadwal.</div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
        <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 1.5rem;">Ajukan Servis Baru</h3>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Jenis Servis</label>
                    <select name="jenis_servis" required>
                        <option value="">-- Pilih Jenis Servis --</option>
                        <option value="servis_rutin">Servis Rutin</option>
                        <option value="ganti_sparepart">Ganti Sparepart</option>
                        <option value="perbaikan">Perbaikan</option>
                        <option value="inspeksi">Inspeksi Berkala</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kendaraan</label>
                    <input type="text" name="kendaraan" placeholder="Contoh: Toyota Avanza 2020" required>
                </div>
                <div class="form-group">
                    <label>Keluhan / Kebutuhan</label>
                    <textarea name="keluhan" rows="4" placeholder="Jelaskan keluhan atau kebutuhan servis Anda..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Tanggal Diinginkan</label>
                    <input type="date" name="tanggal_diinginkan" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Jam Diinginkan</label>
                    <select name="jam_diinginkan" required>
                        <option value="">-- Pilih Jam --</option>
                        <option value="08:00">08:00</option>
                        <option value="09:00">09:00</option>
                        <option value="10:00">10:00</option>
                        <option value="11:00">11:00</option>
                        <option value="13:00">13:00</option>
                        <option value="14:00">14:00</option>
                        <option value="15:00">15:00</option>
                        <option value="16:00">16:00</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estimasi Durasi</label>
                    <select name="durasi_menit">
                        <option value="30">30 menit</option>
                        <option value="60" selected>60 menit</option>
                        <option value="90">90 menit</option>
                        <option value="120">120 menit</option>
                    </select>
                </div>
                <button type="submit" name="ajukan_servis" class="btn">Ajukan Servis</button>
            </form>
        </div>

        <div>
            <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1.5rem;">Jadwal Servis Aktif</h3>
                <?php if (empty($jadwal_saya)): ?>
                    <p style="color: var(--color-slate-grey);">Belum ada jadwal servis aktif.</p>
                <?php else: ?>
                    <?php foreach ($jadwal_saya as $j): ?>
                        <div style="padding: 1rem; border-left: 4px solid var(--color-brass-gold); background: #fafafa; margin-bottom: 1rem; border-radius: 0 4px 4px 0;">
                            <strong><?= date('d M Y H:i', strtotime($j['waktu_mulai'])) ?></strong>
                            <p style="font-size: 0.9rem; color: var(--color-slate-grey); margin: 0.25rem 0;">
                                <?= htmlspecialchars($j['catatan']) ?>
                            </p>
                            <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #e2e8f0; font-size: 0.8rem; border-radius: 4px;">
                                <?= ucfirst(str_replace('_', ' ', $j['status'])) ?>
                            </span>
                            <?php if ($j['sparepart_reservasi']): ?>
                                <p style="font-size: 0.85rem; color: var(--color-brass-gold); margin: 0.25rem 0;">
                                    Sparepart dipesan: <?= htmlspecialchars($j['sparepart_reservasi']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($j['biaya_jasa'] > 0): ?>
                                <p style="font-size: 0.85rem; color: var(--color-slate-grey); margin: 0.25rem 0;">
                                    Biaya jasa: Rp <?= number_format($j['biaya_jasa'], 0, ',', '.') ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($j['nama_teknisi']): ?>
                                <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #dcfce3; font-size: 0.8rem; border-radius: 4px; margin-left: 0.5rem;">
                                    Teknisi: <?= htmlspecialchars($j['nama_teknisi']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 1.5rem;">Riwayat Servis</h3>
                <?php if (empty($riwayat)): ?>
                    <p style="color: var(--color-slate-grey);">Belum ada riwayat servis.</p>
                <?php else: ?>
                    <?php foreach ($riwayat as $r): ?>
                        <div style="padding: 1rem; border-left: 4px solid var(--color-slate-grey); background: #fafafa; margin-bottom: 1rem; border-radius: 0 4px 4px 0;">
                            <strong><?= date('d M Y H:i', strtotime($r['waktu_servis'])) ?></strong>
                            <p style="font-size: 0.9rem; color: var(--color-slate-grey); margin: 0.25rem 0;">
                                <?= htmlspecialchars($r['catatan']) ?>
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
                            <?php if ($r['nama_teknisi']): ?>
                                <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #e2e8f0; font-size: 0.8rem; border-radius: 4px;">
                                    Oleh: <?= htmlspecialchars($r['nama_teknisi']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
