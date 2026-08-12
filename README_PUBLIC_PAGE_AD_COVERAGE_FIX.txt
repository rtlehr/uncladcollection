PUBLIC PAGE AD DELIVERY / CAMPAIGN COVERAGE FIX

Review result
-------------
The public page template already contains both public advertising placements:

- public-page-after-content
- public-page-sidebar

The placement seeder also defines both placements with the expected dimensions:

- Public Page After Content: 760 x 240
- Public Page Sidebar: 300 x 250

The public delivery service is capable of serving both placements.

Workflow gap found
------------------
A campaign could have a placement selected while none of its approved creatives were assigned to that specific placement. The campaign-level placement checkbox therefore made it look like the ad should appear there, but public delivery correctly returned no ad because delivery requires BOTH:

1. the campaign is assigned to the placement, and
2. an approved creative is assigned to the same placement.

Changes in this patch
---------------------
1. Launch readiness now requires every campaign placement to have at least one approved creative assigned to it.
2. The Campaign detail page now shows, under every assigned placement:
   - Approved creative assigned
   - or No approved creative assigned — this placement will not display an ad
3. Added a delivery test covering both public page placement codes.
4. Added a workflow test proving a campaign cannot launch when one of its assigned placements has no approved creative coverage.

No database migration is required.

For an already-active campaign that is missing on a public page
---------------------------------------------------------------
Open:

Admin > Advertising > Campaign > Manage Creatives

Edit/return the creative to draft if necessary, and make sure the appropriate public page placement is checked on the creative itself:

- Public Page After Content, or
- Public Page Sidebar

The creative must match the placement dimensions and be approved before it can be delivered.

After installing
----------------
Run:

php artisan optimize:clear
npm run build
php artisan test --filter=PublicAdDeliveryTest
php artisan test --filter=AdvertisingCampaignWorkflowTest
