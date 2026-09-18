# X / Twitter Scheduled Posting

This module adds **Admin → Marketing & Advertising → X Posts**.

## What it does

- Save X posts as drafts.
- Schedule posts for automatic publishing.
- Post immediately with **Post Now**.
- Attach one image or one video.
- Track draft, scheduled, publishing, published, and failed states.
- Save the returned X post ID/link and API error details.
- Retry failed posts with **Post Now**.
- Protect the feature with the `manage_social_posts` RBAC permission.

## 1. Run migrations and permissions

```bash
php artisan migrate
php artisan db:seed --class=PermissionSeeder
```

The PermissionSeeder also grants `manage_social_posts` to the existing `admin` role, matching the app's current Message Box behavior.

## 2. Configure X credentials

Create an X Developer App for the X account that will publish the posts. The app must have permission to write posts. Generate an API key/secret and a user access token/secret for that X account.

Add these values to `.env`:

```dotenv
X_API_KEY=
X_API_SECRET=
X_ACCESS_TOKEN=
X_ACCESS_TOKEN_SECRET=
```

Then clear cached configuration:

```bash
php artisan config:clear
```

Open **Admin → X Posts** and click **Test X Connection**.

## 3. Scheduler

The module registers:

```php
Schedule::command('social:x-publish')->everyMinute()->withoutOverlapping();
```

Production/staging only needs the normal Laravel scheduler cron already used by this application:

```bash
php artisan schedule:run
```

Run that command once per minute from cron when possible. If Hostinger only runs it every five minutes, scheduled posts can be up to roughly five minutes late.

You can also test due posts manually:

```bash
php artisan social:x-publish
```

## Media notes

Images use X's media upload endpoint before the post is created. Video uses X's chunked media upload workflow and waits for X processing to finish before publishing the post. X plan/account limits and supported-media rules still apply.

The implementation uses OAuth 1.0a user-context credentials and keeps all secrets server-side. No X secret is sent to Vue or stored in the social-post table.

## Long posts

The admin composer does not impose its own character limit. X applies whatever post-length entitlement and API restrictions are available to the connected X account.
