<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token tidak valid']);
        exit;
    }
}

$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'fetch') {
    $stmt = $pdo->prepare("SELECT * FROM notifikasi WHERE user_id = ? AND dibaca = FALSE ORDER BY waktu DESC LIMIT 20");
    $stmt->execute([$user_id]);
    $notifikasi = $stmt->fetchAll();

    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND dibaca = FALSE");
    $stmt_count->execute([$user_id]);
    $unread = $stmt_count->fetchColumn();

    echo json_encode([
        'notifikasi' => $notifikasi,
        'unread' => (int)$unread
    ]);
}
elseif ($action === 'mark_read') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE notifikasi SET dibaca = TRUE WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    echo json_encode(['success' => true]);
}
elseif ($action === 'mark_all_read') {
    $stmt = $pdo->prepare("UPDATE notifikasi SET dibaca = TRUE WHERE user_id = ?");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true]);
}
