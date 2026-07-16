# UC-B101 Hostinger Deployment

Before deployment:

```powershell
php artisan migrate
php artisan migrate --env=testing
php artisan wayfinder:generate --with-form
php artisan test --filter=BlogImageServiceTest
php artisan test --filter=BlogImageEditorIntegrationTest
npm run build
git add -f public/build
```

After deployment:

```bash
cd /home/u534944418/domains/staging.uncladcollection.com/public_html
php artisan migrate --force
php artisan optimize:clear
```

The existing public storage link is required. Clear Hostinger site cache once.
