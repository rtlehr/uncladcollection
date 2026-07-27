<?php
return [
    'enabled' => env('SEARCH_INTELLIGENCE_ENABLED', true),
    'lookback_days' => (int) env('SEARCH_INTELLIGENCE_LOOKBACK_DAYS', 365),
    'ai_minimum_searches' => (int) env('SEARCH_INTELLIGENCE_AI_MINIMUM_SEARCHES', 2),
    'ai_batch_size' => (int) env('SEARCH_INTELLIGENCE_AI_BATCH_SIZE', 20),
    'provider' => env('SEARCH_INTELLIGENCE_PROVIDER', 'ollama'),
    'ollama' => [
        'base_url' => env('SEARCH_INTELLIGENCE_OLLAMA_BASE_URL', env('OLLAMA_ASSET_BASE_URL', 'https://ai.uncladcollection.com')),
        'token' => env('SEARCH_INTELLIGENCE_OLLAMA_TOKEN', env('OLLAMA_ASSET_API_TOKEN')),
        'model' => env('SEARCH_INTELLIGENCE_MODEL', env('OLLAMA_ASSET_MODEL', 'qwen3-vl:8b')),
        'timeout_seconds' => (int) env('SEARCH_INTELLIGENCE_TIMEOUT_SECONDS', 180),
    ],
];
