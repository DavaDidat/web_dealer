import { test, expect } from '@playwright/test';
import {
  CUSTOMER_PASSWORD,
  uniqueEmail,
  registerAndLoginCustomer,
  tomorrow,
} from './helpers.js';

// Satu perjalanan customer lengkap dalam satu test supaya sesi login tetap
// tersimpan (Playwright membuat context baru untuk tiap test).
test.describe('Alur utama Customer', () => {
  test('customer dapat mendaftar, melihat katalog, beli sparepart, booking servis, dan chat', async ({
    page,
  }) => {
    const account = {
      nama: 'Customer E2E',
      email: uniqueEmail('flow'),
      password: CUSTOMER_PASSWORD,
    };

    await test.step('registrasi + login customer baru', async () => {
      await registerAndLoginCustomer(page, account);
      await expect(page).toHaveURL(/\/dashboard\//);
      await expect(page.locator('h2.auto-section-title')).toContainText('Customer');
    });

    await test.step('browse katalog mobil dan buka detail spesifikasi', async () => {
      await page.goto('katalog_mobil.php');
      await expect(page.locator('h2.auto-section-title')).toContainText('Katalog');

      const detailLinks = page.locator('a:has-text("Lihat Detail")');
      await expect(detailLinks.first()).toBeVisible();
      await detailLinks.first().click();

      await expect(page).toHaveURL(/detail_mobil\.php\?id=\d+/);
      await expect(page.getByText('Spesifikasi')).toBeVisible();
      await expect(page.getByRole('button', { name: 'Ajukan Pembelian' })).toBeVisible();
      await expect(page.getByRole('link', { name: 'Chat Marketing' })).toBeVisible();
    });

    await test.step('ajukan pembelian mobil', async () => {
      await page.goto('katalog_mobil.php');
      await page.locator('a:has-text("Lihat Detail")').first().click();
      await expect(page).toHaveURL(/detail_mobil\.php/);

      await page.fill('input[name="jumlah"]', '1');
      await page.fill('textarea[name="catatan"]', 'Pembelian otomatis dari E2E test');
      await page.click('button[name="ajukan_beli"]');
      await expect(page.locator('.alert-success')).toContainText('Pengajuan pembelian berhasil');
    });

    await test.step('tambah sparepart ke keranjang', async () => {
      await page.goto('katalog_sparepart.php');
      await expect(page.locator('h2.auto-section-title')).toContainText('Sparepart');

      await page.locator('a:has-text("Lihat Detail")').first().click();
      await expect(page).toHaveURL(/detail_sparepart\.php\?id=\d+/);
      await expect(page.getByText('Spesifikasi & Kompatibilitas')).toBeVisible();

      await page.click('button[name="tambah_keranjang"]');
      await expect(page.locator('.alert-success')).toContainText('berhasil ditambahkan');
    });

    await test.step('checkout keranjang (ambil di toko)', async () => {
      await page.goto('keranjang.php');
      await expect(page.locator('h2')).toContainText('Keranjang Belanja');
      await expect(page.getByText('Ringkasan Pesanan')).toBeVisible();

      await page.check('input[name="metode_ambil"][value="toko"]');
      page.once('dialog', (dialog) => dialog.accept());
      await page.click('button[name="checkout"]');

      await expect(page).toHaveURL(/pesanan_saya\.php\?sukses=1/);
      await expect(page.locator('.alert-success')).toContainText('Pesanan berhasil dibuat');
    });

    await test.step('ajukan booking servis kendaraan', async () => {
      await page.goto('servis.php');
      await expect(page.locator('h2')).toContainText('Servis Kendaraan');

      await page.selectOption('select[name="jenis_servis"]', 'servis_rutin');
      await page.fill('input[name="kendaraan"]', 'Toyota Avanza 2020');
      await page.fill('textarea[name="keluhan"]', 'Servis rutin berkala - E2E test');
      await page.fill('input[name="tanggal_diinginkan"]', tomorrow());
      await page.selectOption('select[name="jam_diinginkan"]', '10:00');
      await page.selectOption('select[name="durasi_menit"]', '60');
      await page.click('button[name="ajukan_servis"]');

      await expect(page).toHaveURL(/servis\.php\?berhasil=1/);
      await expect(page.locator('.alert-success')).toContainText('Pengajuan servis berhasil');
      await expect(page.getByText('Jadwal Servis Aktif')).toBeVisible();
    });

    await test.step('chat marketing membuat room di antrian', async () => {
      await page.goto('chat.php?channel=marketing&start=1');
      await expect(page.locator('#chat-input')).toBeVisible();
      await expect(page.locator('#chat-status')).toContainText('Menunggu marketing');
      await expect(page.locator('.chat-conv', { hasText: 'Marketing' })).toBeVisible();
    });

    await test.step('halaman Pesanan Saya menampilkan mobil & sparepart', async () => {
      await page.goto('dashboard/pesanan_saya.php');
      await expect(page.locator('h2')).toContainText('Pesanan Saya');
      await expect(page.getByText('Pengajuan Pembelian Mobil')).toBeVisible();
      await expect(page.getByText('Pembelian Sparepart')).toBeVisible();
      await expect(page.getByText(/Jumlah: 1 unit/).first()).toBeVisible();
      await expect(page.getByText(/Order #/).first()).toBeVisible();
    });
  });
});
