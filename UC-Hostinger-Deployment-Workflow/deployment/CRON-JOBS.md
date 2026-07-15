# Hostinger Cron Jobs

## Laravel scheduler

Configure one cron job that runs every minute:

```bash
cd /home/YOUR_HOSTINGER_USER/domains/staging.uncladcollection.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Use the production domain path for production.

## Queue workaround on managed Cloud hosting

Hostinger Cloud hosting does not provide a persistent Supervisor worker in the same way as a VPS.

For light queue workloads, run this from cron:

```bash
cd /home/YOUR_HOSTINGER_USER/domains/staging.uncladcollection.com/public_html && php artisan queue:work --stop-when-empty --tries=3 --timeout=240 >> /dev/null 2>&1
```

Use the shortest interval Hostinger permits.

This is appropriate for light email and application jobs. Heavy video processing, large ZIP creation, or long-running work should eventually move to a VPS or managed queue service.
