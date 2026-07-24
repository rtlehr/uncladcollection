# UC-HELP-002 — Global Header and Page Integration

This package connects the UC-HELP-001 database foundation to the application's shared headers.

## Included

- Automatic route-name to page-key resolution
- Safe shared Inertia `page_help` payload
- Reusable accessible right-side help panel
- Public header integration for desktop and mobile
- Authenticated sidebar-header integration
- Header-layout integration
- Empty-state management shortcut for Page Help administrators
- Expanded initial page-key registry
- Focused integration tests

## Install

1. Extract over the project root.
2. Run:

```powershell
composer dump-autoload
php artisan optimize:clear
npm run build
php artisan test tests/Feature/PageHelp/PageHelpIntegrationTest.php
```

No migration or seeder is required.

## Behavior

The help button appears only when:

- at least one published help entry is visible to the current visitor, or
- the current user has `manage_page_help`, in which case an empty management state is shown.

Page visibility continues to be enforced server-side by `PageHelpResolver`.
