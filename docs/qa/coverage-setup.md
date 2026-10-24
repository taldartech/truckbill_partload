# QA coverage setup

## PHPUnit baseline

```bash
php artisan test --compact
```

PHPUnit uses SQLite `:memory:` via `phpunit.xml`. Copy `.env.testing.example` to `.env.testing` for local overrides.

### Portal end-to-end (PHP)

Full-portal HTTP/Inertia journeys live under `tests/Feature/E2E/`:

```bash
php artisan test --compact tests/Feature/E2E
```

- `PortalSurfaceCoverageTest` — every primary portal page for an operational admin
- `OperationalJourneyTest` — masters → bilty → challan → reports; TBB → invoice; inward → delivery receipt

## Code coverage (Xdebug or PCOV)

```bash
# Xdebug
XDEBUG_MODE=coverage php artisan test --coverage

# HTML report (when coverage driver is loaded)
XDEBUG_MODE=coverage php artisan test --coverage-html=storage/app/coverage
```

Install Xdebug or PCOV for the same PHP CLI used by Artisan. Coverage mode is separate from debugging mode.

## Playwright (browser portal e2e)

```bash
npm ci
npm run build
php artisan e2e:seed
npx playwright install
npx playwright test
npx playwright show-report   # needs a prior test run (writes playwright-report/)
```

`playwright.config.ts` starts `php artisan serve` on `http://127.0.0.1:8000` when needed (`reuseExistingServer` outside CI) and runs `e2e:seed` via `globalSetup`. Specs cover auth, every sidebar page, mobile navigation, create-screen smoke, and the full form-submission process (`e2e/full-ops-process.spec.ts`: masters → bilty → challan).

```bash
npm run test:e2e:process   # Chromium-only full process journey
```

Override with:

- `PLAYWRIGHT_BASE_URL` — e.g. `https://partload.truckbill.test`
- `E2E_EMAIL` / `E2E_PASSWORD` — seeded operator credentials (defaults `e2e@truckbill.test` / `password`)
- `E2E_SKIP_SEED=1` — skip seeding when the tenant already exists

## CI

GitHub Actions workflow `.github/workflows/tests.yml` runs PHPUnit (including `tests/Feature/E2E`) and Playwright chromium portal e2e.
