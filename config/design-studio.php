<?php

return [
    'currency' => env('DESIGN_STUDIO_CURRENCY', 'USD'),
    'complimentary_credits_per_asset_license' => (int) env('DESIGN_STUDIO_COMPLIMENTARY_CREDITS_PER_ASSET_LICENSE', 1),
    'single_export_reference_price_cents' => (int) env('DESIGN_STUDIO_SINGLE_EXPORT_REFERENCE_PRICE_CENTS', 100),

    'max_layer_count' => (int) env('DESIGN_STUDIO_MAX_LAYER_COUNT', 200),

    'max_upload_bytes' => (int) env('DESIGN_STUDIO_MAX_UPLOAD_BYTES', 10 * 1024 * 1024),
    'max_upload_width' => (int) env('DESIGN_STUDIO_MAX_UPLOAD_WIDTH', 8000),
    'max_upload_height' => (int) env('DESIGN_STUDIO_MAX_UPLOAD_HEIGHT', 8000),

    'max_browser_width' => (int) env('DESIGN_STUDIO_MAX_BROWSER_WIDTH', 12000),
    'max_browser_height' => (int) env('DESIGN_STUDIO_MAX_BROWSER_HEIGHT', 12000),
    'max_browser_pixels' => (int) env('DESIGN_STUDIO_MAX_BROWSER_PIXELS', 40000000),

    'max_server_width' => (int) env('DESIGN_STUDIO_MAX_SERVER_WIDTH', 12000),
    'max_server_height' => (int) env('DESIGN_STUDIO_MAX_SERVER_HEIGHT', 12000),
    'max_server_pixels' => (int) env('DESIGN_STUDIO_MAX_SERVER_PIXELS', 80000000),
    'max_queued_renders_per_user' => (int) env('DESIGN_STUDIO_MAX_QUEUED_RENDERS_PER_USER', 5),

    'completed_export_retention_days' => (int) env('DESIGN_STUDIO_EXPORT_RETENTION_DAYS', 90),
    'stale_render_minutes' => (int) env('DESIGN_STUDIO_STALE_RENDER_MINUTES', 60),
    'recommended_min_width' => (int) env('DESIGN_STUDIO_RECOMMENDED_MIN_WIDTH', 1024),

    'jpeg_quality' => (int) env('DESIGN_STUDIO_JPEG_QUALITY', 92),
    'webp_quality' => (int) env('DESIGN_STUDIO_WEBP_QUALITY', 90),
    'font_path' => env('DESIGN_STUDIO_FONT_PATH'),
];
