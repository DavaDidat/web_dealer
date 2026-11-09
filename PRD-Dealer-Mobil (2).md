# PRD — Sistem Informasi Dealer Mobil
### PT Yang Penting Jalan

---

## 1. Overview Produk

**Tujuan**: Membangun platform web terintegrasi yang memungkinkan dealer mobil mengelola stok, penjualan, servis, dan komunikasi dengan customer secara digital, menggantikan proses manual (termasuk chat WA yang tidak terstruktur).

**Masalah yang diselesaikan:**
- Tidak ada sistem terpusat untuk memantau stok mobil & sparepart
- Komunikasi customer-dealer tidak terstruktur (tersebar di berbagai channel WA pribadi)
- Tidak ada pembagian beban kerja yang adil antar staf marketing
- Tidak ada sistem penjadwalan servis yang terorganisir
- Tidak ada laporan performa bisnis yang terukur untuk level manajemen

---

## 2. Role & Definisi Pengguna

| Role | Deskripsi Singkat |
|---|---|
| **Admin (Gudang/Stok)** | Mengelola stok mobil & sparepart, melihat traffic penjualan |
| **Marketing/Sales** | Melayani chat customer, melihat stok (read-only) |
| **Teknisi** | Melihat stok sparepart untuk servis (read-only) |
| **Admin Servis** | Mengelola jadwal servis, assign teknisi, chat dengan customer soal servis |
| **Customer** | Melihat katalog, chat, transaksi pembelian, booking servis |
| **Owner** | Melihat laporan keseluruhan (penjualan, performa staf, keuangan, servis) |

---

## 3. User Stories per Role

### 3.1 Admin (Gudang/Stok)
- Menambah/mengurangi stok mobil dan sparepart sesuai kondisi gudang.
- Melihat log riwayat perubahan stok (siapa mengubah, kapan, dari jumlah berapa ke berapa) untuk akuntabilitas.
- Melihat grafik traffic penjualan mingguan/bulanan.
- Menerima notifikasi saat stok sparepart/mobil di bawah ambang batas tertentu.

### 3.2 Marketing/Sales
- Melihat stok mobil & sparepart (tanpa bisa mengubah).
- Mengklaim chat customer dari antrian bersama.
- Dibatasi maksimal 3 chat aktif secara bersamaan agar beban kerja merata.
- Mengakhiri percakapan yang sudah selesai untuk membuka slot chat baru.
- Melihat status mengetik dari customer.
- Menerima notifikasi real-time saat ada chat baru masuk ke antrian.

### 3.3 Teknisi
- Melihat stok sparepart yang dibutuhkan untuk servis (read-only).
- Melihat jadwal servis yang di-assign ke dirinya.

### 3.4 Admin Servis
- Chat dengan customer untuk negosiasi jadwal servis.
- Melihat kalender ketersediaan teknisi untuk menghindari bentrok jadwal.
- Assign teknisi yang tersedia ke jadwal servis customer.
- Melihat riwayat servis customer sebelumnya.
- Melihat stok sparepart (read-only).

### 3.5 Customer
- Melihat katalog mobil (terpisah dari katalog sparepart).
- Melihat detail spesifikasi mobil (cc, warna, tahun, dll) saat mengklik salah satu mobil.
- Menambahkan sparepart ke keranjang sebelum checkout.
- Checkout sparepart langsung dari keranjang.
- Mengajukan pembelian mobil (bukan checkout instan, karena butuh tindak lanjut marketing).
- Chat dengan marketing langsung dari halaman detail produk jika masih ragu.
- Melihat status pesanan/pengajuan (Menunggu Konfirmasi → Diproses → Selesai/Dibatalkan).
- Chat dengan admin servis untuk booking jadwal servis.
- Melihat riwayat servis kendaraan sebelumnya.

### 3.6 Owner
- Melihat laporan traffic penjualan keseluruhan.
- Melihat performa tiap marketing (jumlah chat ditangani, konversi ke penjualan, dll).
- Melihat tingkat keramaian servis (jumlah booking per periode).
- Melihat laporan keuangan (pemasukan dari penjualan mobil, sparepart, dan servis).

---

## 4. Fitur Utama & Flow

### 4.1 Sistem Distribusi Chat (Shared Queue + Concurrent Limit)

1. Customer klik tombol chat (dari katalog mobil/sparepart atau dari halaman servis) → pesan masuk ke antrian bersama.
2. Semua marketing yang berstatus *available* dan belum mencapai limit 3 chat aktif bisa melihat & mengklaim chat tersebut.
3. Begitu diklaim satu marketing, chat hilang dari antrian yang lain (mencegah rebutan).
4. Jika marketing sudah pegang 3 chat aktif, dia otomatis tidak muncul sebagai kandidat penerima chat baru sampai salah satu chat-nya selesai.
5. Selama chat berlangsung, ada indikator "sedang mengetik..." di kedua sisi (customer & marketing).
6. Jika tidak ada balasan dari customer selama 3-5 menit, marketing bisa menekan "Akhiri Percakapan".
7. Jika marketing lupa mengakhiri, sistem punya auto-timeout: setelah periode tidak aktif tertentu, sistem menandai chat sebagai selesai otomatis dan membuka slot marketing tersebut.
8. Chat yang sudah selesai masuk ke arsip (bukan dihapus permanen), agar tetap tersedia untuk kebutuhan riwayat/pembuktian, namun tidak membebani database aktif.

### 4.2 Katalog & Transaksi

**Katalog Mobil:**
- Terpisah dari katalog sparepart.
- Klik mobil → halaman detail (cc, warna tersedia, tahun, harga, dll).
- Tombol di halaman detail: "Ajukan Pembelian" dan "Chat Marketing".
- Alur "Ajukan Pembelian": customer isi form (data diri, mobil yang diminati, catatan) → status masuk "Menunggu Konfirmasi" → marketing menindaklanjuti via chat/dashboard → status berubah sesuai progres (Diproses/Selesai/Dibatalkan).

**Katalog Sparepart:**
- Terpisah dari katalog mobil.
- Klik sparepart → halaman detail (spesifikasi, kompatibilitas, harga, stok).
- Tombol: "Tambah ke Keranjang" dan "Chat Marketing".
- Customer bisa menambahkan beberapa sparepart ke keranjang sebelum checkout.
- Saat checkout, customer memilih salah satu metode pengambilan:
  1. **Ambil di Toko** — untuk semua jenis sparepart, customer mengambil sendiri tanpa kewajiban servis. Status: Menunggu Diambil → Selesai.
  2. **Sekalian Pasang saat Servis** (opsional) — khusus sparepart yang membutuhkan instalasi (misal aki, kampas rem, oli), tersedia sebagai pilihan tambahan bagi customer yang ingin langsung dipasangkan di dealer, tanpa perlu booking servis terpisah setelah pembelian. Sparepart otomatis tercatat sebagai "reserved" untuk jadwal servis yang disepakati.

*(Catatan: setiap item sparepart di database perlu ditandai apakah dia "butuh instalasi" atau tidak, supaya sistem tahu kapan opsi "Sekalian Pasang saat Servis" harus dimunculkan.)*

### 4.3 Sistem Servis

1. Customer chat dengan Admin Servis untuk menanyakan jadwal kosong — baik untuk servis rutin, maupun karena ingin sekalian pasang sparepart yang sudah dipesan.
2. Admin servis melihat kalender ketersediaan teknisi untuk memastikan tidak ada bentrok.
3. Setelah jadwal disepakati, admin servis assign teknisi yang available di jam tersebut.
4. Jika ada sparepart yang direservasi untuk dipasang, sparepart tersebut otomatis muncul di detail jadwal servis teknisi, sehingga teknisi tahu barang apa yang harus disiapkan.
5. Jadwal otomatis masuk ke dashboard teknisi yang bersangkutan.
6. Riwayat servis customer tersimpan (termasuk sparepart apa saja yang pernah dipasang) untuk referensi kunjungan berikutnya.

### 4.4 Manajemen Stok & Log Perubahan

- Admin bisa menambah/mengurangi stok mobil & sparepart.
- Setiap perubahan stok tercatat dalam log audit: siapa yang mengubah, waktu perubahan, jumlah sebelum & sesudah.
- Log ini bisa diakses admin dan owner untuk transparansi.

### 4.5 Notifikasi

| Jenis Notifikasi | Penerima | Trigger |
|---|---|---|
| Chat baru masuk antrian | Marketing/Admin Servis | Customer mengirim chat baru |
| Stok menipis | Admin | Stok di bawah ambang batas (misal <5 unit) |
| Booking servis baru | Admin Servis | Jadwal servis baru disepakati |
| Jadwal servis ditugaskan | Teknisi | Admin servis assign jadwal ke teknisi |

Implementasi: notifikasi real-time in-app, ditampilkan sebagai badge/toast di dashboard masing-masing role. Tidak perlu push notification ke email/HP untuk menjaga scope tetap terkendali.

### 4.6 Dashboard Owner

- Grafik traffic penjualan (mobil & sparepart) per minggu/bulan.
- Tabel performa marketing (jumlah chat ditangani, jumlah closing/penjualan berhasil).
- Grafik tingkat keramaian servis per periode.
- Ringkasan laporan keuangan (total pemasukan dari penjualan mobil, sparepart, dan jasa servis).

---

## 5. Wireframe Kasar (Deskripsi Layout)

**Halaman Customer — Beranda:**
```
[Header: Logo | Katalog Mobil | Katalog Sparepart | Servis | Login]
[Banner / Mobil Unggulan]
[Grid Mobil] -> klik -> Detail Mobil
[Grid Sparepart] -> klik -> Detail Sparepart
[Floating Button: Chat]
```

**Halaman Detail Mobil:**
```
[Foto Mobil]
[Spesifikasi: CC, Tahun, Warna, Harga]
[Tombol: Ajukan Pembelian] [Tombol: Chat Marketing]
```

**Halaman Detail Sparepart:**
```
[Foto Sparepart]
[Spesifikasi, Kompatibilitas, Harga, Stok]
[Tombol: Tambah ke Keranjang] [Tombol: Chat Marketing]
```

**Dashboard Marketing:**
```
[Sidebar: Antrian Chat | Chat Aktif (max 3) | Riwayat]
[Panel Chat Aktif dengan tombol "Akhiri Percakapan"]
[Panel Stok (read-only)]
```

**Dashboard Admin Servis:**
```
[Kalender Ketersediaan Teknisi]
[Panel Chat dengan Customer]
[Form Assign Jadwal + Pilih Teknisi Available]
[Panel Riwayat Servis Customer]
```

**Dashboard Owner:**
```
[Grafik Traffic Penjualan]
[Tabel Performa Marketing]
[Grafik Keramaian Servis]
[Ringkasan Keuangan]
```

---

## 6. Tech Stack

| Layer | Rekomendasi | Alasan |
|---|---|---|
| Frontend | HTML, CSS, JavaScript (Vanilla) | Tanpa framework, ringan dan mudah dikembangkan bertahap |
| Backend | PHP Native | Tidak bergantung pada framework, mudah digenerate per-file |
| Database | MySQL (via phpMyAdmin) | Relasional, cocok untuk data stok, transaksi, dan role; mudah dicek langsung lewat phpMyAdmin |
| Real-time Chat | AJAX Polling | Browser mengecek pesan baru tiap beberapa detik lewat `fetch()`, tanpa perlu setup server socket terpisah |
| Status "Sedang Mengetik" | AJAX Polling | Pendekatan sama seperti chat — status disimpan & dicek berkala |
| Autentikasi | PHP Session-based + tabel `roles` | Sederhana, cukup untuk mengatur akses berbeda tiap role |
| Notifikasi | AJAX Polling ke endpoint notifikasi | Konsisten dengan pendekatan chat, tanpa perlu WebSocket |

---

## 6.1 Estimasi Waktu Pengerjaan

Asumsi dikerjakan tim kecil (2-3 orang), tidak penuh waktu (disambi kuliah).

| Tahapan | Estimasi Waktu |
|---|---|
| Setup database & autentikasi/role | 1 minggu |
| Modul katalog + keranjang + checkout | 2 minggu |
| Modul chat (AJAX polling + status mengetik) | 1.5 – 2 minggu |
| Modul stok + log audit | 1 minggu |
| Modul jadwal servis + kalender teknisi | 1.5 – 2 minggu |
| Dashboard laporan (owner) | 1 – 1.5 minggu |
| Testing, revisi, & perbaikan bug | 1.5 – 2 minggu |
| **Total** | **± 10 – 13 minggu (2.5 – 3 bulan)** |

---

## 6.2 Estimasi Anggaran (Skala Software House Menengah)

| Item | Estimasi Biaya |
|---|---|
| **Development Penuh** (semua modul: auth & role, katalog, keranjang & checkout, chat real-time, stok & log audit, jadwal servis, dashboard laporan owner) | Rp 26.000.000 – Rp 47.000.000 |
| **Hosting/VPS** | Rp 500.000 – Rp 1.500.000 / bulan |
| **Domain** (.com/.co.id) | Rp 150.000 – Rp 300.000 / tahun |
| **Maintenance** (bug fixing, monitoring, update kecil) | Rp 1.000.000 – Rp 2.500.000 / bulan |

---

## 7. Batasan (Out of Scope)

- Payment gateway otomatis (transaksi bisa disimulasikan/manual dulu)
- Push notification ke email/SMS/HP
- Multi-cabang dealer (asumsi 1 lokasi dealer)
- Aplikasi mobile terpisah (fokus web saja)
- Pengiriman/delivery sparepart ke alamat customer (fitur pengembangan selanjutnya) — untuk versi ini sparepart hanya bisa diambil di toko atau dipasang langsung saat servis

---

## 8. Arahan Desain (Design Direction)

### Konsep: "Showroom Elegan"

Mengangkat kesan premium & tenang, berbeda dari pola warna merah/kuning-hitam yang umum dipakai template dealer/parts store generic.

**Warna (Color Tokens):**

| Nama | Hex | Peran |
|---|---|---|
| Graphite Black | `#16181C` | Background gelap (header, hero) |
| Warm Ivory | `#F5F3EF` | Background terang, hangat |
| Brass Gold | `#B08D57` | Aksen utama (CTA, highlight harga) |
| Slate Grey | `#4A5568` | Aksen sekunder (badge, ikon, teks pendukung) |
| Charcoal Text | `#1F2023` | Teks utama |

**Tipografi:**
- Headline: font serif modern seperti "Fraunces" atau "Libre Caslon Text" — memberi kesan showroom/butik.
- Body: "Inter" atau "Work Sans" — bersih dan mudah dibaca.

**Layout:**
- Search bar dengan tab (misal "Mobil Baru"/"Mobil Bekas" atau kategori lain sesuai kebutuhan dealer) di atas hero beranda.
- Grid "Shop by Category" (tipe bodi mobil, atau kategori sparepart) menggantikan grid brand logo jika dealer hanya menjual satu merek.
- Icon benefit row (garis tipis, warna brass gold di atas warm ivory) untuk menonjolkan keunggulan (misal "Sparepart Asli", "Servis Bergaransi", "Konsultasi Langsung").
- Grid katalog dengan kartu produk (foto, nama, spek kunci, harga) — pola diadaptasi dari standar industri, bukan meniru visual referensi tertentu.
- Section numbered steps untuk alur proses (misal "Cara Booking Servis" atau "Cara Beli Sparepart").
- FAQ accordion untuk pertanyaan umum di halaman katalog/beranda.

**Prinsip:**
- Satu elemen visual yang berani: foto mobil unggulan di hero beranda customer, sisanya tenang & fungsional.
- Hindari icon benefit row dengan kotak seragam identik dan testimonial card generic (avatar bulat + 5 bintang) — styling harus disesuaikan ke palet baru agar tidak terkesan template.
- Tombol pakai kata kerja aktif & jelas: "Ajukan Pembelian", "Tambah ke Keranjang", "Akhiri Percakapan".

---

## 9. Keamanan Sistem

Semua poin berikut diimplementasikan memakai stack yang sama (PHP native + MySQL), tanpa tools/library keamanan tambahan.

| Aspek Keamanan | Implementasi |
|---|---|
| Hash password | `password_hash()` & `password_verify()` (fungsi bawaan PHP) |
| Session timeout & keamanan session | `session_start()`, auto-expire setelah tidak aktif (misal 30 menit), `session_regenerate_id()` setelah login berhasil |
| Cegah SQL Injection | PDO dengan prepared statement untuk semua query, tidak menggabungkan input user langsung ke query |
| Validasi input | Validasi di sisi server (PHP) sebagai validasi utama, validasi JS di client hanya sebagai lapisan tambahan |
| Cegah XSS | `htmlspecialchars()` sebelum menampilkan data dari user (terutama isi chat) |
| Kontrol akses per role | Validasi role dicek ulang di setiap file endpoint backend, bukan hanya disembunyikan di tampilan |
| Validasi upload file | Cek tipe file (jpg/png saja), batasi ukuran maksimal, rename file dengan `uniqid()` agar tidak memakai nama asli dari user |
| Koneksi aman | HTTPS/SSL diaktifkan di level hosting |
| Rate limiting | Batasi percobaan login gagal (misal maksimal 5x lalu dikunci sementara), batasi juga frekuensi request AJAX polling |

---

## 10. Skema Database (Garis Besar)

```
users (id, nama, email, password, role_id)
roles (id, nama_role) -- admin, marketing, teknisi, admin_servis, customer, owner
mobil (id, nama, cc, tahun, warna, harga, deskripsi, stok, foto)
sparepart (id, nama, kompatibilitas, harga, stok, butuh_instalasi, foto)
log_stok (id, item_id, tipe_item, user_id, jumlah_sebelum, jumlah_sesudah, waktu)
chat_room (id, customer_id, staff_id, role_staff, status, waktu_mulai, waktu_selesai)
chat_pesan (id, chat_room_id, pengirim_id, isi_pesan, waktu, status_baca)
pengajuan_beli_mobil (id, customer_id, mobil_id, status, catatan, waktu)
keranjang (id, customer_id, sparepart_id, jumlah)
transaksi_sparepart (id, customer_id, metode_ambil, status, total, waktu)
jadwal_servis (id, customer_id, teknisi_id, admin_servis_id, waktu_mulai, status, catatan)
riwayat_servis (id, customer_id, jadwal_servis_id, sparepart_terpasang, catatan)
notifikasi (id, user_id, pesan, dibaca, waktu)
```

---

## 11. Panduan untuk AI Coding Tool

Saat meminta AI (misal OpenCode) membuatkan kode berdasarkan PRD ini, beberapa catatan agar hasilnya konsisten:

- Bangun bertahap per modul (jangan minta seluruh sistem sekaligus dalam satu prompt) — urutan disarankan: autentikasi & role → katalog → keranjang & checkout → chat → stok & log → jadwal servis → dashboard owner.
- Sebutkan token warna & font dari Section 8 secara eksplisit di setiap prompt halaman baru, agar desain tetap konsisten antar halaman.
- Ingatkan AI untuk selalu memakai PDO prepared statement di setiap query, bukan string concatenation.
- Untuk fitur chat & notifikasi, minta implementasi AJAX polling (bukan WebSocket) sesuai Section 6.
- Setelah tiap modul jadi, uji manual sebelum lanjut ke modul berikutnya — lebih mudah menemukan bug di modul kecil daripada di sistem besar yang sudah tergabung semua.

---

## 12. Status Pengerjaan (Update Terakhir: 10 Oktober 2026)

### 12.1 Modul yang Sudah Diimplementasikan

| Modul | Status | File |
|---|---|---|
| Autentikasi & Role (6 role) | Selesai | `auth/login.php`, `auth/register.php`, `auth/logout.php`, `config/database.php` |
| Rate Limiting Login | Selesai | `auth/login.php` (maks 5x gagal, lockout 15 menit) |
| Session Timeout Otomatis | Selesai | `config/database.php` (auto-logout setelah 30 menit tidak aktif) |
| Katalog Mobil | Selesai | `katalog_mobil.php`, `detail_mobil.php` |
| Katalog Sparepart | Selesai | `katalog_sparepart.php`, `detail_sparepart.php` |
| Keranjang & Checkout | Selesai | `keranjang.php` (dengan konfirmasi checkout + pilihan metode ambil) |
| Pengajuan Beli Mobil | Selesai | `detail_mobil.php` (form ajuan) |
| Chat Marketing (Shared Queue + AJAX) | Selesai | `api/chat_api.php`, `chat.php`, `dashboard/chat_antrian.php`, `dashboard/chat_aktif.php` |
| Chat Servis (AJAX) | Selesai | `api/chat_api.php` (create_servis), `chat_servis.php`, `dashboard/chat_servis.php` |
| Manajemen Stok & Log Audit | Selesai | `dashboard/kelola_mobil.php`, `dashboard/kelola_sparepart.php`, `dashboard/log_stok.php` |
| CRUD Mobil + Upload Foto | Selesai | `dashboard/tambah_mobil.php`, `dashboard/edit_mobil.php`, `dashboard/hapus_mobil.php` |
| CRUD Sparepart + Upload Foto | Selesai | `dashboard/tambah_sparepart.php`, `dashboard/edit_sparepart.php`, `dashboard/hapus_sparepart.php` |
| Booking Servis Customer | Selesai | `servis.php`, `dashboard/riwayat_servis.php` |
| Dashboard Teknisi (Jadwal Servis) | Selesai | `dashboard/jadwal_servis_saya.php` |
| Dashboard Admin Servis (Kelola Jadwal + Kalender) | Selesai | `dashboard/kelola_jadwal.php` |
| Dashboard Pengajuan Beli (Marketing) | Selesai | `dashboard/pengajuan_beli.php` (lihat & ubah status pengajuan) |
| Laporan Penjualan Owner (Chart.js) | Selesai | `dashboard/laporan_penjualan.php` (grafik bar Chart.js) |
| Performa Marketing Owner (Chart.js) | Selesai | `dashboard/performa_marketing.php` (grafik konversi Chart.js) |
| Laporan Keuangan Owner (Chart.js) | Selesai | `dashboard/laporan_keuangan.php` (doughnut chart distribusi pendapatan) |
| Notifikasi In-App (AJAX Polling) | Selesai | `api/notifikasi_api.php`, `includes/notifikasi.php` |
| Notifikasi Otomatis (Trigger Event) | Selesai | `config/database.php` (helper `buat_notifikasi()` & `notifikasi_ke_role()`), trigger di `chat.php`, `api/chat_api.php`, `kelola_mobil.php`, `kelola_sparepart.php`, `kelola_jadwal.php`, `servis.php` |
| Chat Auto-Timeout (5 menit tidak aktif) | Selesai | `api/chat_api.php` (auto-end setelah 5 menit) |
| Pesan Customer Order History | Selesai | `dashboard/pesanan_saya.php` (dengan badge status) |
| Konfirmasi Checkout | Selesai | `keranjang.php` (confirm dialog sebelum checkout) |

### 12.2 Modul yang Belum Diimplementasikan

| Modul | Keterangan |
|---|---|
| — | Semua modul sudah diimplementasikan |

### 12.3 Detail Notifikasi Otomatis (Trigger Event)

| Trigger | Penerima | Kode |
|---|---|---|
| Chat marketing baru masuk antrian | Semua user role `marketing` | `chat.php` — saat customer membuat room baru |
| Chat servis baru masuk antrian | Semua user role `admin_servis` | `api/chat_api.php` — action `create_servis` |
| Stok mobil menipis (< 5 unit) | Semua user role `admin_gudang` | `kelola_mobil.php` — saat update stok |
| Stok sparepart menipis (< 5 unit) | Semua user role `admin_gudang` | `kelola_sparepart.php` — saat update stok |
| Booking servis baru dari customer | Semua user role `admin_servis` | `servis.php` — saat customer ajukan servis |
| Teknisi di-assign ke jadwal servis | User teknisi yang di-assign | `kelola_jadwal.php` — saat assign teknisi |

### 12.4 Detail Keamanan yang Diimplementasikan

| Fitur | Implementasi | File |
|---|---|---|
| Rate limiting login | Maks 5x percobaan gagal, lalu lockout 15 menit | `auth/login.php` |
| Session timeout | Auto-logout setelah 30 menit tidak aktif (cek `last_activity`) | `config/database.php` |
| Hash password | `password_hash()` & `password_verify()` | `auth/login.php`, `auth/register.php` |
| Session regeneration | `session_regenerate_id(true)` setelah login | `auth/login.php` |
| SQL Injection prevention | PDO prepared statement di semua query | Semua file |
| XSS prevention | `htmlspecialchars()` saat output user data | Semua file |
| Role-based access control | `cek_role()` di setiap halaman dashboard | `config/database.php` |

### 12.5 Database Schema Update

Perubahan pada `database.sql`:
- Tabel `chat_room` ditambah kolom: `waktu_aktivitas TIMESTAMP NULL`, `typing_customer TIMESTAMP NULL`, `typing_staff TIMESTAMP NULL` (sebelumnya hanya via `ALTER TABLE` runtime, sekarang sudah termasuk di SQL file)

### 12.6 Catatan Perubahan Terbaru

1. **Keranjang vs Pesanan** — `pesanan_saya.php` hanya menampilkan transaksi yang sudah di-checkout (bukan isi keranjang). Keranjang yang belum checkout tidak muncul di pesanan. Ditambahkan konfirmasi `confirm()` sebelum checkout.
2. **Chat Auto-Timeout** — Chat yang tidak aktif selama 5 menit otomatis berakhir (kolom `waktu_aktivitas` di tabel `chat_room`).
3. **Chat Servis** — Customer bisa chat dengan Admin Servis via `chat_servis.php` atau dari halaman servis. Admin Servis bisa klaim chat dan membalas.
4. **CRUD Produk** — Admin/Owner bisa tambah, edit, hapus mobil & sparepart lengkap dengan upload foto.
5. **Konfirmasi Checkout** — Tombol checkout di `keranjang.php` sekarang meminta konfirmasi dulu sebelum transaksi dibuat.
6. **Notifikasi Badge** — Bell icon di header menampilkan jumlah notifikasi belum dibaca.
7. **Notifikasi Otomatis** — Semua event (chat baru, stok menipis, booking servis, assign teknisi) sekarang otomatis membuat notifikasi ke role terkait.
8. **Rate Limiting** — Login gagal 5x berturut-turut mengunci akun selama 15 menit.
9. **Session Timeout** — Session otomatis berakhir setelah 30 menit tidak aktif, user diarahkan ke halaman login.
10. **Chart.js** — Laporan owner sekarang menggunakan Chart.js (bar chart untuk penjualan & performa, doughnut chart untuk keuangan).
11. **Fix Broken Link** — `dashboard/index.php` previously mengarah ke `pengajuan_beli.php` yang tidak ada. Halaman tersebut sudah dibuat.
12. **Fix Database SQL** — Kolom `waktu_aktivitas`, `typing_customer`, `typing_staff` sudah ditambahkan ke `database.sql` (sebelumnya hanya via `ALTER TABLE` runtime).

### 12.7 Total Progress

**100% selesai** — Semua modul yang direncanakan di PRD sudah diimplementasikan, termasuk notifikasi otomatis, rate limiting, session timeout, dan Chart.js.

### 12.8 Update Sesi 10 Oktober 2026 — Plan Final (Revisi) + Redesign

Perubahan sistem (non-visual) yang dikerjakan sesi ini:

| # | Perubahan | File | Keterangan |
|---|---|---|---|
| 1 | Kelola User — Staff Only | `dashboard/kelola_user.php` | Role yang bisa dibuat/diubah admin tinggal `marketing`, `teknisi`, `admin_servis` (3 titik: tambah user, ubah role, dropdown). Query daftar user difilter `WHERE r.nama_role != 'customer'` — admin tidak melihat & tidak bisa kelola akun customer. Aksi reset password & ubah role ditolak jika target adalah customer ("Akun customer tidak bisa dikelola di sini") |
| 2 | Sistem Lupa Password (token on-screen) | `auth/lupa_password.php`, `auth/reset_password.php` (baru/diverifikasi), `database.sql`, `auth/login.php` | Alur: form input email (khusus role `customer`) → generate token `bin2hex(random_bytes(32))`, expired 1 jam, token lama user dihapus dulu → token **ditampilkan di layar** (tanpa email server, customer salin token untuk reset) → input token + password baru (min 6, konfirmasi) → update DB + tandai token `used`. `auth/login.php` ditambah link "Lupa Password?". Tabel `password_reset (id, user_id, token, expires_at, used, created_at)`. Catatan: tabel sudah ada di `database.sql` tapi belum ada di DB live, sehingga dibuat via migrasi `CREATE TABLE IF NOT EXISTS` |
| 3 | Seed 10 mobil | `seed.php` | Data dummy mobil diperluas 3 → 10 (Avanza, Brio, Pajero Sport, Innova Zenix, HR-V, Xenia, Ertiga, Fortuner, Jazz, Xpander) + 5 sparepart tetap, agar landing page & katalog tidak kosong. DB live di-top-up 7 mobil (3 → 10). `seed_users.php` tidak berubah (5 akun staff, password `password123`) |
| 4 | Dashboard index refactor | `dashboard/index.php` | Logic menu per-role diubah dari blok `if/elseif` HTML menjadi data array yang di-render sebagai kartu menu (isi menu tiap role sama persis, termasuk link Kelola User untuk admin_gudang) |
| 5 | Navbar aktif otomatis | `assets/js/main.js` | JS mencocokkan `location.pathname` dengan `href` link navbar dan menambah class `active` — berlaku untuk semua role/halaman tanpa edit satu-satu |
| 6 | Hapus tombol "Book a Test Drive" | `includes/header.php` | Tombol pill di navbar dihapus sesuai permintaan; warnanya (gradient ungu-biru) dipakai ulang untuk status menu aktif |
| 7 | Logo YPJ di header & footer | `includes/header.php`, `includes/footer.php`, `assets/images/logo-ypj.png` | Teks "▲ Automobile" diganti gambar logo YPJ (tinggi 44px, rounded) dengan fallback teks "▲ YPJ" via `onerror` jika file belum dipasang. File logo perlu disimpan manual ke `assets/images/logo-ypj.png` (disarankan versi background transparan karena header/footer navy gelap) |
| 8 | Sticky footer global | `assets/css/style.css` | `body` flex column `min-height: 100vh` + `main { flex: 1 }` — footer terdorong ke bawah di halaman berkonten pendek (keranjang, pesanan, login, dsb), berlaku seluruh site |
| 9 | Layout tombol detail produk | `detail_mobil.php`, `detail_sparepart.php` | Area aksi: gap 1rem → 1.5rem, kolom min-width 220px, tombol "Chat Marketing" disejajarkan ke bawah (sebelumnya melar setinggi form), tambah margin bawah 3rem sebelum footer. Logic pengajuan/keranjang tidak berubah |

Checklist testing tambahan sesi ini:
- [ ] Lupa password: email customer terdaftar → token tampil → reset berhasil → login dengan password baru; token expired (>1 jam) / sudah dipakai ditolak; email non-customer ditolak
- [ ] Kelola user: customer tidak muncul di daftar; tambah/ubah role ke customer ditolak; reset password target customer ditolak
- [ ] Navbar: link halaman aktif ber-highlight gradient; tombol Book a Test Drive sudah hilang
- [ ] Logo tampil di header & footer (atau fallback teks jika file belum ada)
- [ ] Footer menempel bawah di halaman pendek; tombol detail sejajar & berjarak

### 12.9 Total Progress (Revisi)

**100% + extras** — Semua modul PRD tetap selesai, ditambah 9 item sesi 10 Oktober 2026 di atas (staff-only user management, lupa password, seed 10 mobil, refactor dashboard, active navbar, logo, sticky footer, layout detail).

---

## 13. Panduan Testing Manual

### Persiapan

1. Import `database.sql` ke phpMyAdmin (database `dealer_mobil`)
2. Jalankan `seed.php` dan `seed_users.php` untuk data dummy
3. Login semua akun menggunakan password `password123`:
   - `gudang@dealer.com` (Admin Gudang)
   - `marketing@dealer.com` (Marketing)
   - `teknisi@dealer.com` (Teknisi)
   - `servis@dealer.com` (Admin Servis)
   - `owner@dealer.com` (Owner)
4. Daftar akun customer baru via `auth/register.php`
5. Gunakan 2 browser berbeda untuk simulasi customer & staff secara bersamaan

### Checklist Testing Per Modul

**Customer**
- [ ] Katalog mobil & sparepart tampil dengan benar
- [ ] Detail mobil: spesifikasi lengkap + tombol "Ajukan Pembelian" & "Chat Marketing"
- [ ] Detail sparepart: spesifikasi + tombol "Tambah ke Keranjang" & "Chat Marketing"
- [ ] Tambah beberapa sparepart ke keranjang → keranjang tampil dengan total
- [ ] Checkout → transaksi muncul di "Pesanan Saya" (status: Menunggu)
- [ ] Keranjang yang belum checkout TIDAK muncul di "Pesanan Saya"
- [ ] Ajukan pembelian mobil → muncul di pesanan dengan status "Menunggu Konfirmasi"
- [ ] Chat marketing: kirim pesan → muncul di antrian marketing
- [ ] Chat servis: kirim pesan → muncul di antrian admin servis
- [ ] Ajukan servis: isi form → jadwal muncul di "Jadwal Servis Aktif"

**Marketing**
- [ ] Lihat antrian chat → klaim chat → chat aktif
- [ ] Kirim & terima pesan customer
- [ ] Indikator "sedang mengetik..." muncul
- [ ] Akhiri percakapan → slot terbuka
- [ ] Batas maksimal 3 chat aktif → chat ke-4 ditolak
- [ ] Lihat pengajuan beli mobil → ubah status

**Admin Gudang**
- [ ] Kelola stok mobil (update inline)
- [ ] Tambah mobil baru (dengan upload foto)
- [ ] Edit mobil
- [ ] Hapus mobil
- [ ] Kelola stok sparepart (update inline)
- [ ] Tambah sparepart baru (dengan upload foto)
- [ ] Edit sparepart
- [ ] Hapus sparepart
- [ ] Lihat log perubahan stok

**Teknisi**
- [ ] Lihat jadwal servis yang di-assign
- [ ] Update status (Berjalan / Selesai)
- [ ] Saat selesai, isi sparepart terpasang → riwayat dibuat
- [ ] Lihat riwayat servis selesai

**Admin Servis**
- [ ] Lihat chat servis dari customer → klaim → balas
- [ ] Lihat semua jadwal servis
- [ ] Assign teknisi ke jadwal (dengan validasi bentrok)
- [ ] Tambah jadwal manual
- [ ] Lihat kalender bulan ini

**Owner**
- [ ] Laporan penjualan: grafik mobil & sparepart per bulan
- [ ] Performa marketing: tabel dengan konversi
- [ ] Laporan keuangan: total pendapatan & riwayat transaksi

**Sistem**
- [ ] Chat auto-timeout 5 menit
- [ ] Notifikasi badge muncul di header
- [ ] Validasi akses: customer tidak bisa akses dashboard admin, dll
- [ ] Login gagal 5x → terkunci (rate limiting, jika diimplementasikan)
