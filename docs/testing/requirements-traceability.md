# Requirements ↔ test traceability — Partload

**Note:** This project does not ship a separate formal requirements specification.  
`REQ-*` IDs below are **derived** from implemented modules and business rules for traceability.

| Requirement ID | Description | Mandatory scenarios | Coverage status |
|----------------|-------------|---------------------|-----------------|
| REQ-FIN-01..09 | GST, money, invoice totals, TDS/outstanding arithmetic | UNIT-001..005, FIN-001..004 | Validated (automated) |
| REQ-OPS-01..08 | Party/bilty/invoice/goods functional + negative | FUNC-*, POS-*, NEG-* | Validated (automated) |
| REQ-API-01..02 | Onboarding + admin subscription APIs | API-001, API-002 | Validated (automated) |
| REQ-ACC-01..04 | Accounting bridge, balance, audit, TB budget | INT-*, ACC-* | Validated (automated) |
| REQ-TEN-01..02 | Company isolation | TEN-001, TEN-002 | Validated (automated) |
| REQ-SUB-01..02 | Proration + expiry lock | UNIT-006, SUB-001 | Validated (automated); live checkout manual |
| REQ-SEC-01..05 | Authz, IDOR, injection/XSS filter handling | SEC-* | Validated (automated); no DAST |
| REQ-DATA-01..02 | Soft delete + transaction rollback | DB-* | Validated (automated); MySQL parity manual |
| REQ-REL-01..02 | Catalog failure + idempotent posting | REL-* | Validated (automated) |
| REQ-PERF-01..03 | 5s budgets + k6 smoke | PERF-* | PERF-001/002 validated; PERF-003 blocked (k6) |
| REQ-BAK-01..02 | Backup command + restore SOP | BAK-* | BAK-001 automated (faked); BAK-002 manual |
| REQ-AUD-01..02 | Soft-delete audit + secret masking | AUD-* | Validated (automated) |
| REQ-UAT-01..05 | Portal journeys + transporter sign-off | E2E-*, UI-*, UAT-001 | Automation present; **UAT-001 pending** |
| REQ-DEP-01..03 | Env readiness / routes / schedule | DEP-* | Validated (automated) |
| REQ-MIG-01 | Migration smoke | MIG-001 | Validated (CI) |
| REQ-REG-01 | Full regression suite | REG-001 | Validated (1235/1235) |

## Metrics definitions

- **Requirement coverage** = requirements with ≥1 associated scenario / total applicable requirements.
- **Requirement validation coverage** = requirements whose **mandatory** scenarios all executed **and passed** / total.

As of 2026-10-24 execution:

| Metric | Value |
|--------|-------|
| Applicable REQ groups | 16 |
| With ≥1 scenario | 16 (100%) |
| Fully validated mandatory set | 14 / 16 (87.5%) — excludes UAT-001 business sign-off and PERF-003 k6 |
| Ambiguities | No external REQ baseline; payment settlement edge cases not fully enumerated |
