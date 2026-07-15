# Rollback Procedure

## Before every significant deployment

Create:

- A database backup
- A backup of uploaded files
- A record of the current Git commit

Record the deployed commit:

```bash
git rev-parse HEAD
```

## Code rollback

In Hostinger Git deployment, redeploy the last known-good commit or branch.

If using SSH and the repository is available:

```bash
git fetch origin
git checkout LAST_KNOWN_GOOD_COMMIT
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan config:clear
php artisan optimize
```

## Database rollback

Do not run `migrate:rollback` automatically in production.

Restore the database backup when a migration has caused destructive or incompatible changes.

## File rollback

Restore the uploaded-file backup when deployment or migration work altered stored assets.

## Verification

After rollback:

```bash
php artisan about
php artisan migrate:status
tail -n 100 storage/logs/laravel.log
```

Test login, admin access, assets, purchases, licenses, and downloads.
