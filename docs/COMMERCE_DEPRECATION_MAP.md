# Commerce Deprecation Map

The following classes remain operational for backward compatibility:

| Legacy class | New class |
|---|---|
| `App\Services\AssetConfigurationService` | `App\Commerce\Configuration\ConfigurationManager` |
| `App\Services\AssetPricingEngine` | `App\Commerce\Pricing\PricingEngine` |
| `App\Data\AssetPricingQuote` | `App\Commerce\Pricing\PriceBreakdown` |
| `App\Services\SmartCartService` | `App\Commerce\Cart\CartEngine` |

Existing controllers and tests may continue using the legacy names during the transition. New UC-A005.2 checkout code should use the Commerce namespace directly.
