<?php

return [
    'enabled' => env('ANALYTICS_ENABLED', true),
    'exclude_bots' => env('ANALYTICS_EXCLUDE_BOTS', true),
    'deduplicate' => env('ANALYTICS_DEDUPLICATE', true),
    'deduplication_window_seconds' => (int) env('ANALYTICS_DEDUPLICATION_WINDOW_SECONDS', 30),
    'maximum_dimension_depth' => (int) env('ANALYTICS_MAX_DIMENSION_DEPTH', 4),
    'maximum_dimension_items' => (int) env('ANALYTICS_MAX_DIMENSION_ITEMS', 50),
    'maximum_dimension_string_length' => (int) env('ANALYTICS_MAX_DIMENSION_STRING_LENGTH', 500),
    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 730),
    'prune_chunk_size' => (int) env('ANALYTICS_PRUNE_CHUNK_SIZE', 5000),
    'report_cache_seconds' => (int) env('ANALYTICS_REPORT_CACHE_SECONDS', 300),
    'report_row_limit' => (int) env('ANALYTICS_REPORT_ROW_LIMIT', 250),
    'bot_patterns' => [
        'bot', 'crawler', 'spider', 'slurp', 'bingpreview', 'facebookexternalhit',
        'headlesschrome', 'lighthouse', 'pagespeed', 'uptimerobot', 'monitoring',
    ],
];
