<?php

namespace App\Enums;

enum DiscoverySource: string
{
    case Catalog = 'catalog';
    case SearchResults = 'search_results';
    case Autocomplete = 'autocomplete';
    case RelatedAssets = 'related_assets';
    case RecentlyViewed = 'recently_viewed';
    case Trending = 'trending';
    case FeaturedCollection = 'featured_collection';
    case SeasonalCollection = 'seasonal_collection';
    case RecommendedForYou = 'recommended_for_you';
    case Homepage = 'homepage';
}
