# UC-B102 Hostinger Deployment

UC-B102 does not include a migration.

Before deployment:

```powershell
php artisan wayfinder:generate --with-form
php artisan test --filter=BlogImageEditorIntegrationTest
npm run build
git add -f public/build
```

After deployment:

```bash
cd /home/u534944418/domains/staging.uncladcollection.com/public_html
php artisan optimize:clear
```

Clear Hostinger's site cache once because this package changes frontend CSS and
Vite bundles.
