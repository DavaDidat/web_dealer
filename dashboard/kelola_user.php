<?php
require_once '../config/database.php';

cek_role(['admin_gudang']);

$pesan = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    if (isset($_POST['tambah_user'])) {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role_id = (int)$_POST['role_id'];

        if (empty($nama) || empty($email) || empty($password)) {
            $pesan_error = "Semua field wajib diisi.";
        } else {
            $role_check = $pdo->prepare("SELECT nama_role FROM roles WHERE id = ? AND nama_role IN ('marketing', 'teknisi', 'admin_servis')");
            $role_check->execute([$role_id]);
            if (!$role_check->fetch()) {
                $pesan_error = "Role tidak valid. Customer tidak bisa dikelola di sini.";
            } else {
                $stmt_cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt_cek->execute([$email]);
                if ($stmt_cek->fetch()) {
                    $pesan_error = "Email sudah terdaftar.";
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_ins = $pdo->prepare("INSERT INTO users (nama, email, password, role_id) VALUES (?, ?, ?, ?)");
                    if ($stmt_ins->execute([$nama, $email, $hashed, $role_id])) {
                        $pesan = "User berhasil ditambahkan.";
                    } else {
                        $pesan_error = "Gagal menambahkan user.";
                    }
                }
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $user_id = (int)$_POST['user_id'];
        $password_baru = $_POST['password_baru'] ?? '';

        if (empty($password_baru)) {
            $pesan_error = "Password baru tidak boleh kosong.";
        } else {
            // Staff only: tolak jika target adalah customer
            $stmt_t = $pdo->prepare("SELECT r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
            $stmt_t->execute([$user_id]);
            $target = $stmt_t->fetch();
            if ($target && $target['nama_role'] === 'customer') {
                $pesan_error = "Akun customer tidak bisa dikelola di sini.";
            } else {
            $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                $pesan = "Password berhasil direset.";
            } else {
                $pesan_error = "Gagal reset password.";
            }
            }
        }
    } elseif (isset($_POST['ubah_role'])) {
        $user_id = (int)$_POST['user_id'];
        $role_baru = (int)$_POST['role_baru'];

        $role_check = $pdo->prepare("SELECT nama_role FROM roles WHERE id = ? AND nama_role IN ('marketing', 'teknisi', 'admin_servis')");
        $role_check->execute([$role_baru]);
        if (!$role_check->fetch()) {
            $pesan_error = "Role tidak valid. Customer tidak bisa dikelola di sini.";
        } else {
            // Staff only: tolak jika target saat ini adalah customer
            $stmt_t2 = $pdo->prepare("SELECT r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
            $stmt_t2->execute([$user_id]);
            $target2 = $stmt_t2->fetch();
            if ($target2 && $target2['nama_role'] === 'customer') {
                $pesan_error = "Akun customer tidak bisa dikelola di sini.";
            } else {
            $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            if ($stmt->execute([$role_baru, $user_id])) {
                $pesan = "Role berhasil diubah.";
            } else {
                $pesan_error = "Gagal mengubah role.";
            }
            }
        }
    }
}

$stmt_users = $pdo->prepare("
    SELECT u.id, u.nama, u.email, r.nama_role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.nama_role != 'customer'
    ORDER BY u.created_at DESC
");
$stmt_users->execute();
$user_list = $stmt_users->fetchAll();

$stmt_roles = $pdo->prepare("SELECT id, nama_role FROM roles WHERE nama_role IN ('marketing', 'teknisi', 'admin_servis')");
$stmt_roles->execute();
$roles = $stmt_roles->fetchAll();

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Kelola User</h2>

    <?php if ($pesan): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 2rem;">
        <div style="padding: 1rem 1.5rem; background: var(--color-graphite-dark); color: #fff;">
            <h3 style="margin: 0; font-size: 1.1rem;">Tambah Staff Baru</h3>
        </div>
        <div style="padding: 1.5rem;">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role_id" required>
                            <option value="">-- Pilih Role --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= ucfirst(str_replace('_', ' ', $r['nama_role'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="tambah_user" class="btn" style="margin-top: 1rem;">Tambah User</button>
            </form>
        </div>
    </div>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="padding: 1rem 1.5rem; background: var(--color-graphite-dark); color: #fff;">
            <h3 style="margin: 0; font-size: 1.1rem;">Daftar User</h3>
        </div>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: #f8f9fa;">
                <tr>
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Nama</th>
                    <th style="padding: 1rem;">Email</th>
                    <th style="padding: 1rem;">Role</th>
                    <th style="padding: 1rem;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_list as $u): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;"><?= $u['id'] ?></td>
                        <td style="padding: 1rem;"><?= htmlspecialchars($u['nama']) ?></td>
                        <td style="padding: 1rem;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding: 1rem;">
                            <form method="POST" action="" style="display: inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="role_baru" onchange="this.form.submit()" style="padding: 0.4rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem;">
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= $u['nama_role'] == $r['nama_role'] ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $r['nama_role'])) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="ubah_role" value="1">
                            </form>
                        </td>
                        <td style="padding: 1rem;">
                            <form method="POST" action="" style="display: inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="password" name="password_baru" placeholder="Password baru" style="padding: 0.4rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem; width: 120px;">
                                <button type="submit" name="reset_password" style="padding: 0.4rem 0.75rem; background: var(--color-dynamic-blue); color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Reset</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
