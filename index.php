<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/");
    exit;
}

// Hero: 1 mobil unggulan (mobil terbaru yang punya foto)
$stmt_mobil = $pdo->prepare("SELECT * FROM mobil WHERE foto IS NOT NULL AND foto != '' ORDER BY id DESC LIMIT 1");
$stmt_mobil->execute();
$mobil_unggulan = $stmt_mobil->fetch();

// Stats: count mobil, sparepart, customer, servis selesai
$total_mobil = $pdo->query("SELECT COUNT(*) FROM mobil")->fetchColumn();
$total_sparepart = $pdo->query("SELECT COUNT(*) FROM sparepart")->fetchColumn();
$total_customer = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM roles WHERE nama_role = 'customer')")->fetchColumn();
$total_servis = $pdo->query("SELECT COUNT(*) FROM jadwal_servis WHERE status = 'selesai'")->fetchColumn();

// Category cards: group by brand (kata pertama nama mobil)
try {
    $stmt_kat = $pdo->query("SELECT SUBSTRING_INDEX(nama, ' ', 1) AS kategori, COUNT(*) AS jumlah FROM mobil GROUP BY kategori ORDER BY jumlah DESC LIMIT 6");
    $kategori_list = $stmt_kat->fetchAll();
} catch (Exception $e) {
    $kategori_list = [];
}

// Featured cars: 3 mobil terbaru
$stmt_feat = $pdo->prepare("SELECT * FROM mobil ORDER BY id DESC LIMIT 3");
$stmt_feat->execute();
$featured_cars = $stmt_feat->fetchAll();

// Placeholder gambar (dipakai bila kolom foto masih NULL) + fallback via onerror
$img_hero = "https://images.unsplash.com/photo-1555215695-3004980ad54e?w=1000&q=80&auto=format&fit=crop";
$img_showroom = "https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=1000&q=80&auto=format&fit=crop";
$img_cat = [
    "https://images.unsplash.com/photo-1555215695-3004980ad54e?w=600&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=600&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=600&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1542362567-b07e54358753?w=600&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=600&q=80&auto=format&fit=crop",
];
$img_feat_default = [
    "https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800&q=80&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=800&q=80&auto=format&fit=crop",
];

require_once 'includes/header.php';
?>

<section class="auto-hero" style="padding: 3.5rem 0 3rem;">
    <svg class="hero-motif float-slow" viewBox="0 0 200 200" fill="none" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="ypjArrow" x1="0" y1="200" x2="200" y2="0" gradientUnits="userSpaceOnUse">
                <stop offset="0%" stop-color="#2E7DD1"/>
                <stop offset="55%" stop-color="#3F8FE0"/>
                <stop offset="100%" stop-color="#F5821F"/>
            </linearGradient>
        </defs>
        <path d="M25 175 C 70 165, 125 118, 150 62" stroke="url(#ypjArrow)" stroke-width="10" stroke-linecap="round"/>
        <path d="M120 56 L166 44 L158 92" stroke="url(#ypjArrow)" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <div class="container">
        <div class="landing-grid-2">
            <div>
                <p class="auto-eyebrow hero-in" style="--d:.05s;">Showroom Mobil Terpercaya</p>
                <h1 class="auto-hero-title hero-in" style="--d:.12s;">Mobil Berkualitas untuk<br>Setiap Perjalanan</h1>
                <p class="hero-in" style="--d:.2s; color: var(--auto-text-dim); font-size: 1.02rem; line-height: 1.7; margin-bottom: 2rem;">
                    Dari mobil keluarga hingga SUV tangguh, PT Yangpenting Maju menyediakan unit terinspeksi
                    dengan harga transparan dan servis bergaransi.
                </p>
                <div class="hero-in" style="--d:.28s; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="katalog_mobil.php" class="auto-btn-primary">Lihat Katalog Mobil</a>
                    <a href="katalog_sparepart.php" class="auto-btn-ghost">Lihat Sparepart</a>
                </div>
            </div>
            <div class="auto-hero-img hero-in" style="--d:.18s;">
                <?php if ($mobil_unggulan && !empty($mobil_unggulan['foto'])): ?>
                    <img src="assets/images/<?= htmlspecialchars($mobil_unggulan['foto']) ?>" width="600" height="340" alt="<?= htmlspecialchars($mobil_unggulan['nama']) ?>" onerror="this.onerror=null;this.src='<?= $img_hero ?>'">
                <?php else: ?>
                    <img src="<?= $img_hero ?>" width="600" height="340" alt="Mobil unggulan" onerror="this.style.display='none'">
                <?php endif; ?>
                <?php if ($mobil_unggulan): ?>
                <div class="hero-info">
                    <b style="color: #fff;"><?= htmlspecialchars($mobil_unggulan['nama']) ?></b>
                    <span style="color: var(--auto-text-dim); font-size: 0.85rem;"> &middot; <?= $mobil_unggulan['tahun'] ?> &middot; Rp <?= number_format($mobil_unggulan['harga'], 0, ',', '.') ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="auto-stats-bar hero-in" style="--d:.36s;">
            <div class="auto-stat"><div class="ic"><?= ypj_icon('car') ?></div><div><b data-count="<?= (int)$total_mobil ?>"><?= number_format($total_mobil) ?></b><span>Unit Mobil</span></div></div>
            <div class="auto-stat"><div class="ic"><?= ypj_icon('users') ?></div><div><b data-count="<?= (int)$total_customer ?>"><?= number_format($total_customer) ?></b><span>Pelanggan Terdaftar</span></div></div>
            <div class="auto-stat"><div class="ic"><?= ypj_icon('package') ?></div><div><b data-count="<?= (int)$total_sparepart ?>"><?= number_format($total_sparepart) ?></b><span>Jenis Sparepart</span></div></div>
            <div class="auto-stat"><div class="ic"><?= ypj_icon('wrench') ?></div><div><b data-count="<?= (int)$total_servis ?>"><?= number_format($total_servis) ?></b><span>Servis Selesai</span></div></div>
        </div>
    </div>
</section>

<section class="auto-section-light">
    <div class="container">
        <p class="auto-eyebrow reveal" style="color: var(--color-slate-grey);">Kategori</p>
        <div style="display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
            <div>
                <h2 class="auto-section-title reveal">Pilih Sesuai Merk</h2>
                <span class="brand-rule reveal"></span>
            </div>
            <a href="katalog_mobil.php" style="font-weight: 600;">Lihat semua mobil &rarr;</a>
        </div>
        <div class="landing-cards">
            <?php if (!empty($kategori_list)): ?>
                <?php foreach ($kategori_list as $i => $k): ?>
                    <div class="auto-cat-card reveal">
                        <img src="<?= $img_cat[$i % count($img_cat)] ?>" width="400" height="220" alt="<?= htmlspecialchars($k['kategori']) ?>" loading="lazy" decoding="async" onerror="this.style.display='none'">
                        <h4><?= htmlspecialchars($k['kategori']) ?></h4>
                        <p><?= (int)$k['jumlah'] ?> unit tersedia</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="auto-muted reveal">Belum ada kategori.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container" style="padding: 0 1.5rem;"><div class="brand-divider"></div></div>

<section class="auto-section-dark">
    <div class="container">
        <p class="auto-eyebrow reveal">Unit Terbaru</p>
        <div style="display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
            <div>
                <h2 class="auto-section-title reveal" style="color: #fff;">Mobil Baru Masuk</h2>
                <span class="brand-rule on-dark reveal"></span>
            </div>
            <a href="katalog_mobil.php" style="color: #fff; font-weight: 600;">Lihat semua &rarr;</a>
        </div>
        <div class="landing-cards">
            <?php if (!empty($featured_cars)): ?>
                <?php foreach ($featured_cars as $i => $m): ?>
                    <div class="auto-car-card reveal">
                        <div class="imgwrap">
                            <span class="auto-badge">Terbaru</span>
                            <?php if (!empty($m['foto'])): ?>
                                <img src="assets/images/<?= htmlspecialchars($m['foto']) ?>" width="400" height="250" alt="<?= htmlspecialchars($m['nama']) ?>" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='<?= $img_feat_default[$i % 3] ?>'">
                            <?php else: ?>
                                <img src="<?= $img_feat_default[$i % 3] ?>" width="400" height="250" alt="<?= htmlspecialchars($m['nama']) ?>" loading="lazy" decoding="async" onerror="this.style.display='none'">
                            <?php endif; ?>
                        </div>
                        <div class="auto-car-body">
                            <h4><?= htmlspecialchars($m['nama']) ?></h4>
                            <p class="spec"><?= $m['tahun'] ?> &middot; <?= htmlspecialchars($m['warna']) ?> &middot; Stok <?= (int)$m['stok'] ?> unit</p>
                            <p class="auto-car-price">Rp <?= number_format($m['harga'], 0, ',', '.') ?></p>
                            <a href="detail_mobil.php?id=<?= (int)$m['id'] ?>" class="auto-btn-primary" style="width: 100%; text-align: center; font-size: 0.88rem;">Lihat Detail</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--auto-text-dim);">Belum ada data mobil.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="auto-section-light">
    <div class="container">
        <div class="landing-grid-2">
            <div class="auto-hero-img reveal">
                <img src="<?= $img_showroom ?>" width="600" height="320" alt="Showroom PT Yangpenting Maju" loading="lazy" decoding="async" style="height: 320px;" onerror="this.style.display='none'">
            </div>
            <div>
                <p class="auto-eyebrow reveal" style="color: var(--color-slate-grey);">Kualitas &amp; Layanan</p>
                <h2 class="auto-section-title reveal">Unit Terinspeksi,<br>Servis Bergaransi</h2>
                <span class="brand-rule reveal"></span>
                <p class="auto-muted reveal" style="margin: 0 0 1.5rem; font-size: 0.95rem;">Beli mobil dengan tenang: unit sudah diperiksa, harga jelas, dan after-sales yang siaga.</p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="auto-feature-item reveal"><div class="fic"><?= ypj_icon('shield-check') ?></div><div><b>Unit Terinspeksi</b><br><span class="auto-muted" style="font-size: 0.82rem;">Pemeriksaan menyeluruh.</span></div></div>
                    <div class="auto-feature-item reveal"><div class="fic"><?= ypj_icon('credit-card') ?></div><div><b>Pembayaran Fleksibel</b><br><span class="auto-muted" style="font-size: 0.82rem;">Opsi cicilan ringan.</span></div></div>
                    <div class="auto-feature-item reveal"><div class="fic"><?= ypj_icon('repeat') ?></div><div><b>Tukar Tambah</b><br><span class="auto-muted" style="font-size: 0.82rem;">Nilai tukar yang jelas.</span></div></div>
                    <div class="auto-feature-item reveal"><div class="fic"><?= ypj_icon('headphones') ?></div><div><b>Dukungan Teknisi</b><br><span class="auto-muted" style="font-size: 0.82rem;">Teknisi siaga membantu.</span></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="auto-section-dark">
    <div class="container">
        <p class="auto-eyebrow reveal">Cara Membeli</p>
        <h2 class="auto-section-title reveal" style="color: #fff; margin-bottom: 0;">Mudah dalam Tiga Langkah</h2>
        <span class="brand-rule on-dark reveal" style="margin-bottom: 2rem;"></span>
        <div class="auto-steps">
            <div class="step reveal">
                <div class="step-number">1</div>
                <div><h4>Pilih Unit</h4><p>Jelajahi katalog dan buka detail spesifikasi mobil yang Anda minati.</p></div>
            </div>
            <div class="step reveal">
                <div class="step-number">2</div>
                <div><h4>Ajukan Pembelian</h4><p>Kirim pengajuan lewat halaman detail, tim marketing akan menghubungi Anda.</p></div>
            </div>
            <div class="step reveal">
                <div class="step-number">3</div>
                <div><h4>Ambil &amp; Servis</h4><p>Selesaikan pembayaran di showroom dan nikmati layanan servis bergaransi.</p></div>
            </div>
        </div>
    </div>
</section>

<section class="auto-section-light" style="text-align: center;">
    <div class="container">
        <h2 class="auto-section-title reveal">Siap Memilih Mobil Anda?</h2>
        <span class="brand-rule center reveal"></span>
        <p class="auto-muted reveal" style="margin: 0 0 2rem;">Daftar gratis dan mulai chat dengan tim kami, atau telusuri katalog terlebih dahulu.</p>
        <a href="auth/register.php" class="auto-btn-primary reveal" style="padding: 1rem 3rem;">Daftar Sekarang</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
