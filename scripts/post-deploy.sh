#!/bin/bash

set -e

PROJECT="$(pwd)"
DOMAIN_ROOT="$(dirname "$PROJECT")"
SHARED="$DOMAIN_ROOT/shared/storage/app/public"

mkdir -p "$SHARED"

if [ -e "$PROJECT/storage/app/public" ] || [ -L "$PROJECT/storage/app/public" ]; then
    rm -rf "$PROJECT/storage/app/public"
fi

ln -s "$SHARED" "$PROJECT/storage/app/public"

if [ -e "$PROJECT/public/storage" ] || [ -L "$PROJECT/public/storage" ]; then
    rm -rf "$PROJECT/public/storage"
fi

cd "$PROJECT"

php artisan storage:link
php artisan optimize:clear

if [ "$(readlink -f "$PROJECT/storage/app/public")" != "$SHARED" ]; then
    echo "ERROR: storage/app/public is not linked to persistent storage."
    exit 1
fi

if [ "$(readlink -f "$PROJECT/public/storage")" != "$SHARED" ]; then
    echo "ERROR: public/storage is not linked to persistent storage."
    exit 1
fi

echo "Persistent storage linked successfully."
echo "Storage path: $SHARED"