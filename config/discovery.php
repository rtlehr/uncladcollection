<?php

return [
    'cache_version_key' => 'discovery:version',
    'catalog_per_page' => 24,
    'new_asset_days' => 30,
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
