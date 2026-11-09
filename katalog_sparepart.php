<?php
require_once 'config/database.php';

require_once 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM sparepart ORDER BY id DESC");
$sparepart_list = $stmt->fetchAll();

$img_fallback = "https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=600&q=80&auto=format&fit=crop";
?>

<section class="auto-hero" style="padding: 2.5rem 0;">
    <div class="container" style="text-align: center;">
        <p class="auto-eyebrow hero-in" style="--d:.05s;">Sparepart</p>
        <h2 class="auto-section-title hero-in" style="--d:.12s; color: #fff;">Katalog Sparepart</h2>
        <span class="brand-rule center on-dark hero-in" style="--d:.16s;"></span>
        <p class="hero-in" style="--d:.2s; color: var(--auto-text-dim); margin-top: 0.5rem;">Suku cadang asli dan berkualitas untuk performa maksimal.</p>
    </div>
</section>

<section class="auto-section-light" style="padding-top: 2.5rem;">
<div class="container">
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
        <?php foreach ($sparepart_list as $part): ?>
            <div class="auto-cat-card reveal" style="text-align: left; display: flex; flex-direction: column; padding: 0; overflow: hidden;">
                <?php if ($part['foto']): ?>
                    <img src="assets/images/<?= htmlspecialchars($part['foto']) ?>" width="300" height="180" alt="<?= htmlspecialchars($part['nama']) ?>" loading="lazy" decoding="async" style="height: 180px;" onerror="this.onerror=null;this.src='<?= $img_fallback ?>'">
                <?php else: ?>
                    <img src="<?= $img_fallback ?>" width="300" height="180" alt="<?= htmlspecialchars($part['nama']) ?>" loading="lazy" decoding="async" style="height: 180px;" onerror="this.style.display='none'">
                <?php endif; ?>
                <div style="padding: 1.25rem; flex-grow: 1; display: flex; flex-direction: column;">
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.05rem;"><?= htmlspecialchars($part['nama']) ?></h3>
                    <p style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem;">
                        Rp <?= number_format($part['harga'], 0, ',', '.') ?>
                    </p>
                    <a href="detail_sparepart.php?id=<?= $part['id'] ?>" class="auto-btn-primary" style="text-align: center; margin-top: auto; padding: 0.5rem; font-size: 0.88rem;">Lihat Detail</a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($sparepart_list)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: #fff; border-radius: 16px;">
                <p>Belum ada data sparepart yang tersedia.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</section>

<?php require_once 'includes/footer.php'; ?>
