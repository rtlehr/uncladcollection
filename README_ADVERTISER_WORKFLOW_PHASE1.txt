Unclad Collection - Advertiser Workflow Phase 1

What this adds
--------------
1. Advertisers become the client-level advertising workspace.
2. Advertiser list now shows:
   - Client status
   - Current workflow stage
   - Campaign counts
   - Next action
   - Workflow health
3. New /admin/advertisers/{advertiser} workspace.
4. Automatic workflow stages calculated from existing records:
   - Client Setup
   - Portal Access
   - Sales Opportunity
   - Proposal
   - Campaign Setup
   - Creative Preparation
   - Campaign Approval
   - Billing
   - Launch Readiness
   - Live / Performance
5. Per-campaign launch readiness checks, including the important staging issue:
   - Advertiser active
   - Campaign approved
   - Dates valid
   - Active placements assigned
   - All creatives approved
   - All creative media files physically exist on the public disk
   - Destination URLs present
   - Placement compatibility
   - Billing record shown as informational
6. Create Campaign from an advertiser workspace preselects that advertiser.
7. New campaigns now return to the campaign page so the workflow can continue directly into creatives/approval.

Install
-------
Copy the package contents over the matching application paths.

Then run:

php artisan optimize:clear
npm run build

If your deployment uses Wayfinder, npm run build will regenerate route types automatically.

No database migration is required for Phase 1.

Recommended test path
---------------------
1. Open Admin > Advertising > Advertisers.
2. Choose an advertiser and click Workflow.
3. Verify workflow stages reflect existing client records.
4. Create a campaign from the advertiser workspace and confirm advertiser is preselected.
5. Add/approve creatives.
6. Expand Launch Readiness Checklist.
7. Confirm a missing media file is flagged as a launch blocker.
8. Regenerate/upload media and refresh; the media check should pass.

Notes
-----
Billing existence is informational in Phase 1 and does NOT block launch. This keeps the workflow flexible until a configurable pre-launch payment policy is added.

The workflow is derived from existing data; it does not create duplicate workflow-status fields that can become out of sync.
