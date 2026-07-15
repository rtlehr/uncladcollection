# Hostinger Troubleshooting

## 403 Forbidden after deployment

Likely cause: Hostinger is serving `public_html`, but Laravel's entry point is in `public_html/public`.

Verify:

```bash
ls -la .htaccess
ls -la public/.htaccess
```

The root `.htaccess` must forward requests into `/public`.

## 500 — Vite manifest not found

Verify:

```bash
ls -la public/build/manifest.json
```

Fix locally:

```powershell
npm run build
git add -f public/build
git commit -m "Build production frontend assets"
git push origin main
```

Redeploy through Hostinger.

## Composer lock-file compatibility error

Verify PHP:

```bash
php -v
composer --version
```

Run Composer manually without quiet mode:

```bash
composer install --prefer-dist --no-interaction -vvv
```

Do not run `composer update` on the server unless dependency changes are intentional and reviewed locally.

## Symfony Process / proc_open error

Error:

```text
The Process class relies on proc_open
```

Remove `proc_open` from Hostinger's disabled PHP functions.

Verify:

```bash
php -r "var_dump(function_exists('proc_open'));"
```

Expected:

```text
bool(true)
```

## Storage link fails

Enable `symlink` in Hostinger PHP configuration.

Verify:

```bash
php -r "var_dump(function_exists('symlink'));"
```

Then run:

```bash
php artisan storage:link
```

## Database access denied for root

The server is using local development credentials.

Edit `.env` and use the Hostinger database name, username, password, and host.

Then run:

```bash
php artisan config:clear
php artisan migrate:status
```

## `npm` command not found over SSH

This is expected on the normal Hostinger Cloud SSH environment.

Build frontend assets locally and commit `public/build`.

## Laravel log inspection

```bash
tail -n 100 storage/logs/laravel.log
```

## Required deployment diagnostics

```bash
php artisan about
php artisan migrate:status
ls -la .env
ls -la vendor/autoload.php
ls -la public/build/manifest.json
ls -la public/storage
ls -ld storage storage/logs bootstrap/cache
tail -n 100 storage/logs/laravel.log
```
