# Release Deployment Checklist

## Before push

- [ ] Working tree reviewed
- [ ] Current branch confirmed
- [ ] Local tests pass
- [ ] Database migrations reviewed
- [ ] Production frontend build generated
- [ ] `public/build/manifest.json` exists
- [ ] `public/build` staged with `git add -f`
- [ ] Root `.htaccess` still exists
- [ ] No secrets or `.env` files staged
- [ ] Commit pushed to GitHub

## Hostinger deployment

- [ ] Correct site selected
- [ ] Correct branch selected
- [ ] Git deployment completed
- [ ] Latest commit confirmed
- [ ] Root `.htaccess` present
- [ ] `vendor/autoload.php` present
- [ ] `public/build/manifest.json` present
- [ ] Server `.env` still present

## Post-deployment

Run:

```bash
php artisan config:clear
php artisan migrate --force
php artisan storage:link
chmod -R 775 storage bootstrap/cache
php artisan optimize
php artisan queue:restart || true
```

Then verify:

- [ ] Home page loads
- [ ] Login works
- [ ] Admin loads
- [ ] Asset catalog loads
- [ ] Asset detail page loads
- [ ] Images and branding load
- [ ] Blog loads
- [ ] Database writes succeed
- [ ] Uploads succeed
- [ ] Logs show no new critical errors

## Rollback trigger

Rollback when:

- The site cannot boot
- Migrations fail
- Login is broken
- Purchases or downloads are broken
- Uploaded files are inaccessible
- A security or permission issue is discovered
