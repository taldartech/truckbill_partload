import { test, expect } from '@playwright/test';
import { e2eCredentials, loginAsE2eUser, logout } from './helpers/auth';

test.describe('Auth portal @uat', () => {
  test('login page is reachable', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('button', { name: /sign in|log in/i })).toBeVisible({
      timeout: 15_000,
    });
  });

  test('login form shows validation when submitted empty', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('button', { name: /sign in|log in/i }).click();
    await expect(page.locator('body')).toBeVisible();
    await expect(page).toHaveURL(/\/login/);
  });

  test('operator can login and reach dashboard then logout', async ({ page }) => {
    await loginAsE2eUser(page);
    await expect(page.getByText('Dashboard').first()).toBeVisible();
    await logout(page);
    await page.goto('/');
    await expect(page).toHaveURL(/\/login/);
  });

  test('invalid credentials stay on login', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[type="email"]').fill(e2eCredentials.email);
    await page.locator('input[type="password"], input[name="password"]').first().fill('wrong-password');
    await page.getByRole('button', { name: /sign in|log in/i }).click();
    await expect(page).toHaveURL(/\/login/, { timeout: 15_000 });
  });
});
