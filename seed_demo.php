<?php
require_once __DIR__ . '/config/database.php';

function demo_out(string $message): void
{
    echo $message . (php_sapi_name() === 'cli' ? PHP_EOL : '<br>');
}

$customer_email = 'customer@dealer.com';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$customer_email]);
$customer_id = $stmt->fetchColumn();

if (!$customer_id) {
    $role_id = $pdo->query("SELECT id FROM roles WHERE nama_role = 'customer'")->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role_id) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Customer Demo', $customer_email, password_hash('password123', PASSWORD_DEFAULT), $role_id]);
    $customer_id = (int)$pdo->lastInsertId();
    demo_out('Akun customer demo dibuat: ' . $customer_email . ' / password123');
} else {
    $customer_id = (int)$customer_id;
    demo_out('Akun customer demo sudah ada: ' . $customer_email);
}

$staff = [];
foreach (['marketing', 'teknisi', 'admin_servis'] as $role_name) {
    $stmt = $pdo->prepare("
        SELECT u.id FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.nama_role = ?
        ORDER BY u.id ASC
        LIMIT 1
    ");
    $stmt->execute([$role_name]);
    $staff[$role_name] = (int)($stmt->fetchColumn() ?: 0);
}

$mobil_rows = $pdo->query("SELECT id, harga FROM mobil ORDER BY id ASC LIMIT 5")->fetchAll();
$sparepart_rows = $pdo->query("SELECT id, harga FROM sparepart ORDER BY id ASC LIMIT 5")->fetchAll();

if (empty($mobil_rows) || empty($sparepart_rows)) {
    demo_out('Data mobil/sparepart kosong. Jalankan seed.php terlebih dahulu.');
    exit;
}

$stmt = $pdo->query("SELECT COUNT(*) FROM pengajuan_beli_mobil WHERE catatan LIKE '[DEMO]%'");
if ((int)$stmt->fetchColumn() > 0) {
    demo_out('Data demo sudah ada. Seeding dilewati agar tidak duplikat.');
    exit;
}

$mobil_status = [
    0 => ['selesai', 'selesai', 'selesai', 'diproses'],
    1 => ['selesai', 'selesai', 'menunggu', 'dibatalkan'],
    2 => ['selesai', 'selesai', 'selesai', 'selesai'],
];
$sparepart_status = ['selesai', 'selesai', 'selesai', 'menunggu', 'dibatalkan'];

try {
    $pdo->beginTransaction();

    for ($offset = 2; $offset >= 0; $offset--) {
        $month = new DateTime('first day of this month');
        $month->modify('-' . $offset . ' months');
        $ym = $month->format('Y-m');
        $days_in_month = (int)$month->format('t');
        $max_day = $offset === 0 ? (int)date('j') : $days_in_month;

        foreach ($mobil_status[$offset] as $index => $status) {
            $day = min($max_day, 2 + ($index * 4));
            $waktu = sprintf('%s-%02d %02d:00:00', $ym, max(1, $day), 9 + ($index % 6));
            $produk = $mobil_rows[$index % count($mobil_rows)];
            $jumlah = 1 + ($index % 2);

            $stmt = $pdo->prepare("
                INSERT INTO pengajuan_beli_mobil (customer_id, mobil_id, jumlah, status, catatan, waktu)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$customer_id, $produk['id'], $jumlah, $status, '[DEMO] Data contoh dashboard', $waktu]);
        }

        foreach ($sparepart_status as $index => $status) {
            $day = min($max_day, 3 + ($index * 4));
            $waktu = sprintf('%s-%02d %02d:30:00', $ym, max(1, $day), 10 + ($index % 6));
            $detail_items = [$sparepart_rows[$index % count($sparepart_rows)]];
            if ($index % 2 === 0) {
                $detail_items[] = $sparepart_rows[($index + 1) % count($sparepart_rows)];
            }

            $total = 0.0;
            foreach ($detail_items as $i => $item) {
                $detail_items[$i]['jumlah'] = 1 + (($index + $i) % 2);
                $total += (float)$item['harga'] * $detail_items[$i]['jumlah'];
            }

            $stmt = $pdo->prepare("
                INSERT INTO transaksi_sparepart (customer_id, metode_ambil, status, total, waktu)
                VALUES (?, 'toko', ?, ?, ?)
            ");
            $stmt->execute([$customer_id, $status, $total, $waktu]);
            $transaksi_id = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO transaksi_sparepart_detail (transaksi_id, sparepart_id, jumlah, harga_satuan)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($detail_items as $item) {
                $stmt->execute([$transaksi_id, $item['id'], $item['jumlah'], $item['harga']]);
            }
        }

        $servis_days = [5, 15];
        $servis_biaya = [175000, 320000];

        foreach ($servis_days as $index => $day) {
            $day = min($max_day, $day);
            $waktu = sprintf('%s-%02d 10:00:00', $ym, max(1, $day));

            $stmt = $pdo->prepare("
                INSERT INTO jadwal_servis (customer_id, teknisi_id, admin_servis_id, waktu_mulai, durasi_menit, status, catatan, biaya_jasa)
                VALUES (?, ?, ?, ?, 60, 'selesai', '[DEMO] Servis berkala', ?)
            ");
            $stmt->execute([
                $customer_id,
                $staff['teknisi'] ?: null,
                $staff['admin_servis'] ?: null,
                $waktu,
                $servis_biaya[$index],
            ]);
            $jadwal_id = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO riwayat_servis (customer_id, jadwal_servis_id, sparepart_terpasang, biaya_jasa, catatan)
                VALUES (?, ?, ?, ?, '[DEMO] Servis selesai')
            ");
            $stmt->execute([$customer_id, $jadwal_id, 'Oli mesin, filter udara', $servis_biaya[$index]]);
        }

        if ($offset === 0) {
            $today = date('Y-m-d');
            $today_schedules = [
                ['09:00:00', 'dijadwalkan', 150000],
                ['13:00:00', 'berjalan', 0],
            ];

            foreach ($today_schedules as $row) {
                $stmt = $pdo->prepare("
                    INSERT INTO jadwal_servis (customer_id, teknisi_id, admin_servis_id, waktu_mulai, durasi_menit, status, catatan, biaya_jasa)
                    VALUES (?, ?, ?, ?, 60, ?, '[DEMO] Jadwal hari ini', ?)
                ");
                $stmt->execute([
                    $customer_id,
                    $staff['teknisi'] ?: null,
                    $staff['admin_servis'] ?: null,
                    $today . ' ' . $row[0],
                    $row[1],
                    $row[2],
                ]);
            }

            $stmt = $pdo->prepare("SELECT id FROM jadwal_servis WHERE catatan = '[DEMO] Jadwal hari ini' AND waktu_mulai = ? LIMIT 1");
            $stmt->execute([$today . ' 09:00:00']);
            $upcoming_id = $stmt->fetchColumn();

            if ($upcoming_id) {
                $stmt = $pdo->prepare("
                    INSERT INTO transaksi_sparepart (customer_id, metode_ambil, status, total, waktu)
                    VALUES (?, 'servis', 'menunggu', 450000, ?)
                ");
                $stmt->execute([$customer_id, $today . ' 08:30:00']);
                $transaksi_id = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare("
                    INSERT INTO transaksi_sparepart_detail (transaksi_id, sparepart_id, jumlah, harga_satuan)
                    VALUES (?, ?, 1, ?)
                ");
                $stmt->execute([$transaksi_id, $sparepart_rows[0]['id'], $sparepart_rows[0]['harga']]);

                $stmt = $pdo->prepare("UPDATE jadwal_servis SET transaksi_sparepart_id = ? WHERE id = ?");
                $stmt->execute([$transaksi_id, (int)$upcoming_id]);
            }
        }
    }

    if ($staff['marketing']) {
        $stmt = $pdo->prepare("
            INSERT INTO chat_room (customer_id, role_staff, status, waktu_mulai)
            VALUES (?, 'marketing', 'antri', NOW())
        ");
        $stmt->execute([$customer_id]);

        $stmt = $pdo->prepare("
            INSERT INTO chat_room (customer_id, staff_id, role_staff, status, waktu_mulai, waktu_aktivitas)
            VALUES (?, ?, 'marketing', 'aktif', NOW(), NOW())
        ");
        $stmt->execute([$customer_id, $staff['marketing']]);
    }

    if ($staff['admin_servis']) {
        $stmt = $pdo->prepare("
            INSERT INTO chat_room (customer_id, role_staff, status, waktu_mulai)
            VALUES (?, 'admin_servis', 'antri', NOW())
        ");
        $stmt->execute([$customer_id]);
    }

    $pdo->commit();
    demo_out('Seeding demo selesai. Data contoh 3 bulan terakhir ditambahkan.');
    demo_out('Login customer demo: ' . $customer_email . ' / password123');
    demo_out('Catatan: data demo ditandai "[DEMO]" dan tidak mengubah stok fisik.');
} catch (Exception $e) {
    $pdo->rollBack();
    demo_out('Gagal seeding demo: ' . $e->getMessage());
}
