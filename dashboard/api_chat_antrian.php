<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role_nama'] != 'marketing') {
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil antrian
$stmt_q = $pdo->query("SELECT * FROM chat_room WHERE status = 'antri' AND role_staff = 'marketing' ORDER BY waktu_mulai ASC");
$antrian = $stmt_q->fetchAll();

// Ambil chat aktif marketing ini
$stmt_a = $pdo->prepare("SELECT * FROM chat_room WHERE status = 'aktif' AND staff_id = ? ORDER BY waktu_mulai DESC");
$stmt_a->execute([$user_id]);
$aktif = $stmt_a->fetchAll();

echo json_encode([
    'antrian' => $antrian,
    'aktif' => $aktif
]);
