# License Pricing Models Patch

## What was wrong

The dynamic pricing engine already supported per-image/per-video multiplication, but several marketplace and admin presentation paths still used the legacy stored `asset_offerings.price_cents`. That made a package configured as $1.00 per image with 5 image units appear as $1.00 in places even though checkout could later reprice it to $5.00.

## New pricing models

### Per image / video (`per_unit`)
The reusable License Type stores a price per image and price per video. Each Asset Offering stores the number of logical image/video units in that package.

Example: image unit price $1.00 × 5 image units = $5.00.

Important: `image_units` is intentionally a logical-image count, not a raw file count. A single image may be delivered as JPG + TIFF + EPS, and those alternate formats should not automatically triple the price.

### Flat total (`flat_total`)
The reusable License Type stores one total license price. Image/video unit counts do not affect the base price.

Example: total license price $5.00 with 10 image units = $5.00.

Minimum package price, offering-level adjustment, optional manual override, configuration adjustments, quantity pricing tiers, cart pricing, and Stripe checkout continue to operate after the selected base pricing model is calculated.

## Included changes

- Migration adds `license_types.pricing_model` and `license_types.total_price_cents`.
- License Type Create/Edit now lets Admin choose Per image/video or Flat total.
- DynamicLicensePriceCalculator supports both pricing models.
- Asset Offering Builder and comparison matrix show the actual calculated price.
- Public asset detail and marketplace cards use the dynamic price instead of stale `asset_offerings.price_cents`.
- Asset offering saves synchronize the compatibility `price_cents` field.
- Editing a reusable License Type resynchronizes existing offerings.
- Added pricing tests for $1 × 5 = $5 and flat $5 regardless of 10 images.

## Installation

Copy the patch files into the project root, then run:

```powershell
php artisan migrate
php artisan optimize:clear
npm run build
php artisan test --filter=DynamicLicensePricingTest
```

Then test an asset offering in Admin:

1. Create/edit a License Type and choose **Price per image / video**.
2. Set Price per image to `1.00`.
3. On an Asset Offering, set Chargeable image count to `5`.
4. Confirm Calculated package price is `$5.00` and the public asset page also shows `$5.00`.
5. Change the License Type to **Flat total price**, enter `5.00`, and set the offering image count to `10`.
6. Confirm the package and public page remain `$5.00`.

## Testing note

PHP syntax checks were run on all modified PHP files. The uploaded source package did not contain `vendor` or `node_modules`, so the Laravel/Pest tests and Vite build could not be executed in the sandbox.
