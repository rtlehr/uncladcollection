Unclad Collection - Advertising Workflow Phase 4: Operations & Lifecycle
=======================================================================

Built against the ad4.zip code supplied on August 8, 2026.

WHAT THIS PATCH ADDS
--------------------
1. Campaign Progress Timeline
   - Setup
   - Creative
   - Approval
   - Billing (non-blocking, matching current launch policy)
   - Launch
   - Live
   - States: complete, current, needs attention, pending

2. Live Campaign Lifecycle Controls
   - Pause Delivery
   - Resume Delivery
   - Mark Complete
   - Paused/completed campaigns are excluded from public delivery because
     PublicAdDeliveryService already delivers only campaigns whose status is active.
   - Resume validates start/end dates and current launch-readiness conditions.

3. Stronger Next-Action Guidance
   - Paused campaigns tell the admin to resume or complete.
   - Completed campaigns send the admin toward final performance/reporting.
   - Advertiser workflow now recognizes paused campaigns instead of treating them
     as ordinary launch-readiness failures.

4. Rotation Status
   - Shows intended rotation weight versus actual recorded impression share.
   - Displayed separately for every placement assigned to the campaign.
   - Uses the last 30 days of existing advertising_impression analytics events.
   - Campaign selection remains campaign-first / fair weighted rotation from the
     previous patch; this is a reporting view only.

5. Workflow History
   - Reuses the existing admin_activities table and AdminActivityService.
   - Records campaign status transitions for submit, approve/reject, schedule/
     activate, pause, resume, and complete.
   - No new migration is needed.
   - Historical transitions that occurred before this patch cannot be reconstructed;
     the Campaign screen clearly states that history begins when this feature is installed.

FILES IN THIS PATCH
-------------------
app/Advertising/AdvertisingRotationStatusService.php              NEW
app/Advertising/AdvertiserWorkflowService.php                     UPDATED
app/Http/Controllers/Admin/AdvertisingCampaignController.php      UPDATED
resources/js/components/Advertising/CampaignProgressTimeline.vue  NEW
resources/js/pages/Admin/Advertising/Campaigns/Show.vue            UPDATED
routes/admin.php                                                   UPDATED
tests/Feature/Advertising/AdvertisingCampaignWorkflowTest.php     UPDATED

INSTALL
-------
Copy/extract the patch over the project root, preserving directories.

Then run:

    php artisan optimize:clear
    npm run build
    php artisan test --filter=AdvertisingCampaignWorkflowTest
    php artisan test --filter=PublicAdDeliveryTest

NO DATABASE MIGRATION IS REQUIRED.

MANUAL TEST
-----------
1. Open an active advertising campaign.
2. Confirm Campaign Progress, Rotation Status, and Workflow History are visible.
3. Click Pause Delivery and confirm status becomes Paused.
4. Confirm the campaign no longer appears in the public placement rotation.
5. Click Resume Delivery and confirm status returns to Active.
6. Confirm a workflow-history entry exists for pause and resume.
7. Mark the campaign Complete and confirm it no longer delivers.
8. Visit its advertiser workspace and confirm the workflow reflects the completed state.

VALIDATION PERFORMED HERE
-------------------------
PHP syntax checks passed for all changed PHP files.
The uploaded source package does not include vendor/ or node_modules/, so PHPUnit
and Vite could not be executed in the assistant environment. Run the commands above
in your local project after installing the patch.
