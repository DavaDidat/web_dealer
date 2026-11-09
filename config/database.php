<?php
session_start();

$config_file = __DIR__ . '/database.local.php';
$config = is_file($config_file)
    ? require $config_file
    : [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'dbname' => getenv('DB_NAME') ?: 'dealer_mobil',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
    ];

$host = $config['host'] ?? 'localhost';
$dbname = $config['dbname'] ?? 'dealer_mobil';
$username = $config['username'] ?? 'root';
$password = $config['password'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: /project_PTYangpentingMaju/auth/login.php?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function cek_role($allowed_roles) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /project_PTYangpentingMaju/auth/login.php");
        exit;
    }
    
    if (!in_array($_SESSION['role_nama'], $allowed_roles)) {
        die("Akses ditolak. Anda tidak memiliki izin untuk halaman ini.");
    }
}

function buat_notifikasi($user_id, $pesan) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifikasi (user_id, pesan) VALUES (?, ?)");
    $stmt->execute([$user_id, $pesan]);
}

function notifikasi_ke_role($role_nama, $pesan) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifikasi (user_id, pesan) SELECT id, ? FROM users WHERE role_id = (SELECT id FROM roles WHERE nama_role = ?)");
    $stmt->execute([$pesan, $role_nama]);
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';

    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('CSRF token tidak valid. Silakan muat ulang halaman.');
    }
}

function cekJadwalBentrok($pdo, $waktu_mulai, $durasi_menit, $kolom, $nilai, $exclude_id = null) {
    $kolom_valid = ['teknisi_id', 'customer_id'];
    if (!in_array($kolom, $kolom_valid, true) || !$nilai) {
        return false;
    }

    $mulai = new DateTime($waktu_mulai);
    $selesai = (clone $mulai)->modify('+' . max(1, (int)$durasi_menit) . ' minutes');

    $sql = "SELECT COUNT(*) FROM jadwal_servis
            WHERE status != 'batal'
            AND waktu_mulai < ?
            AND DATE_ADD(waktu_mulai, INTERVAL durasi_menit MINUTE) > ?
            AND $kolom = ?";
    $params = [$selesai->format('Y-m-d H:i:s'), $mulai->format('Y-m-d H:i:s'), (int)$nilai];

    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = (int)$exclude_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn() > 0;
}
?>
