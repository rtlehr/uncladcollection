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
}
