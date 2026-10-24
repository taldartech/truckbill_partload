# Coverage matrix — 22 testing types

> **Honesty note:** Xdebug **line** coverage of `app/` (excluding orphan `Driver*`) is **100%**.  
> Category “Complete” below means **meaningful automated coverage exists for the applicable surface**, not that every conceivable scenario or business UAT is signed off.  
> Detailed audit: `docs/testing/testing-coverage-audit.md`.

| # | Type | Artifact(s) | Status |
|---|------|-------------|--------|
| 1 | Unit | `tests/Unit/**` (GstCalculator, Money, DocumentNumber, SubscriptionProration, BiltyPrint, …) | Strong |
| 2 | Functional | Feature suites for modules/routes (`tests/Feature/Ops/**`, …) | Complete |
| 3 | Positive | Happy-path Feature/Accounting/Auth/API tests | Complete |
| 4 | Negative | Validation, forbidden, invalid state + `tests/Feature/Negative/**` | Strong |
| 5 | API | `tests/Feature/Api/*` (onboarding + admin subscription) | Partial (portal is Inertia/web) |
| 6 | Integration | Accounting bridge, bulk upload, subscription + DB | Complete |
| 7 | End-to-end | PHP `tests/Feature/E2E/**` + Playwright (`e2e/*`); process = Chromium | Partial |
| 8 | Database | `tests/Feature/Database/**` (soft delete + txn rollback) | Strong (SQLite) |
| 9 | Security | `tests/Feature/Security/**`, Auth | Strong (no DAST) |
| 10 | Multi-tenant | `tests/Feature/Tenant/**`, SuperAdminOwnerIsolation | Complete |
| 11 | Subscription | `tests/Feature/Subscription/**` | Complete (Razorpay faked) |
| 12 | Financial | `tests/Feature/Financial/**`, GST/charge tests | Strong |
| 13 | Accounting | `tests/Feature/Accounting/**` | Complete |
| 14 | Migration | CI migrate smoke, LoadingReportsBackup, reconcile | Partial |
| 15 | Regression | Full PHPUnit + GitHub Actions | Complete |
| 16 | Performance | `tests/Performance/**` + `perf/k6/smoke.js` | Partial (k6 manual) |
| 17 | Reliability | `tests/Feature/Reliability/**` | Strong |
| 18 | UI/UX / compatibility | Playwright projects; CI Chromium; forms validation | Partial |
| 19 | Backup / recovery | `tests/Feature/Backup/**`, `docs/qa/backup-recovery.md` | Partial (restore manual) |
| 20 | Audit / logging | `tests/Feature/Audit/**`, AccountingAuditTrail | Strong |
| 21 | UAT | `docs/testing/uat-checklist.md`, Playwright `@uat` | Partial (**sign-off pending**) |
| 22 | Deployment | CI migrate smoke, `EnvironmentReadinessTest`, rollback doc | Strong |

Orphan / excluded from PHPUnit `<source>`: unrouted `DriverController` and `Driver` model (drivers table dropped).

**Line coverage (app/):** **100.00%** statements within scope — measured with:

`XDEBUG_MODE=coverage php -d memory_limit=-1 vendor/bin/phpunit --coverage-clover=storage/app/coverage.xml`

CI: push job `php-coverage` regenerates Clover and fails if line coverage &lt; 100% within `<source>`.

Suite size (2026-10-24): **1235** PHPUnit tests, **7422** assertions — all passed.

Setup notes: `docs/qa/coverage-setup.md`. Program docs: `docs/testing/`.
