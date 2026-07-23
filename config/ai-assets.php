<?php

return [
    'enabled' => env('AI_ASSET_ASSISTANT_ENABLED', true),

    'default_provider' => env('AI_ASSET_PROVIDER', 'ollama'),
    'fallback_enabled' => env('AI_ASSET_FALLBACK_ENABLED', true),
    'fallback_provider' => env('AI_ASSET_FALLBACK_PROVIDER', 'openai'),

    'max_image_dimension' => (int) env('AI_ASSET_MAX_IMAGE_DIMENSION', 1600),
    'jpeg_quality' => (int) env('AI_ASSET_JPEG_QUALITY', 82),
    'request_max_execution_seconds' => (int) env('AI_ASSET_REQUEST_MAX_EXECUTION_SECONDS', 360),

    'providers' => [
        'ollama' => [
            'base_url' => rtrim(env('OLLAMA_ASSET_BASE_URL', 'https://ai.uncladcollection.com'), '/'),
            'token' => env('OLLAMA_ASSET_API_TOKEN'),
            'model' => env('OLLAMA_ASSET_MODEL', 'qwen3-vl:8b'),
            'timeout_seconds' => (int) env('OLLAMA_ASSET_TIMEOUT_SECONDS', 300),
            'connect_timeout_seconds' => (int) env('OLLAMA_ASSET_CONNECT_TIMEOUT_SECONDS', 15),
            'retry_times' => (int) env('OLLAMA_ASSET_RETRY_TIMES', 1),
            'retry_sleep_milliseconds' => (int) env('OLLAMA_ASSET_RETRY_SLEEP_MS', 750),
            'keep_alive' => env('OLLAMA_ASSET_KEEP_ALIVE', '10m'),
            'temperature' => (float) env('OLLAMA_ASSET_TEMPERATURE', 0.1),
            'num_predict' => (int) env('OLLAMA_ASSET_NUM_PREDICT', 2048),
            'think' => filter_var(env('OLLAMA_ASSET_THINK', false), FILTER_VALIDATE_BOOL),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
            'model' => env('OPENAI_ASSET_MODEL', 'gpt-4.1-mini'),
            'timeout_seconds' => (int) env('OPENAI_TIMEOUT_SECONDS', 120),
            'connect_timeout_seconds' => (int) env('OPENAI_CONNECT_TIMEOUT_SECONDS', 15),
            'retry_times' => (int) env('OPENAI_RETRY_TIMES', 1),
            'retry_sleep_milliseconds' => (int) env('OPENAI_RETRY_SLEEP_MS', 750),
        ],
    ],
];
