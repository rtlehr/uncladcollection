Epic 7 — My Library replacement-image fix

Problem
-------
The first Asset -> Image drill-down implementation filtered image files using
License.included_asset_files_snapshot. If an admin deleted an image and uploaded
a replacement, the replacement had a new asset_files.id and disappeared from
the customer's Image Studio library even though the customer still owned the
asset.

New entitlement rule
--------------------
An active license grants Image Studio access to the purchased asset. The
customer can use the asset's CURRENT active, downloadable image files.

This means:
- Replacing an image keeps the asset visible in My Library.
- The new replacement image becomes available automatically.
- Deleted/inactive/non-downloadable files are not exposed.
- Files must still belong to the licensed asset.
- The customer's license must still be active and belong to that customer.
- Export validation uses the same rule.
- Existing saved designs remain supported.

Included files
--------------
app/Http/Controllers/Account/DesignLibraryController.php
app/Http/Controllers/Account/DesignExportController.php
resources/js/pages/Account/Designs/Edit.vue
routes/account.php

The Vue and route files are included so this patch is cumulative with the
previous Asset -> Image drill-down patch.

Install
-------
1. Copy the included folders into the project root.
2. Run:
   php artisan optimize:clear
   npm run build
3. Refresh with Ctrl + F5.

No migration is required.
