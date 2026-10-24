# Performance testing

## Existing automated budgets

PHPUnit `@group performance` tests assert that selected portal/report endpoints respond in **under 5000 ms** in the SQLite test environment:

- `tests/Performance/ReportResponseBudgetTest.php` — trial balance
- `tests/Performance/PortalListBudgetTest.php` — parties / bilties index

These budgets come from the existing trial-balance assertion already in the suite. They are **not** production SLAs.

## k6 (manual / dedicated workflow)

Script: `perf/k6/smoke.js`

```bash
k6 run -e BASE_URL=http://127.0.0.1:8000 perf/k6/smoke.js
```

Thresholds: `http_req_failed rate < 5%`, `p(95) duration < 5000ms` (aligned to the PHPUnit budget above).

Requires a running app and the `k6` binary. Not part of the default PR workflow (expensive / environment-specific).
