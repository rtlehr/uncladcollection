#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-$(pwd)}"

cd "$APP_DIR"

echo "Unclad Collection post-deployment"
echo "Application directory: $APP_DIR"

if [ ! -f artisan ]; then
    echo "ERROR: artisan was not found in $APP_DIR"
    exit 1
fi

if [ ! -f .env ]; then
    echo "ERROR: .env is missing. Create it before running this script."
    exit 1
fi

if [ ! -f public/build/manifest.json ]; then
    echo "ERROR: public/build/manifest.json is missing."
    echo "Build locally with npm run build, commit public/build, and redeploy."
    exit 1
fi

php artisan config:clear
php artisan migrate --force

if [ ! -e public/storage ]; then
    php artisan storage:link
else
    echo "public/storage already exists; leaving it unchanged."
fi

chmod -R 775 storage bootstrap/cache

php artisan optimize

# Safe even when no persistent queue worker exists.
php artisan queue:restart || true

echo "Deployment checks:"
php artisan about
php artisan migrate:status
ls -la public/build/manifest.json

echo "Post-deployment completed."
