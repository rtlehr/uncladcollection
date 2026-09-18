# X Post Scheduling Timezone Fix

This patch fixes scheduled X posts appearing four hours earlier after saving.

## Cause
The HTML `datetime-local` control sends a date/time without timezone information. Laravel runs in UTC, so a local value such as `5:50 PM` was being interpreted as `5:50 PM UTC`. When the saved UTC value was sent back to the browser, Eastern Daylight Time displayed it as `1:50 PM`.

## Fix
`resources/js/pages/Admin/SocialPosts/Form.vue` now converts the administrator's selected browser-local date/time to an explicit UTC ISO-8601 timestamp before submitting it to Laravel. Laravel can continue storing and comparing scheduled times in UTC.

Example during EDT:
- Admin selects: `5:50 PM`
- Browser sends: `21:50 UTC`
- Edit screen displays: `5:50 PM`
- Scheduler publishes at: `5:50 PM` local time

## Install
Replace:

`resources/js/pages/Admin/SocialPosts/Form.vue`

Then rebuild the frontend:

```bash
npm run build
```

No database migration or `.env` change is required.
