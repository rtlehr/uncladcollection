# X AI Campaign Subject / Topic Memory

## What changed

AI X campaigns now remember their latest **Subject / Topic**.

When an administrator enters a campaign name and clicks **Generate Post Ideas**:

- A new campaign profile is created if the campaign has not been used before.
- The current Subject / Topic is saved with that campaign.
- If the campaign already exists, its saved Subject / Topic is updated to the current value.
- Selecting an existing campaign in the generator restores its latest saved Subject / Topic into the form.
- Historical AI suggestions are not rewritten. They keep the exact subject that was used when those suggestions were generated.

Existing campaign names created before this feature remain available. Their most recent suggestion subject is shown as a fallback until the campaign is generated again, at which point a campaign profile is saved.

## Database

New table:

- `social_post_ai_campaigns`

It stores:

- campaign name
- current saved Subject / Topic
- creator / last updater
- timestamps

## Install / update

After copying the changed files, run:

```bash
php artisan migrate
npm run build
```

No `.env` changes are required.

## Changed files

- `app/Http/Controllers/Admin/SocialPostAiController.php`
- `app/Models/SocialPostAiCampaign.php`
- `database/migrations/2026_09_20_000001_create_social_post_ai_campaigns_table.php`
- `resources/js/pages/Admin/SocialPosts/AiGenerator.vue`
