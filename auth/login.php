<?php
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $pdo->exec("DELETE FROM login_attempts WHERE waktu < DATE_SUB(NOW(), INTERVAL 1 DAY)");

    $stmt_attempt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN email = ? THEN 1 ELSE 0 END), 0) AS email_fails,
            COALESCE(SUM(CASE WHEN ip_address = ? THEN 1 ELSE 0 END), 0) AS ip_fails,
            MIN(waktu) AS percobaan_pertama
        FROM login_attempts
        WHERE berhasil = 0 AND waktu > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt_attempt->execute([$email, $ip_address]);
    $attempt = $stmt_attempt->fetch();
    $jumlah_gagal = max((int)$attempt['email_fails'], (int)$attempt['ip_fails']);

    if ($jumlah_gagal >= 5) {
        $sisa_detik = 900 - (time() - strtotime($attempt['percobaan_pertama']));
        $sisa_menit = max(1, (int)ceil($sisa_detik / 60));
        $error = "Terlalu banyak percobaan gagal. Coba lagi dalam {$sisa_menit} menit.";
    } elseif (empty($email) || empty($password)) {
        $error = "Email dan password harus diisi.";
    } else {
        $stmt = $pdo->prepare("SELECT u.*, r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $stmt_log = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, berhasil) VALUES (?, ?, 1)");
            $stmt_log->execute([$email, $ip_address]);
            $pdo->prepare("DELETE FROM login_attempts WHERE berhasil = 0 AND email = ?")->execute([$email]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_nama'] = $user['nama_role'];
            $_SESSION['last_activity'] = time();

            session_regenerate_id(true);

            header("Location: ../index.php");
            exit;
        } else {
            $stmt_log = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, berhasil) VALUES (?, ?, 0)");
            $stmt_log->execute([$email, $ip_address]);

            $sisa = 5 - ($jumlah_gagal + 1);
            if ($sisa > 0) {
                $error = "Email atau password salah. Percobaan tersisa: {$sisa}.";
            } else {
                $error = "Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.";
            }
        }
    }
}

require_once '../includes/header.php';
?>

<section class="auto-hero" style="padding: 3rem 0 4rem;">
<div class="container">
    <div class="auth-container reveal" style="max-width: 440px;">
        <p class="auto-eyebrow" style="text-align: center; color: var(--color-slate-grey);">Masuk</p>
        <h2 style="text-align: center;">Login</h2>
        <span class="brand-rule center"></span>
        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email" style="color: black;">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password" style="color: black;">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="auto-btn-primary" style="width: 100%;">Masuk</button>
        </form>
        <p style="text-align: center; margin-top: 1rem;">
            <a href="lupa_password.php">Lupa Password?</a>
        </p>
        <p style="text-align: center; margin-top: 0.5rem; color:black;">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </p>
    </div>
</div>
</section>

<?php require_once '../includes/footer.php'; ?>
