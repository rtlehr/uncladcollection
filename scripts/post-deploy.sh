#!/bin/bash

set -e

PROJECT="$(pwd)"
DOMAIN_ROOT="$(dirname "$PROJECT")"
SHARED="$DOMAIN_ROOT/shared/storage/app"

echo "Configuring persistent Laravel storage..."
echo "Project: $PROJECT"
echo "Shared storage: $SHARED"

mkdir -p "$SHARED/public"
mkdir -p "$SHARED/private"

# Remove the deployment-created storage/app directory or old symlink.
if [ -e "$PROJECT/storage/app" ] || [ -L "$PROJECT/storage/app" ]; then
    rm -rf "$PROJECT/storage/app"
fi

# Link the entire Laravel storage/app tree to persistent storage.
ln -s "$SHARED" "$PROJECT/storage/app"

# Recreate Laravel's browser-facing public storage link.
if [ -e "$PROJECT/public/storage" ] || [ -L "$PROJECT/public/storage" ]; then
    rm -rf "$PROJECT/public/storage"
fi

cd "$PROJECT"

php artisan storage:link
php artisan optimize:clear

# Verify private/persistent storage.
if [ "$(readlink -f "$PROJECT/storage/app")" != "$SHARED" ]; then
    echo "ERROR: storage/app is not linked to persistent storage."
    exit 1
fi

if [ "$(readlink -f "$PROJECT/storage/app/public")" != "$SHARED/public" ]; then
    echo "ERROR: storage/app/public is not using persistent storage."
    exit 1
fi

if [ "$(readlink -f "$PROJECT/public/storage")" != "$SHARED/public" ]; then
    echo "ERROR: public/storage is not linked to persistent public storage."
    exit 1
fi

echo "Persistent storage configured successfully."
echo "Private media: $SHARED/private"
echo "Public media:  $SHARED/public"