Unclad Collection — Advertising Campaign Launch / Schedule Fix
August 8, 2026

Purpose
-------
This patch fixes the campaign workflow issue found while testing the American Association for Nude Recreation advertiser workflow.

Problems fixed
--------------
1. Campaigns converted from sponsorship proposals were created with an invalid objective value such as:
   "Sponsorship proposal SP-20260808-..."
   The campaign edit form only accepts awareness, traffic, conversion, or sponsorship, so edits (including start-date changes) silently failed validation.

2. The campaign edit page did not display Laravel validation errors, making failed saves look like the date simply did not save.

3. The guided workflow could say "Schedule or activate the ready campaign", but there was no controller route/action to actually move an approved campaign to scheduled or active.

What changed
------------
- Future sponsorship-proposal conversions now set objective = sponsorship.
- Existing legacy proposal-created campaigns are normalized to sponsorship when edited/saved.
- Campaign edit validation errors are displayed on the form.
- Added POST /admin/ad-campaigns/{campaign}/launch.
- Launch checks use the same readiness rules as the advertiser workflow.
- If the campaign is launch-ready and starts in the future, Launch sets status = scheduled.
- If the start time is blank, now, or in the past, Launch sets status = active.
- A scheduled campaign can use the same Launch action on/after its start time to become active.
- The campaign Next Workflow Step now shows Schedule Campaign or Activate Campaign at the correct time.
- Added feature tests for legacy objective repair and schedule/activation transitions.

Install
-------
Copy the patch contents over the project root, preserving folders.

Then run:

    php artisan optimize:clear
    npm run build

Recommended tests:

    php artisan test --filter=AdvertisingCampaignWorkflowTest
    php artisan test --filter=SponsorshipSalesPipelineTest

Manual test for the current AANR campaign
-----------------------------------------
1. Open the campaign Edit screen.
2. Objective should now display Sponsorship instead of appearing blank.
3. Change Starts to Aug 8, 2026 (or another time that is now/past).
4. Save Campaign. It should save and return to the campaign page.
5. The Next Workflow Step should show "Activate Campaign" if all required launch checks pass.
6. Click Activate Campaign.
7. Campaign status should become Active.
8. Return to the AANR advertiser workspace; it should show 1 active campaign and move into Live / Performance.

If the start date is in the future:
- the action will be Schedule Campaign;
- status becomes Scheduled;
- on/after the start time, return to the campaign page and click Activate Campaign.

No database migration is required.
