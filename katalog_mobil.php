<?php
require_once 'config/database.php';

require_once 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM mobil ORDER BY id DESC");
$mobil_list = $stmt->fetchAll();

$img_fallback = [
    "https://images.unsplash.com/photo-1555215695-3004980ad54e?w=600&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=600&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=600&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1542362567-b07e54358753?w=600&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=600&q=80&auto=format&fit=crop",
];
?>

<section class="auto-hero" style="padding: 2.5rem 0;">
    <div class="container" style="text-align: center;">
        <p class="auto-eyebrow hero-in" style="--d:.05s;">Katalog</p>
        <h2 class="auto-section-title hero-in" style="--d:.12s; color: #fff;">Katalog Mobil</h2>
        <span class="brand-rule center on-dark hero-in" style="--d:.16s;"></span>
        <p class="hero-in" style="--d:.2s; color: var(--auto-text-dim); margin-top: 0.5rem;">Temukan kendaraan yang sesuai kebutuhan Anda, lengkap dengan spesifikasi.</p>
    </div>
</section>

<section class="auto-section-light" style="padding-top: 2.5rem;">
<div class="container">
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <?php foreach ($mobil_list as $i => $mobil): ?>
            <div class="auto-cat-card reveal" style="text-align: left; display: flex; flex-direction: column; padding: 0; overflow: hidden;">
                <?php if ($mobil['foto']): ?>
                    <img src="assets/images/<?= htmlspecialchars($mobil['foto']) ?>" width="300" height="200" alt="<?= htmlspecialchars($mobil['nama']) ?>" loading="lazy" decoding="async" style="height: 200px;" onerror="this.onerror=null;this.src='<?= $img_fallback[$i % count($img_fallback)] ?>'">
                <?php else: ?>
                    <img src="<?= $img_fallback[$i % count($img_fallback)] ?>" width="300" height="200" alt="<?= htmlspecialchars($mobil['nama']) ?>" loading="lazy" decoding="async" style="height: 200px;" onerror="this.style.display='none'">
                <?php endif; ?>
                <div style="padding: 1.25rem; flex-grow: 1; display: flex; flex-direction: column;">
                    <h3 style="margin-bottom: 0.25rem; font-size: 1.15rem;"><?= htmlspecialchars($mobil['nama']) ?></h3>
                    <p style="color: var(--color-slate-grey); margin-bottom: 0.75rem; font-size: 0.88rem;">
                        <?= htmlspecialchars($mobil['tahun']) ?> &bull; <?= htmlspecialchars($mobil['cc']) ?> CC &bull; <?= htmlspecialchars($mobil['warna']) ?>
                    </p>
                    <p style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem;">
                        Rp <?= number_format($mobil['harga'], 0, ',', '.') ?>
                    </p>
                    <a href="detail_mobil.php?id=<?= $mobil['id'] ?>" class="auto-btn-primary" style="text-align: center; margin-top: auto; font-size: 0.88rem;">Lihat Detail</a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($mobil_list)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: #fff; border-radius: 16px;">
                <p>Belum ada data mobil yang tersedia.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</section>

<?php require_once 'includes/footer.php'; ?>
