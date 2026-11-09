<?php
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$pesan = '';
$pesan_error = '';
$token_generated = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $pesan_error = "Email harus diisi.";
    } else {
        $stmt = $pdo->prepare("SELECT id, nama FROM users WHERE email = ? AND role_id = (SELECT id FROM roles WHERE nama_role = 'customer')");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt_del = $pdo->prepare("DELETE FROM password_reset WHERE user_id = ?");
            $stmt_del->execute([$user['id']]);

            $stmt_ins = $pdo->prepare("INSERT INTO password_reset (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt_ins->execute([$user['id'], $token, $expires]);

            $token_generated = $token;
            $pesan = "Token reset password berhasil dibuat. Token berlaku selama 1 jam.";
        } else {
            $pesan_error = "Email tidak terdaftar sebagai customer.";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="auth-container reveal">
        <h2 style="text-align: center;">Lupa Password</h2>
        <span class="brand-rule center"></span>
        <p style="text-align: center; color: var(--color-steel-grey); margin-bottom: 1.5rem;">Masukkan email Anda untuk mendapatkan token reset password.</p>

        <?php if($pesan): ?>
            <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
        <?php endif; ?>
        <?php if($pesan_error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
        <?php endif; ?>

        <?php if ($token_generated): ?>
            <div style="background: #f0f7ff; border: 1px solid var(--color-dynamic-blue); border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; text-align: center;">
                <p style="font-weight: 600; margin-bottom: 0.5rem;">Token Reset Password:</p>
                <code style="display: block; background: #fff; padding: 0.75rem; border-radius: 4px; font-size: 0.85rem; word-break: break-all; margin-bottom: 1rem;"><?= htmlspecialchars($token_generated) ?></code>
                <a href="reset_password.php" class="btn" style="background: var(--color-link); color: #fff;">Lanjut ke Reset Password</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="btn" style="width: 100%;">Kirim Token</button>
            </form>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 1rem;">
            <a href="login.php">Kembali ke Login</a>
        </p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
