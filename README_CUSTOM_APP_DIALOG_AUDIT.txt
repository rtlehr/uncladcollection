CUSTOM APP DIALOG AUDIT
=======================

Baseline reviewed: alertCheck.zip

What was found
--------------
The Vue/TypeScript front end contained 39 explicit native browser dialog calls
across 19 files using alert(), confirm(), prompt(), window.confirm(), or
window.prompt(). These produce the browser/default HTML-style dialog instead of
the application's reusable dialog UI.

What changed
------------
1. Added a global app-dialog service:
   - resources/js/lib/appDialog.ts
   - resources/js/lib/appDialogState.ts
   - resources/js/components/Shared/AppDialogHost.vue

2. Reused the existing ConfirmActionDialog styling for:
   - informational alerts
   - confirmation dialogs
   - destructive confirmations
   - text/number/URL prompts

3. Extended ConfirmActionDialog with a showCancel option so informational
   notices can display a single OK button while keeping the same application
   design.

4. Initialized the global dialog host in resources/js/app.ts.

5. Replaced all 39 explicit native browser dialog calls found in the front end.

Areas updated include:
- Advertising campaigns, creatives, and billing
- AI provider and prompt management
- Public page and Page Help administration
- Discovery collection placements
- Marketing campaigns
- Email template restore
- User impersonation
- Image editor/design studio
- Wish lists
- Rich-text editor link and upload messages

Important browser exception
---------------------------
The Design Studio still uses the browser's native BEFOREUNLOAD warning when a
user attempts to close/reload the browser tab with unsaved work. Modern browsers
do not permit a custom application modal to replace that operating/browser-level
navigation warning. There are no remaining explicit alert(), confirm(), or
prompt() calls in resources/js.

Validation performed
--------------------
- Searched resources/js after the patch: no explicit native alert(), confirm(),
  prompt(), window.alert(), window.confirm(), or window.prompt() calls remain.
- TypeScript syntax transpilation passed for all modified script blocks.
- Full Vite build could not be run because the uploaded source package does not
  contain node_modules.

Recommended local validation
----------------------------
php artisan optimize:clear
npm run build

Then spot-check:
- Delete a creative or campaign-related item
- Reject an advertising creative/campaign (custom prompt)
- Record an advertising payment/refund (custom numeric prompt)
- Test an AI provider connection (custom informational notice)
- Add/edit a rich-text link (custom URL prompt)
- Delete a design or wish list
