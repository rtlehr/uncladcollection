# Hostinger Deployment — UC-B105B

1. Deploy the replacement files.
2. Preserve runtime uploads and the existing storage symlink.
3. Run:

```bash
php artisan optimize:clear
php artisan test --filter=AssetCardDataTest
```

4. Build frontend assets locally if Node is unavailable on Hostinger:

```powershell
npm run build
```

5. Deploy the resulting `public/build` directory with the code.
6. Open a public Blog article containing Compact, Standard, and Featured Smart Asset Cards and verify commerce data.

No migration, queue restart, or Wayfinder generation is required.
