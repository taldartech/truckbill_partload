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
npx playwright install chromium
npx playwright test
npx playwright show-report
```

Set `PLAYWRIGHT_BASE_URL` (default `http://127.0.0.1:8000`). Use dedicated test users only.

## CI

GitHub Actions workflow `.github/workflows/tests.yml` runs PHPUnit on pull requests and pushes. Playwright runs in a separate job when configured.
