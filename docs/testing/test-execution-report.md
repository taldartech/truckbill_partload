# Test execution report — Partload

**Project:** TruckBill Partload  
**Execution date:** 2026-10-24  
**Environment:** Local CLI — PHP 8.4.21, SQLite `:memory:`, `memory_limit=-1`  
**Command:** `php -d memory_limit=-1 vendor/bin/phpunit --log-junit=storage/app/testing/junit.xml`

## Summary totals

| # | Metric | Value |
|---|--------|------:|
| 1 | Project name | TruckBill Partload |
| 2 | Total applicable requirements (REQ groups) | 16 |
| 3 | Total applicable catalog scenarios (matrix rows) | 52 |
| 4 | Automated catalog scenarios | 49 |
| 5 | Manual catalog scenarios | 3 (UAT-001, BAK-002, PERF-003) |
| 6 | Executed PHPUnit tests | **1235** |
| 7 | Passed PHPUnit tests | **1235** |
| 8 | Failed PHPUnit tests | **0** |
| 9 | Blocked scenarios | 3 (manual/external) |
| 10 | Skipped PHPUnit tests | **0** (root backup permission skip not hit) |
| 11 | Test execution coverage (catalog) | 49/52 = **94.2%** executed automated; 3 manual pending |
| 12 | Test pass rate (PHPUnit) | 1235/1235 = **100%** |
| 13 | Requirement coverage | 16/16 = **100%** (≥1 scenario) |
| 14 | Requirement validation coverage | 14/16 = **87.5%** |
| 15 | Automation coverage (catalog) | 49/52 = **94.2%** |
| 16 | Xdebug code coverage | **100% line** within `phpunit.xml` `<source>` (documented prior; CI `php-coverage` on push) |
| 17 | Failed test details | None |
| 18 | Missing scenarios | See `testing-coverage-audit.md` gaps (DAST, MySQL parity, live payments, multi-browser CI process) |
| 19 | Remaining manual testing | UAT sign-off, DR restore, Razorpay sandbox, k6 against staging |
| 20 | Final testing status | **Automated regression green; not claiming 100% overall testing coverage** |

## PHPUnit detail

| Field | Value |
|-------|------:|
| Assertions | 7422 |
| Duration | 291.4 s (~4m 53s) |
| Memory | 129 MB (after unlimited limit) |
| Failures | 0 |
| Errors | 0 |
| Skipped | 0 |
| Machine-readable | `storage/app/testing/junit.xml` |

## Newly added this increment (executed & passed)

| Area | Files |
|------|-------|
| Unit GST/Money | `tests/Unit/Support/GstCalculatorTest.php`, `MoneyTest.php` |
| Security | `tests/Feature/Security/InputAndSessionSecurityTest.php` |
| Financial | `tests/Feature/Financial/IndependentGstAndRoundingTest.php` |
| Reliability | `tests/Feature/Reliability/AccountingBridgeIdempotencyTest.php` |
| Database | `tests/Feature/Database/TransactionRollbackTest.php` |
| Negative | `tests/Feature/Negative/ValidationRejectionTest.php` |
| Performance | `tests/Performance/PortalListBudgetTest.php` |
| Deployment | `tests/Feature/Deployment/EnvironmentReadinessTest.php` |
| Playwright | `e2e/forms-validation.spec.ts` (not re-run in this report) |
| k6 | `perf/k6/smoke.js` (not executed — binary/env) |

## Playwright / CI

Default CI runs Chromium Playwright after PHPUnit (`playwright-smoke`). HTML + JUnit reporters and artifact upload configured in `.github/workflows/tests.yml`.  
This local report did **not** re-execute Playwright.

## Honesty statement

Do **not** interpret 100% Xdebug line coverage or 100% PHPUnit pass rate as complete UAT, security DAST, performance SLO certification, or production DR validation.
