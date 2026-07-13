# Unclad Collection Commerce Architecture

## Flow

```text
Asset
  → Offering
  → ConfigurationSelection
  → PricingEngine
  → PriceBreakdown
  → CartEngine
  → Checkout (UC-A005.2)
  → Order
  → Fulfillment
```

## Namespaces

- `App\Commerce\Configuration` owns selection normalization, validation, labels, hashes, and configuration editing.
- `App\Commerce\Pricing` owns deterministic price calculation and price breakdowns.
- `App\Commerce\Cart` owns cart mutation, line merging, aggregate repricing, and snapshots.
- `App\Commerce\Events` contains immutable domain-event payloads for future analytics and integrations.

## Rules

1. Controllers do not calculate prices.
2. Cart records store snapshots; they do not reconstruct historical labels from mutable catalog data.
3. Configuration equality is based on a normalized SHA-256 hash.
4. Quantity tiers aggregate across cart lines with the same user, asset, and offering.
5. Legacy image cart items remain supported through nullable asset fields and compatibility services.
6. New checkout code must consume `PriceBreakdown` and the cart snapshots rather than recalculating totals independently.

## Snapshot versions

Configuration and pricing snapshots include `version: 1`. Future schema changes should introduce a new version while preserving readers for prior versions.
