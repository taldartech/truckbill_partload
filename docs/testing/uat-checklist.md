# UAT checklist — Partload (business sign-off)

Seed (non-production only): `php artisan db:seed --class=OperationalFlowSeeder`  
Automated `@uat` Playwright tags exist under `e2e/` — **automation ≠ business approval**.

## Sign-off table

| Journey | Tester (name) | Org / role | Date | Pass/Fail | Notes |
|---------|---------------|------------|------|-----------|-------|
| Login + branch select | | | | | Pending |
| Create bilty → print | | | | | Pending |
| Create challan / main challan | | | | | Pending |
| Delivery receipt collection | | | | | Pending |
| Invoice + GST | | | | | Pending |
| Accounting voucher + Trial Balance | | | | | Pending |
| Subscription checkout (sandbox) | | | | | Pending / manual keys |
| Role denied screens | | | | | Pending |

## Exit criteria

- All critical journeys **Pass** with named business owners.
- No P0/P1 defects open for the UAT company.
- Accounting audit clean or reconciled for the UAT company.
- This table must **not** be marked approved solely because PHPUnit/Playwright passed.

Also see: `docs/qa/uat-checklist.md` (legacy location; keep in sync).
