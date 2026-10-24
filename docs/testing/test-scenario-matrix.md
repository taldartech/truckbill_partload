# Test scenario matrix — Partload

IDs follow `CATEGORY-NNN`. Automation status reflects the suite as of 2026-10-24.  
**Actual Result / Test Status** for automated rows are filled from the latest PHPUnit run (`OK 1235 tests`).

| Test Case ID | Module | Category | Requirement ID | Scenario Title | Preconditions | Test Data | Steps | Expected Result | Actual Result | Test Status | Automation Status | Priority | Related Test File | Execution Notes |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| UNIT-001 | Support/GST | Unit | REQ-FIN-01 | GST amount rounds to paise | — | taxable 1000, rate 18 | Call `GstCalculator::amount` | 180.00 | Matches | Passed | Automated | P0 | `tests/Unit/Support/GstCalculatorTest.php` | Independent literals |
| UNIT-002 | Support/GST | Unit | REQ-FIN-02 | Intra-state CGST/SGST split | — | gst 18.01 | Call `split` same state | CGST+SGST=18.01, IGST=0 | Matches | Passed | Automated | P0 | `tests/Unit/Support/GstCalculatorTest.php` | Odd paise on SGST |
| UNIT-003 | Support/GST | Unit | REQ-FIN-03 | Inter-state IGST only | — | gst 180, states 10/20 | Call `split` | IGST 180 | Matches | Passed | Automated | P0 | `tests/Unit/Support/GstCalculatorTest.php` | |
| UNIT-004 | Support/GST | Unit | REQ-FIN-04 | Bilty GST transporter liable | — | freight+charges 1100, 18%, advance 100 | `forBilty` | amount 1198 | Matches | Passed | Automated | P0 | `tests/Unit/Support/GstCalculatorTest.php` | DataProvider |
| UNIT-005 | Support/Money | Unit | REQ-FIN-05 | Paise conversion symmetry | — | 99.99 | toPaise/fromPaise | 9999 / 99.99 | Matches | Passed | Automated | P1 | `tests/Unit/Support/MoneyTest.php` | |
| UNIT-006 | Subscription | Unit | REQ-SUB-01 | Proration never exceeds annual | — | annual 1000, days 400 | `prorateAnnualAmount` | 1000 | Matches | Passed | Automated | P0 | `tests/Unit/Support/SubscriptionProrationTest.php` | Existing |
| FUNC-001 | Parties | Functional | REQ-OPS-01 | Create/view party | Auth + create permission | Valid party payload | POST create, GET show | Persisted + visible | Matches | Passed | Automated | P0 | `tests/Feature/Ops/PartyCrudTest.php` | Existing suite |
| FUNC-002 | Bilties | Functional | REQ-OPS-02 | Bilty CRUD + duplicate number | Branch context | Same bilty_number | Store twice | Second rejected | Matches | Passed | Automated | P0 | `tests/Feature/Ops/BiltyCrudTest.php` | Existing |
| FUNC-003 | Invoices | Functional | REQ-OPS-03 | Invoice store links bilties | TBB bilty pending | bilty_ids | POST invoices.store | Invoice + links | Matches | Passed | Automated | P0 | `tests/Feature/Ops/InvoiceCrudTest.php` | Existing |
| POS-001 | Goods | Positive | REQ-OPS-04 | Valid good create | create goods | name | POST goods.store | Row created | Matches | Passed | Automated | P1 | `tests/Feature/Ops/GoodCrudTest.php` | Existing |
| NEG-001 | Parties | Negative | REQ-OPS-05 | Missing required party fields | create parties | empty name | POST parties.store | Errors; no write | Matches | Passed | Automated | P0 | `tests/Feature/Negative/ValidationRejectionTest.php` | New |
| NEG-002 | Parties | Negative | REQ-OPS-06 | Invalid mobile rejected | create parties | mobile 12345 | POST parties.store | mobile error; no write | Matches | Passed | Automated | P0 | `tests/Feature/Negative/ValidationRejectionTest.php` | New |
| NEG-003 | Goods | Negative | REQ-OPS-07 | Missing good name | create goods | {} | POST goods.store | name error | Matches | Passed | Automated | P1 | `tests/Feature/Negative/ValidationRejectionTest.php` | New |
| NEG-004 | Invoices | Negative | REQ-OPS-08 | Invoice required fields | create invoices | {} | POST invoices.store | Errors; count 0 | Matches | Passed | Automated | P0 | `tests/Feature/Ops/InvoiceCrudTest.php` | Existing |
| API-001 | Onboarding | API | REQ-API-01 | Signed create-user | Valid signature | payload | POST api/onboarding/create-user | 2xx / idempotent | Matches | Passed | Automated | P0 | `tests/Feature/Api/OnboardingApiTest.php` | Existing |
| API-002 | Admin subscription | API | REQ-API-02 | Admin subscription index | onboarding.signature | headers | GET api/admin/subscriptions | Authorized response | Matches | Passed | Automated | P0 | `tests/Feature/Api/AdminSubscriptionApiTest.php` | Existing |
| INT-001 | Accounting | Integration | REQ-ACC-01 | Invoice posts balanced voucher | Accounting enabled | Invoice 1500 | Bridge sync | Debit=Credit | Matches | Passed | Automated | P0 | `tests/Feature/Accounting/OperationalBridgeTest.php` | Existing |
| INT-002 | Accounting | Integration | REQ-ACC-02 | Unchanged resync is no-op | Posted invoice | Same document | sync ×3 | 1 voucher | Matches | Passed | Automated | P0 | `tests/Feature/Reliability/AccountingBridgeIdempotencyTest.php` | New |
| E2E-001 | Auth | E2E | REQ-UAT-01 | Login/logout portal | e2e seed | e2e user | Playwright auth | Dashboard then login | See CI | Automated | Automated | P0 | `e2e/auth.spec.ts` | Chromium CI |
| E2E-002 | Ops | E2E | REQ-UAT-02 | Full ops process journey | e2e seed | forms | Playwright process | Masters→bilty→challan | See CI | Automated | Automated | P0 | `e2e/full-ops-process.spec.ts` | Chromium only |
| E2E-003 | UI | UI/UX | REQ-UAT-03 | Party create empty validation | e2e seed | empty form | Submit create | Stays on create + message | Not re-run this session | Pending local | Automated | P1 | `e2e/forms-validation.spec.ts` | New |
| DB-001 | Parties | Database | REQ-DATA-01 | Soft delete hides party | delete parties | Party | destroy | soft deleted | Matches | Passed | Automated | P0 | `tests/Feature/Database/SoftDeleteAndConstraintsTest.php` | Existing |
| DB-002 | Parties | Database | REQ-DATA-02 | Failed txn rolls back | — | Party + throw | DB::transaction | No row | Matches | Passed | Automated | P0 | `tests/Feature/Database/TransactionRollbackTest.php` | New |
| SEC-001 | Auth | Security | REQ-SEC-01 | Guest redirected from parties | Guest | — | GET parties.index | Redirect login | Matches | Passed | Automated | P0 | `tests/Feature/Security/PermissionAndIdorTest.php` | Existing |
| SEC-002 | Parties | Security | REQ-SEC-02 | Cross-company party IDOR | Two companies | Party B id | GET/PUT as A | 404; unchanged | Matches | Passed | Automated | P0 | `tests/Feature/Security/PermissionAndIdorTest.php` | Existing |
| SEC-003 | Parties | Security | REQ-SEC-03 | SQL meta chars in search | view parties | `' OR '1'='1` | GET index search | 0 rows; ok | Matches | Passed | Automated | P0 | `tests/Feature/Security/InputAndSessionSecurityTest.php` | New |
| SEC-004 | Parties | Security | REQ-SEC-04 | XSS payload as filter value | view parties | script tag | GET index search | Plain filter value | Matches | Passed | Automated | P1 | `tests/Feature/Security/InputAndSessionSecurityTest.php` | New |
| SEC-005 | Parties | Security | REQ-SEC-05 | Cross-tenant delete blocked | Two companies | Party B | DELETE as A | 404; remains | Matches | Passed | Automated | P0 | `tests/Feature/Security/InputAndSessionSecurityTest.php` | New |
| TEN-001 | Tenant | Multi-tenant | REQ-TEN-01 | Index excludes other company | Two companies | Parties | GET parties.index | Only own | Matches | Passed | Automated | P0 | `tests/Feature/Tenant/MultiTenantIsolationTest.php` | Existing |
| TEN-002 | Tenant | Multi-tenant | REQ-TEN-02 | Bilty show cross-company 404 | Two companies | Bilty B | GET show as A | 404 | Matches | Passed | Automated | P0 | `tests/Feature/Tenant/MultiTenantIsolationTest.php` | Existing |
| SUB-001 | Subscription | Subscription | REQ-SUB-02 | Expired without grace locked | Expired sub | — | Middleware | Locked | Matches | Passed | Automated | P0 | `tests/Feature/Subscription/*` | Existing |
| FIN-001 | Invoice | Financial | REQ-FIN-06 | Invoice GST independent check | Accounting | 2500 @ 18% | Persist + sync | gst 450; net 2950; balanced | Matches | Passed | Automated | P0 | `tests/Feature/Financial/IndependentGstAndRoundingTest.php` | New |
| FIN-002 | Payments | Financial | REQ-FIN-07 | Partial payment outstanding | — | 1180−500 | Arithmetic | 680 | Matches | Passed | Automated | P1 | `tests/Feature/Financial/IndependentGstAndRoundingTest.php` | Literal calc |
| FIN-003 | TDS | Financial | REQ-FIN-08 | TDS 2% deduction | — | gross 10000 | Arithmetic | TDS 200; payable 9800 | Matches | Passed | Automated | P1 | `tests/Feature/Financial/IndependentGstAndRoundingTest.php` | Literal calc |
| FIN-004 | Invoice | Financial | REQ-FIN-09 | Ops invoice GST package rate | TBB bilty | rate 100×10 + charge | Store invoice | totals match | Matches | Passed | Automated | P0 | `tests/Feature/Ops/InvoiceCrudTest.php` | Existing |
| ACC-001 | Reports | Accounting | REQ-ACC-03 | Trial balance within budget | view reports | date range | GET trial-balance | <5s + 200 | Matches | Passed | Automated | P1 | `tests/Performance/ReportResponseBudgetTest.php` | Existing |
| ACC-002 | Vouchers | Accounting | REQ-ACC-04 | Accounting audit trail | Posted docs | — | Sync + audit | Trail present | Matches | Passed | Automated | P0 | `tests/Feature/Accounting/AccountingAuditTrailTest.php` | Existing |
| MIG-001 | Schema | Migration | REQ-MIG-01 | CI migrate --force | Empty sqlite file | — | migrate | Success | Matches | Passed | Automated | P0 | `.github/workflows/tests.yml` | CI |
| REG-001 | Suite | Regression | REQ-REG-01 | Full PHPUnit green | — | — | phpunit | All pass | 1235/1235 | Passed | Automated | P0 | `phpunit.xml` | 2026-10-24 run |
| PERF-001 | Lists | Performance | REQ-PERF-01 | Parties index <5s | view parties | — | GET index | <5000ms | Matches | Passed | Automated | P2 | `tests/Performance/PortalListBudgetTest.php` | New |
| PERF-002 | Lists | Performance | REQ-PERF-02 | Bilties index <5s | view bilties | — | GET index | <5000ms | Matches | Passed | Automated | P2 | `tests/Performance/PortalListBudgetTest.php` | New |
| PERF-003 | Login | Performance | REQ-PERF-03 | k6 login smoke p95<5s | Running app + k6 | BASE_URL | k6 run smoke.js | Thresholds | Not executed | Blocked | Manual | P2 | `perf/k6/smoke.js` | Needs k6 binary |
| REL-001 | Catalog | Reliability | REQ-REL-01 | Catalog unavailable fallback | Mock AdminApi | throw | cachedSettings | Defaults | Matches | Passed | Automated | P1 | `tests/Feature/Reliability/DuplicateRequestAndCatalogFailureTest.php` | Existing |
| REL-002 | Accounting | Reliability | REQ-REL-02 | Bridge idempotent sync | Posted invoice | resync | No duplicates | Matches | Passed | Automated | P0 | `tests/Feature/Reliability/AccountingBridgeIdempotencyTest.php` | New |
| UI-001 | Auth | UI/UX | REQ-UAT-04 | Empty login validation | — | empty form | Submit | Stay on login | See CI | Automated | Automated | P1 | `e2e/auth.spec.ts` | Existing |
| BAK-001 | Backup | Backup | REQ-BAK-01 | DBBackup uploads when dump ok | Fake process | — | artisan backup | Upload called | Matches | Passed | Automated | P1 | `tests/Feature/Backup/DbBackupCommandTest.php` | Faked |
| BAK-002 | Backup | Backup | REQ-BAK-02 | Production restore | Prod backup | — | Restore SOP | Verified restore | — | Blocked | Manual | P0 | `docs/qa/backup-recovery.md` | Never on prod from CI |
| AUD-001 | Parties | Audit | REQ-AUD-01 | Destroy sets deleted_by | delete parties | Party | DELETE | deleted_at + user | Matches | Passed | Automated | P0 | `tests/Feature/Audit/SoftDeleteAuditAndLoggingTest.php` | Existing |
| AUD-002 | Auth | Audit | REQ-AUD-02 | Failed login hides password | Guest | bad password | POST login | Password not in errors | Matches | Passed | Automated | P0 | `tests/Feature/Audit/SoftDeleteAuditAndLoggingTest.php` | Existing |
| UAT-001 | Ops | UAT | REQ-UAT-05 | Transporter ops sign-off | Staging data | Journeys | Manual checklist | Named Pass | Pending | Manual | Manual | P0 | `docs/testing/uat-checklist.md` | Not fabricated |
| DEP-001 | Env | Deployment | REQ-DEP-01 | App key + critical tables | Migrated test DB | — | readiness tests | Present | Matches | Passed | Automated | P0 | `tests/Feature/Deployment/EnvironmentReadinessTest.php` | New |
| DEP-002 | Routes | Deployment | REQ-DEP-02 | Login + API routes registered | — | — | route:list JSON | URIs present | Matches | Passed | Automated | P0 | `tests/Feature/Deployment/EnvironmentReadinessTest.php` | New |
| DEP-003 | Schedule | Deployment | REQ-DEP-03 | schedule:list succeeds | — | — | artisan | Exit 0 | Matches | Passed | Automated | P1 | `tests/Feature/Deployment/EnvironmentReadinessTest.php` | New |

## Inventory notes

- This matrix is the **structured catalog** for release tracking. It does **not** list every one of the ~1235 PHPUnit methods (Ops CRUD alone covers dozens of POS/NEG/FUNC cases).
- Additional automated coverage lives under `tests/Feature/Ops/**`, `tests/Feature/Accounting/**`, `tests/Feature/Subscription/**`, and Coverage helpers.
- Formal product requirements IDs (`REQ-*`) are derived for traceability; no separate external requirements repository was available in-repo.
