# UAT checklist (transporters / internal users)

Canonical copy with pending sign-off: **`docs/testing/uat-checklist.md`**.

Seed recipe: `php artisan db:seed --class=OperationalFlowSeeder` (non-production only).

## Sign-off template

| Journey | Tester | Date | Pass/Fail | Notes |
|---------|--------|------|-----------|-------|
| Login + branch select | | | | Pending |
| Create bilty → print | | | | Pending |
| Create challan / main challan | | | | Pending |
| Delivery receipt collection | | | | Pending |
| Invoice + GST | | | | Pending |
| Accounting voucher + Trial Balance | | | | Pending |
| Subscription checkout (sandbox) | | | | Pending |
| Role denied screens | | | | Pending |

## Automated UAT tags

Playwright specs tagged `@uat` under `e2e/` cover auth, full portal page walk, mobile nav, create-screen smoke, forms validation, and `@process` form submits (masters → bilty → challan). PHP mirror: `tests/Feature/E2E`.

**Automation pass ≠ business approval.**

## Exit criteria

- All critical journeys Pass with named business owners.
- No P0/P1 defects open.
- Accounting audit clean or reconciled for the UAT company.
