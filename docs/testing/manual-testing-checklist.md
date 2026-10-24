# Manual testing checklist — Partload

Use staging or an isolated database. **Never** run destructive restore against production.

| ID | Area | Steps | Pass? | Tester | Date | Notes |
|----|------|-------|-------|--------|------|-------|
| MAN-01 | MySQL constraint parity | Run critical unique/FK scenarios on MySQL staging (duplicate bilty_number, FK deletes) | | | | SQLite CI only |
| MAN-02 | Razorpay sandbox checkout | Super admin → subscription checkout with sandbox keys; complete payment | | | | HTTP faked in PHPUnit |
| MAN-03 | Backup restore drill | Generate backup; restore to **empty disposable** DB; compare row counts for companies/bilties/vouchers | | | | See `docs/qa/backup-recovery.md` |
| MAN-04 | S3 backup disk | Confirm `app:dbbackup` uploads to configured non-prod disk | | | | |
| MAN-05 | Multi-browser smoke | Run Playwright firefox + webkit locally for auth + portal pages | | | | Process journey is Chromium-only |
| MAN-06 | k6 smoke | `k6 run -e BASE_URL=… perf/k6/smoke.js` against staging | | | | Thresholds = 5s p95 |
| MAN-07 | Print layouts | Spot-check bilty / challan / invoice / delivery prints with logo + GST | | | | |
| MAN-08 | Role denial UX | Login as limited role; confirm forbidden pages + flash messaging | | | | |
| MAN-09 | Branch switch | Multi-branch user switches branch; confirm document lists scoped | | | | |
| MAN-10 | Deploy rollback dry-run | Follow `docs/qa/deployment-rollback.md` on staging | | | | |

## Exit criteria for manual pack

- All P0 rows Pass or explicitly Waived with owner.
- UAT checklist (`uat-checklist.md`) has named business sign-off.
- No open P0/P1 defects for the release candidate.
