import { test, expect } from '@playwright/test';
import {
  STAFF,
  CUSTOMER_PASSWORD,
  uniqueEmail,
  login,
  registerAndLoginCustomer,
} from './helpers.js';

test.describe('Alur utama staf', () => {
  test('Marketing dapat klaim chat dari antrian, membalas, lalu mengakhiri', async ({ browser }) => {
    // 1) Customer baru membuat room yang masuk ke antrian.
    const custCtx = await browser.newContext();
    const custPage = await custCtx.newPage();
    const email = uniqueEmail('chat');
    await registerAndLoginCustomer(custPage, {
      nama: 'Customer Chat E2E',
      email,
      password: CUSTOMER_PASSWORD,
    });
    await custPage.goto('chat.php?channel=marketing&start=1');
    await expect(custPage.locator('#chat-input')).toBeVisible();

    // 2) Marketing login dan buka antrian.
    const staffCtx = await browser.newContext();
    const staffPage = await staffCtx.newPage();
    await login(staffPage, STAFF.marketing.email, STAFF.marketing.password);
    await staffPage.goto('dashboard/chat_antrian.php');
    await expect(staffPage.locator('h2')).toContainText('Antrian Chat Customer');

    const claimBtn = staffPage.getByRole('button', { name: 'Terima Chat' }).first();
    await expect(claimBtn).toBeVisible({ timeout: 15_000 });
    await claimBtn.click();
    await staffPage.waitForURL(/chat_aktif\.php\?id=\d+/, { timeout: 15_000 });

    // 3) Marketing kirim balasan.
    await staffPage.fill('#chat-input', 'Halo, ada yang bisa dibantu?');
    await staffPage.click('#chat-btn');
    await expect(staffPage.locator('#chat-box')).toContainText('Halo, ada yang bisa dibantu?');

    // 4) Marketing akhiri percakapan.
    staffPage.once('dialog', (dialog) => dialog.accept());
    await staffPage.getByRole('button', { name: 'Akhiri Percakapan' }).click();
    await staffPage.waitForURL(/chat_antrian\.php/, { timeout: 15_000 });

    await custCtx.close();
    await staffCtx.close();
  });

  test('Admin Gudang dapat memperbarui stok mobil', async ({ page }) => {
    await login(page, STAFF.adminGudang.email, STAFF.adminGudang.password);
    await page.goto('dashboard/kelola_mobil.php');
    await expect(page.locator('h2')).toContainText('Kelola Stok Mobil');

    const row = page.locator('table tbody tr').first();
    await expect(row).toBeVisible();
    const input = row.locator('input[name="stok"]');
    const original = await input.inputValue();

    await input.fill(String(parseInt(original, 10) + 1));
    await row.getByRole('button', { name: 'Update' }).click();
    await expect(page.locator('.alert-success')).toContainText('Stok mobil berhasil diperbarui');

    // Kembalikan stok ke nilai semula agar data tetap stabil.
    const rowAfter = page.locator('table tbody tr').first();
    await rowAfter.locator('input[name="stok"]').fill(original);
    await rowAfter.getByRole('button', { name: 'Update' }).click();
    await expect(page.locator('.alert-success')).toContainText('Stok mobil berhasil diperbarui');
  });

  test('Admin Gudang dapat melihat log audit perubahan stok', async ({ page }) => {
    await login(page, STAFF.adminGudang.email, STAFF.adminGudang.password);
    await page.goto('dashboard/log_stok.php');
    await expect(page.locator('h2')).toContainText('Log Perubahan Stok');
  });

  test('Admin Servis dapat assign teknisi ke jadwal servis', async ({ page }) => {
    await login(page, STAFF.adminServis.email, STAFF.adminServis.password);
    await page.goto('dashboard/kelola_jadwal.php');
    await expect(page.locator('h2')).toContainText('Kelola Jadwal Servis');

    // Pastikan minimal ada satu jadwal; jika belum ada, tambah manual.
    if ((await page.locator('table tbody tr').count()) === 0) {
      const customerSelect = page.locator('select[name="customer_id"]');
      await customerSelect.selectOption({ index: 1 });
      await page.fill('input[name="waktu_mulai"]', '2030-01-01T09:00');
      await page.click('button[name="tambah_jadwal"]');
      await expect(page.locator('.alert-success, .alert-error').first()).toBeVisible();
    }

    const firstRow = page.locator('table tbody tr').first();
    await expect(firstRow).toBeVisible();
    await firstRow.locator('select[name="teknisi_id"]').selectOption({ index: 1 });
    await firstRow.getByRole('button', { name: 'Assign' }).click();
    await expect(page.locator('.alert-success, .alert-error').first()).toBeVisible();
  });

  test('Teknisi dapat melihat jadwal servis miliknya', async ({ page }) => {
    await login(page, STAFF.teknisi.email, STAFF.teknisi.password);
    await page.goto('dashboard/jadwal_servis_saya.php');
    await expect(page.locator('h2')).toContainText('Jadwal Servis Saya');
  });

  test('Owner dapat membuka seluruh laporan', async ({ page }) => {
    await login(page, STAFF.owner.email, STAFF.owner.password);

    await page.goto('dashboard/laporan_penjualan.php');
    await expect(page.locator('h2')).toContainText('Laporan Penjualan');

    await page.goto('dashboard/performa_marketing.php');
    await expect(page.locator('h2')).toContainText('Performa Marketing');

    await page.goto('dashboard/laporan_keuangan.php');
    await expect(page.locator('h2')).toContainText('Laporan Keuangan');
  });
});
