Advertising Workflow Phase 4 Hotfix

Fixes the Campaign Show 500 error:
Call to undefined method Illuminate\\Database\\Eloquent\\Builder::orWhereKey()

Changed AdvertisingRotationStatusService to use orWhere('id', $campaign->id),
which is supported by the application's Eloquent version.

No migration required.

After copying over the project root:
  php artisan optimize:clear

Then open the campaign again.
