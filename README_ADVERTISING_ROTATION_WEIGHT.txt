Unclad Collection - Advertising Rotation Weight Patch

Purpose
-------
Expose the existing advertising_campaign_placement.priority value in the campaign UI as "Rotation weight".

Changes
-------
1. Campaign Create/Edit
   - Each selected placement now exposes Rotation weight (1-100).
   - Default is 50.
   - Helper text explains that equal weights rotate approximately evenly.

2. Campaign Show
   - Assigned placements now display their current Rotation weight.
   - Includes an "Edit weights" shortcut.

3. Backend
   - Campaign store/update validates placement_priorities.
   - Saves each value to advertising_campaign_placement.priority.
   - Preserves existing allocated_budget_cents when editing placement weights.
   - Existing callers that do not send a weight retain the current weight, or default to 50 for a new assignment.

4. Test
   - Adds coverage proving rotation weight can be changed without losing the placement's allocated budget.

Install
-------
Copy the package over the project root, then run:

php artisan optimize:clear
npm run build
php artisan test --filter=AdvertisingCampaignWorkflowTest

No migration is required. The priority pivot column already exists.
