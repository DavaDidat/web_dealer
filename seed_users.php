<?php
require_once __DIR__ . '/config/database.php';

// Semua akun menggunakan password: password123
$password = password_hash('password123', PASSWORD_DEFAULT);

$users = [
    ['nama' => 'Admin Gudang', 'email' => 'gudang@dealer.com', 'password' => $password, 'role_id' => 1],
    ['nama' => 'Budi Marketing', 'email' => 'marketing@dealer.com', 'password' => $password, 'role_id' => 2],
    ['nama' => 'Tono Teknisi', 'email' => 'teknisi@dealer.com', 'password' => $password, 'role_id' => 3],
    ['nama' => 'Siti Admin Servis', 'email' => 'servis@dealer.com', 'password' => $password, 'role_id' => 4],
    ['nama' => 'Bos Owner', 'email' => 'owner@dealer.com', 'password' => $password, 'role_id' => 6],
];

try {
    $pdo->beginTransaction();
    foreach ($users as $u) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$u['email']]);
        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO users (nama, email, password, role_id) VALUES (?, ?, ?, ?)");
            $insert->execute([$u['nama'], $u['email'], $u['password'], $u['role_id']]);
            echo "Berhasil membuat akun: " . $u['email'] . "<br>";
        } else {
            echo "Akun " . $u['email'] . " sudah ada.<br>";
        }
    }
    $pdo->commit();
    echo "<br><b>Selesai! Semua password adalah: password123</b>";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
