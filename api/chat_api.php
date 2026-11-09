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
$role = $_SESSION['role_nama'];

// Hanya customer & staff chat (marketing/admin_servis) yang boleh berinteraksi.
$staff_roles = ['marketing', 'admin_servis'];

// Ambil room bila user berhak mengaksesnya (cegah IDOR antar user/role).
function chat_room_access($pdo, $room_id, $user_id, $role)
{
    global $staff_roles;
    if (!$room_id) {
        return false;
    }
    if ($role === 'customer') {
        $stmt = $pdo->prepare("SELECT * FROM chat_room WHERE id = ? AND customer_id = ?");
        $stmt->execute([$room_id, $user_id]);
    } elseif (in_array($role, $staff_roles, true)) {
        $stmt = $pdo->prepare("SELECT * FROM chat_room WHERE id = ? AND role_staff = ?");
        $stmt->execute([$room_id, $role]);
    } else {
        return false;
    }
    return $stmt->fetch() ?: false;
}

// Auto-tutup room aktif yang sudah lama tidak ada aktivitas.
$pdo->exec("UPDATE chat_room SET status = 'selesai', waktu_selesai = NOW() WHERE status = 'aktif' AND waktu_aktivitas < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

if ($action === 'fetch') {
    $room_id = (int)($_GET['room_id'] ?? 0);
    $last_id = (int)($_GET['last_id'] ?? 0);

    $room_row = chat_room_access($pdo, $room_id, $user_id, $role);
    if (!$room_row) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT cp.*, u.nama as pengirim_nama, r.nama_role as pengirim_role
        FROM chat_pesan cp
        JOIN users u ON cp.pengirim_id = u.id
        JOIN roles r ON u.role_id = r.id
        WHERE cp.chat_room_id = ? AND cp.id > ?
        ORDER BY cp.id ASC
    ");
    $stmt->execute([$room_id, $last_id]);
    $messages = $stmt->fetchAll();

    // Tandai pesan dari lawan bicara sebagai sudah dibaca (read receipt + unread).
    $pdo->prepare("UPDATE chat_pesan SET status_baca = 1 WHERE chat_room_id = ? AND pengirim_id <> ? AND status_baca = 0")
        ->execute([$room_id, $user_id]);

    $is_typing = ($role === 'customer')
        ? (!empty($room_row['typing_staff']) && strtotime($room_row['typing_staff']) > time() - 5)
        : (!empty($room_row['typing_customer']) && strtotime($room_row['typing_customer']) > time() - 5);

    echo json_encode([
        'status_room' => $room_row['status'],
        'messages' => $messages,
        'is_typing' => $is_typing,
    ]);
}
elseif ($action === 'typing') {
    $room_id = (int)($_POST['room_id'] ?? 0);
    if (!chat_room_access($pdo, $room_id, $user_id, $role)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $is_typing = ($_POST['is_typing'] ?? '') === 'true';
    $col = ($role === 'customer') ? 'typing_customer' : 'typing_staff';
    if ($is_typing) {
        $stmt = $pdo->prepare("UPDATE chat_room SET $col = NOW(), waktu_aktivitas = NOW() WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE chat_room SET $col = NULL, waktu_aktivitas = NOW() WHERE id = ?");
    }
    $stmt->execute([$room_id]);
    echo json_encode(['success' => true]);
}
elseif ($action === 'send') {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $pesan = trim($_POST['pesan'] ?? '');

    $room_row = chat_room_access($pdo, $room_id, $user_id, $role);
    if (!$room_row || $room_row['status'] === 'selesai') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    if ($pesan) {
        $stmt = $pdo->prepare("INSERT INTO chat_pesan (chat_room_id, pengirim_id, isi_pesan) VALUES (?, ?, ?)");
        $stmt->execute([$room_id, $user_id, $pesan]);

        $col = ($role === 'customer') ? 'typing_customer' : 'typing_staff';
        $pdo->prepare("UPDATE chat_room SET $col = NULL, waktu_aktivitas = NOW() WHERE id = ?")->execute([$room_id]);

        echo json_encode(['success' => true]);
    }
}
elseif ($action === 'claim') {
    $room_id = (int)($_POST['room_id'] ?? 0);

    $stmt_limit = $pdo->prepare("SELECT COUNT(*) FROM chat_room WHERE staff_id = ? AND status = 'aktif' AND role_staff = ?");
    $stmt_limit->execute([$user_id, $role]);
    $aktif = $stmt_limit->fetchColumn();

    if ($aktif >= 3) {
        echo json_encode(['success' => false, 'error' => 'Batas maksimal 3 chat aktif tercapai. Selesaikan chat sebelumnya.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE chat_room SET staff_id = ?, status = 'aktif' WHERE id = ? AND status = 'antri' AND role_staff = ?");
    if ($stmt->execute([$user_id, $room_id, $role]) && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chat ini sudah diklaim oleh staff lain.']);
    }
}
elseif ($action === 'start_room') {
    // Customer memulai percakapan dengan salah satu kanal (marketing / admin_servis).
    if ($role !== 'customer') {
        http_response_code(403);
        echo json_encode(['error' => 'Hanya customer yang dapat memulai chat']);
        exit;
    }

    $channel = $_POST['role'] ?? $_POST['channel'] ?? '';
    if (!in_array($channel, $staff_roles, true)) {
        echo json_encode(['success' => false, 'error' => 'Kanal tidak valid']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM chat_room WHERE customer_id = ? AND role_staff = ? AND status IN ('antri', 'aktif') LIMIT 1");
    $stmt->execute([$user_id, $channel]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        echo json_encode(['success' => true, 'room_id' => (int)$existing, 'created' => false]);
        exit;
    }

    $stmt_new = $pdo->prepare("INSERT INTO chat_room (customer_id, role_staff, status) VALUES (?, ?, 'antri')");
    $stmt_new->execute([$user_id, $channel]);
    notifikasi_ke_role($channel, $channel === 'marketing' ? 'Chat baru masuk antrian.' : 'Chat servis baru masuk antrian.');

    echo json_encode(['success' => true, 'room_id' => (int)$pdo->lastInsertId(), 'created' => true]);
}
elseif ($action === 'create_servis') {
    // Kompatibilitas: dipetakan ke start_room kanal admin_servis.
    if ($role !== 'customer') {
        http_response_code(403);
        echo json_encode(['error' => 'Hanya customer yang dapat memulai chat']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM chat_room WHERE customer_id = ? AND role_staff = 'admin_servis' AND status IN ('antri', 'aktif') LIMIT 1");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        echo json_encode(['success' => true, 'room_id' => (int)$existing]);
        exit;
    }

    $stmt_new = $pdo->prepare("INSERT INTO chat_room (customer_id, role_staff, status) VALUES (?, 'admin_servis', 'antri')");
    $stmt_new->execute([$user_id]);
    notifikasi_ke_role('admin_servis', 'Chat servis baru masuk antrian.');
    echo json_encode(['success' => true, 'room_id' => (int)$pdo->lastInsertId()]);
}
elseif ($action === 'end') {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE chat_room SET status = 'selesai', waktu_selesai = NOW() WHERE id = ? AND staff_id = ?");
    $stmt->execute([$room_id, $user_id]);
    echo json_encode(['success' => true]);
}
