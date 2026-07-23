<?php

return [
    'enabled' => env('AI_ASSET_ASSISTANT_ENABLED', true),
    'provider' => 'openai',
    'model' => env('OPENAI_ASSET_MODEL', 'gpt-4.1-mini'),
    'api_key' => env('OPENAI_API_KEY'),
    'base_url' => rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
    'timeout_seconds' => (int) env('OPENAI_TIMEOUT_SECONDS', 90),
    'max_image_dimension' => (int) env('AI_ASSET_MAX_IMAGE_DIMENSION', 1600),
    'jpeg_quality' => (int) env('AI_ASSET_JPEG_QUALITY', 82),
];
