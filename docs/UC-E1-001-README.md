# UC-E1-001 — Analytics and Measurement Foundation

## Purpose

This package creates the shared measurement layer for Marketplace Intelligence. It does not attempt to finish every Epic 1 dashboard. It establishes the data contracts, reporting-period behavior, KPI service, event storage, permissions, routes, and starter admin screen that later packages will extend.

## Included

- `analytics_events` migration with normalized event, subject, user, source, channel, monetary value, dimensions, and occurrence time.
- `AnalyticsEventName` enum for controlled marketplace event names.
- `AnalyticsEvent` model.
- `AnalyticsTracker` service for consistent event recording.
- `AnalyticsPeriod` value object with 7/30/90-day, year-to-date, custom, and prior-period behavior.
- `MarketplaceMetricsService` for revenue, orders, AOV, downloads, users, assets, licenses, views, and purchase conversion.
- Permission-protected `/admin/analytics` route.
- Marketplace Intelligence navigation item.
- Accessible reporting-period controls and starter KPI screen.
- Feature tests for event recording and KPI calculations.

## Installation

1. Copy all package files into the project, preserving paths.
2. Run `php artisan migrate`.
3. Run `php artisan db:seed --class=PermissionSeeder` so `view_reports` remains available.
4. Run `php artisan wayfinder:generate` if route types are generated manually in your environment.
5. Run `npm run build`.
6. Run `php artisan test --filter=AnalyticsFoundationTest`.

## Integration rule

All future marketplace behavior should record events through `AnalyticsTracker`; controllers should not insert directly into `analytics_events`.

Example:

```php
$tracker->record(
    AnalyticsEventName::AssetViewed,
    subject: $asset,
    user: $request->user(),
    dimensions: ['surface' => 'public_asset_show'],
    source: $request->query('utm_source'),
);
```

## Next package

UC-E1-002 should implement the Dynamic License Pricing Engine. As checkout and pricing calculations are updated, it should record pricing dimensions and snapshots that UC-E1-003 and UC-E1-004 can report against.
