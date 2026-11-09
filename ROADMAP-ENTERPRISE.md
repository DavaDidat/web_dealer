# ROADMAP-ENTERPRISE.md

## Roadmap Kesiapan Sistem — PT Yang Penting Jalan

Dokumen ini memuat daftar perubahan yang diperlukan agar sistem informasi dealer/showroom
mobil ini siap dipakai untuk operasional skala besar (PT besar), dengan fokus pada
**perbaikan alur bisnis inti dan keamanan dasar**.

Status awal (hasil audit): modul sudah lengkap sesuai PRD, tetapi beberapa alur bisnis
belum utuh dan keamanan belum siap produksi.

---

## 1. Ringkasan & Tujuan

- **Tujuan**: menyelaraskan implementasi dengan alur bisnis yang seharusnya dan menutup
  celah keamanan dasar, sehingga sistem layak dipakai operasional nyata.
- **Lingkup dokumen**: perbaikan alur penjualan mobil & sparepart, servis, laporan
  keuangan, kontrol akses/keamanan dasar, dan kebersihan konfigurasi.
- **Di luar lingkup (dicatat sebagai prasyarat produksi lanjutan)**: multi-cabang,
  payment gateway/DP, email/SMTP, WebSocket/SSE, export laporan, audit berjenjang.

---

## 2. Skor Kesiapan per Aspek

| Aspek | Kondisi Sekarang | Target | Skor |
|---|---|---|---|
| Cakupan fitur (sesuai PRD) | Semua modul ada | Lengkap | 8/10 |
| Akurasi alur bisnis | Stok mobil & sparepart konsisten, transaksi sparepart ditutup, pendapatan servis dihitung | Akurat | 9/10 |
| Keamanan dasar | Prepared statement, hash password, RBAC, CSRF, rate-limit login per akun/IP | Aman dasar | 8/10 |
| Akurasi data/inventory | Stok mengikuti pengajuan mobil (termasuk jumlah) dan pembatalan transaksi | Konsisten | 8/10 |
| Kesiapan operasional | File debug dibersihkan, kredensial DB terpisah, tanpa ALTER runtime | Bersih | 8/10 |
| Skalabilitas | AJAX polling, tanpa index/pagination | *Di luar lingkup fase inti* | 4/10 |

---

## 3. Yang Harus DIUBAH (Perbaikan Alur)

| # | Item | File Terdampak | Prioritas | Estimasi |
|---|---|---|---|---|
| A1 | Stok mobil berkurang saat pengajuan berstatus "selesai" + dicatat ke `log_stok` | `dashboard/pengajuan_beli.php` | Tinggi | 2–3 jam |
| A2 | Siklus transaksi sparepart: halaman staff (akses: `admin_servis`) untuk mengubah status (menunggu → selesai/dibatalkan) + mengembalikan stok saat dibatalkan | baru `dashboard/kelola_transaksi.php`, `includes/header.php`, `dashboard/index.php` | Tinggi | 4–6 jam |
| A3 | Pendapatan jasa servis ikut dihitung di laporan keuangan (tambah `biaya_jasa`) | `database.sql`, `jadwal_servis`/`riwayat_servis`, `dashboard/jadwal_servis_saya.php`, `dashboard/laporan_keuangan.php` | Tinggi | 3–4 jam |
| A4 | Validasi bentrok jadwal memakai rentang waktu (overlap) + durasi, bukan `waktu_mulai = ...` | `dashboard/kelola_jadwal.php`, `servis.php` | Sedang | 3–4 jam |
| A5 | Katalog & detail produk dapat dilihat tamu (belum login); login hanya diwajibkan saat aksi beli/chat | `detail_mobil.php`, `detail_sparepart.php` | Sedang | 1–2 jam |
| A6 | Rate limit login per akun/IP (tabel `login_attempts`), bukan per-session | `auth/login.php`, `database.sql` | Sedang | 2–3 jam |

---

## 4. Yang Harus DITAMBAH

| # | Item | File Terdampak | Prioritas | Estimasi |
|---|---|---|---|---|
| B1 | CSRF token pada semua form POST (dan endpoint AJAX tulis) + verifikasi server | `config/database.php` (helper), seluruh file form, `api/*.php` | Tinggi | 4–6 jam |
| B2 | Reservasi sparepart metode "servis" terhubung ke `jadwal_servis` dan muncul di dashboard teknisi | schema, `keranjang.php`, `servis.php`, `dashboard/kelola_jadwal.php`, `dashboard/jadwal_servis_saya.php` | Tinggi | 5–7 jam |
| B3 | Kolom `jumlah` pada pengajuan pembelian mobil | `database.sql`, `detail_mobil.php`, `dashboard/pengajuan_beli.php` | Rendah | 1 jam |
| B4 | Validasi upload file memakai `finfo`/`getimagesize` (bukan hanya ekstensi) | `dashboard/tambah_mobil.php`, `dashboard/tambah_sparepart.php`, `dashboard/edit_mobil.php`, `dashboard/edit_sparepart.php` | Sedang | 1–2 jam |
| B5 | (Prasyarat produksi, di luar paket inti) reset password via email/SMTP, CSRF menyeluruh termasuk GET delete, multi-cabang, payment/DP, export laporan | — | Info | — |

---

## 5. Yang Harus DIHAPUS

| # | Item | Prioritas | Estimasi |
|---|---|---|---|
| C1 | File debug `check_db.php` dan `check_data.php` (dan skrip migrasi setelah dijalankan) | Tinggi | <15 menit |
| C2 | Kredensial DB hardcoded di `config/database.php` → pindah ke file konfigurasi terpisah yang tidak ikut di-commit | Sedang | 30 menit |
| C3 | `ALTER TABLE` runtime di `api/chat_api.php` → pindahkan ke migrasi | Rendah | 30 menit |

---

## 6. Peta Prioritas (Fase)

### Fase 1 — Fondasi keamanan & akurasi inti — **SELESAI**
- **B1** CSRF token — selesai (helper `csrf_token()`/`csrf_field()`/`verify_csrf()`; dipasang di 22 form POST dan endpoint AJAX chat/notifikasi)
- **A1** Stok mobil berkurang saat pengajuan selesai — selesai (transaksi + `log_stok`, idempoten, mengembalikan stok bila status dibatalkan)
- **A2** Halaman & siklus transaksi sparepart — selesai (`dashboard/kelola_transaksi.php`, akses: `admin_servis`; stok dikembalikan saat dibatalkan)
- **C1** Hapus file debug — selesai (`check_db.php`, `check_data.php` dihapus)

### Fase 2 — Kelengkapan alur bisnis — **SELESAI**
- **A3** Pendapatan jasa servis — selesai (`jadwal_servis.biaya_jasa` + `riwayat_servis.biaya_jasa`; input oleh teknisi; laporan keuangan & grafik menghitung jasa)
- **B2** Reservasi sparepart → jadwal servis — selesai (checkout metode `servis` wajib tanggal/jam, membuat `jadwal_servis` terkait `transaksi_sparepart_id`; tampil di admin servis, teknisi, dan pesanan customer; batal transaksi ikut membatalkan jadwal)
- **Migrasi**: schema `dealer_mobil` sudah diterapkan (kolom `biaya_jasa`, `transaksi_sparepart_id`, dan FK); `database.sql` sudah diperbarui untuk instalasi baru

### Fase 3 — Penyempurnaan & keamanan lanjutan — **SELESAI**
- **A4** Validasi bentrok jadwal — selesai (kolom `durasi_menit`; cek overlap pada booking customer, tambah jadwal, dan assign teknisi)
- **A5** Katalog publik — selesai (katalog & detail mobil/sparepart dapat dilihat tanpa login; aksi beli/chat tetap butuh login)
- **A6** Rate limit per akun/IP — selesai (tabel `login_attempts`, maks 5 kegagalan / 15 menit per email & IP)
- **B4** Validasi upload — selesai (`includes/upload.php` memakai `finfo` + `getimagesize`; JPG/PNG/WEBP maks 2MB)
- **C2** Konfigurasi DB terpisah — selesai (`config/database.local.php` + `.example`, fallback ke env; di-`.gitignore`)

### Fase 4 — Penyelesaian — **SELESAI**
- **B3** Jumlah pada pengajuan mobil — selesai (kolom `jumlah`; form, validasi stok, stok mobil saat selesai, dan laporan mengikuti jumlah)
- **C3** Migrasi `ALTER` `chat_api` — selesai (blok `ALTER TABLE` runtime dihapus; kolom sudah ada di `database.sql`)

---

## 7. Estimasi Total

| Fase | Estimasi |
|---|---|
| Fase 1 | 10–15 jam |
| Fase 2 | 8–11 jam |
| Fase 3 | 8–13 jam |
| Fase 4 | 1–2 jam |
| **Total** | **± 30–45 jam kerja** |

---

## 8. Risiko & Catatan Migrasi

- **Perubahan schema** (A3 `biaya_jasa`, A4 `durasi_menit`, A6 `login_attempts`,
  B2 `transaksi_sparepart_id`, B3 `jumlah`) sudah diterapkan pada database `dealer_mobil`
  dan `database.sql` sudah disesuaikan untuk instalasi baru.
- **CSRF (B1)** menyentuh hampir semua form; uji setiap alur untuk memastikan tidak ada
  form yang lupa dipasangi token.
- **Pengembalian stok saat pembatalan (A2)** harus idempoten: pastikan stok hanya
  dikembalikan sekali untuk transisi status yang sah.
- **Penurunan stok mobil (A1)** harus dijaga agar tidak bernilai negatif dan tidak
  terpotong dua kali bila status diubah bolak-balik.
- Selalu uji dengan dua peran berbeda (mis. customer + staff) untuk alur chat/transaksi.
