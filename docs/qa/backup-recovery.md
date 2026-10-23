# Backup and recovery

## Automated backup

Command: `php artisan app:dbbackup`

- Runs `mysqldump` against the configured MySQL connection.
- Compresses to `storage/app/backup/{timestamp}.sql.gz`.
- Uploads to the disk configured in `filesystems.backup.disk` / `filesystems.backup.path`.
- Prunes local backups older than 24 hours.
- Scheduled every 3 hours (`routes/console.php`).

## Automated tests

See `tests/Feature/Backup/DbBackupCommandTest.php` (Process/Storage fakes; never hits production).

## Manual restore checklist

1. Confirm target is a **non-production** restore environment unless performing approved DR.
2. Download the `.sql.gz` object from the backup disk.
3. `gunzip` the file and review the header (database name, timestamp).
4. Restore into an isolated database: `mysql -u… -p… target_db < backup.sql`.
5. Point a staging `.env` at the restored DB; run `php artisan migrate --force` only if schema lag is expected and approved.
6. Smoke-check login, one bilty, one accounting report, and subscription status.
7. Record restore time, backup filename, and verifier in the incident log.

## Integrity checks after restore

- `php artisan accounting:audit --company={id}`
- Spot-check document counts vs pre-incident metrics.
- Verify no production credentials were written into restored app logs.
