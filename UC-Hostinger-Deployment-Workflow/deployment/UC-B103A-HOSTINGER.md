# UC-B103A Hostinger Deployment

Before deployment:

```powershell
php artisan wayfinder:generate --with-form
php artisan test --filter=BlogUnifiedImageWorkflowTest
npm run build
git add -f public/build
```

After deployment:

```bash
cd /home/u534944418/domains/staging.uncladcollection.com/public_html
php artisan optimize:clear
```

No migration is required.
