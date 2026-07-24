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
        'max_kb' => (int) env('SUPPORT_ATTACHMENT_MAX_KB', 10240),
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'zip'],
        'retention_days' => (int) env('SUPPORT_ATTACHMENT_RETENTION_DAYS', 365),
    ],
    'retention' => [
        'closed_ticket_days' => (int) env('SUPPORT_CLOSED_TICKET_RETENTION_DAYS', 2555),
        'guest_token_days' => (int) env('SUPPORT_GUEST_TOKEN_RETENTION_DAYS', 180),
        'prune_chunk_size' => (int) env('SUPPORT_PRUNE_CHUNK_SIZE', 500),
    ],
    'rate_limits' => [
        'public_submissions_per_minute' => (int) env('SUPPORT_PUBLIC_SUBMISSIONS_PER_MINUTE', 6),
        'guest_replies_per_minute' => (int) env('SUPPORT_GUEST_REPLIES_PER_MINUTE', 12),
        'member_writes_per_minute' => (int) env('SUPPORT_MEMBER_WRITES_PER_MINUTE', 20),
    ],
    'allowed_relation_types' => [Asset::class, Order::class, License::class, Download::class, Advertiser::class, AdvertisingCampaign::class, User::class],
];
