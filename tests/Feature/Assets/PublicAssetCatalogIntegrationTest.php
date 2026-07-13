<?php

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('mysql');
    expect(DB::connection()->getDatabaseName())->toBe('uncladcollection_testing');
});

it('renders the public images route from published assets without duplicating linked legacy images', function (): void {
    $image = Image::query()->create([
        'title' => 'Legacy Beach Image',
        'slug' => 'legacy-beach-image',
        'is_active' => true,
    ]);

    $asset = catalogAsset([
        'legacy_image_id' => $image->id,
        'title' => $image->title,
        'slug' => $image->slug,
        'is_featured' => true,
    ]);
    catalogFile($asset, 'jpg');

    $this->get(route('images.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Images/Index')
            ->has('assets.data', 1)
            ->where('assets.data.0.id', $asset->id)
            ->where('assets.data.0.legacy_image_id', $image->id)
            ->where('assets.data.0.is_featured', true)
            ->where('assets.data.0.href', route('images.show', $image)));
});

it('lists new non-image assets and filters by type and file format', function (): void {
    $video = catalogAsset([
        'title' => 'Beach Motion Clip',
        'slug' => 'beach-motion-clip',
        'asset_type' => AssetType::Video,
    ]);
    catalogFile($video, 'mp4', AssetMediaType::Video);

    $image = catalogAsset([
        'title' => 'Beach Still',
        'slug' => 'beach-still',
        'asset_type' => AssetType::Image,
    ]);
    catalogFile($image, 'jpg');

    $this->get(route('images.index', ['asset_type' => 'video', 'format' => 'mp4']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('assets.data', 1)
            ->where('assets.data.0.id', $video->id)
            ->where('assets.data.0.asset_type', 'video')
            ->where('assets.data.0.formats.0', 'MP4'));
});

it('does not expose draft or inactive assets in the public catalog', function (): void {
    catalogAsset(['title' => 'Draft Asset', 'slug' => 'draft-asset', 'status' => AssetStatus::Draft]);
    catalogAsset(['title' => 'Inactive Asset', 'slug' => 'inactive-asset', 'is_active' => false]);

    $this->get(route('images.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('assets.data', 0));
});

function catalogAsset(array $overrides = []): Asset
{
    return Asset::query()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'title' => 'Catalog Asset '.Str::random(6),
        'slug' => 'catalog-asset-'.Str::lower(Str::random(8)),
        'asset_type' => AssetType::Image,
        'status' => AssetStatus::Published,
        'is_active' => true,
        'published_at' => now()->subMinute(),
    ], $overrides));
}

function catalogFile(Asset $asset, string $extension, AssetMediaType $mediaType = AssetMediaType::Image): AssetFile
{
    $file = AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => (string) Str::uuid(),
        'role' => AssetFileRole::Preview,
        'media_type' => $mediaType,
        'disk' => 'asset-files',
        'directory' => 'assets/testing/'.$asset->uuid.'/preview',
        'stored_filename' => Str::ulid().'.'.$extension,
        'original_filename' => 'preview.'.$extension,
        'extension' => $extension,
        'mime_type' => $extension === 'mp4' ? 'video/mp4' : 'image/jpeg',
        'size_bytes' => 1024,
        'checksum_sha256' => hash('sha256', $asset->uuid.$extension),
        'sort_order' => 10,
        'processing_status' => AssetFileProcessingStatus::Ready,
        'virus_scan_status' => AssetFileScanStatus::NotRequired,
        'is_downloadable' => true,
        'is_active' => true,
        'is_legacy' => false,
    ]);

    if ($mediaType === AssetMediaType::Image) {
        $asset->update(['primary_preview_file_id' => $file->id]);
    }

    return $file;
}
