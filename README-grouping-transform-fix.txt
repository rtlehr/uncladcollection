Epic 7 — Group / Ungroup Transform Fix

Problem
-------
Ungrouped objects could jump, resize, or appear off-canvas.

Cause
-----
After Group.removeAll() correctly restored each child's canvas transform, the
editor immediately wrapped those children in a new ActiveSelection. That extra
selection parent could recalculate relative transforms and visibly move/scale
the released objects.

Fix
---
- Group directly from the current ActiveSelection using ActiveSelection.removeAll().
- Ungroup using Group.removeAll().
- Restore each released object to the group's stack position.
- Do NOT immediately create another ActiveSelection after ungrouping.
- Preserve each child's position, size, scale, angle, flip, and layer metadata.

Replace
-------
resources/js/pages/Account/Designs/Edit.vue

Install
-------
php artisan optimize:clear
npm run build

Then refresh with Ctrl + F5.

No migration or dependency changes are required.
