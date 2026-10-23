# QA coverage setup

## PHPUnit baseline

```bash
php artisan test --compact
```

PHPUnit uses SQLite `:memory:` via `phpunit.xml`. Copy `.env.testing.example` to `.env.testing` for local overrides.

## Code coverage (Xdebug or PCOV)

```bash
# Xdebug
XDEBUG_MODE=coverage php artisan test --coverage

# HTML report (when coverage driver is loaded)
XDEBUG_MODE=coverage php artisan test --coverage-html=storage/app/coverage
```

Install Xdebug or PCOV for the same PHP CLI used by Artisan. Coverage mode is separate from debugging mode.

## Playwright

```bash
npm ci
npm run build
npx playwright install
npx playwright test
npx playwright show-report   # needs a prior test run (writes playwright-report/)
```

`playwright.config.ts` starts `php artisan serve` on `http://127.0.0.1:8000` when needed (`reuseExistingServer` outside CI). Build frontend assets first (or run `composer run dev`) so Inertia/Vite pages load. Override with `PLAYWRIGHT_BASE_URL` if the app is already on another host (e.g. Valet).

## CI

GitHub Actions workflow `.github/workflows/tests.yml` runs PHPUnit on pull requests and pushes. Playwright runs in a separate job when configured.
