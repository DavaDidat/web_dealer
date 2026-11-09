import { test, expect } from '@playwright/test';
import {
  STAFF,
  CUSTOMER_PASSWORD,
  uniqueEmail,
  login,
  registerCustomer,
  logout,
} from './helpers.js';

test.describe('Halaman publik & autentikasi', () => {
  test('beranda menampilkan hero, navigasi, dan katalog unggulan', async ({ page }) => {
    await page.goto('index.php');
    await expect(page).toHaveTitle(/Yang Penting Jalan/i);
    await expect(page.locator('h1.auto-hero-title')).toContainText('Mobil Berkualitas');
    // Link navigasi utama untuk pengunjung belum login.
    await expect(page.locator('a[href$="katalog_mobil.php"]').first()).toBeVisible();
    await expect(page.locator('a[href$="katalog_sparepart.php"]').first()).toBeVisible();
    await expect(page.locator('a[href$="servis.php"]').first()).toBeVisible();
    await expect(page.locator('a[href$="auth/login.php"]').first()).toBeVisible();
  });

  test('registrasi customer baru berhasil', async ({ page }) => {
    const email = uniqueEmail('reg');
    await registerCustomer(page, { nama: 'Customer E2E', email, password: CUSTOMER_PASSWORD });
    await expect(page.locator('.alert-success')).toContainText('Pendaftaran berhasil');
    await expect(page.getByRole('link', { name: 'Login di sini' })).toBeVisible();
  });

  test('customer dapat login dan logout', async ({ page }) => {
    const email = uniqueEmail('login');
    await registerCustomer(page, { nama: 'Customer Login', email, password: CUSTOMER_PASSWORD });
    await login(page, email, CUSTOMER_PASSWORD);

    await expect(page).toHaveURL(/\/dashboard\//);
    await expect(page.locator('h2.auto-section-title')).toContainText('Customer');

    await logout(page);
    await expect(page).toHaveURL(/\/auth\/login\.php|\/index\.php/);
  });

  test('setiap role staff dapat login dan melihat menu dashboard-nya', async ({ page }) => {
    const cases = [
      { user: STAFF.adminGudang, menu: 'Kelola Stok Mobil' },
      { user: STAFF.marketing, menu: 'Antrian Chat' },
      { user: STAFF.teknisi, menu: 'Jadwal Servis Saya' },
      { user: STAFF.adminServis, menu: 'Kelola Jadwal Servis' },
      { user: STAFF.owner, menu: 'Laporan Penjualan' },
    ];

    for (const c of cases) {
      await page.goto('auth/logout.php');
      await login(page, c.user.email, c.user.password);
      await expect(page).toHaveURL(/\/dashboard\//);
      await expect(page.getByText(c.menu, { exact: true }).first()).toBeVisible();
    }
  });
});
