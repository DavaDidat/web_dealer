<?php
require_once '../config/database.php';

cek_role(['admin_servis']);

$admin_servis_id = $_SESSION['user_id'];

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    if (isset($_POST['assign_teknisi'])) {
        $jadwal_id = (int)$_POST['jadwal_id'];
        $teknisi_id = (int)$_POST['teknisi_id'];

        $stmt_j = $pdo->prepare("SELECT waktu_mulai, durasi_menit FROM jadwal_servis WHERE id = ?");
        $stmt_j->execute([$jadwal_id]);
        $jadwal_target = $stmt_j->fetch();

        if (!$jadwal_target) {
            $pesan_error = "Jadwal tidak ditemukan.";
        } elseif (!$teknisi_id) {
            $pesan_error = "Pilih teknisi terlebih dahulu.";
        } elseif (cekJadwalBentrok($pdo, $jadwal_target['waktu_mulai'], $jadwal_target['durasi_menit'], 'teknisi_id', $teknisi_id, $jadwal_id)) {
            $pesan_error = "Teknisi sudah memiliki jadwal lain pada rentang waktu tersebut.";
        } else {
            $stmt = $pdo->prepare("UPDATE jadwal_servis SET teknisi_id = ? WHERE id = ?");
            $stmt->execute([$teknisi_id, $jadwal_id]);
            buat_notifikasi($teknisi_id, "Anda di-assign ke jadwal servis baru.");
            $pesan_sukses = "Teknisi berhasil di-assign.";
        }
    } elseif (isset($_POST['update_status'])) {
        $jadwal_id = (int)$_POST['jadwal_id'];
        $status_baru = $_POST['status_baru'];
        $stmt = $pdo->prepare("UPDATE jadwal_servis SET status = ? WHERE id = ?");
        $stmt->execute([$status_baru, $jadwal_id]);
        $pesan_sukses = "Status jadwal diperbarui.";
    } elseif (isset($_POST['tambah_jadwal'])) {
        $customer_id = (int)$_POST['customer_id'];
        $teknisi_id = (int)$_POST['teknisi_id'];
        $waktu_mulai = $_POST['waktu_mulai'];
        $catatan = trim($_POST['catatan'] ?? '');
        $durasi_menit = (int)($_POST['durasi_menit'] ?? 60);
        if (!in_array($durasi_menit, [30, 60, 90, 120], true)) {
            $durasi_menit = 60;
        }

        if (cekJadwalBentrok($pdo, $waktu_mulai, $durasi_menit, 'customer_id', $customer_id)) {
            $pesan_error = "Customer sudah memiliki jadwal lain pada rentang waktu tersebut.";
        } elseif ($teknisi_id && cekJadwalBentrok($pdo, $waktu_mulai, $durasi_menit, 'teknisi_id', $teknisi_id)) {
            $pesan_error = "Teknisi sudah memiliki jadwal lain pada rentang waktu tersebut.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO jadwal_servis (customer_id, teknisi_id, admin_servis_id, waktu_mulai, durasi_menit, status, catatan) VALUES (?, ?, ?, ?, ?, 'dijadwalkan', ?)");
            $stmt->execute([$customer_id, $teknisi_id ?: null, $admin_servis_id, $waktu_mulai, $durasi_menit, $catatan]);
            if ($teknisi_id) {
                buat_notifikasi($teknisi_id, "Anda di-assign ke jadwal servis baru.");
            }
            $pesan_sukses = "Jadwal servis baru ditambahkan.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT j.*, u.nama as nama_customer, t.nama as nama_teknisi,
        (SELECT GROUP_CONCAT(CONCAT(td.jumlah, 'x ', s.nama) SEPARATOR ', ')
         FROM transaksi_sparepart_detail td
         JOIN sparepart s ON td.sparepart_id = s.id
         WHERE td.transaksi_id = j.transaksi_sparepart_id) AS sparepart_reservasi
    FROM jadwal_servis j
    JOIN users u ON j.customer_id = u.id
    LEFT JOIN users t ON j.teknisi_id = t.id
    ORDER BY j.waktu_mulai DESC
");
$stmt->execute();
$semua_jadwal = $stmt->fetchAll();

$stmt_teknisi = $pdo->prepare("SELECT id, nama FROM users WHERE role_id = (SELECT id FROM roles WHERE nama_role = 'teknisi')");
$stmt_teknisi->execute();
$teknisi_list = $stmt_teknisi->fetchAll();

$stmt_customer = $pdo->prepare("SELECT id, nama, email FROM users WHERE role_id = (SELECT id FROM roles WHERE nama_role = 'customer')");
$stmt_customer->execute();
$customer_list = $stmt_customer->fetchAll();

$bulan_ini = date('Y-m');
$stmt_kalender = $pdo->prepare("
    SELECT j.*, u.nama as nama_customer, t.nama as nama_teknisi
    FROM jadwal_servis j
    JOIN users u ON j.customer_id = u.id
    LEFT JOIN users t ON j.teknisi_id = t.id
    WHERE DATE_FORMAT(j.waktu_mulai, '%Y-%m') = ?
    ORDER BY j.waktu_mulai ASC
");
$stmt_kalender->execute([$bulan_ini]);
$kalender = $stmt_kalender->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Kelola Jadwal Servis</h2>

    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan_sukses) ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-top: 2rem;">
        <div>
            <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1.5rem;">Tambah Jadwal Manual</h3>
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Customer</label>
                        <select name="customer_id" required>
                            <option value="">-- Pilih Customer --</option>
                            <?php foreach ($customer_list as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Teknisi</label>
                        <select name="teknisi_id">
                            <option value="">-- Pilih Teknisi --</option>
                            <?php foreach ($teknisi_list as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Waktu Mulai</label>
                        <input type="datetime-local" name="waktu_mulai" required>
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
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="catatan" rows="3" placeholder="Catatan servis..."></textarea>
                    </div>
                    <button type="submit" name="tambah_jadwal" class="btn">Tambah Jadwal</button>
                </form>
            </div>

            <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 1.5rem;">Kalender Bulan Ini</h3>
                <?php if (empty($kalender)): ?>
                    <p style="color: var(--color-slate-grey);">Tidak ada jadwal bulan ini.</p>
                <?php else: ?>
                    <?php foreach ($kalender as $k): ?>
                        <div style="padding: 0.75rem; border-left: 4px solid var(--color-brass-gold); background: #fafafa; margin-bottom: 0.75rem; border-radius: 0 4px 4px 0;">
                            <strong style="font-size: 0.9rem;"><?= date('d M H:i', strtotime($k['waktu_mulai'])) ?></strong>
                            <p style="font-size: 0.85rem; color: var(--color-slate-grey); margin: 0.1rem 0;">
                                <?= htmlspecialchars($k['nama_customer']) ?>
                                <?php if ($k['nama_teknisi']): ?>
                                    &rarr; <?= htmlspecialchars($k['nama_teknisi']) ?>
                                <?php endif; ?>
                            </p>
                            <span style="display: inline-block; padding: 0.15rem 0.5rem; background: #e2e8f0; font-size: 0.75rem; border-radius: 4px;">
                                <?= ucfirst(str_replace('_', ' ', $k['status'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 1.5rem;">Semua Jadwal Servis</h3>
            <?php if (empty($semua_jadwal)): ?>
                <p style="color: var(--color-slate-grey);">Belum ada jadwal servis.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #eee;">
                                <th style="text-align: left; padding: 0.75rem;">Tanggal</th>
                                <th style="text-align: left; padding: 0.75rem;">Customer</th>
                                <th style="text-align: left; padding: 0.75rem;">Teknisi</th>
                                <th style="text-align: left; padding: 0.75rem;">Status</th>
                                <th style="text-align: left; padding: 0.75rem;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($semua_jadwal as $j): ?>
                                <tr style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 0.75rem;"><?= date('d M Y H:i', strtotime($j['waktu_mulai'])) ?><br><span style="font-size: 0.78rem; color: var(--color-slate-grey);"><?= (int)$j['durasi_menit'] ?> menit</span></td>
                                    <td style="padding: 0.75rem;">
                                        <?= htmlspecialchars($j['nama_customer']) ?>
                                        <?php if (!empty($j['sparepart_reservasi'])): ?>
                                            <br><span style="font-size: 0.78rem; color: var(--color-brass-gold);">Reservasi: <?= htmlspecialchars($j['sparepart_reservasi']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <?php if ($j['nama_teknisi']): ?>
                                            <?= htmlspecialchars($j['nama_teknisi']) ?>
                                        <?php else: ?>
                                            <span style="color: #e53e3e;">Belum di-assign</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #e2e8f0; font-size: 0.8rem; border-radius: 4px;">
                                            <?= ucfirst(str_replace('_', ' ', $j['status'])) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <form method="POST" action="" style="display: flex; gap: 0.5rem; align-items: center;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="jadwal_id" value="<?= $j['id'] ?>">
                                            <select name="teknisi_id" style="padding: 0.4rem; font-size: 0.85rem; border: 1px solid #ccc; border-radius: 4px;">
                                                <option value="">-- Pilih --</option>
                                                <?php foreach ($teknisi_list as $t): ?>
                                                    <option value="<?= $t['id'] ?>" <?= $j['teknisi_id'] == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nama']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_teknisi" class="btn" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">Assign</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
