Advertising Workflow Phase 5 — Automatic Lifecycle & Creative Audit
====================================================================

Built from the supplied adReview.zip baseline.

What changed
------------
1. Automatic campaign lifecycle
   - New Artisan command: php artisan advertising:sync-campaign-statuses
   - Scheduled campaigns automatically become active when their start time arrives.
   - Automatic activation re-checks launch readiness at activation time.
   - If launch readiness is blocked (for example missing media or inactive advertiser), the campaign stays scheduled and will be retried on the next scheduler run.
   - Active, paused, or scheduled campaigns automatically become completed when their end time arrives.
   - Expiration is processed before activation, so an already-expired scheduled campaign is completed rather than briefly activated.

2. Laravel scheduler
   - routes/console.php now runs advertising:sync-campaign-statuses every five minutes with withoutOverlapping().
   - Existing Hostinger scheduler cron (php artisan schedule:run every minute) is sufficient; no additional server cron is needed if that existing scheduler cron is already configured.

3. Workflow guidance
   - Scheduled campaign guidance now explains that activation happens automatically at the scheduled start time when readiness still passes.

4. Workflow history
   - Automatic campaign transitions are recorded in admin_activities with user_id NULL and display as System.
   - Creative create/update/submit/approve/reject/return-to-draft/delete actions are now audited.
   - Campaign Workflow History now includes both campaign lifecycle and related creative lifecycle entries.

5. Tests
   - Added AdvertisingCampaignLifecycleAutomationTest covering:
     * scheduled -> active automatic transition
     * launch-readiness blocked scheduled campaigns remain scheduled
     * expired active/paused/scheduled -> completed
   - Existing AdvertisingCreativeManagementTest now checks creative status audit logging.

No database migration is required.

Install
-------
Copy the package contents over the project root, then run:

php artisan optimize:clear
php artisan advertising:sync-campaign-statuses
php artisan schedule:list
php artisan test --filter=AdvertisingCampaignLifecycleAutomationTest
php artisan test --filter=AdvertisingCreativeManagementTest
php artisan test --filter=AdvertisingCampaignWorkflowTest
npm run build

Manual verification
-------------------
1. Create/prepare a launch-ready campaign with a future start time.
2. Schedule it.
3. Temporarily set its start time to a minute in the past.
4. Run: php artisan advertising:sync-campaign-statuses
5. Confirm status becomes Active and Workflow History shows a System activation entry.
6. Set ends_at to a minute in the past and run the command again.
7. Confirm status becomes Completed and Workflow History shows a System completion entry.

Production/Staging
------------------
The repository already documents a normal Laravel scheduler cron running every minute:

php artisan schedule:run

As long as that cron is active, the new campaign lifecycle task will run every five minutes automatically.
