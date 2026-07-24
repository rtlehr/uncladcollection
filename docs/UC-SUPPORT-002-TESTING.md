# Testing

```powershell
php artisan optimize:clear
php artisan test tests/Feature/Support/SupportCenterTest.php
npm run build
```

Manual checks:
- Guest submits at `/support/create` and receives a secure tracking link.
- Member sees `/support/tickets`, creates a ticket, replies, downloads attachments, closes and reopens eligible tickets.
- Another member receives 403 for a ticket they do not own.
- Internal notes never appear in public/member responses.
