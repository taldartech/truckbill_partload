import { test, expect } from '@playwright/test';

test.describe('Navigation compatibility @uat', () => {
  test('login page is usable on mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/login');
    await expect(page.locator('form, input[type="email"], input[name="email"]').first()).toBeVisible({
      timeout: 15_000,
    });
  });
});
