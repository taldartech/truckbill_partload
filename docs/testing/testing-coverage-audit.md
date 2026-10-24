# Partload — Testing coverage audit

**Project:** TruckBill Partload  
**Audit date:** 2026-10-24  
**Method:** Filesystem + config review; full PHPUnit execution recorded in `test-execution-report.md`.  
**Stack:** Laravel 12 / PHP 8.4 / PHPUnit 11 (not Pest) / Playwright / SQLite `:memory:` in PHPUnit.

## Scope note

Xdebug **line** coverage of `app/` (excluding orphan `Driver*` after table drop) is documented at **100%** in `docs/qa/coverage-matrix.md`. That is **code** coverage only. It does **not** mean all 22 testing categories are fully validated for business sign-off.

## Category posture (honest)

| # | Category | Status | Evidence | Gap |
|---|----------|--------|----------|-----|
| 1 | Unit | Partial → Stronger | `tests/Unit/**` including `GstCalculatorTest`, `MoneyTest`, proration, pricing | Still Feature-heavy vs pure unit |
| 2 | Functional | Complete | `tests/Feature/Ops/**`, accounting, masters | — |
| 3 | Positive | Complete | Happy-path CRUD / journeys | — |
| 4 | Negative | Partial → Stronger | Widespread validation + `tests/Feature/Negative/**` | Not every master has a dedicated negative catalog |
| 5 | API | Partial | `tests/Feature/Api/**` (onboarding + admin subscription) | Portal is mostly Inertia/web |
| 6 | Integration | Complete | Accounting bridge, bulk upload, subscription+DB | — |
| 7 | E2E | Partial | PHP `tests/Feature/E2E/**` + Playwright; process journey Chromium-only | Firefox/WebKit process not in CI |
| 8 | Database | Partial → Stronger | Soft deletes + `TransactionRollbackTest` | SQLite only; MySQL parity manual |
| 9 | Security | Partial → Stronger | Permissions/IDOR + SQL meta / XSS filter / guest write / cross-tenant delete | No DAST / OWASP pipeline |
| 10 | Multi-tenant | Complete | `tests/Feature/Tenant/**`, owner isolation | — |
| 11 | Subscription | Complete | `tests/Feature/Subscription/**` (Razorpay faked) | Live sandbox checkout = manual |
| 12 | Financial | Partial → Stronger | Independent GST/rounding/TDS assertions + invoice GST ops tests | Settlement matrix still incomplete |
| 13 | Accounting | Complete | `tests/Feature/Accounting/**` | — |
| 14 | Migration | Partial | CI migrate smoke + backup/reconcile tests | Not per-migration regression |
| 15 | Regression | Complete | Full PHPUnit on PR/push | — |
| 16 | Performance | Partial → Stronger | PHPUnit `@group performance` + `perf/k6/smoke.js` | k6 not in default CI |
| 17 | Reliability | Partial → Stronger | Catalog failure + accounting bridge idempotency | Narrow surface |
| 18 | UI/UX | Partial | Playwright multi-project config; CI Chromium; forms validation spec | Multi-browser not enforced in CI |
| 19 | Backup/Recovery | Partial | `tests/Feature/Backup/**` (faked dump) | Restore = manual |
| 20 | Audit/Logging | Partial | Soft-delete audit + accounting audit trail + password masking | Incomplete masking matrix |
| 21 | UAT | Partial | Checklist + `@uat` automation | **Business approval pending** |
| 22 | Deployment | Partial → Stronger | CI migrate/cache + `EnvironmentReadinessTest` + rollback doc | No deploy workflow |

**Counts (post-increment):** Complete **8** · Stronger Partial **14** · Missing **0** · N/A **0**

## Infrastructure

| Item | State |
|------|--------|
| PHPUnit + SQLite `:memory:` | Present (`memory_limit=-1` in `phpunit.xml`) |
| Playwright + `e2e:seed` | Present |
| GitHub Actions `tests.yml` | PHPUnit + JUnit artifact + Chromium Playwright + push-time Clover gate |
| Coverage in CI | Measured on **push** (`php-coverage` job); PRs stay fast |
| k6 | `perf/k6/smoke.js` (manual / dedicated) |
| Scenario inventory | `docs/testing/test-scenario-matrix.md` |

## Blocked / manual

- Production DB restore / S3 backup integrity
- Live Razorpay sandbox checkout
- Named transporter UAT sign-off
- MySQL-specific constraint behavior vs SQLite
- Root-only backup permission skip in tests
- Full ops Playwright on Firefox/WebKit
