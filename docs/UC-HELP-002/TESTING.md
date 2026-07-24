# Testing

Run the focused integration suite:

```powershell
php artisan test tests/Feature/PageHelp/PageHelpIntegrationTest.php
```

Then run the existing foundation suite:

```powershell
php artisan test tests/Feature/PageHelp/PageHelpFoundationTest.php
```

Manual checks:

1. Publish help for `public.home` and confirm the public header button appears.
2. Confirm guest-only help disappears after login.
3. Confirm admin-only help does not appear publicly.
4. Visit an admin page with no content as a Page Help manager and confirm the management empty state appears.
5. Test keyboard focus, Escape close, and mobile public-header behavior.
