# Managed Message Boxes

This update adds an admin-managed message system with three presentation styles:

1. **Modal window** — centered overlay that can contain an image, rich text, buttons, and an optional form.
2. **Bottom banner** — full browser width, fixed at 200px tall, rising from the bottom.
3. **Top banner** — full browser width, fixed at 200px tall, lowering from the top.

## Admin

Open `/admin/message-boxes` from the Marketing Dashboard.

Each message supports:
- active/inactive status
- start/end scheduling
- automatic page-load display or user-action trigger
- one or more page patterns (`/`, `/blog/*`, `/assets/*`, `*`)
- all users, guests only, or authenticated users
- show once per signed-in user or guest browser/device
- dismissible/non-dismissible display
- priority ordering
- optional uploaded image
- rich-text content
- up to 3 action buttons
- optional simple form fields and stored submissions

## User-action triggers

For a message configured as **User action / trigger key**, give it a trigger key such as:

`welcome.signup-info`

Then either add this attribute to a clickable element:

```html
<button data-message-box-trigger="welcome.signup-info">Learn more</button>
```

or trigger it in Vue/TypeScript:

```ts
import { triggerMessageBox } from '@/lib/messageBoxes';
triggerMessageBox('welcome.signup-info');
```

## Seeder examples

`MessageBoxSeeder` creates one example of each style:
- Welcome Modal Example
- Bottom Banner Example
- Top Banner Action Example

The top-banner example uses the trigger key `example.interest-form` and includes a sample form.

## After installing the files

Run:

```bash
php artisan migrate
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=MessageBoxSeeder
npm install
npm run build
```

If your administrator role does not automatically receive newly seeded permissions, assign `manage_message_boxes` to the appropriate admin role in Permissions/Roles.

## Validation note

PHP syntax validation was completed on the new PHP classes. The uploaded source archive did not contain Composer `vendor/`, so Laravel feature tests could not boot in the sandbox. The frontend dependency installation also exceeded the sandbox execution window before Vite became available. Run the normal local test/build commands after extracting this package.
