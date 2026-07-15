# Unclad Collection — Hostinger Cloud Deployment

This guide documents the deployment process proven to work for Unclad Collection on Hostinger Cloud Startup.

## Hosting model

Use:

- Hostinger Cloud Startup
- Standard Cloud Website
- Hostinger Git Deployment
- PHP 8.4
- MySQL
- Laravel deployed to `public_html`
- Root `.htaccess` forwarding traffic to Laravel's `public` directory

Do not use Hostinger's Node/Vite Web App deployment. Unclad Collection is a Laravel application with a Vue/Inertia frontend, not a frontend-only Vite application.

## Required Hostinger PHP configuration

Set PHP to 8.4.

Ensure these PHP functions are not disabled:

```text
proc_open
symlink
```

Leave unnecessary command-execution functions disabled unless a future feature specifically requires them.

Recommended PHP extensions:

```text
bcmath
ctype
curl
dom
fileinfo
gd
intl
mbstring
openssl
pdo
pdo_mysql
tokenizer
xml
zip
```

## Hostinger site setup

1. Create the site as a normal Cloud Website.
2. Create a staging subdomain:
   `staging.uncladcollection.com`
3. Confirm these tools are available:
   - SSH
   - Git
   - Cron Jobs
   - PHP Configuration
   - Database Manager
   - File Manager
4. Set PHP to 8.4.
5. Enable `proc_open` and `symlink`.
6. Create a staging MySQL database and user.

## Git deployment

Connect the GitHub repository:

```text
Repository: https://github.com/rtlehr/uncladcollection.git
Branch: main
Deploy path: public_html
```

Hostinger will run Composer during deployment.

## Required repository files

The repository must contain:

```text
.htaccess
public/.htaccess
public/build/manifest.json
public/build/assets/
```

The root `.htaccess` forwards all public traffic into Laravel's `public` directory.

The `public/build` directory must be included in Git because npm is not available in Hostinger's normal SSH environment.

## Local frontend release build

Before deploying frontend changes:

```powershell
cd C:\Development\uncladcollection
npm run build
git add -f public/build
git commit -m "Build production frontend assets"
git push origin main
```

`npm ci` is optional when dependencies are already installed and valid.

## Server environment file

Create `.env` directly on Hostinger. Never commit the production or staging `.env`.

Minimum staging values:

```dotenv
APP_NAME="Unclad Collection"
APP_ENV=staging
APP_KEY=
APP_DEBUG=false
APP_URL=https://staging.uncladcollection.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=HOSTINGER_DATABASE_NAME
DB_USERNAME=HOSTINGER_DATABASE_USER
DB_PASSWORD="HOSTINGER_DATABASE_PASSWORD"

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public
```

Generate the application key:

```bash
php artisan key:generate
```

## First deployment commands

```bash
cd /home/YOUR_HOSTINGER_USER/domains/staging.uncladcollection.com/public_html

php artisan config:clear
php artisan migrate --force
php artisan storage:link
chmod -R 775 storage bootstrap/cache
php artisan optimize
```

If `storage:link` reports that the link already exists, verify it instead of recreating it.

## Verify deployment

```bash
php artisan about
php artisan migrate:status
ls -la public/build/manifest.json
ls -la public/storage
tail -n 100 storage/logs/laravel.log
```

Then visit:

```text
https://staging.uncladcollection.com
```

## Repeat deployment process

For each release:

1. Run tests locally.
2. Run `npm run build`.
3. Commit `public/build`.
4. Push to GitHub.
5. Trigger Hostinger Git deployment.
6. Run the post-deployment commands.
7. Test staging.
8. Promote the approved commit to production.

## Production promotion

Production should use:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://uncladcollection.com
```

Use a separate production database, Stripe live credentials, production mail credentials, and production storage.

Never copy staging secrets into production.
