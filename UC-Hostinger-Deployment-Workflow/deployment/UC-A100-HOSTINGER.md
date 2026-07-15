# UC-A100 Hostinger Deployment

UC-A100 does not require a migration.

## Before deployment

```powershell
php artisan test --filter=UploadStreamResolverTest
php artisan test --filter=AssetStorageServiceTest
php artisan test --filter=AssetMultiFileUploadManagerTest
npm run build
git add -f public/build
```

## After deployment

```bash
cd /home/u534944418/domains/staging.uncladcollection.com/public_html
php artisan optimize:clear
```

## PHP limits

Check:

```bash
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit|max_execution_time|max_input_time"
```

Recommended starting values for multi-file staging tests:

```text
upload_max_filesize = 256M
post_max_size = 512M
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
```

`post_max_size` must exceed the combined size of all selected files plus
multipart overhead.

## Storage

Asset files use the private disk configured by:

```text
asset-media.private_disk
```

Public preview files continue to use the existing preview/presentation
services. Do not expose private downloadable files through `/storage`.

## Logs

If processing fails:

```bash
tail -n 150 storage/logs/laravel.log
```

Search for:

```text
Asset upload processing failed.
```
