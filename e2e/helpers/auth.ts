import { expect, type Page } from '@playwright/test';

export const e2eCredentials = {
  email: process.env.E2E_EMAIL ?? 'e2e@truckbill.test',
  password: process.env.E2E_PASSWORD ?? 'password',
};

export async function loginAsE2eUser(page: Page): Promise<void> {
  await page.goto('/login');
  await page.locator('input[type="email"]').fill(e2eCredentials.email);
  await page.locator('input[type="password"], input[name="password"]').first().fill(e2eCredentials.password);
  await page.getByRole('button', { name: /sign in|log in/i }).click();
  await expect(page).toHaveURL(/\/($|\?)/, { timeout: 30_000 });
  // Do not mix CSS and text= in one locator string — Playwright parses it as CSS and throws.
  await expect(page.locator('.app-sidebar')).toBeVisible({ timeout: 30_000 });
  await expect(page.getByRole('link', { name: /dashboard/i }).first()).toBeVisible();
}

export async function logout(page: Page): Promise<void> {
  await page.locator('.dropdown-toggle').filter({ hasText: /.+/ }).first().click();
  await page.getByRole('button', { name: /sign out/i }).click();
  await expect(page).toHaveURL(/\/login/, { timeout: 15_000 });
}
