<?php

use App\Models\AdvertisingCampaign;
use App\Models\Advertiser;
use App\Models\Asset;
use App\Models\Download;
use App\Models\License;
use App\Models\Order;
use App\Models\User;

return [
    'attachments' => [
        'disk' => env('SUPPORT_ATTACHMENT_DISK', 'local'),
        'max_kb' => 10240,
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'zip'],
    ],
    'allowed_relation_types' => [Asset::class, Order::class, License::class, Download::class, Advertiser::class, AdvertisingCampaign::class, User::class],
];
