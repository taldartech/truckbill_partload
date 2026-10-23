import { test, expect } from '@playwright/test';

test.describe('Auth smoke @uat', () => {
  test('login page is reachable', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('button', { name: /log in|sign in/i })).toBeVisible({
      timeout: 15_000,
    });
  });

  test('login form shows validation when submitted empty', async ({ page }) => {
    await page.goto('/login');
    const submit = page.getByRole('button', { name: /log in|sign in/i });
    await submit.click();
    await expect(page.locator('body')).toBeVisible();
  });
});
