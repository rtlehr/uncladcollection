# Hostinger Deployment Addendum — Universal Image Editor v2

The existing Hostinger deployment workflow remains valid.

## Required release steps

Universal Image Editor frontend changes require a fresh Vite build:

```powershell
npm run build
git add -f public/build
```

Editor metadata continues to use the existing `media_edit_data` JSON column, so
Universal Image Editor v2 does not require a new migration.

After deployment:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Clear Hostinger's site cache after changes to public image generation or
frontend bundles.

## Persistent storage

Marketing Campaign originals and generated crops are stored on Laravel's
`public` disk. Ensure this remains valid:

```bash
php artisan storage:link
```

Generated crops use unique filenames. Do not change this behavior to a fixed
filename because browsers and CDNs may serve stale image bytes.

## Verification

Confirm:

- `public/build/manifest.json` has the new deployment timestamp.
- `/public/storage` points to `storage/app/public`.
- Edited campaign images receive unique UUID-based filenames.
- Re-editing the same campaign twice preserves the newest crop.
