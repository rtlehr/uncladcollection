# X AI Prompt Additions Library

## What this adds

The AI X Post Generator now has an editable Prompt Additions Library. Prompt additions are optional reusable context/instructions that can be selected for an individual generation request. Nothing in the library is automatically injected into every AI request.

Examples include:

- About Unclad Collection
- Positive Naturism Tone
- Do Not Invent Features
- Include Unclad Collection URL
- Conversation and Engagement

All of these are database records and can be edited, disabled, added to, or deleted from the AI X Post Generator screen.

## Generation behavior

The final AI request is assembled from:

1. The generator's structural/output rules.
2. The current Subject / Topic.
3. Only the Prompt Additions explicitly selected by the editor.

This means a general community campaign can run without promotional context, while an Unclad Collection promotional campaign can opt into the brand context and URL additions.

## Historical snapshots

Each generated suggestion stores a JSON snapshot of the prompt additions that were actually used. Editing or deleting a library item later does not change old suggestions.

Regenerate and More like this use the source suggestion's saved snapshot. This keeps a derived suggestion aligned with the instructions that produced its source.

## Library management

On Admin -> Marketing & Advertising -> X Posts -> Generate with AI:

- Click **Manage Library**.
- Add a name, optional category, and reusable prompt text.
- Active entries appear as selectable checkboxes in the generator.
- Inactive entries remain in the library but cannot be selected for new batches.
- Edit an entry at any time; future generations use the updated text.
- Delete an entry if no longer needed; historical suggestions retain their saved copy.

## Installation

Run:

```bash
php artisan migrate
php artisan db:seed --class=SocialPostPromptAdditionSeeder
npm run build
```

The seeder adds a starter set of editable additions. They are not hard-coded into the generator and are not automatically selected.

No `.env` changes or new Composer/npm packages are required.
