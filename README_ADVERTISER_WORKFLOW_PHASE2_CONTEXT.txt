Unclad Collection — Advertiser Workflow Phase 2: Persistent Client Context

Purpose
-------
Keep the advertiser/client identity and related workflow records connected while moving through sales opportunity, proposal, campaign, creative, billing, approval, and launch steps. This reduces duplicate entry and accidental work under the wrong client.

What changed
------------
1. Added AdvertisingWorkflowContextService.
   - Resolves advertiser context from advertiser_id, lead_id, or campaign_id.
   - Campaign and lead context automatically resolve back to their advertiser.

2. Advertiser workflow links are now client-aware.
   - Create Opportunity opens with the current advertiser prefilled.
   - Create Proposal carries advertiser + active lead.
   - Billing carries advertiser + campaign.
   - Existing opportunity/proposal/billing links stay scoped to the current advertiser.

3. Sales opportunity form carries advertiser details forward.
   - Company, contact name, email, phone, and advertiser are prefilled from the Advertiser record.
   - No retyping client contact data when starting from the Advertiser Workspace.

4. Proposal form carries advertiser + sales opportunity forward.
   - Advertiser is preselected.
   - Active sales opportunity is preselected when present.
   - Lead Show -> Create Proposal now uses lead_id and advertiser_id correctly.

5. Campaign form retains advertiser context.
   - Existing advertiser preselection remains.
   - A visible workflow context banner confirms which client is being worked on.

6. Invoice form carries advertiser + campaign forward.
   - Advertiser is preselected.
   - Campaign is preselected.
   - Campaign dropdown is filtered to the chosen advertiser.
   - When launched from a campaign, the first invoice line is seeded from campaign name, pricing model, and contract value.

7. Added WorkflowContextBanner.vue.
   - Clearly shows "Working with <Advertiser>" on workflow forms.
   - Shows contact/lead/campaign context when available.
   - Provides a direct link back to the Advertiser Workspace.

No migration is required.

Install
-------
Copy the package over the project root, preserving directories.

Then run:

php artisan optimize:clear
npm run build

Recommended test
----------------
1. Open Admin -> Advertising -> Advertisers.
2. Open an advertiser workspace.
3. Use Next Action to create a Sales Opportunity.
   Confirm company/contact/advertiser are already populated.
4. Save the lead, then click Create Proposal.
   Confirm advertiser and opportunity are already selected.
5. Progress/convert the proposal.
   Confirm the campaign remains linked to the same advertiser.
6. From the campaign, click Create Invoice.
   Confirm advertiser, campaign, pricing model, description, and contract value are carried forward.
7. Return to the Advertiser Workspace and confirm workflow stage/status updates automatically.

Validation performed
--------------------
PHP syntax checks passed for all modified PHP files.
The source package did not include installed node_modules, so npm build could not be executed in the packaging sandbox (vite executable unavailable). Run npm run build in the normal development environment after copying the files.
