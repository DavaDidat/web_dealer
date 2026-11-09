<?php
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    
    // Default register as customer
    $role_id = 5; 

    if (empty($nama) || empty($email) || empty($password) || empty($konfirmasi)) {
        $error = "Semua kolom harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif ($password !== $konfirmasi) {
        $error = "Password dan Konfirmasi Password tidak cocok.";
    } else {
        // Cek email sudah ada atau belum
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email sudah terdaftar.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role_id) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$nama, $email, $hashed_password, $role_id])) {
                $success = "Pendaftaran berhasil! Silakan login.";
            } else {
                $error = "Terjadi kesalahan saat mendaftar.";
            }
        }
    }
}

require_once '../includes/header.php';
?>

<section class="auto-hero" style="padding: 3rem 0 4rem;">
<div class="container">
    <div class="auth-container reveal" style="max-width: 440px;">
        <p class="auto-eyebrow" style="text-align: center; color: var(--color-slate-grey);">Daftar</p>
        <h2 style="text-align: center;">Daftar Akun</h2>
        <span class="brand-rule center"></span>
        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="nama" style="color:black;">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email" style="color:black;">Email</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password" style="color:black;">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="konfirmasi" style="color:black;">Konfirmasi Password</label>
                <input type="password" id="konfirmasi" name="konfirmasi" required>
            </div>
            <button type="submit" class="auto-btn-primary" style="width: 100%;">Daftar</button>
        </form>
        <p style="text-align: center; margin-top: 1rem; color:black;">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </p>
    </div>
</div>
</section>

<?php require_once '../includes/footer.php'; ?>
