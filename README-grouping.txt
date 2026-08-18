Epic 7 — Group / Ungroup elements

Files changed
- resources/js/pages/Account/Designs/Edit.vue
- app/Services/DesignStudio/DesignProjectAssetService.php

What this adds
- Hold Shift and select two or more canvas elements.
- Group selected elements into a single Fabric group layer.
- Grouped elements move, resize, rotate, flip, lock, duplicate, and reorder together.
- Ungroup restores each child as an individually editable layer.
- Group/child metadata persists through save/reopen and undo/redo.
- Licensed UC Library images nested inside groups remain recursively validated.

Install
1. Copy the included app and resources folders into the project root.
2. Run:
   php artisan optimize:clear
   npm run build
3. Refresh with Ctrl + F5.

No migration or dependency change is required.
