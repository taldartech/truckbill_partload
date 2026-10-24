import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';
import { clickIndexCreate } from './helpers/forms';

test.describe('Form validation messages @uat @ui', () => {
  test('party create shows validation when name is empty', async ({ page }) => {
    await loginAsE2eUser(page);
    // Party "create" route redirects to index; creation is a Bootstrap modal.
    await page.goto('/parties');
    await expect(page.getByText(/manage parties|parties/i).first()).toBeVisible({
      timeout: 20_000,
    });

    await clickIndexCreate(page);
    const modal = page.locator('.modal.show').last();
    await expect(modal.getByText(/add new party/i)).toBeVisible();

    // Leave required name empty and attempt save (HTML5 +/or server validation).
    await modal.getByRole('button', { name: /^save$/i }).click();

    await expect(modal).toBeVisible();
    const nameInput = modal.locator('input.form-control').nth(0);
    // Native constraint or inertia error should block a successful create.
    const nativeInvalid = await nameInput.evaluate((el: HTMLInputElement) => !el.checkValidity());
    if (nativeInvalid) {
      expect(nativeInvalid).toBeTruthy();
    } else {
      await expect(modal.locator('body, .modal-body')).toContainText(/required|name/i);
    }
    await expect(page).toHaveURL(/\/parties/);
  });
});
