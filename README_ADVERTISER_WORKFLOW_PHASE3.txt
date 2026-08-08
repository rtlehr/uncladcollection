UNCLAD COLLECTION — ADVERTISER WORKFLOW PHASE 3
Guided Workflow Transitions

Purpose
-------
This phase makes the advertiser workflow actively guide the administrator from one advertising step to the next while preserving advertiser/client context and avoiding duplicate data entry.

What changed
------------
1. Proposal workflow is now explicitly guided:
   Draft -> Mark Sent -> Record Acceptance -> Convert to Campaign.
2. An accepted proposal is no longer treated as a completed proposal stage until it is converted.
3. Proposal pages display the active advertiser context and a prominent Next Workflow Step card.
4. Proposal conversion confirms that advertiser, proposal, campaign, and billing context are carried forward.
5. Campaign pages now display advertiser context and derive the next action from campaign data.
6. Campaign guidance checks:
   - assigned placements
   - creative presence
   - creative approval
   - physical creative media file existence
   - destination URLs
   - placement compatibility
   - campaign approval status
7. Campaign guidance then directs the administrator toward approval, launch readiness, billing, or analytics.
8. Existing modules, statuses, routes, and records remain the source of truth. No duplicate workflow records were added.

Files changed/added
-------------------
app/Advertising/AdvertiserWorkflowService.php
app/Http/Controllers/Admin/SponsorshipProposalController.php
app/Http/Controllers/Admin/AdvertisingCampaignController.php
resources/js/components/Advertising/WorkflowNextStepCard.vue
resources/js/pages/Admin/Sponsorship/Proposals/Show.vue
resources/js/pages/Admin/Advertising/Campaigns/Show.vue

Database
--------
No migration is required.

Install
-------
Copy the package contents over the project root.

Then run:
    php artisan optimize:clear
    npm run build

Recommended test flow
---------------------
1. Open an advertiser workspace.
2. Create/open a sales opportunity.
3. Create a proposal from that advertiser/opportunity.
4. After saving, verify the proposal displays "Send the proposal" and Mark Sent.
5. Click Mark Sent; verify the next action becomes Record the advertiser decision.
6. Click Accept Manually; verify the next action becomes Convert the accepted proposal to a campaign.
7. Click Convert to Campaign.
8. Verify the campaign opens automatically with the same advertiser context.
9. Verify the campaign next step guides you to placements or creatives as appropriate.
10. Add creatives and progress approval; verify the next-step card advances automatically.
11. Return to Client Workspace and confirm the Proposal stage does not show complete until conversion.

Validation performed
--------------------
PHP syntax validation passed for all changed PHP files.
The packaging environment does not include node_modules, so Vite could not be run here. Run npm run build in the normal development project after copying the files.
