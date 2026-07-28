<?php

return [
    'wish_lists' => [
        'enabled' => env('CUSTOMER_WISH_LIST_NOTIFICATIONS_ENABLED', true),
        'price_change_minimum_cents' => (int) env('CUSTOMER_PRICE_CHANGE_MINIMUM_CENTS', 100),
        'price_change_minimum_percent' => (float) env('CUSTOMER_PRICE_CHANGE_MINIMUM_PERCENT', 5),
        'chunk_size' => (int) env('CUSTOMER_NOTIFICATION_CHUNK_SIZE', 200),
    ],
    'interests' => [
        'enabled' => env('CUSTOMER_INTEREST_NOTIFICATIONS_ENABLED', true),
        'minimum_affinity_score' => (float) env('CUSTOMER_INTEREST_MINIMUM_SCORE', 2.0),
        'asset_age_days' => (int) env('CUSTOMER_INTEREST_ASSET_AGE_DAYS', 7),
        'maximum_assets_per_user' => (int) env('CUSTOMER_INTEREST_MAX_ASSETS', 6),
        'cooldown_days' => (int) env('CUSTOMER_INTEREST_COOLDOWN_DAYS', 7),
    ],
    'retention_days' => (int) env('CUSTOMER_NOTIFICATION_EVENT_RETENTION_DAYS', 365),
];
