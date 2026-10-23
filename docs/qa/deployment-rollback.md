# Deployment and rollback

## Pre-deploy checklist

1. CI green: `.github/workflows/tests.yml` (PHPUnit + migration smoke).
2. `composer install --no-dev` / `npm ci && npm run build` on the release artifact.
3. Review pending migrations: `php artisan migrate --pretend`.
4. Confirm queue workers and schedule runner (`subscription:apply-upcoming`, `app:dbbackup`).
5. Confirm `ONBOARDING_SIGNING_SECRET` and payment sandbox/live keys for the target environment.

## Deploy steps

1. Put application in maintenance mode if required.
2. Deploy code; run `php artisan migrate --force`.
3. `php artisan config:cache`, `route:cache`, `view:cache` as applicable.
4. Restart queue workers / scheduler.
5. Smoke: login, dashboard, one write path, accounting health.

## Rollback

1. Redeploy previous release artifact.
2. Roll back only migrations that are explicitly reversible and approved; irreversible data migrations (e.g. dropped loading report tables) use backup JSON / DB restore instead.
3. Clear caches; restart workers.
4. Re-run smoke checklist; open incident note with migration versions.

## CI migration smoke

The `php-tests` job creates a temporary SQLite file, runs `migrate --force`, then `config:cache` / `route:cache` / `schedule:list`.
