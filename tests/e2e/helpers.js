// Helper bersama untuk semua E2E test.
// Akun staff dibuat oleh seed_users.php (password: password123).

export const STAFF = {
  adminGudang: { email: 'gudang@dealer.com', password: 'password123' },
  marketing: { email: 'marketing@dealer.com', password: 'password123' },
  teknisi: { email: 'teknisi@dealer.com', password: 'password123' },
  adminServis: { email: 'servis@dealer.com', password: 'password123' },
  owner: { email: 'owner@dealer.com', password: 'password123' },
};

export const CUSTOMER_PASSWORD = 'password123';

// Email unik supaya tiap run bikin akun customer baru (tidak bentrok).
export function uniqueEmail(prefix = 'e2e') {
  const stamp = Date.now();
  const rand = Math.floor(Math.random() * 100000);
  return `${prefix}.${stamp}.${rand}@example.com`;
}

/**
 * Login lewat UI dan tunggu sampai masuk ke dashboard.
 * @returns {Promise<boolean>} true jika login sukses (redirect ke dashboard).
 */
export async function login(page, email, password = CUSTOMER_PASSWORD) {
  await page.goto('auth/login.php');
  await page.fill('#email', email);
  await page.fill('#password', password);
  await page.click('button[type="submit"]');
  await page.waitForURL(/\/dashboard\//, { timeout: 15_000 });
}

/** Registrasi customer baru lewat UI (tidak auto-login). */
export async function registerCustomer(page, { nama, email, password }) {
  await page.goto('auth/register.php');
  await page.fill('#nama', nama);
  await page.fill('#email', email);
  await page.fill('#password', password);
  await page.fill('#konfirmasi', password);
  await page.click('button[type="submit"]');
  await page.waitForSelector('.alert-success', { timeout: 15_000 });
}

/** Registrasi customer baru lalu login otomatis. */
export async function registerAndLoginCustomer(page, { nama, email, password }) {
  await registerCustomer(page, { nama, email, password });
  await login(page, email, password);
}

/** Logout lewat menu navbar. */
export async function logout(page) {
  await page.click('a[href$="auth/logout.php"]');
  await page.waitForURL(/\/auth\/login\.php|\/index\.php/);
}

/** Tanggal besok dalam format YYYY-MM-DD (untuk form servis). */
export function tomorrow() {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  return d.toISOString().slice(0, 10);
}
