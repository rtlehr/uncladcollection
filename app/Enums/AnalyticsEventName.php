<?php

namespace App\Enums;

enum AnalyticsEventName: string
{
    case AssetViewed = 'asset_viewed';
    case AssetFavorited = 'asset_favorited';
    case AssetAddedToCart = 'asset_added_to_cart';
    case CheckoutStarted = 'checkout_started';
    case OrderPaid = 'order_paid';
    case AssetDownloaded = 'asset_downloaded';
    case BlogPostViewed = 'blog_post_viewed';
    case CampaignViewed = 'campaign_viewed';
    case CampaignClicked = 'campaign_clicked';
    case SearchPerformed = 'search_performed';
    case SearchFiltersApplied = 'search_filters_applied';
    case SearchSuggestionSelected = 'search_suggestion_selected';
    case CollectionViewed = 'collection_viewed';
    case AdvertisingImpression = 'advertising_impression';
    case AdvertisingClicked = 'advertising_clicked';
}
