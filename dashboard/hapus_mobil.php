<?php
require_once '../config/database.php';

cek_role(['admin_gudang']);

$mobil_id = (int)($_GET['id'] ?? 0);

if ($mobil_id) {
    $stmt = $pdo->prepare("SELECT foto FROM mobil WHERE id = ?");
    $stmt->execute([$mobil_id]);
    $mobil = $stmt->fetch();

    if ($mobil && $mobil['foto']) {
        $file_path = '../assets/images/' . $mobil['foto'];
        if (file_exists($file_path)) unlink($file_path);
    }

    $stmt_hapus = $pdo->prepare("DELETE FROM mobil WHERE id = ?");
    $stmt_hapus->execute([$mobil_id]);
}

header("Location: kelola_mobil.php");
exit;
