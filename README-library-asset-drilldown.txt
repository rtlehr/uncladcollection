Epic 7 — Add From My Library: Asset → Image Drill-Down

Purpose
A purchased UC asset can contain multiple image files. The Design Studio library
picker now shows purchased assets first, then lets the customer drill into the
licensed image files included with that asset and choose the exact image layer
they want to add.

Files to replace
- app/Http/Controllers/Account/DesignLibraryController.php
- app/Http/Controllers/Account/DesignExportController.php
- resources/js/pages/Account/Designs/Edit.vue
- routes/account.php

Behavior
- Add from My Library initially lists purchased/licensed assets.
- Each asset displays the count of currently available licensed image files.
- View images opens a second level containing every usable image file included
  in that license's included_asset_files_snapshot.
- Each image shows filename, file role, format, and dimensions when available.
- Selecting Add to design inserts that specific file into Fabric.
- New Fabric layers save sourceAssetFileId in addition to license and asset IDs.
- Export validation checks the specific file still belongs to the matching
  active license and purchased asset snapshot.
- Existing saved designs created before sourceAssetFileId was introduced remain
  compatible and continue to validate at the asset/license level.
- The old one-image library URL remains in place so previously saved Fabric JSON
  that references it continues to load.

Security / entitlement behavior
The picker does not expose every current file attached to an asset blindly.
It only exposes active, downloadable image files that are represented by the
customer's license snapshot (or the legacy fallback when an older license has no
snapshot IDs/UUIDs), and the file must physically exist in storage.

Install
1. Copy the app, resources, and routes folders into the project root.
2. Run:
   php artisan optimize:clear
   npm run build
3. Refresh the browser with Ctrl + F5.

No migration or dependency changes are required.
