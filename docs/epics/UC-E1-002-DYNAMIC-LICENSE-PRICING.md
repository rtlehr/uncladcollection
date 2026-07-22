# UC-E1-002 — Dynamic License Pricing Engine

## Pricing source of truth

License types define reusable rights and unit prices. Asset offerings define the number of chargeable image and video units in a package.

`package price = image units × image price + video units × video price + adjustment`

The license minimum is then applied. A manual offering override, when present, becomes the final package price. Existing configuration adjustments and quantity tiers are applied afterward by the established pricing engine.

## Historical protection

The existing cart, checkout, and order snapshot pipeline continues to store the calculated unit and line totals. Future license price changes therefore do not change completed order totals.

## Compatibility

`license_types.price_cents` and `asset_offerings.price_cents` remain temporarily for compatibility. The migration copies the former fixed license price into both unit prices. New calculations use the dynamic fields.

## Install

1. Extract into the project root.
2. Run `php artisan migrate`.
3. Run `php artisan optimize:clear`.
4. Run `php artisan wayfinder:generate` if your generated routes require it.
5. Run `npm run build`.
6. Run `php artisan test --filter=DynamicLicensePricingTest`.
7. Run `php artisan test --filter=AssetPricingEngineTest`.

## Admin workflow

1. Set price per image and price per video on each License Type.
2. On an Asset's Offerings section, enter chargeable image and video units.
3. Add an optional positive or negative adjustment.
4. Use a manual override only for exceptional packages.
