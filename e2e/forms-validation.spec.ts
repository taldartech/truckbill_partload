import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';

test.describe('Form validation messages @uat @ui', () => {
  test('party create shows validation when name is empty', async ({ page }) => {
    await loginAsE2eUser(page);
    await page.goto('/parties/create');
    await expect(page.getByRole('heading', { name: /party|create/i }).first()).toBeVisible({
      timeout: 20_000,
    });

    const submit = page.getByRole('button', { name: /save|create|submit/i }).first();
    await submit.click();

    await expect(page).toHaveURL(/\/parties\/create/);
    await expect(page.locator('body')).toContainText(/required|name/i);
  });
});
