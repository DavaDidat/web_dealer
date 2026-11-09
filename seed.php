<?php
require_once __DIR__ . '/config/database.php';

// Data Dummy Mobil (10 mobil agar landing & katalog tidak kosong)
$mobil_data = [
    ['nama' => 'Toyota Avanza 1.5 G', 'cc' => 1500, 'tahun' => 2023, 'warna' => 'Hitam', 'harga' => 250000000, 'deskripsi' => 'Mobil keluarga yang irit bahan bakar dan nyaman.', 'stok' => 5],
    ['nama' => 'Honda Brio RS', 'cc' => 1200, 'tahun' => 2022, 'warna' => 'Kuning', 'harga' => 220000000, 'deskripsi' => 'City car gesit yang cocok untuk jalanan macet perkotaan.', 'stok' => 3],
    ['nama' => 'Mitsubishi Pajero Sport', 'cc' => 2400, 'tahun' => 2023, 'warna' => 'Putih', 'harga' => 600000000, 'deskripsi' => 'SUV tangguh untuk segala medan, nyaman untuk perjalanan jauh.', 'stok' => 2],
    ['nama' => 'Toyota Innova Zenix', 'cc' => 2000, 'tahun' => 2023, 'warna' => 'Silver', 'harga' => 450000000, 'deskripsi' => 'MPV premium dengan kabin lega dan fitur modern.', 'stok' => 4],
    ['nama' => 'Honda HR-V SE', 'cc' => 1500, 'tahun' => 2023, 'warna' => 'Merah', 'harga' => 380000000, 'deskripsi' => 'Compact SUV stylish dengan handling lincah.', 'stok' => 3],
    ['nama' => 'Daihatsu Xenia 1.3 R', 'cc' => 1300, 'tahun' => 2022, 'warna' => 'Abu-abu', 'harga' => 230000000, 'deskripsi' => 'Mobil keluarga ekonomis dengan perawatan mudah.', 'stok' => 6],
    ['nama' => 'Suzuki Ertiga GX', 'cc' => 1500, 'tahun' => 2022, 'warna' => 'Putih', 'harga' => 260000000, 'deskripsi' => 'Low MPV nyaman untuk keluarga muda.', 'stok' => 4],
    ['nama' => 'Toyota Fortuner VRZ', 'cc' => 2800, 'tahun' => 2023, 'warna' => 'Hitam', 'harga' => 620000000, 'deskripsi' => 'SUV ladder-frame gagah untuk segala kebutuhan.', 'stok' => 2],
    ['nama' => 'Honda Jazz RS', 'cc' => 1500, 'tahun' => 2021, 'warna' => 'Biru', 'harga' => 240000000, 'deskripsi' => 'Hatchback fun to drive dengan kabin fleksibel.', 'stok' => 3],
    ['nama' => 'Mitsubishi Xpander Ultimate', 'cc' => 1500, 'tahun' => 2023, 'warna' => 'Silver', 'harga' => 310000000, 'deskripsi' => 'Small MPV terlaris dengan desain futuristik.', 'stok' => 5],
];

// Data Dummy Sparepart
$sparepart_data = [
    ['nama' => 'Aki GS Astra 12V', 'kompatibilitas' => 'Semua mobil', 'harga' => 850000, 'stok' => 15, 'butuh_instalasi' => 1],
    ['nama' => 'Kampas Rem Depan Honda', 'kompatibilitas' => 'Brio, Mobilio, BRV', 'harga' => 350000, 'stok' => 20, 'butuh_instalasi' => 1],
    ['nama' => 'Oli Mesin Shell Helix 4L', 'kompatibilitas' => 'Mobil Bensin', 'harga' => 450000, 'stok' => 50, 'butuh_instalasi' => 1],
    ['nama' => 'Wiper Bosch', 'kompatibilitas' => 'Universal', 'harga' => 120000, 'stok' => 30, 'butuh_instalasi' => 0],
    ['nama' => 'Karpet Karet Mobil', 'kompatibilitas' => 'Universal', 'harga' => 250000, 'stok' => 10, 'butuh_instalasi' => 0],
];

try {
    $pdo->beginTransaction();
    
    // Cek apakah data mobil sudah ada, agar tidak duplikat jika dijalankan berkali-kali
    $cek_mobil = $pdo->query("SELECT COUNT(*) FROM mobil")->fetchColumn();
    if ($cek_mobil == 0) {
        $stmt_m = $pdo->prepare("INSERT INTO mobil (nama, cc, tahun, warna, harga, deskripsi, stok) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($mobil_data as $m) {
            $stmt_m->execute([$m['nama'], $m['cc'], $m['tahun'], $m['warna'], $m['harga'], $m['deskripsi'], $m['stok']]);
        }
        echo "Data mobil ditambahkan.\n";
    } else {
        echo "Data mobil sudah ada, dilewati.\n";
    }
    
    // Cek apakah data sparepart sudah ada
    $cek_sparepart = $pdo->query("SELECT COUNT(*) FROM sparepart")->fetchColumn();
    if ($cek_sparepart == 0) {
        $stmt_s = $pdo->prepare("INSERT INTO sparepart (nama, kompatibilitas, harga, stok, butuh_instalasi) VALUES (?, ?, ?, ?, ?)");
        foreach ($sparepart_data as $s) {
            $stmt_s->execute([$s['nama'], $s['kompatibilitas'], $s['harga'], $s['stok'], $s['butuh_instalasi']]);
        }
        echo "Data sparepart ditambahkan.\n";
    } else {
        echo "Data sparepart sudah ada, dilewati.\n";
    }

    $pdo->commit();
    echo "Proses seeding selesai!\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Gagal: " . $e->getMessage() . "\n";
}
