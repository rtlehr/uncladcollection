# Hostinger Deployment

Deploy the replacement files and the newly generated `public/build` output.

Recommended local commands:

```powershell
npm run build
```

On Hostinger:

```bash
php artisan optimize:clear
```

No migration or queue restart is required.
