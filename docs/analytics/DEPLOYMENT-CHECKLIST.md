# Epic 1 Deployment and Validation Checklist

## Before deployment

- Back up the database.
- Confirm `.env`, `.env-testing`, secrets, uploads, `vendor`, and `node_modules` are excluded from deployment ZIPs.
- Remove diagnostic files such as `public/phpinfo.php` before production deployment.
- Confirm production analytics environment values.

## Deployment commands

```bash
php artisan down
php artisan optimize:clear
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan analytics:validate --strict
npm ci
npm run build
php artisan optimize
php artisan up
```

Adapt the frontend build step when the production host does not provide Node; build locally and deploy `public/build`.

## Regression validation

```bash
php artisan test --filter=EpicOneCompletionValidationTest
php artisan test --filter=Analytics
php artisan analytics:prune --days=730 --dry-run
```

## Staging smoke test

- Open every analytics report from the shared navigation.
- Confirm the current section is highlighted on index and detail pages.
- Test standard and custom periods.
- Test report-specific filters and reset behavior.
- Open at least one detail page for every report that supports details.
- Download every CSV export.
- Print one index report and one detail report.
- Confirm users without `view_reports` cannot open analytics routes.
- Confirm public campaign and search tracking endpoints still work.

## Rollback

- Put the application in maintenance mode.
- Restore the pre-deployment database backup when a migration rollback is unsafe.
- Restore the prior application release and compiled frontend assets.
- Clear caches and rerun `analytics:validate` after restoration.
