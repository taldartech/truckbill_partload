import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Navigation compatibility @uat', () => {
  test('login page is usable on mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/login');
    await expect(page.locator('form, input[type="email"]').first()).toBeVisible({
      timeout: 15_000,
    });
  });

  test('authenticated sidebar navigation works on mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await loginAsE2eUser(page);

    await page.getByRole('button', { name: /toggle sidebar/i }).click();
    await page.locator('a.nav-link', { hasText: 'Bilty' }).first().click();
    await expect(page).toHaveURL(/\/bilties/);

    await page.getByRole('button', { name: /toggle sidebar/i }).click();
    await page.locator('a.nav-link', { hasText: 'Parties' }).first().click();
    await expect(page).toHaveURL(/\/parties/);
  });
});
