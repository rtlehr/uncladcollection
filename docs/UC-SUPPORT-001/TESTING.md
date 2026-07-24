# Testing UC-SUPPORT-001

## Focused automated test

```powershell
php artisan test tests/Feature/Support/SupportTicketFoundationTest.php
```

## Full regression suite

```powershell
php artisan test
```

## Manual verification

```powershell
php artisan migrate:status
php artisan tinker
```

In Tinker:

```php
App\Models\SupportTicketCategory::count();
App\Models\Permission::where('group_name', 'Support')->pluck('name');
```

Expected results:

- Nine seeded categories after running `SupportTicketCategorySeeder`
- Eight Support permissions
- Administrators receive all new permissions after rerunning `RoleSeeder`

## Storage

Support attachments default to `storage/app/private/support-tickets`. No public storage link is required.
