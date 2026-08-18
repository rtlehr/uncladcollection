Epic 7 — Add lines to the Image Studio

Replace:
- resources/js/pages/Account/Designs/Edit.vue

What this adds:
- Add line tool in the left Tools panel
- New line layers on the canvas
- Line color control in Properties
- Line width control in Properties
- Saved/reopenable line layers through Fabric serialization

After copying the file, run:
php artisan optimize:clear
npm run build

Then refresh with Ctrl + F5.
