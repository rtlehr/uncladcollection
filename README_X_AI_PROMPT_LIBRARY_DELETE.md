# X AI Prompt Additions Library — Delete Action

## What changed

The Prompt Additions Library now supports safe deletion through the application's custom confirmation dialog.

- Click **Delete** on a prompt addition.
- A destructive confirmation dialog is displayed before the request is sent.
- Confirming deletes the library record so it is no longer available for future generations.
- If the deleted item was selected in the current generator form, it is removed from the current selection.
- Historical AI suggestions are not changed because each suggestion stores a snapshot of the prompt additions used when it was generated.
- The existing Active / Inactive option remains available when you only want to hide an addition temporarily.

## Installation

No database migration, seed, Composer install, or environment change is required for this enhancement.

Replace the changed Vue file and rebuild the frontend:

```bash
npm run build
```
