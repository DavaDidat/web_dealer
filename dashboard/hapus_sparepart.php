<?php
require_once '../config/database.php';

cek_role(['admin_gudang']);

$sparepart_id = (int)($_GET['id'] ?? 0);

if ($sparepart_id) {
    $stmt = $pdo->prepare("SELECT foto FROM sparepart WHERE id = ?");
    $stmt->execute([$sparepart_id]);
    $sparepart = $stmt->fetch();

    if ($sparepart && $sparepart['foto']) {
        $file_path = '../assets/images/' . $sparepart['foto'];
        if (file_exists($file_path)) unlink($file_path);
    }

    $stmt_hapus = $pdo->prepare("DELETE FROM sparepart WHERE id = ?");
    $stmt_hapus->execute([$sparepart_id]);
}

header("Location: kelola_sparepart.php");
exit;
