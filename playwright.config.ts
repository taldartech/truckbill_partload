import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000';

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 1,
  // php artisan serve is single-threaded; keep concurrency modest locally.
  workers: process.env.CI ? 1 : 3,
  reporter: [
    ['list'],
    ['html', { open: 'never' }],
    ['junit', { outputFile: 'storage/app/testing/playwright-junit.xml' }],
  ],
  globalSetup: './e2e/global-setup.ts',
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  // Starts Laravel when nothing is listening (fixes ERR_CONNECTION_REFUSED).
  // Reuses an already-running server locally. Requires a Vite build (`npm run build`)
  // or a running `npm run dev` / `composer run dev` so Inertia pages can load assets.
  // E2E_ALLOW_MULTI_DEVICE keeps SingleDeviceLogin from wiping parallel browser sessions.
  webServer: {
    command: 'php artisan serve --host=127.0.0.1 --port=8000',
    url: baseURL,
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
    env: {
      ...process.env,
      E2E_ALLOW_MULTI_DEVICE: '1',
    },
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
  ],
});
