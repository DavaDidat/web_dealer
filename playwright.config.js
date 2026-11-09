// Konfigurasi Playwright untuk E2E test sistem dealer mobil PT Yang Penting Jalan.
// Jalankan: npm run test:e2e
// Base URL default mengarah ke instalasi Laragon lokal. Ubah via env BASE_URL bila perlu.
import { defineConfig, devices } from '@playwright/test';

const BASE_URL =
  process.env.BASE_URL || 'http://localhost/project_PTYangpentingMaju/';

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 45_000,
  expect: { timeout: 10_000 },
  fullyParallel: false,
  workers: 1,
  retries: 0,
  forbidOnly: !!process.env.CI,
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
  use: {
    baseURL: BASE_URL,
    headless: true,
    viewport: { width: 1440, height: 900 },
    actionTimeout: 15_000,
    navigationTimeout: 20_000,
    ignoreHTTPSErrors: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'off',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  outputDir: 'test-results',
});
