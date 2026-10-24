import { expect, type Locator, type Page } from '@playwright/test';

/** Active Bootstrap modal (create/edit masters). */
function openModal(page: Page): Locator {
  return page.locator('.modal.show').last();
}

/** Click a header "Create" toolbar button on index pages. */
export async function clickIndexCreate(page: Page): Promise<void> {
  await page.locator('.app-index-card-toolbar button').filter({ hasText: /create/i }).first().click();
  await expect(openModal(page)).toBeVisible({ timeout: 10_000 });
}

/** Fill a plain input inside the currently open Bootstrap modal. */
export async function fillModalInput(page: Page, label: RegExp | string, value: string): Promise<void> {
  const modal = openModal(page);
  const group = modal.locator('.form-group, .mb-3, .mb-4').filter({ hasText: label }).first();
  const input = group.locator('input.form-control, textarea.form-control').first();
  await input.fill(value);
}

/**
 * Choose an option from SearchableSelect by its placeholder text.
 * Prefers the open modal when one is visible.
 */
export async function selectByPlaceholder(page: Page, placeholder: string, optionText: string): Promise<void> {
  const modal = page.locator('.modal.show');
  const scope = (await modal.count()) > 0 ? modal.last() : page;
  const trigger = scope.locator(`.searchable-select__trigger[placeholder="${placeholder}"]`).last();
  await trigger.click();
  await trigger.fill(optionText);
  const option = page.locator('.ss__option', { hasText: optionText }).first();
  await expect(option).toBeVisible({ timeout: 10_000 });
  await option.click();
}

/** Choose SearchableSelect scoped under a field label (bilty/challan forms). */
export async function selectField(page: Page, fieldLabel: RegExp | string, optionText: string): Promise<void> {
  const field = page.locator('.bilty-field').filter({ hasText: fieldLabel }).first();
  const trigger = field.locator('.searchable-select__trigger').first();
  await trigger.click();
  await trigger.fill(optionText);
  const option = page.locator('.ss__option', { hasText: optionText }).first();
  await expect(option).toBeVisible({ timeout: 10_000 });
  await option.click();
}

export async function submitModal(page: Page): Promise<void> {
  const modal = openModal(page);
  await modal.getByRole('button', { name: /^save$/i }).click();
  await expect(modal).toBeHidden({ timeout: 20_000 });
}

export async function confirmBiltySave(page: Page): Promise<void> {
  const ack = page.locator('#bilty-declared-value-ack');
  if (await ack.count()) {
    await ack.check();
  }
  await page.getByRole('button', { name: /yes,\s*save bilty/i }).click();
}

export async function confirmChallanSave(page: Page): Promise<void> {
  await page.getByRole('button', { name: /yes,\s*save challan/i }).click();
}

/** Dismiss "Do you want to add another …?" keyboard confirm if it appears. */
export async function dismissAddAnotherPrompt(page: Page): Promise<void> {
  const overlay = page.locator('.keyboard-confirm-overlay');
  try {
    await overlay.waitFor({ state: 'visible', timeout: 5_000 });
    // Accessible name includes kbd hints ("No N Esc"), so match by prefix / class.
    await overlay.locator('button.btn-outline-secondary').click();
    await expect(overlay).toBeHidden({ timeout: 5_000 });
  } catch {
    // Prompt did not appear — continue.
  }
}

export async function expectFlashOrListContains(page: Page, text: string | RegExp): Promise<void> {
  const needle = typeof text === 'string' ? text : text.source;
  // Index pages paginate (10) and sort by name — newly created rows often are not on page 1.
  const search = page.locator('input[placeholder*="Name"], input[placeholder*="name"], .app-index-search input').first();
  if (await search.count()) {
    await search.fill(needle);
    const searchBtn = page.getByRole('button', { name: /search/i }).first();
    if (await searchBtn.count()) {
      await searchBtn.click();
    } else {
      await search.press('Enter');
    }
    await page.waitForLoadState('domcontentloaded');
  }

  // Prefer a visible match — index pages duplicate rows in mobile + desktop panes.
  await expect(page.getByText(text).locator('visible=true').first()).toBeVisible({ timeout: 20_000 });
}

export function uniqueSuffix(): string {
  return `${Date.now().toString().slice(-8)}`;
}

export async function fillNearLabel(page: Page, label: RegExp | string, value: string): Promise<Locator> {
  const field = page.locator('.bilty-field, .bilty-id__field, .form-group').filter({ hasText: label }).first();
  const input = field.locator('input.form-control, select.form-select').first();
  await input.fill(value);

  return input;
}
