<?php
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$pesan = '';
$pesan_error = '';
$valid_token = false;
$user_id = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $token = trim($_POST['token'] ?? '');
    $password_baru = $_POST['password_baru'] ?? '';
    $password_konfirmasi = $_POST['password_konfirmasi'] ?? '';

    if (empty($token) || empty($password_baru)) {
        $pesan_error = "Token dan password baru wajib diisi.";
    } elseif ($password_baru !== $password_konfirmasi) {
        $pesan_error = "Password tidak cocok.";
    } elseif (strlen($password_baru) < 6) {
        $pesan_error = "Password minimal 6 karakter.";
    } else {
        $stmt = $pdo->prepare("SELECT id, user_id FROM password_reset WHERE token = ? AND used = FALSE AND expires_at > NOW()");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if ($reset) {
            $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt_upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_upd->execute([$hashed, $reset['user_id']]);

            $stmt_used = $pdo->prepare("UPDATE password_reset SET used = TRUE WHERE id = ?");
            $stmt_used->execute([$reset['id']]);

            $pesan = "Password berhasil direset! Silakan login dengan password baru Anda.";
        } else {
            $pesan_error = "Token tidak valid atau sudah kedaluwarsa.";
        }
    }
} elseif (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    $stmt = $pdo->prepare("SELECT id, user_id FROM password_reset WHERE token = ? AND used = FALSE AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $valid_token = true;
        $user_id = $reset['user_id'];
    } else {
        $pesan_error = "Token tidak valid atau sudah kedaluwarsa.";
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="auth-container">
        <h2 style="text-align: center;">Reset Password</h2>

        <?php if($pesan): ?>
            <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
            <p style="text-align: center; margin-top: 1rem;">
                <a href="login.php" class="btn">Login Sekarang</a>
            </p>
        <?php elseif($valid_token): ?>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="form-group">
                    <label for="password_baru">Password Baru</label>
                    <input type="password" id="password_baru" name="password_baru" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="password_konfirmasi">Konfirmasi Password</label>
                    <input type="password" id="password_konfirmasi" name="password_konfirmasi" required minlength="6">
                </div>
                <button type="submit" class="btn" style="width: 100%;">Reset Password</button>
            </form>
        <?php else: ?>
            <?php if($pesan_error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="token">Token Reset</label>
                    <input type="text" id="token" name="token" required placeholder="Masukkan token yang Anda terima">
                </div>
                <div class="form-group">
                    <label for="password_baru">Password Baru</label>
                    <input type="password" id="password_baru" name="password_baru" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="password_konfirmasi">Konfirmasi Password</label>
                    <input type="password" id="password_konfirmasi" name="password_konfirmasi" required minlength="6">
                </div>
                <button type="submit" class="btn" style="width: 100%;">Reset Password</button>
            </form>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 1rem;">
            <a href="login.php">Kembali ke Login</a>
        </p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
