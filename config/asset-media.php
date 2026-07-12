<?php

use App\Services\NullAssetVirusScanner;

return [
    'private_disk' => env('ASSET_FILESYSTEM_DISK', 'asset-files'),

    'max_upload_kilobytes' => (int) env('ASSET_MAX_UPLOAD_KB', 512000),

    'process_uploads' => env('ASSET_PROCESS_UPLOADS', true),

    'virus_scanner' => env('ASSET_VIRUS_SCANNER', NullAssetVirusScanner::class),

    'extensions' => [
        'image' => ['jpg', 'jpeg', 'png', 'webp', 'tif', 'tiff'],
        'vector' => ['eps', 'svg'],
        'video' => ['mp4', 'mov', 'webm'],
        'archive' => ['zip'],
        'document' => ['pdf'],
        'source' => ['psd'],
    ],

    'mime_types' => [
        'image' => [
            'image/jpeg', 'image/png', 'image/webp', 'image/tiff',
        ],
        'vector' => [
            'application/postscript', 'application/eps', 'image/x-eps',
            'image/svg+xml', 'text/xml', 'application/xml', 'text/plain',
        ],
        'video' => [
            'video/mp4', 'video/quicktime', 'video/webm', 'application/octet-stream',
        ],
        'archive' => [
            'application/zip', 'application/x-zip-compressed', 'application/octet-stream',
        ],
        'document' => ['application/pdf'],
        'source' => [
            'image/vnd.adobe.photoshop', 'application/octet-stream',
        ],
    ],

    'blocked_extensions' => [
        'app', 'bat', 'bin', 'cmd', 'com', 'cpl', 'dll', 'dmg', 'exe', 'gadget',
        'hta', 'inf', 'ins', 'isp', 'jar', 'js', 'jse', 'lnk', 'msc', 'msi',
        'msp', 'mst', 'pif', 'ps1', 'reg', 'scr', 'sh', 'sys', 'vb', 'vbe',
        'vbs', 'ws', 'wsc', 'wsf', 'wsh', 'php', 'phar', 'phtml', 'cgi', 'pl',
        'py', 'rb', 'asp', 'aspx', 'jsp',
    ],

    'zip' => [
        'require_extension' => env('ASSET_ZIP_REQUIRE_EXTENSION', false),
        'max_entries' => (int) env('ASSET_ZIP_MAX_ENTRIES', 5000),
        'max_expanded_bytes' => (int) env('ASSET_ZIP_MAX_EXPANDED_BYTES', 2147483648),
        'max_compression_ratio' => (int) env('ASSET_ZIP_MAX_COMPRESSION_RATIO', 100),
        'allow_nested_archives' => false,
    ],
];
