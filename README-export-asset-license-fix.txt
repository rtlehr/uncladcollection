Epic 7 — Export 403 after asset image replacement

Cause
-----
Saved UC Library layers store sourceAssetFileId. Replacing an image in an asset
creates a new asset_files row. The export controller was still requiring the
old saved file ID to exist and be active, producing HTTP 403 even though the
customer still had an active license for the purchased asset.

Fix
---
Export authorization for UC Library layers now checks:
- the license belongs to the current user
- the license is active
- the license asset matches sourceAssetId

It no longer invalidates an existing saved design because an administrator
replaced the underlying asset file.

The Add From My Library picker still only exposes the asset's CURRENT active,
downloadable image files for new additions.

Replace
-------
app/Http/Controllers/Account/DesignExportController.php

Install
-------
php artisan optimize:clear

No migration or frontend build is required.
