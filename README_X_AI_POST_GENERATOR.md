# AI X Post Generator

This update expands the AI-assisted workflow under **Admin > Marketing & Advertising > X Posts > Generate with AI**.

## Workflow

1. Enter any subject/topic.
2. Optionally assign a **Campaign / group** such as `Body Acceptance Week`, `Blog Promotion`, or `Marketplace Launch`.
3. Choose 1-20 posts and generate.
4. Review each suggestion:
   - **Accept** creates a normal X Post draft.
   - **Reject** preserves the rejected suggestion in review history.
   - **Regenerate** is available on rejected suggestions and creates a new pending replacement linked to the rejected item. It does not overwrite the old suggestion.
5. On an accepted suggestion, choose 1-10 and select **More like this**. The AI creates new pending suggestions inspired by the accepted post without duplicating its wording.
6. Accepted X Post drafts can still be **Post Now** or **Edit / Schedule**, where an image/video can be uploaded.

## Campaign organization

Campaign is optional. Suggestions with a campaign retain that campaign when they are regenerated or when more ideas are generated from them. The review queue can be filtered by campaign and status.

## AI Provider Assignment

The existing AI feature assignment remains:

`Social / X post generation`

Use **Admin > AI Providers** to select the desired provider/model.

## Database

Run:

```bash
php artisan migrate
```

The new migration adds `campaign` and `source_suggestion_id` to `social_post_ai_suggestions` so campaign grouping and AI lineage are preserved.

## Frontend

Rebuild after deployment:

```bash
npm run build
```

No new Composer or npm package is required.

## Campaign Subject / Topic memory (2026-09-20)

Campaigns now remember their latest Subject / Topic. Selecting an existing campaign restores the saved subject, and clicking **Generate Post Ideas** saves any updated subject back to that campaign. Historical suggestions retain the subject originally used to generate them. See `README_X_AI_CAMPAIGN_SUBJECT_MEMORY.md`.

## Prompt Additions Library (Sept. 20, 2026)

The generator now supports an optional editable Prompt Additions Library. Select only the context or instructions needed for the current batch. Library content is stored in the database, not permanently embedded in the social generator prompt. Each suggestion saves a snapshot of the additions used so historical generations remain reproducible even after library entries are edited.

Install/update with:

```bash
php artisan migrate
php artisan db:seed --class=SocialPostPromptAdditionSeeder
npm run build
```

See `README_X_AI_PROMPT_ADDITIONS_LIBRARY.md` for details.
