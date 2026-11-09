<?php

if (!function_exists('ypj_valid_month')) {
    function ypj_valid_month($bulan): bool
    {
        return is_string($bulan) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan) === 1;
    }
}

if (!function_exists('ypj_month_start')) {
    function ypj_month_start(string $bulan): DateTime
    {
        $dt = ypj_valid_month($bulan) ? DateTime::createFromFormat('!Y-m', $bulan) : false;
        if (!$dt) {
            $dt = new DateTime('first day of this month');
            $dt->setTime(0, 0, 0);
        }
        return $dt;
    }
}

if (!function_exists('ypj_rupiah')) {
    function ypj_rupiah($value): string
    {
        return 'Rp ' . number_format((float)$value, 0, ',', '.');
    }
}

if (!function_exists('ypj_month_label')) {
    function ypj_month_label(string $bulan): string
    {
        $names = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $dt = ypj_month_start($bulan);
        return $names[(int)$dt->format('n')] . ' ' . $dt->format('Y');
    }
}

if (!function_exists('ypj_month_days')) {
    function ypj_month_days(string $bulan): int
    {
        return (int)ypj_month_start($bulan)->format('t');
    }
}

if (!function_exists('buildDailySeries')) {
    function buildDailySeries(array $rows, string $bulan, string $key = 'jumlah'): array
    {
        $days = ypj_month_days($bulan);
        $series = array_fill(1, $days, 0);

        foreach ($rows as $row) {
            if (empty($row['tanggal'])) {
                continue;
            }
            $day = (int)date('j', strtotime($row['tanggal']));
            if ($day < 1 || $day > $days) {
                continue;
            }
            $value = $row[$key] ?? 0;
            if ($key === 'total') {
                $series[$day] += (float)$value;
            } else {
                $series[$day] += (int)$value;
            }
        }

        return array_values($series);
    }
}

if (!function_exists('dailyLabels')) {
    function dailyLabels(string $bulan): array
    {
        $days = ypj_month_days($bulan);
        $labels = [];
        for ($d = 1; $d <= $days; $d++) {
            $labels[] = (string)$d;
        }
        return $labels;
    }
}

if (!function_exists('ownerStats')) {
    function ownerStats(PDO $pdo, string $bulan): array
    {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0), COUNT(*) FROM transaksi_sparepart WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'");
        $stmt->execute([$bulan]);
        [$sparepart_revenue, $sparepart_count] = $stmt->fetch(PDO::FETCH_NUM);

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(m.harga * p.jumlah), 0) AS total,
                   COALESCE(SUM(p.jumlah), 0) AS unit,
                   COUNT(*) AS orders
            FROM pengajuan_beli_mobil p
            JOIN mobil m ON p.mobil_id = m.id
            WHERE DATE_FORMAT(p.waktu, '%Y-%m') = ? AND p.status = 'selesai'
        ");
        $stmt->execute([$bulan]);
        $mobil = $stmt->fetch();

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(biaya_jasa), 0) AS total, COUNT(*) AS jumlah
            FROM jadwal_servis
            WHERE DATE_FORMAT(waktu_mulai, '%Y-%m') = ? AND status = 'selesai'
        ");
        $stmt->execute([$bulan]);
        $jasa = $stmt->fetch();

        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(waktu, '%Y-%m-%d') AS tanggal, COUNT(*) AS jumlah
            FROM pengajuan_beli_mobil
            WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'
            GROUP BY tanggal
            ORDER BY tanggal ASC
        ");
        $stmt->execute([$bulan]);
        $mobil_rows = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(waktu, '%Y-%m-%d') AS tanggal, COUNT(*) AS jumlah
            FROM transaksi_sparepart
            WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'
            GROUP BY tanggal
            ORDER BY tanggal ASC
        ");
        $stmt->execute([$bulan]);
        $sparepart_rows = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(waktu_mulai, '%Y-%m-%d') AS tanggal, COUNT(*) AS jumlah
            FROM jadwal_servis
            WHERE DATE_FORMAT(waktu_mulai, '%Y-%m') = ? AND status <> 'batal'
            GROUP BY tanggal
            ORDER BY tanggal ASC
        ");
        $stmt->execute([$bulan]);
        $servis_rows = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT u.id, u.nama,
                   COUNT(DISTINCT cr.id) AS total_chat,
                   COUNT(DISTINCT CASE WHEN p.status = 'selesai' THEN p.id END) AS closing
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN chat_room cr ON cr.staff_id = u.id
                AND cr.role_staff = 'marketing'
                AND DATE_FORMAT(cr.waktu_mulai, '%Y-%m') = ?
            LEFT JOIN pengajuan_beli_mobil p ON p.customer_id IN (
                SELECT customer_id FROM chat_room WHERE staff_id = u.id AND role_staff = 'marketing'
            ) AND DATE_FORMAT(p.waktu, '%Y-%m') = ?
            WHERE r.nama_role = 'marketing'
            GROUP BY u.id, u.nama
            ORDER BY closing DESC, total_chat DESC
            LIMIT 3
        ");
        $stmt->execute([$bulan, $bulan]);
        $marketing = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT tanggal, tipe, jumlah FROM (
                SELECT DATE_FORMAT(waktu, '%Y-%m-%d') AS tanggal, 'sparepart' AS tipe, total AS jumlah
                FROM transaksi_sparepart
                WHERE DATE_FORMAT(waktu, '%Y-%m') = ? AND status <> 'dibatalkan'
                UNION ALL
                SELECT DATE_FORMAT(p.waktu, '%Y-%m-%d') AS tanggal, 'mobil' AS tipe, (m.harga * p.jumlah) AS jumlah
                FROM pengajuan_beli_mobil p
                JOIN mobil m ON p.mobil_id = m.id
                WHERE DATE_FORMAT(p.waktu, '%Y-%m') = ? AND p.status = 'selesai'
                UNION ALL
                SELECT DATE_FORMAT(j.waktu_mulai, '%Y-%m-%d') AS tanggal, 'jasa' AS tipe, j.biaya_jasa AS jumlah
                FROM jadwal_servis j
                WHERE DATE_FORMAT(j.waktu_mulai, '%Y-%m') = ? AND j.status = 'selesai' AND j.biaya_jasa > 0
            ) t
            ORDER BY t.tanggal DESC
            LIMIT 6
        ");
        $stmt->execute([$bulan, $bulan, $bulan]);
        $recent = $stmt->fetchAll();

        return [
            'total_revenue' => (float)$mobil['total'] + (float)$sparepart_revenue + (float)$jasa['total'],
            'mobil_revenue' => (float)$mobil['total'],
            'mobil_units' => (int)$mobil['unit'],
            'mobil_orders' => (int)$mobil['orders'],
            'sparepart_revenue' => (float)$sparepart_revenue,
            'sparepart_count' => (int)$sparepart_count,
            'jasa_revenue' => (float)$jasa['total'],
            'servis_count' => (int)$jasa['jumlah'],
            'mobil_series' => buildDailySeries($mobil_rows, $bulan),
            'sparepart_series' => buildDailySeries($sparepart_rows, $bulan),
            'servis_series' => buildDailySeries($servis_rows, $bulan),
            'labels' => dailyLabels($bulan),
            'marketing' => $marketing,
            'recent' => $recent,
        ];
    }
}

if (!function_exists('customerStats')) {
    function customerStats(PDO $pdo, int $uid, string $bulan): array
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_beli_mobil WHERE customer_id = ?");
        $stmt->execute([$uid]);
        $mobil_total = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_beli_mobil WHERE customer_id = ? AND status IN ('menunggu', 'diproses')");
        $stmt->execute([$uid]);
        $mobil_active = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi_sparepart WHERE customer_id = ?");
        $stmt->execute([$uid]);
        $sparepart_total = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi_sparepart WHERE customer_id = ? AND status = 'menunggu'");
        $stmt->execute([$uid]);
        $sparepart_active = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT j.*, u.nama AS nama_teknisi
            FROM jadwal_servis j
            LEFT JOIN users u ON j.teknisi_id = u.id
            WHERE j.customer_id = ? AND j.status IN ('dijadwalkan', 'berjalan') AND j.waktu_mulai >= NOW()
            ORDER BY j.waktu_mulai ASC
            LIMIT 1
        ");
        $stmt->execute([$uid]);
        $next_service = $stmt->fetch() ?: null;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_servis WHERE customer_id = ? AND status IN ('dijadwalkan', 'berjalan') AND waktu_mulai >= NOW()");
        $stmt->execute([$uid]);
        $service_count = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM transaksi_sparepart WHERE customer_id = ? AND status <> 'dibatalkan' AND DATE_FORMAT(waktu, '%Y-%m') = ?");
        $stmt->execute([$uid, $bulan]);
        $sparepart_spend = (float)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(m.harga * p.jumlah), 0)
            FROM pengajuan_beli_mobil p
            JOIN mobil m ON p.mobil_id = m.id
            WHERE p.customer_id = ? AND p.status = 'selesai' AND DATE_FORMAT(p.waktu, '%Y-%m') = ?
        ");
        $stmt->execute([$uid, $bulan]);
        $mobil_spend = (float)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM keranjang WHERE customer_id = ?");
        $stmt->execute([$uid]);
        $cart_count = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT p.waktu, 'mobil' AS tipe, m.nama AS judul, p.status, (m.harga * p.jumlah) AS total
            FROM pengajuan_beli_mobil p
            JOIN mobil m ON p.mobil_id = m.id
            WHERE p.customer_id = ?
            ORDER BY p.waktu DESC
            LIMIT 5
        ");
        $stmt->execute([$uid]);
        $recent_mobil = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT waktu, 'sparepart' AS tipe, CONCAT('Order #', LPAD(id, 5, '0')) AS judul, status, total
            FROM transaksi_sparepart
            WHERE customer_id = ?
            ORDER BY waktu DESC
            LIMIT 5
        ");
        $stmt->execute([$uid]);
        $recent_sparepart = $stmt->fetchAll();

        $recent = array_merge($recent_mobil, $recent_sparepart);
        usort($recent, function ($a, $b) {
            return strtotime($b['waktu']) <=> strtotime($a['waktu']);
        });
        $recent = array_slice($recent, 0, 5);

        return [
            'mobil_total' => $mobil_total,
            'mobil_active' => $mobil_active,
            'sparepart_total' => $sparepart_total,
            'sparepart_active' => $sparepart_active,
            'service_count' => $service_count,
            'next_service' => $next_service,
            'spend_month' => $sparepart_spend + $mobil_spend,
            'cart_count' => $cart_count,
            'recent' => $recent,
        ];
    }
}

if (!function_exists('gudangStats')) {
    function gudangStats(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT COALESCE(SUM(stok), 0), COUNT(*) FROM mobil");
        [$mobil_stock, $mobil_items] = $stmt->fetch(PDO::FETCH_NUM);

        $stmt = $pdo->query("SELECT COALESCE(SUM(stok), 0), COUNT(*) FROM sparepart");
        [$sparepart_stock, $sparepart_items] = $stmt->fetch(PDO::FETCH_NUM);

        $stmt = $pdo->query("
            SELECT 'mobil' AS tipe, id, nama, stok FROM mobil WHERE stok < 5
            UNION ALL
            SELECT 'sparepart' AS tipe, id, nama, stok FROM sparepart WHERE stok < 5
            ORDER BY stok ASC, nama ASC
            LIMIT 8
        ");
        $low_stock = $stmt->fetchAll();

        $stmt = $pdo->query("SELECT (SELECT COUNT(*) FROM mobil WHERE stok < 5) + (SELECT COUNT(*) FROM sparepart WHERE stok < 5)");
        $low_count = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM pengajuan_beli_mobil WHERE status IN ('menunggu', 'diproses')");
        $pending_mobil = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM transaksi_sparepart WHERE status = 'menunggu'");
        $pending_sparepart = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("
            SELECT l.*, u.nama AS nama_user,
                   COALESCE(m.nama, s.nama) AS nama_item
            FROM log_stok l
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN mobil m ON l.tipe_item = 'mobil' AND l.item_id = m.id
            LEFT JOIN sparepart s ON l.tipe_item = 'sparepart' AND l.item_id = s.id
            ORDER BY l.waktu DESC
            LIMIT 5
        ");
        $recent_logs = $stmt->fetchAll();

        return [
            'mobil_stock' => (int)$mobil_stock,
            'mobil_items' => (int)$mobil_items,
            'sparepart_stock' => (int)$sparepart_stock,
            'sparepart_items' => (int)$sparepart_items,
            'low_stock' => $low_stock,
            'low_count' => $low_count,
            'pending_mobil' => $pending_mobil,
            'pending_sparepart' => $pending_sparepart,
            'recent_logs' => $recent_logs,
        ];
    }
}

if (!function_exists('marketingStats')) {
    function marketingStats(PDO $pdo, int $uid, string $bulan): array
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_room WHERE role_staff = 'marketing' AND status = 'antri'");
        $stmt->execute();
        $queue = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_room WHERE staff_id = ? AND role_staff = 'marketing' AND status = 'aktif'");
        $stmt->execute([$uid]);
        $active = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_beli_mobil WHERE status = 'menunggu'");
        $stmt->execute();
        $pending = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_room WHERE staff_id = ? AND role_staff = 'marketing' AND DATE_FORMAT(waktu_mulai, '%Y-%m') = ?");
        $stmt->execute([$uid, $bulan]);
        $chats_month = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM pengajuan_beli_mobil p
            WHERE p.status = 'selesai'
              AND DATE_FORMAT(p.waktu, '%Y-%m') = ?
              AND p.customer_id IN (
                  SELECT customer_id FROM chat_room WHERE staff_id = ? AND role_staff = 'marketing'
              )
        ");
        $stmt->execute([$bulan, $uid]);
        $closing = (int)$stmt->fetchColumn();

        return [
            'queue' => $queue,
            'active' => $active,
            'pending' => $pending,
            'chats_month' => $chats_month,
            'closing' => $closing,
        ];
    }
}

if (!function_exists('adminServisStats')) {
    function adminServisStats(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT COUNT(*) FROM jadwal_servis WHERE DATE(waktu_mulai) = CURDATE() AND status <> 'batal'");
        $today = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM jadwal_servis WHERE teknisi_id IS NULL AND status = 'dijadwalkan'");
        $unassigned = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM jadwal_servis WHERE status = 'berjalan'");
        $running = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM transaksi_sparepart WHERE status = 'menunggu'");
        $pending_sparepart = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("
            SELECT j.*, u.nama AS nama_customer, t.nama AS nama_teknisi
            FROM jadwal_servis j
            JOIN users u ON j.customer_id = u.id
            LEFT JOIN users t ON j.teknisi_id = t.id
            WHERE DATE(j.waktu_mulai) = CURDATE() AND j.status <> 'batal'
            ORDER BY j.waktu_mulai ASC
            LIMIT 6
        ");
        $today_schedule = $stmt->fetchAll();

        return [
            'today' => $today,
            'unassigned' => $unassigned,
            'running' => $running,
            'pending_sparepart' => $pending_sparepart,
            'today_schedule' => $today_schedule,
        ];
    }
}

if (!function_exists('teknisiStats')) {
    function teknisiStats(PDO $pdo, int $uid, string $bulan): array
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_servis WHERE teknisi_id = ? AND DATE(waktu_mulai) = CURDATE() AND status <> 'batal'");
        $stmt->execute([$uid]);
        $today = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_servis WHERE teknisi_id = ? AND status = 'berjalan'");
        $stmt->execute([$uid]);
        $running = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_servis WHERE teknisi_id = ? AND status = 'selesai' AND DATE_FORMAT(waktu_mulai, '%Y-%m') = ?");
        $stmt->execute([$uid, $bulan]);
        $done_month = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(biaya_jasa), 0) FROM jadwal_servis WHERE teknisi_id = ? AND status = 'selesai' AND DATE_FORMAT(waktu_mulai, '%Y-%m') = ?");
        $stmt->execute([$uid, $bulan]);
        $jasa_month = (float)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT j.*, u.nama AS nama_customer
            FROM jadwal_servis j
            JOIN users u ON j.customer_id = u.id
            WHERE j.teknisi_id = ? AND j.status IN ('dijadwalkan', 'berjalan')
            ORDER BY j.waktu_mulai ASC
            LIMIT 6
        ");
        $stmt->execute([$uid]);
        $jobs = $stmt->fetchAll();

        return [
            'today' => $today,
            'running' => $running,
            'done_month' => $done_month,
            'jasa_month' => $jasa_month,
            'jobs' => $jobs,
        ];
    }
}
