<?php
// Halaman chat servis lama digabung ke halaman Chat terpadu (chat.php).
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_nama'] !== 'customer') {
    header("Location: auth/login.php");
    exit;
}

header("Location: chat.php?channel=admin_servis");
exit;
