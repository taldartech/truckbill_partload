import { test, expect } from '@playwright/test';
import { ensureLoggedIn, loginAsE2eUser } from './helpers/auth';

/**
 * Full portal surface walk (sidebar map). Each path must load without a server error.
 */
const portalPages: Array<{ name: string; path: string; heading?: RegExp }> = [
  { name: 'Dashboard', path: '/', heading: /dashboard/i },
  { name: 'Quick Search', path: '/quick-search' },
  { name: 'Bilties', path: '/bilties', heading: /bilty/i },
  { name: 'Create Bilty', path: '/bilties/create' },
  { name: 'Loading Report', path: '/loading-reports' },
  { name: 'Challans', path: '/challans', heading: /challan/i },
  { name: 'Create Challan', path: '/challans/create' },
  { name: 'Main Challans', path: '/main-challans' },
  { name: 'Inward Registers', path: '/inward-registers' },
  { name: 'Delivery Receipts', path: '/delivery-receipts' },
  { name: 'Invoices', path: '/invoices' },
  { name: 'Accounting Vouchers', path: '/accounting/vouchers' },
  { name: 'Bank Accounts', path: '/bank-accounts' },
  { name: 'Chart of Accounts', path: '/accounting/accounts' },
  { name: 'Account Groups', path: '/accounting/account-groups' },
  { name: 'Opening Balances', path: '/accounting/opening-balances/create' },
  { name: 'Financial Years', path: '/accounting/financial-years' },
  { name: 'Accounting Health', path: '/accounting/health' },
  { name: 'Parties', path: '/parties', heading: /part/i },
  { name: 'Stations', path: '/stations' },
  { name: 'Goods', path: '/goods' },
  { name: 'Groups', path: '/groups' },
  { name: 'Package Types', path: '/package-types' },
  { name: 'Trucks', path: '/trucks' },
  { name: 'Truck Types', path: '/truck-types' },
  { name: 'Agents', path: '/agents' },
  { name: 'Account Ledger', path: '/accounting/reports/ledger' },
  { name: 'Day Book', path: '/accounting/reports/day-book' },
  { name: 'Cash Book', path: '/accounting/reports/cash-book' },
  { name: 'Bank Book', path: '/accounting/reports/bank-book' },
  { name: 'Trial Balance', path: '/accounting/reports/trial-balance' },
  { name: 'Profit and Loss', path: '/accounting/reports/profit-and-loss' },
  { name: 'Balance Sheet', path: '/accounting/reports/balance-sheet' },
  { name: 'Receivables', path: '/accounting/reports/receivables' },
  { name: 'Payables', path: '/accounting/reports/payables' },
  { name: 'Party Ledger', path: '/accounting/reports/party-ledger' },
  { name: 'Voucher Register', path: '/accounting/reports/voucher-register' },
  { name: 'Expense Report', path: '/accounting/reports/expenses' },
  { name: 'GST Report', path: '/accounting/reports/gst' },
  { name: 'TDS Report', path: '/accounting/reports/tds' },
  { name: 'Bilty Register', path: '/reports/bilty-register' },
  { name: 'Challan Register', path: '/reports/challan-register' },
  { name: 'Branches', path: '/branches' },
  { name: 'Change Password', path: '/password' },
];

test.describe('Portal page coverage @uat', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsE2eUser(page);
  });

  for (const pageDef of portalPages) {
    test(`loads ${pageDef.name} (${pageDef.path})`, async ({ page }) => {
      const response = await page.goto(pageDef.path, { waitUntil: 'domcontentloaded' });
      expect(response, `No response for ${pageDef.path}`).not.toBeNull();
      await ensureLoggedIn(page);
      if (/\/login/.test(page.url())) {
        await page.goto(pageDef.path, { waitUntil: 'domcontentloaded' });
      }
      expect(page.url(), `${pageDef.path} redirected to login`).not.toMatch(/\/login/);
      const status = response?.status() ?? 0;
      if (status > 0) {
        expect(status, `${pageDef.path} returned ${status}`).toBeLessThan(500);
      }
      await expect(page.locator('body')).toBeVisible();
      await expect(page).not.toHaveURL(/\/login/);
      if (pageDef.heading) {
        await expect(page.getByText(pageDef.heading).first()).toBeVisible({ timeout: 15_000 });
      }
    });
  }
});
