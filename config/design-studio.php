<?php

return [
    'max_server_width' => (int) env('DESIGN_STUDIO_MAX_SERVER_WIDTH', 12000),
    'max_server_height' => (int) env('DESIGN_STUDIO_MAX_SERVER_HEIGHT', 12000),
    'max_server_pixels' => (int) env('DESIGN_STUDIO_MAX_SERVER_PIXELS', 80000000),
    'jpeg_quality' => (int) env('DESIGN_STUDIO_JPEG_QUALITY', 92),
    'webp_quality' => (int) env('DESIGN_STUDIO_WEBP_QUALITY', 90),
    'font_path' => env('DESIGN_STUDIO_FONT_PATH'),
];
