# UC-B105A Hostinger Deployment

No migration is required.

Before deployment:

```powershell
php artisan wayfinder:generate --with-form
php artisan test --filter=AssetCardDataTest
npm run build
git add -f public/build
```

After deployment:

```bash
cd /home/u534944418/domains/staging.uncladcollection.com/public_html
php artisan optimize:clear
```

Clear Hostinger site cache once.
