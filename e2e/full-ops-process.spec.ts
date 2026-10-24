import { test, expect } from '@playwright/test';
import { loginAsE2eUser } from './helpers/auth';
import {
  clickIndexCreate,
  confirmBiltySave,
  confirmChallanSave,
  dismissAddAnotherPrompt,
  expectFlashOrListContains,
  fillModalInput,
  fillNearLabel,
  selectByPlaceholder,
  selectField,
  submitModal,
  uniqueSuffix,
} from './helpers/forms';

/**
 * Full operational process: create masters via UI modals/forms, book a bilty,
 * then raise a challan — including confirm dialogs and list verification.
 *
 * Runs on Chromium only (heavy serial journey). Smoke page-walk stays in portal-pages / ops-journey.
 */
test.describe('Full operational process @uat @process', () => {
  test.describe.configure({ mode: 'serial' });
  test.setTimeout(90_000);
  // mobile-chrome also reports browserName=chromium; pin to desktop Chromium project only.
  test.beforeEach(({}, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium', 'Process journey runs on Chromium only');
  });

  const suffix = uniqueSuffix();
  const consignor = `E2E Consignor ${suffix}`;
  const consignee = `E2E Consignee ${suffix}`;
  const agent = `E2E Agent ${suffix}`;
  const good = `E2E Goods ${suffix}`;
  const packing = `E2E Box ${suffix}`;
  const truckType = `E2E Open ${suffix}`;
  const truckNumber = `RJ14E2${suffix.slice(-4)}`;
  const biltyNumber = `E2E-BLT-${suffix}`;
  const challanNumber = `E2E-CH-${suffix}`;

  test.beforeEach(async ({ page }) => {
    await loginAsE2eUser(page);
  });

  test('1) create parties via modal form submit', async ({ page }) => {
    await page.goto('/parties');
    await clickIndexCreate(page);
    await fillModalInput(page, /party name/i, consignor);
    await fillModalInput(page, /address/i, '1 Consignor Road');
    await selectByPlaceholder(page, 'Select State', 'Rajasthan');
    await selectByPlaceholder(page, 'Select City', 'Udaipur');
    await fillModalInput(page, /mobile/i, `98${suffix}`);
    await submitModal(page);
    await expectFlashOrListContains(page, consignor);

    await clickIndexCreate(page);
    await fillModalInput(page, /party name/i, consignee);
    await fillModalInput(page, /address/i, '2 Consignee Road');
    await selectByPlaceholder(page, 'Select State', 'Rajasthan');
    await selectByPlaceholder(page, 'Select City', 'Udaipur');
    await fillModalInput(page, /mobile/i, `97${suffix}`);
    await submitModal(page);
    await expectFlashOrListContains(page, consignee);
  });

  test('2) create agent, goods, package type via modal form submit', async ({ page }) => {
    await page.goto('/agents');
    await clickIndexCreate(page);
    await page.locator('#agent_name').fill(agent);
    await page.locator('#agent_address').fill('3 Agent Road');
    await selectByPlaceholder(page, 'Select State', 'Rajasthan');
    await selectByPlaceholder(page, 'Select City', 'Udaipur');
    await page.locator('#agent_mobile').fill(`96${suffix}`);
    await submitModal(page);
    await expectFlashOrListContains(page, agent);

    await page.goto('/goods');
    await clickIndexCreate(page);
    await fillModalInput(page, /^name$/i, good);
    await submitModal(page);
    await expectFlashOrListContains(page, good);

    await page.goto('/package-types');
    await clickIndexCreate(page);
    await fillModalInput(page, /^name$/i, packing);
    await submitModal(page);
    await expectFlashOrListContains(page, packing);
  });

  test('3) create truck type and truck via form submit', async ({ page }) => {
    await page.goto('/truck-types');
    await clickIndexCreate(page);
    await fillModalInput(page, /^name$/i, truckType);
    await submitModal(page);
    await expectFlashOrListContains(page, truckType);

    await page.goto('/trucks/create');
    await fillNearLabel(page, /truck number/i, truckNumber);
    await selectByPlaceholder(page, 'Select Type', truckType);
    await selectByPlaceholder(page, 'Select Owner', agent);
    await selectByPlaceholder(page, 'Select State', 'Rajasthan');
    await selectByPlaceholder(page, 'Select City', 'Udaipur');
    await selectByPlaceholder(page, 'Select Station', 'E2E Station');
    await page.getByRole('button', { name: /save truck/i }).click();
    await expect(page).toHaveURL(/\/trucks(\?|$)/, { timeout: 20_000 });
    await expectFlashOrListContains(page, new RegExp(truckNumber, 'i'));
  });

  test('4) create bilty via full form submit + confirm modal', async ({ page }) => {
    await page.goto('/bilties/create');

    await page.locator('.bilty-id__field', { hasText: /bilty number/i }).locator('input').fill(biltyNumber);

    await selectField(page, /from station/i, 'E2E Station');
    await selectField(page, /to station/i, 'E2E Destination');
    await selectField(page, /consignor/i, consignor);
    await selectField(page, /consignee/i, consignee);

    const goodsRow = page.locator('table.table-grid tbody tr').first();
    await goodsRow.locator('.searchable-select__trigger[placeholder="Select Good"]').click();
    await goodsRow.locator('.searchable-select__trigger[placeholder="Select Good"]').fill(good);
    await page.locator('.ss__option', { hasText: good }).first().click();

    await goodsRow.locator('.searchable-select__trigger[placeholder="Select Packing"]').click();
    await goodsRow.locator('.searchable-select__trigger[placeholder="Select Packing"]').fill(packing);
    await page.locator('.ss__option', { hasText: packing }).first().click();

    await goodsRow.locator('input[type="number"]').nth(0).fill('5');
    await goodsRow.locator('input[type="number"]').nth(1).fill('100');
    await goodsRow.locator('input[type="number"]').nth(2).fill('100');
    await goodsRow.locator('input[type="number"]').nth(3).fill('250');

    await page.locator('form.bilty-form button[type="submit"]').click();
    await expect(page.getByRole('button', { name: /yes,\s*save bilty/i })).toBeVisible({ timeout: 10_000 });
    await confirmBiltySave(page);

    await expect(page).toHaveURL(/\/bilties(\?|$)/, { timeout: 30_000 });
    await expectFlashOrListContains(page, biltyNumber);
  });

  test('5) create challan from bilty via form submit + confirm modal', async ({ page }) => {
    await page.goto('/challans/create');

    await page.locator('.bilty-id__field', { hasText: /challan number/i }).locator('input').fill(challanNumber);
    await selectField(page, /from station/i, 'E2E Station');
    await selectField(page, /to station/i, 'E2E Destination');
    await selectField(page, /truck no/i, truckNumber);
    await fillNearLabel(page, /^driver/i, 'E2E Driver');

    const lineTrigger = page.locator('table.table-grid tbody tr').first().locator('.searchable-select__trigger');
    await lineTrigger.click();
    await lineTrigger.fill(biltyNumber);
    await page.locator('.ss__option', { hasText: biltyNumber }).first().click();
    await dismissAddAnotherPrompt(page);

    await page.locator('form.bilty-form button[type="submit"]').click();
    await expect(page.getByRole('button', { name: /yes,\s*save challan/i })).toBeVisible({ timeout: 10_000 });
    await confirmChallanSave(page);

    await expect(page).toHaveURL(/\/challans(\?|$)/, { timeout: 30_000 });
    await expectFlashOrListContains(page, challanNumber);
  });
});
