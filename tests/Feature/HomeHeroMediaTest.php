<?php

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;

it('publishes selected asset media to the welcome page', function () {
    $asset = Asset::query()->create([
        'uuid' => (string) str()->uuid(),
        'title' => 'Hero Video',
        'slug' => 'hero-video',
        'asset_type' => 'video',
        'status' => AssetStatus::Published,
        'is_active' => true,
        'published_at' => now(),
    ]);

    AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => (string) str()->uuid(),
        'role' => AssetFileRole::Video,
        'media_type' => AssetMediaType::Video,
        'disk' => 'public',
        'directory' => 'hero',
        'stored_filename' => 'hero.mp4',
        'original_filename' => 'hero.mp4',
        'extension' => 'mp4',
        'mime_type' => 'video/mp4',
        'size_bytes' => 100,
        'checksum_sha256' => str_repeat('a', 64),
        'sort_order' => 1,
        'processing_status' => AssetFileProcessingStatus::Ready,
        'virus_scan_status' => AssetFileScanStatus::Clean,
        'is_downloadable' => false,
        'is_active' => true,
        'is_legacy' => false,
    ]);

    SiteSetting::query()->updateOrCreate(
        [
            'group_name' => 'homepage',
            'setting_key' => 'hero_asset_id',
        ],
        [
            'setting_value' => (string) $asset->id,
            'setting_type' => 'integer',
            'description' => 'Published Asset used as homepage hero media.',
            'is_public' => true,
        ],
    );

    app(SiteSettingService::class)->clearCache();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('heroMedia.asset_id', $asset->id)
            ->where('heroMedia.media_type', 'video'));
});
