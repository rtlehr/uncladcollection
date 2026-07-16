# UC-A101 Hostinger Deployment

## Before deployment

```powershell
php artisan migrate
php artisan migrate --env=testing
php artisan wayfinder:generate --with-form
php artisan test --filter=AssetFileRelationshipServiceTest
php artisan test --filter=AssetFileRelationshipManagerTest
npm run build
git add -f public/build
```

## After Hostinger deploys

```bash
cd /home/u534944418/domains/staging.uncladcollection.com/public_html

php artisan migrate --force
php artisan optimize:clear
```

UC-A101 stores relationship records only. It does not move, copy, or delete
physical Asset files.
