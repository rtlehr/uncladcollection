<?php

return [
    'private_disk' => env('ASSET_FILESYSTEM_DISK', 'asset-files'),

    'max_upload_kilobytes' => (int) env('ASSET_MAX_UPLOAD_KB', 512000),

    'extensions' => [
        'image' => ['jpg', 'jpeg', 'png', 'webp', 'tif', 'tiff'],
        'vector' => ['eps', 'svg'],
        'video' => ['mp4', 'mov', 'webm'],
        'archive' => ['zip'],
        'document' => ['pdf'],
        'source' => ['psd'],
    ],

    'blocked_extensions' => [
        'app', 'bat', 'bin', 'cmd', 'com', 'cpl', 'dll', 'dmg', 'exe', 'gadget',
        'hta', 'inf', 'ins', 'isp', 'jar', 'js', 'jse', 'lnk', 'msc', 'msi',
        'msp', 'mst', 'pif', 'ps1', 'reg', 'scr', 'sh', 'sys', 'vb', 'vbe',
        'vbs', 'ws', 'wsc', 'wsf', 'wsh',
    ],

    'zip' => [
        'max_entries' => (int) env('ASSET_ZIP_MAX_ENTRIES', 5000),
        'max_expanded_bytes' => (int) env('ASSET_ZIP_MAX_EXPANDED_BYTES', 2147483648),
        'max_compression_ratio' => (int) env('ASSET_ZIP_MAX_COMPRESSION_RATIO', 100),
        'allow_nested_archives' => false,
    ],
];
