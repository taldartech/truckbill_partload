import { execSync } from 'node:child_process';

/**
 * Seeds a full-permission operational user for authenticated Playwright journeys.
 * Uses the app database from `.env` (same DB as `php artisan serve`).
 */
export default async function globalSetup(): Promise<void> {
  if (process.env.E2E_SKIP_SEED === '1') {
    return;
  }

  const email = process.env.E2E_EMAIL ?? 'e2e@truckbill.test';
  const password = process.env.E2E_PASSWORD ?? 'password';

  execSync(`php artisan e2e:seed --email="${email}" --password="${password}"`, {
    stdio: 'inherit',
    env: process.env,
  });
}
