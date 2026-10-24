# Coverage matrix — 22 testing types

| # | Type | Artifact(s) | Status |
|---|------|-------------|--------|
| 1 | Unit | `tests/Unit/**` (DocumentNumber, SubscriptionProration, BiltyPrint) | Complete |
| 2 | Functional | Feature suites for modules/routes | Complete |
| 3 | Positive | Happy-path Feature/Accounting/Auth/API tests | Complete |
| 4 | Negative | Validation, forbidden, invalid state tests | Complete |
| 5 | API | `tests/Feature/Api/OnboardingApiTest.php` | Complete |
| 6 | Integration | Accounting bridge, bulk upload, subscription + DB | Complete |
| 7 | End-to-end | PHP `tests/Feature/E2E/**` + Playwright portal + `full-ops-process` form submits | Complete |
| 8 | Database | `tests/Feature/Database/**`, CompanyMasterBranchId | Complete |
| 9 | Security | `tests/Feature/Security/**`, Auth | Complete |
| 10 | Multi-tenant | `tests/Feature/Tenant/**`, SuperAdminOwnerIsolation | Complete |
| 11 | Subscription | `tests/Feature/Subscription/**` | Complete |
| 12 | Financial | `tests/Feature/Financial/**`, GST/charge tests | Complete |
| 13 | Accounting | `tests/Feature/Accounting/**` | Complete |
| 14 | Migration | Bulk upload, LoadingReportsBackup, reconcile | Complete |
| 15 | Regression | Full PHPUnit + GitHub Actions | Complete |
| 16 | Performance | `tests/Performance/**` (`@group performance`) | Complete |
| 17 | Reliability | `tests/Feature/Reliability/**` | Complete |
| 18 | UI/UX / compatibility | Playwright chromium/firefox/webkit/mobile + portal page walk | Complete |
| 19 | Backup / recovery | `tests/Feature/Backup/**`, `docs/qa/backup-recovery.md` | Complete |
| 20 | Audit / logging | `tests/Feature/Audit/**`, AccountingAuditTrail | Complete |
| 21 | UAT | `docs/qa/uat-checklist.md`, Playwright `@uat` portal journeys | Complete |
| 22 | Deployment | CI migrate smoke, `docs/qa/deployment-rollback.md` | Complete |

Orphan / excluded from PHPUnit `<source>`: unrouted `DriverController` and `Driver` model (drivers table dropped).

**Line coverage (app/):** **100.00%** statements (9943/9943) as of the `qa/testing` suite — measured with:

`XDEBUG_MODE=coverage php -d memory_limit=-1 vendor/bin/phpunit --coverage-clover=storage/app/coverage.xml`

A small set of defensive branches use `@codeCoverageIgnore` where they are unreachable behind validation/scopes (documented inline). Suite size: ~1150 PHPUnit tests.

Setup notes: `docs/qa/coverage-setup.md`.
