# UAT checklist (transporters / internal users)

Seed recipe: `php artisan db:seed --class=OperationalFlowSeeder` (non-production only).

## Sign-off template

| Journey | Tester | Date | Pass/Fail | Notes |
|---------|--------|------|-----------|-------|
| Login + branch select | | | | |
| Create bilty → print | | | | |
| Create challan / main challan | | | | |
| Delivery receipt collection | | | | |
| Invoice + GST | | | | |
| Accounting voucher + Trial Balance | | | | |
| Subscription checkout (sandbox) | | | | |
| Role denied screens | | | | |

## Automated UAT tags

Playwright specs tagged `@uat` under `e2e/` cover login smoke. Expand journeys as environments stabilize.

## Exit criteria

- All critical journeys Pass with named business owners.
- No P0/P1 defects open.
- Accounting audit clean or reconciled for the UAT company.
