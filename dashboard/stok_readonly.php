<?php
require_once '../config/database.php';

cek_role(['marketing', 'admin_servis']);

$stmt_mobil = $pdo->query("SELECT * FROM mobil ORDER BY id DESC");
$mobil_list = $stmt_mobil->fetchAll();

$stmt_sparepart = $pdo->query("SELECT * FROM sparepart ORDER BY id DESC");
$sparepart_list = $stmt_sparepart->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Stok Read-Only</h2>
    <p style="color: var(--color-steel-grey); margin-bottom: 2rem;">Menampilkan ketersediaan stok mobil dan sparepart. Anda tidak dapat mengubah data di halaman ini.</p>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 3rem;">
        <div style="padding: 1rem 1.5rem; background: var(--color-graphite-dark); color: #fff;">
            <h3 style="margin: 0; font-size: 1.1rem;">Stok Mobil</h3>
        </div>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: #f8f9fa;">
                <tr>
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Nama Mobil</th>
                    <th style="padding: 1rem;">Harga</th>
                    <th style="padding: 1rem;">Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mobil_list as $m): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;"><?= $m['id'] ?></td>
                        <td style="padding: 1rem;">
                            <strong><?= htmlspecialchars($m['nama']) ?></strong><br>
                            <small><?= $m['tahun'] ?> &bull; <?= htmlspecialchars($m['warna']) ?> &bull; <?= $m['cc'] ?>cc</small>
                        </td>
                        <td style="padding: 1rem;">Rp <?= number_format($m['harga'], 0, ',', '.') ?></td>
                        <td style="padding: 1rem;">
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; background: <?= $m['stok'] > 0 ? '#dcfce3' : '#fee2e2' ?>; border-radius: 4px; font-weight: 600;">
                                <?= $m['stok'] ?> unit
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($mobil_list)): ?>
                    <tr>
                        <td colspan="4" style="padding: 2rem; text-align: center;">Belum ada data mobil.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="padding: 1rem 1.5rem; background: var(--color-graphite-dark); color: #fff;">
            <h3 style="margin: 0; font-size: 1.1rem;">Stok Sparepart</h3>
        </div>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: #f8f9fa;">
                <tr>
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Nama Sparepart</th>
                    <th style="padding: 1rem;">Harga</th>
                    <th style="padding: 1rem;">Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sparepart_list as $s): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;"><?= $s['id'] ?></td>
                        <td style="padding: 1rem;">
                            <strong><?= htmlspecialchars($s['nama']) ?></strong><br>
                            <small><?= $s['butuh_instalasi'] ? 'Butuh Instalasi' : 'Pemasangan Mandiri' ?></small>
                        </td>
                        <td style="padding: 1rem;">Rp <?= number_format($s['harga'], 0, ',', '.') ?></td>
                        <td style="padding: 1rem;">
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; background: <?= $s['stok'] > 0 ? '#dcfce3' : '#fee2e2' ?>; border-radius: 4px; font-weight: 600;">
                                <?= $s['stok'] ?> unit
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($sparepart_list)): ?>
                    <tr>
                        <td colspan="4" style="padding: 2rem; text-align: center;">Belum ada data sparepart.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
