import { test, expect } from '@playwright/test';
import {
  STAFF,
  CUSTOMER_PASSWORD,
  uniqueEmail,
  login,
  registerAndLoginCustomer,
} from './helpers.js';

test.describe('Kontrol akses & keamanan', () => {
  test('pengunjung tanpa login dialihkan ke halaman login', async ({ page }) => {
    await page.goto('keranjang.php');
    await expect(page).toHaveURL(/auth\/login\.php/);

    await page.goto('dashboard/jadwal_servis_saya.php');
    await expect(page).toHaveURL(/auth\/login\.php/);
  });

  test('customer tidak bisa membuka halaman admin gudang/owner', async ({ page }) => {
    const email = uniqueEmail('acl');
    await registerAndLoginCustomer(page, {
      nama: 'Customer ACL',
      email,
      password: CUSTOMER_PASSWORD,
    });

    // Halaman dengan guard die().
    await page.goto('dashboard/kelola_mobil.php');
    await expect(page.locator('body')).toContainText('Akses ditolak');

    // Halaman dengan cek_role().
    await page.goto('dashboard/laporan_penjualan.php');
    await expect(page.locator('body')).toContainText('Akses ditolak');
  });

  test('customer tetap dapat mengakses halaman miliknya', async ({ page }) => {
    const email = uniqueEmail('aclok');
    await registerAndLoginCustomer(page, {
      nama: 'Customer ACL OK',
      email,
      password: CUSTOMER_PASSWORD,
    });

    await page.goto('dashboard/pesanan_saya.php');
    await expect(page.locator('h2')).toContainText('Pesanan Saya');
    await expect(page).toHaveURL(/pesanan_saya\.php/);
  });

  test('marketing tidak bisa membuka fitur yang butuh role lain', async ({ page }) => {
    await login(page, STAFF.marketing.email, STAFF.marketing.password);

    await page.goto('dashboard/kelola_jadwal.php');
    await expect(page.locator('body')).toContainText('Akses ditolak');

    // Keranjang hanya untuk customer. Marketing dialihkan keluar dari keranjang
    // (lewati login.php, yang lalu membalikkan kembali ke dashboard).
    await page.goto('keranjang.php');
    await expect(page).not.toHaveURL(/keranjang\.php/);
    await expect(page.locator('body')).not.toContainText('Keranjang Belanja');
  });
});
