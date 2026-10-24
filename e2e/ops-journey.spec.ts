import { test, expect } from '@playwright/test';
import { ensureLoggedIn, loginAsE2eUser } from './helpers/auth';

/**
 * Browser end-to-end smoke of the core operational create screens.
 * Full form-submission process (masters → bilty → challan) lives in full-ops-process.spec.ts.
 * PHP HTTP mirror: tests/Feature/E2E/OperationalJourneyTest.php.
 */
test.describe('Operational UI journey @uat', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsE2eUser(page);
  });

  test('can open master create screens in sequence', async ({ page }) => {
    for (const path of ['/parties', '/agents', '/goods', '/trucks/create']) {
      await page.goto(path);
      await ensureLoggedIn(page);
      if (/\/login/.test(page.url())) {
        await page.goto(path);
      }
      await expect(page).not.toHaveURL(/\/login/);
      await expect(page.locator('form, .card, .content-wrapper').first()).toBeVisible({ timeout: 15_000 });
    }
  });

  test('can open bilty and challan create screens', async ({ page }) => {
    await page.goto('/bilties/create');
    await ensureLoggedIn(page);
    if (/\/login/.test(page.url())) {
      await page.goto('/bilties/create');
    }
    await expect(page).toHaveURL(/\/bilties\/create/);
    await expect(page.locator('form, .card, .content-wrapper').first()).toBeVisible({ timeout: 15_000 });

    await page.goto('/challans/create');
    await ensureLoggedIn(page);
    if (/\/login/.test(page.url())) {
      await page.goto('/challans/create');
    }
    await expect(page).toHaveURL(/\/challans\/create/);
    await expect(page.locator('form, .card, .content-wrapper').first()).toBeVisible({ timeout: 15_000 });
  });

  test('can open inward, delivery, and invoice create screens', async ({ page }) => {
    for (const path of ['/inward-registers/create', '/delivery-receipts/create', '/invoices/create']) {
      await page.goto(path);
      await ensureLoggedIn(page);
      if (/\/login/.test(page.url())) {
        await page.goto(path);
      }
      await expect(page).not.toHaveURL(/\/login/);
      await expect(page.locator('form, .card, .content-wrapper').first()).toBeVisible({ timeout: 15_000 });
    }
  });
});
