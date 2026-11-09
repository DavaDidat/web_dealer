# Design Direction — Web PT Yang Penting Jalan (YPJ)

## Konsep: "Dynamic Drive"

Terinspirasi langsung dari logo YPJ: gradient biru-ke-oranye, panah growth yang mengarah ke atas, dark background glossy, dan motif jalan/gear/kunci pas. Kesan yang diangkat: modern, dinamis, terpercaya — bukan "elegan-tenang" seperti konsep sebelumnya, tapi "bergerak maju, energik, teknologi".

---

## 1. Warna (Color Tokens)

| Nama | Hex | Peran |
|---|---|---|
| Graphite Dark | `#1B1E24` | Background utama (header, hero, dashboard internal) |
| Cloud White | `#FFFFFF` | Background section terang, teks di atas dark background |
| Brand Blue | `#2E7DD1` | Warna primer — navigasi, link, angka statistik, aksen gradient |
| Brand Orange | `#F5821F` | Aksen utama — tombol CTA ("Ajukan Pembelian", "Tambah ke Keranjang"), highlight |
| Steel Grey | `#8C97A6` | Teks sekunder, ikon pendukung, border |
| Charcoal Text | `#20242B` | Teks utama di atas background terang |

**Gradient signature**: biru `#2E7DD1` → biru terang `#3F8FE0` → oranye `#F5821F` (`linear-gradient(115deg, ...)`), disampel dari panah logo. Dipakai **hanya pada elemen aksen non-teks**: garis bawah heading (`brand-rule`), tepi kartu, tile statistik, hairline footer, motif panah hero. **Tidak** dipakai untuk teks heading (anti-slop) dan tidak sebagai background besar.

Teks di atas tombol oranye memakai Graphite Dark `#1B1E24` (kontras ~7.7:1).

**Alias lama**: token `--color-*` (Dynamic Blue, Momentum Orange, dst) dan `--auto-*` (Auto Purple, Auto Blue) tetap ada di `:root` namun nilainya sudah diarahkan ke brand biru/oranye, supaya halaman dashboard/chat lama tidak rusak.

---

## 2. Tipografi

- **Headline**: font sans-serif bold/extra-bold seperti **"Poppins"** atau **"Montserrat"** (weight 700-800) — mengikuti gaya huruf blocky tebal di logo "YPJ".
- **Body**: **"Inter"** atau **"Work Sans"** (regular/medium) — tetap bersih dan mudah dibaca, kontras dengan headline yang tebal.
- Hindari huruf serif — logo ini 100% sans-serif modern, jadi seluruh web sebaiknya konsisten tanpa elemen serif sama sekali (beda arahan dari konsep sebelumnya yang pakai serif).

---

## 3. Elemen Visual dari Logo yang Diturunkan ke Web

| Elemen di Logo | Diterapkan di Web Sebagai |
|---|---|
| Panah growth (biru-oranye) | Ikon tren naik di dashboard Owner (grafik penjualan), progress bar status pesanan |
| Garis jalan putus-putus | Divider/separator section, bisa jadi elemen dekoratif tipis di footer atau antar section |
| Gear & kunci pas | Ikon kategori servis/sparepart di halaman katalog dan dashboard teknisi |
| Siluet mobil glossy | Gaya foto produk — gunakan foto mobil dengan pencahayaan studio/glossy, bukan foto flat biasa, biar konsisten sama nuansa logo |
| Background gelap glossy | Header & hero section pakai Graphite Dark, kasih sedikit efek gradient radial halus di belakang elemen utama (bukan flat polos) |

---

## 4. Layout

- **Hero beranda customer**: background Graphite Dark, 1 mobil unggulan ditampilkan besar dengan efek pencahayaan (mirip studio shot di logo), headline bold putih, CTA button Momentum Orange.
- **Search bar** dengan tab kategori (Mobil/Sparepart/Servis) diletakkan overlap di bawah hero, card putih mengambang di atas background gelap — kontras tegas sesuai gaya logo yang "mobil biru di atas background gelap".
- **Grid katalog**: card dengan background putih bersih, foto produk dominan, badge harga pakai Momentum Orange, hover state pakai Dynamic Blue.
- **Dashboard internal** (admin/marketing/teknisi/admin servis/owner): sidebar Graphite Dark dengan ikon Steel Grey, area konten Cloud White, notifikasi/alert penting pakai Momentum Orange supaya langsung kelihatan.
- **Section "Cara Booking Servis"/"Cara Beli"**: numbered steps dengan ikon gear/kunci pas kecil di tiap angka, garis penghubung antar step pakai gradient signature biru-oranye.

---

## 5. Prinsip Desain

- Gradient biru-oranye adalah **identitas utama** — pakai konsisten di seluruh titik CTA dan elemen "progress/growth" (status pesanan, grafik, progress bar), supaya user langsung mengasosiasikan web ini dengan brand YPJ.
- Kontras tegas dark-light: section gelap (Graphite Dark) untuk hero/branding moment, section terang (Cloud White) untuk konten fungsional (katalog, dashboard) — jangan dicampur jadi abu-abu di tengah.
- Tombol & CTA selalu Momentum Orange supaya konsisten jadi "warna aksi" di seluruh web, sementara Dynamic Blue dipakai untuk elemen navigasi/informasi (bukan aksi).
- Hindari rounded card berlebihan dan bayangan lembut generic (ciri khas template SaaS) — ikuti gaya logo yang tegas dan sedikit glossy/tech, bukan soft & playful.

---

## 6. Token Aktif Halaman Publik (Update 19 Oktober 2026)

Section 1–5 menjadi fondasi. Palet aksen halaman publik **kembali ke biru-oranye** (sebelumnya sempat navy + gradient ungu-biru) agar konsisten dengan logo. Seluruh variabel lama (`--color-*`, `--auto-*`) dipertahankan sebagai alias agar dashboard/tabel tidak rusak.

### 6.1 Token Warna (`:root` di `assets/css/style.css`)

| Nama | Hex | Peran |
|---|---|---|
| `--brand-blue` | `#2E7DD1` | Primer — link, nav, angka statistik |
| `--brand-blue-deep` | `#1B5FA8` | Hover link, judul harga |
| `--brand-blue-light` | `#5FA8EC` | Aksen terang, halo hover kartu |
| `--brand-orange` | `#F5821F` | Tombol CTA (teks Graphite Dark) |
| `--brand-orange-light` | `#F2994A` | Hover/aksen oranye |
| `--brand-steel` | `#8C97A6` | Teks sekunder, ikon, border |
| `--brand-silver` | `#C3C9D1` | Border input/kartu terang |
| `--brand-dark`, `--brand-dark-2` | `#16181D`, `#1B1E24` | Permukaan gelap |
| `--brand-gradient` | `linear-gradient(115deg,#2E7DD1,#3F8FE0 45%,#F5821F)` | Aksen non-teks |
| `--auto-navy`, `--auto-navy-2` | `#0B0F1A`, `#101728` | Background hero/section gelap |
| `--auto-card`, `--auto-card-2` | `#141A2B`, `#1A2240` | Kartu gelap (featured cars) |
| `--auto-text-dim` | `#A7B0CE` | Teks sekunder di atas gelap |

### 6.2 Tipografi

Tidak berubah — Poppins (600–800) untuk headline, Inter untuk body (Section 2).

---

## 7. Komponen Desain (Update 19 Oktober 2026)

| Komponen | Class CSS | File | Keterangan |
|---|---|---|---|
| Pill navbar | `.auto-header`, `.auto-pill-nav` | `includes/header.php` | Header gelap sticky + blur; nav pill; link aktif ditandai **garis bawah gradient** + `aria-current` (JS `main.js`) |
| Tombol primer | `.auto-btn-primary` / `.btn` | global | **Solid oranye** `#F5821F`, teks Graphite Dark, radius 8px, hover lift + glow |
| Tombol ghost | `.auto-btn-ghost` | `index.php` | Outline putih, dipakai "Lihat Sparepart" di hero |
| Hero landing | `.auto-hero`, `.auto-eyebrow`, `.auto-hero-title`, `.auto-hero-img`, `.hero-motif` | `index.php` | Navy + radial glow biru/oranye; motif panah growth (SVG gradient, dekoratif, `float-slow`); entrance `.hero-in` bertahap; gambar mobil unggulan dari DB |
| Stats bar | `.auto-stats-bar`, `.auto-stat` | `index.php` | 4 item angka dari DB, ikon tile gradient, **count-up** `data-count` |
| Kategori | `.auto-cat-card` | `index.php` | Kartu putih + gambar + jumlah unit; group-by merk dari DB; hover lift + zoom gambar |
| Featured cars | `.auto-car-card`, `.auto-badge` | `index.php` | Kartu gelap 3 mobil terbaru DB + badge "Terbaru" + harga Rp + "Lihat Detail" |
| Kualitas/Layanan | `.auto-feature-item` | `index.php` | 2 kolom (visual + 4 fitur: Unit Terinspeksi, Pembayaran Fleksibel, Tukar Tambah, Dukungan Teknisi) |
| Cara Membeli | `.auto-steps`, `.step`, `.step-number` | `index.php` | 3 langkah bernomor tile gradient (Pilih Unit → Ajukan Pembelian → Ambil & Servis) |
| CTA akhir | `.auto-section-light` + `.brand-rule.center` | `index.php` | Ajakan daftar akun |
| Footer | `.auto-footer`, `.auto-footer-grid` | `includes/footer.php` | Navy `#080C16`, hairline gradient di atas, 4 kolom (brand, Navigasi, Layanan, Kontak) |
| Kartu katalog | `.auto-cat-card` (reuse) | `katalog_mobil.php`, `katalog_sparepart.php` | Hero strip gelap + eyebrow/rule + kartu putih + fallback `onerror` + tombol oranye |
| Kartu auth | `.auth-container` + `.auto-hero` | `auth/*.php` | Form putih di atas background navy + tombol oranye + `brand-rule` |
| Kartu dashboard | `.auto-feature-item` (reuse) | `dashboard/index.php` | Menu per-role sebagai kartu fitur + hero strip; isi menu tiap role tidak berubah |
| Sticky footer | `body` flex, `main { flex: 1 }` | `assets/css/style.css` | Footer selalu di bawah walau konten pendek |
| Logo brand | `.brand-logo`, `.brand-fallback` | `includes/header.php`, `includes/footer.php` | Gambar `assets/images/logo-ypj.png` (tinggi 44px, rounded 8px), fallback teks "YPJ" |
| Class landing lama | `.landing-grid-2`, `.landing-cards`, `.card-mobil`, dst | `assets/css/style.css` | Dipertahankan; `.landing-grid-2` dipakai ulang untuk layout detail produk |

Responsive: grid 3 → 1 kolom, stats 4 → 2 kolom, footer 4 → 2 → 1 kolom, hero title mengecil — breakpoint 900px/768px/600px.

---

## 8. Logo & Gambar (Update 19 Oktober 2026)

- **Logo**: `assets/images/logo-ypj.png` (disimpan manual oleh user, nama persis). Dipakai di header & footer; bila belum ada, `onerror` menampilkan fallback teks `YPJ` (bukan emoji). Folder `assets/images/` dibuat otomatis saat upload pertama.
- **Foto produk**: kolom `foto` di DB masih NULL untuk sebagian data dan `assets/images/` belum terisi, sehingga gambar memakai **hotlink placeholder Unsplash** (hero, kategori, featured, kualitas, katalog) dengan fallback `onerror` menyembunyikan gambar bila gagal (tanpa emoji). Bila foto lokal di-upload via CRUD admin, otomatis dipakai karena kode mengutamakan `assets/images/<foto>`.
- **Struktur landing**: hero → kategori → featured → kualitas/layanan → cara membeli → CTA → footer. Seluruh teks berbahasa Indonesia dan seluruh angka/daftar diambil dari database; **tidak ada konten dummy** (testimoni & newsletter palsu sudah dihapus).

---

## 9. Daftar File Desain yang Diubah (Update 19 Oktober 2026)

| File | Perubahan |
|---|---|
| `assets/css/style.css` | Token brand biru/oranye + alias lama, seluruh class `.auto-*`, `.brand-rule`/`.brand-divider`, sistem motion (`.reveal`, `.hero-in`, keyframes), komponen dashboard (`page-head`, `stat-card`, `table-card`, `status-badge`) |
| `includes/header.php` | Class `auto-header`, pill nav, logo image, script inline penanda `.js`, CSS `?v=6` |
| `includes/footer.php` | Footer 4 kolom + hairline gradient + logo image |
| `includes/icons.php` | Helper `ypj_icon()` — ikon SVG inline (offline, tanpa CDN/emoji) |
| `index.php` | Total redesign section (query DB tetap, tanpa konten dummy) |
| `katalog_mobil.php`, `katalog_sparepart.php` | Hero strip + rule + kartu + tombol oranye (query tetap) |
| `auth/login.php`, `auth/register.php`, `auth/lupa_password.php` | Form di atas background navy + tombol oranye (logic tetap) |
| `dashboard/index.php` | Menu kartu + hero strip (isi menu tetap) |
| `dashboard/laporan_penjualan.php`, `laporan_keuangan.php`, `performa_marketing.php` | Restyle `page-head`/`stat-card`/`table-card` + warna chart brand |
| `detail_mobil.php`, `detail_sparepart.php` | Layout 2 kolom `landing-grid-2` + rule + tombol (logic tetap) |
| `chat.php` | Inbox chat terpadu (daftar percakapan Marketing + Admin Servis + panel) |
| `chat_servis.php` | Dialihkan (redirect) ke `chat.php?channel=admin_servis` |
| `assets/js/main.js` | Menu navbar aktif, reveal on scroll, count-up, smooth scroll, loading tombol |
| `tests/e2e/auth.spec.js` | Assertion hero disesuaikan ("Mobil Berkualitas") |

---

## 11. Sistem Motion (Update 19 Oktober 2026)

Animasi **subtil & profesional**, hanya `transform`/`opacity`, tanpa dependency (CSS + vanilla JS).

- **Progressive enhancement**: script inline di `<head>` menambah class `js` ke `<html>`. Tanpa JS, konten tetap tampil penuh.
- **Reveal on scroll**: class `.reveal` → `IntersectionObserver` menambah `.is-visible` (fade-up 12px, sekali). Anak berurutan diberi `transition-delay` (60ms × indeks, maks 480ms) untuk efek gelombang.
- **Hero entrance**: `.hero-in` + `--d` (delay bertahap eyebrow → judul → teks → tombol → gambar → stats).
- **Count-up**: elemen `[data-count]` menghitung naik saat terlihat (easing ease-out, format `id-ID`).
- **Hover**: kartu lift `translateY(-4px)` + shadow; gambar zoom `scale(1.05)`; tombol lift + glow.
- **`prefers-reduced-motion`**: semua animasi/transisi dimatikan dan `.reveal` langsung tampil (konten tidak pernah tersembunyi).

---

## 12. Komponen Dashboard (Update 19 Oktober 2026)

Class bersama untuk halaman dashboard agar konsisten dengan brand:

| Class | Fungsi |
|---|---|
| `.page-head` | Header halaman: judul + `::after` garis gradient, dan slot form filter di kanan |
| `.stat-grid` / `.stat-card` | Grid kartu statistik (auto-fit min 200px); `.num` biru, `.num.orange` oranye, `.stat-card.highlight` tile gradient |
| `.table-card` | Wrapper tabel: header gelap `auto-navy`, zebra hover, radius 14px |
| `.status-badge` | Badge status rounded; varian `selesai`/`disetujui` (hijau), `diproses`/`dijadwalkan` (biru), `menunggu`/`antri` (kuning), `dibatalkan`/`ditolak` (merah) |
| `.btn-secondary` / `.btn-danger` / `.btn-sm` | Tombol outline biru / merah / kecil |

Chart.js (`penjualan`, `keuangan`, `performa_marketing`): dataset memakai brand `#2E7DD1` (biru) & `#F5821F` (oranye), plus `#1E7A54` untuk jasa; `maxBarThickness` membatasi lebar batang saat data sedikit.

---

## 13. Cache-Busting CSS (Update 19 Oktober 2026)

Link stylesheet memakai query versi (`style.css?v=6`, naik tiap perubahan CSS besar) karena browser sempat menampilkan CSS lama dari cache. Setelah update CSS, lakukan hard refresh (`Ctrl+Shift+R`).

---

## 14. Chat Terpadu (Update 19 Oktober 2026)

**Satu halaman chat untuk customer** (`chat.php`) menggantikan halaman Marketing (`chat.php` lama) + Servis (`chat_servis.php`). Menu navbar customer berubah dari "Chat Servis" menjadi **"Chat"**.

- **Pola inbox**: grid 2 kolom — daftar percakapan di kiri (`.chat-list` / `.chat-conv`), panel percakapan di kanan (`.chat-panel`). Meniru pola 2 kolom dashboard staff (`dashboard/chat_servis.php`, `chat_antrian.php`, `chat_aktif.php`).
- **Dua kanal**: tiap customer bisa punya percakapan **Marketing** (`role_staff='marketing'`) dan **Admin Servis** (`role_staff='admin_servis'`) sekaligus, semuanya tampil di satu daftar.
- **Mulai eksplisit**: tidak ada room yang dibuat hanya dari mengunjungi halaman. Room dibuat lewat tombol **"Mulai chat Marketing"/"Mulai chat Servis"** (di state kosong maupun di bawah daftar) yang memanggil `chat.php?channel=<role>&start=1`. Di halaman detail produk, "Chat Marketing" mengarah ke `chat.php?channel=marketing&start=1`.
- **Riwayat**: percakapan `selesai` tetap tampil (read-only) di bawah percakapan aktif, maksimal 5 terakhir.
- **Read receipt**: `chat_pesan.status_baca` di-set saat polling `fetch` oleh lawan bicara. Customer melihat centang ganda (`read-receipt.read`) saat staff sudah membaca; room dengan pesan staff yang belum dibaca menampilkan badge oranye `.chat-unread`.
- **Status room**: `antri` (input nonaktif, "Menunggu …"), `aktif` ("Online · <nama staff>"), `selesai` (input dinonaktifkan permanen).
- **Keamanan API** (`api/chat_api.php`): `chat_room_access()` membatasi `fetch`/`send`/`typing` — customer hanya room miliknya, staff hanya room sesuai role-nya. `start_room` khusus customer dengan whitelist role.
- **Responsif**: <860px jadi satu kolom; daftar tampil dulu, panel muncul setelah memilih (`.has-selection`), tombol kembali `.chat-back` untuk balik ke daftar.
