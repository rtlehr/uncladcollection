<?php

return [
    'cache_version_key' => 'discovery:version',
    'catalog_per_page' => 24,
    'new_asset_days' => 30,
    'suggestions' => [
        'limit' => 8,
        'debounce_ms' => 250,
        'cache_minutes' => 10,
        'popular_cache_minutes' => 30,
    ],
    'recently_viewed' => [
        'limit' => 8,
        'storage_limit' => 100,
        'deduplication_minutes' => 30,
    ],
    'trending' => [
        'periods' => [
            'now' => 1,
            'week' => 7,
            'month' => 30,
        ],
        'weights' => [
            'asset_viewed' => 1,
            'asset_favorited' => 5,
            'asset_added_to_cart' => 10,
            'asset_downloaded' => 14,
            'purchases' => 24,
        ],
        'half_life_hours' => 72,
        'featured_boost' => 3,
        'cache_minutes' => 15,
        'homepage_limit' => 8,
    ],
    'recommendations' => [
        'homepage_limit' => 8,
        'candidate_limit' => 180,
        'cache_minutes' => 30,
        'profile_refresh_hours' => 24,
        'history_days' => 180,
        'affinity_half_life_days' => 45,
        'max_per_collection' => 2,
        'max_per_asset_type' => 4,
    ],
    'related' => [
        'limit' => 6,
        'candidate_limit' => 120,
        'cache_minutes' => 30,
    ],
    'search' => [
        'minimum_term_length' => 2,
        'title_exact_weight' => 120,
        'title_prefix_weight' => 80,
        'title_contains_weight' => 50,
        'document_contains_weight' => 20,
        'featured_boost' => 6,
        'freshness_days' => 45,
    ],
];
